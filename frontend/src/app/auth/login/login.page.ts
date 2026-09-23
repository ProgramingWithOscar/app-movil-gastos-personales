import { Component, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';

import { AuthService, mensajeDeError } from '../../core/auth';
import { FUNCIONES } from '../../core/funcionalidades';

@Component({
  selector: 'app-login',
  templateUrl: './login.page.html',
  standalone: false,
})
export class LoginPage {
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);

  readonly correoHabilitado = FUNCIONES.correo;
  readonly enviando = signal(false);
  readonly verClave = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]],
  });

  alternarClave(): void {
    this.verClave.update((visible) => !visible);
  }

  invalido(campo: 'email' | 'password'): boolean {
    const control = this.form.controls[campo];
    return control.invalid && control.touched;
  }

  async entrar(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.enviando.set(true);
    this.error.set(null);

    try {
      await this.auth.iniciarSesion(this.form.getRawValue());
      await this.router.navigateByUrl('/tabs/inicio', { replaceUrl: true });
    } catch (error) {
      this.error.set(mensajeDeError(error, 'No se pudo iniciar sesión.'));
    } finally {
      this.enviando.set(false);
    }
  }
}
