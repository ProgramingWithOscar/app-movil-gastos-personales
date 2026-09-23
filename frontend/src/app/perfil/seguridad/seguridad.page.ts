import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AlertController, ToastController } from '@ionic/angular';

import { AuthService, mensajeDeError, Sesion } from '../../core/auth';

@Component({
  selector: 'app-seguridad',
  templateUrl: './seguridad.page.html',
  standalone: false,
})
export class SeguridadPage implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly alertCtrl = inject(AlertController);
  private readonly toastCtrl = inject(ToastController);

  readonly sesiones = signal<Sesion[]>([]);
  readonly cargando = signal(true);
  readonly cambiando = signal(false);
  readonly verClave = signal(false);

  readonly form = this.fb.nonNullable.group({
    current_password: ['', [Validators.required]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', [Validators.required]],
  });

  ngOnInit(): void {
    void this.cargarSesiones();
  }

  alternarClave(): void {
    this.verClave.update((visible) => !visible);
  }

  async cargarSesiones(): Promise<void> {
    this.cargando.set(true);

    try {
      this.sesiones.set(await this.auth.listarSesiones());
    } catch {
      await this.avisar('No se pudieron cargar las sesiones.', 'danger');
    } finally {
      this.cargando.set(false);
    }
  }

  async cambiarContrasena(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const datos = this.form.getRawValue();

    if (datos.password !== datos.password_confirmation) {
      await this.avisar('Las contraseñas nuevas no coinciden.', 'danger');
      return;
    }

    this.cambiando.set(true);

    try {
      await this.auth.cambiarContrasena(datos);
      this.form.reset();
      await this.cargarSesiones();
      await this.avisar('Contraseña actualizada. Cerramos los demás dispositivos.', 'success');
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo cambiar la contraseña.'), 'danger');
    } finally {
      this.cambiando.set(false);
    }
  }

  async revocar(sesion: Sesion): Promise<void> {
    try {
      await this.auth.revocarSesion(sesion.id);
      this.sesiones.update((actuales) => actuales.filter((s) => s.id !== sesion.id));
      await this.avisar('Sesión cerrada.', 'medium');
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo cerrar la sesión.'), 'danger');
    }
  }

  async cerrarOtras(): Promise<void> {
    try {
      await this.auth.cerrarOtrasSesiones();
      await this.cargarSesiones();
      await this.avisar('Cerramos las demás sesiones.', 'success');
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo completar.'), 'danger');
    }
  }

  async confirmarEliminarCuenta(): Promise<void> {
    const alerta = await this.alertCtrl.create({
      header: 'Eliminar cuenta',
      message:
        'Se borrarán tus movimientos y no podrás volver a entrar. Escribe tu contraseña para confirmar.',
      inputs: [{ name: 'password', type: 'password', placeholder: 'Tu contraseña' }],
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: 'Eliminar',
          role: 'destructive',
          handler: (datos) => void this.eliminarCuenta(datos.password),
        },
      ],
    });

    await alerta.present();
  }

  private async eliminarCuenta(password: string): Promise<void> {
    if (!password) {
      return;
    }

    try {
      await this.auth.eliminarCuenta(password);
      await this.router.navigateByUrl('/bienvenida', { replaceUrl: true });
      await this.avisar('Tu cuenta fue eliminada.', 'medium');
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo eliminar la cuenta.'), 'danger');
    }
  }

  private async avisar(mensaje: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({ message: mensaje, duration: 2600, color });
    await toast.present();
  }
}
