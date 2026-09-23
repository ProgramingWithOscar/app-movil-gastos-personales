import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { API_URL } from './api.config';
import { Preferences } from '@capacitor/preferences';

import { Dashboard } from './dashboard.model';
import { Periodo } from './movimiento.model';

const CLAVE_PERIODO = 'dashboard_periodo';
const PERIODOS: Periodo[] = ['dia', 'semana', 'mes', 'anio'];

@Injectable({ providedIn: 'root' })
export class DashboardService {
  private readonly http = inject(HttpClient);

  /**
   * El período elegido se recuerda entre sesiones: cada persona tiende a mirar
   * siempre el mismo y volver a "mes" en cada arranque estorba.
   */
  async periodoGuardado(): Promise<Periodo> {
    try {
      const { value } = await Preferences.get({ key: CLAVE_PERIODO });

      return PERIODOS.includes(value as Periodo) ? (value as Periodo) : 'mes';
    } catch {
      return 'mes';
    }
  }

  async guardarPeriodo(periodo: Periodo): Promise<void> {
    try {
      await Preferences.set({ key: CLAVE_PERIODO, value: periodo });
    } catch {
      // Sin persistencia se vuelve al valor por defecto; no es motivo de error.
    }
  }

  /** Una sola petición trae todo lo que pinta la pantalla principal. */
  cargar(periodo: Periodo, movimientos = 20): Observable<{ data: Dashboard }> {
    return this.http.get<{ data: Dashboard }>(`${API_URL}/dashboard`, {
      params: { periodo, movimientos },
    });
  }
}
