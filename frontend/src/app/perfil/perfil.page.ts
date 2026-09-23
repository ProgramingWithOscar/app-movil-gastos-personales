import { Component, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';

import { AuthService, mensajeDeError } from '../core/auth';

@Component({
  selector: 'app-perfil',
  templateUrl: './perfil.page.html',
  styleUrl: './perfil.page.scss',
  standalone: false,
})
export class PerfilPage {
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly toastCtrl = inject(ToastController);

  readonly usuario = this.auth.usuario;
  readonly guardando = signal(false);
  readonly editando = signal(false);

  readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(120)]],
  });

  get iniciales(): string {
    const nombre = this.usuario()?.name ?? '';

    return nombre
      .split(' ')
      .filter(Boolean)
      .slice(0, 2)
      .map((parte) => parte[0]?.toUpperCase() ?? '')
      .join('');
  }

  editar(): void {
    this.form.patchValue({ name: this.usuario()?.name ?? '' });
    this.editando.set(true);
  }

  cancelar(): void {
    this.editando.set(false);
  }

  async guardar(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.guardando.set(true);

    try {
      await this.auth.actualizarPerfil(this.form.getRawValue());
      this.editando.set(false);
      await this.avisar('Perfil actualizado.', 'success');
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo guardar el perfil.'), 'danger');
    } finally {
      this.guardando.set(false);
    }
  }

  async cerrarSesion(): Promise<void> {
    await this.auth.cerrarSesion();
    await this.router.navigateByUrl('/bienvenida', { replaceUrl: true });
  }

  private async avisar(mensaje: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({ message: mensaje, duration: 2200, color });
    await toast.present();
  }
}
