import { Component, computed, inject, signal } from '@angular/core';
import { AbstractControl, FormBuilder, ValidationErrors, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { ToastController } from '@ionic/angular';

import { AuthService, erroresPorCampo, mensajeDeError } from '../../core/auth';

/** Las mismas reglas que valida el backend, para avisar antes de enviar. */
function contrasenaSegura(control: AbstractControl): ValidationErrors | null {
  const valor = String(control.value ?? '');

  if (valor === '') {
    return null;
  }

  const fallos = {
    corta: valor.length < 8,
    sinMayuscula: !/[A-ZÁÉÍÓÚÑ]/.test(valor),
    sinMinuscula: !/[a-záéíóúñ]/.test(valor),
    sinNumero: !/\d/.test(valor),
  };

  return Object.values(fallos).some(Boolean) ? { debil: fallos } : null;
}

function coinciden(grupo: AbstractControl): ValidationErrors | null {
  const password = grupo.get('password')?.value;
  const confirmacion = grupo.get('password_confirmation')?.value;

  return password && confirmacion && password !== confirmacion ? { noCoinciden: true } : null;
}

@Component({
  selector: 'app-registro',
  templateUrl: './registro.page.html',
  styleUrl: './registro.page.scss',
  standalone: false,
})
export class RegistroPage {
  private readonly auth = inject(AuthService);
  private readonly fb = inject(FormBuilder);
  private readonly router = inject(Router);
  private readonly toastCtrl = inject(ToastController);

  readonly enviando = signal(false);
  readonly verClave = signal(false);
  readonly error = signal<string | null>(null);

  /**
   * Lo que el backend rechazó de la contraseña. Va aparte porque hay reglas
   * que solo él puede comprobar —si apareció en filtraciones conocidas—, así
   * que no se pueden anticipar en el formulario.
   */
  readonly errorClave = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group(
    {
      name: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(120)]],
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, contrasenaSegura]],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: coinciden },
  );

  private readonly clave = signal('');

  /** 0 a 4: cuántos requisitos cumple la contraseña. */
  readonly fuerza = computed(() => {
    const valor = this.clave();

    if (valor === '') {
      return 0;
    }

    return [
      valor.length >= 8,
      /[A-ZÁÉÍÓÚÑ]/.test(valor),
      /[a-záéíóúñ]/.test(valor),
      /\d/.test(valor),
    ].filter(Boolean).length;
  });

  readonly etiquetaFuerza = computed(
    () => ['', 'Muy débil', 'Débil', 'Aceptable', 'Segura'][this.fuerza()],
  );

  constructor() {
    this.form.controls.password.valueChanges.subscribe((valor) => {
      this.clave.set(valor ?? '');
      // En cuanto la cambia, el rechazo del servidor ya no habla de lo que hay
      // escrito: dejarlo puesto sería señalar un problema que quizá ya no está.
      this.errorClave.set(null);
    });
  }

  alternarClave(): void {
    this.verClave.update((visible) => !visible);
  }

  invalido(campo: 'name' | 'email' | 'password' | 'password_confirmation'): boolean {
    const control = this.form.controls[campo];
    return control.invalid && control.touched;
  }

  get confirmacionNoCoincide(): boolean {
    return this.form.hasError('noCoinciden') && this.form.controls.password_confirmation.touched;
  }

  async registrar(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.enviando.set(true);
    this.error.set(null);
    this.errorClave.set(null);

    try {
      await this.auth.registrar(this.form.getRawValue());
      await this.router.navigateByUrl('/tabs/inicio', { replaceUrl: true });

      const toast = await this.toastCtrl.create({
        message: '¡Listo! Tu cuenta ya está creada.',
        duration: 3200,
        color: 'success',
      });

      await toast.present();
    } catch (error) {
      const porCampo = erroresPorCampo(error);
      const deLaClave = porCampo['password'] ?? null;
      const otro = Object.entries(porCampo).find(([campo]) => campo !== 'password')?.[1];

      this.errorClave.set(deLaClave);
      this.error.set(otro ?? (deLaClave ? null : mensajeDeError(error, 'No se pudo crear la cuenta.')));
    } finally {
      this.enviando.set(false);
    }
  }
}
