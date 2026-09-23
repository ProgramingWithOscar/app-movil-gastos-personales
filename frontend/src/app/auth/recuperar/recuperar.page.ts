import { Component, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';

import { AuthService, mensajeDeError } from '../../core/auth';

@Component({
  selector: 'app-recuperar',
  templateUrl: './recuperar.page.html',
  standalone: false,
})
export class RecuperarPage {
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);

  readonly enviando = signal(false);
  readonly enviado = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
  });

  get correo(): string {
    return this.form.controls.email.value;
  }

  invalido(): boolean {
    const control = this.form.controls.email;
    return control.invalid && control.touched;
  }

  async enviar(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.enviando.set(true);
    this.error.set(null);

    try {
      await this.auth.recuperarContrasena(this.correo);

      // La respuesta es la misma exista o no el correo: no revelamos qué
      // direcciones están registradas.
      this.enviado.set(true);
    } catch (error) {
      this.error.set(mensajeDeError(error, 'No se pudo enviar el correo.'));
    } finally {
      this.enviando.set(false);
    }
  }
}
