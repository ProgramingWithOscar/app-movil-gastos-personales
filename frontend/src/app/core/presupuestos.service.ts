import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { API_URL } from './api.config';
import { PresupuestoEvaluado } from './dashboard.model';

export interface NuevoPresupuesto {
  categoria: string | null;
  monto: number;
  periodo: 'semana' | 'mes';
  umbral_alerta?: number;
}

@Injectable({ providedIn: 'root' })
export class PresupuestosService {
  private readonly http = inject(HttpClient);
  private readonly url = `${API_URL}/presupuestos`;

  /** Ya vienen cruzados con lo gastado: la app no recalcula nada. */
  listar(): Observable<{ data: PresupuestoEvaluado[] }> {
    return this.http.get<{ data: PresupuestoEvaluado[] }>(this.url);
  }

  crear(presupuesto: NuevoPresupuesto): Observable<unknown> {
    return this.http.post(this.url, presupuesto);
  }

  actualizar(id: number, presupuesto: NuevoPresupuesto): Observable<unknown> {
    return this.http.put(`${this.url}/${id}`, presupuesto);
  }

  eliminar(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
}
