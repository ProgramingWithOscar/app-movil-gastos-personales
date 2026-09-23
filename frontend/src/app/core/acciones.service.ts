import { Injectable, signal } from '@angular/core';

/**
 * Puente entre el botón "+" de la barra de pestañas y la pantalla de inicio,
 * que es quien abre la hoja de acciones. Sin esto, el botón tendría que
 * conocer el estado interno de otra página.
 */
@Injectable({ providedIn: 'root' })
export class AccionesService {
  private readonly _solicitudes = signal(0);

  readonly solicitudes = this._solicitudes.asReadonly();

  abrir(): void {
    this._solicitudes.update((n) => n + 1);
  }
}
