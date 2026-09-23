import { Component, inject, signal } from '@angular/core';
import { ToastController } from '@ionic/angular';

import { AuthService, mensajeDeError, TemaUsuario } from '../../core/auth';

@Component({
  selector: 'app-preferencias',
  templateUrl: './preferencias.page.html',
  standalone: false,
})
export class PreferenciasPage {
  private readonly auth = inject(AuthService);
  private readonly toastCtrl = inject(ToastController);

  readonly usuario = this.auth.usuario;
  readonly guardando = signal(false);

  readonly monedas = [
    { codigo: 'COP', nombre: 'Peso colombiano' },
    { codigo: 'USD', nombre: 'Dólar estadounidense' },
    { codigo: 'EUR', nombre: 'Euro' },
    { codigo: 'MXN', nombre: 'Peso mexicano' },
    { codigo: 'ARS', nombre: 'Peso argentino' },
    { codigo: 'CLP', nombre: 'Peso chileno' },
    { codigo: 'PEN', nombre: 'Sol peruano' },
  ];

  readonly zonas = [
    'America/Bogota',
    'America/Mexico_City',
    'America/Lima',
    'America/Santiago',
    'America/Argentina/Buenos_Aires',
    'Europe/Madrid',
  ];

  readonly temas: { valor: TemaUsuario; nombre: string; icono: string }[] = [
    { valor: 'system', nombre: 'Automático', icono: 'phone-portrait-outline' },
    { valor: 'light', nombre: 'Claro', icono: 'sunny-outline' },
    { valor: 'dark', nombre: 'Oscuro', icono: 'moon-outline' },
  ];

  readonly notificaciones = [
    { clave: 'payment_reminder', nombre: 'Recordatorio de pago' },
    { clave: 'budget_threshold', nombre: 'Presupuesto cerca del límite' },
    { clave: 'budget_exceeded', nombre: 'Presupuesto superado' },
    { clave: 'recurring_upcoming', nombre: 'Movimiento recurrente próximo' },
    { clave: 'goal_reached', nombre: 'Meta alcanzada' },
    { clave: 'daily_log_reminder', nombre: 'Recordatorio diario para registrar' },
  ];

  async cambiar(campo: 'default_currency' | 'timezone' | 'locale' | 'theme', valor: string): Promise<void> {
    if (this.usuario()?.[campo] === valor) {
      return;
    }

    this.guardando.set(true);

    try {
      await this.auth.actualizarPerfil({ [campo]: valor });

      if (campo === 'theme') {
        this.aplicarTema(valor as TemaUsuario);
      }
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo guardar el cambio.'), 'danger');
    } finally {
      this.guardando.set(false);
    }
  }

  async cambiarNotificacion(clave: string, activa: boolean): Promise<void> {
    if (this.usuario()?.notification_preferences?.[clave] === activa) {
      return;
    }

    try {
      await this.auth.actualizarNotificaciones({ [clave]: activa });
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo guardar la preferencia.'), 'danger');
    }
  }

  /** Aplica el tema al instante, sin esperar a reiniciar la app. */
  private aplicarTema(tema: TemaUsuario): void {
    const raiz = document.documentElement;

    if (tema === 'system') {
      raiz.removeAttribute('data-tema');
      return;
    }

    raiz.setAttribute('data-tema', tema);
  }

  private async avisar(mensaje: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({ message: mensaje, duration: 2200, color });
    await toast.present();
  }
}
