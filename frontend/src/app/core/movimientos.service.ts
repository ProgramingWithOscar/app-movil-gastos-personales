import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { API_URL } from './api.config';
import { Movimiento, NuevoMovimiento, TipoMovimiento } from './movimiento.model';

@Injectable({ providedIn: 'root' })
export class MovimientosService {
  private readonly http = inject(HttpClient);
  private readonly url = `${API_URL}/movimientos`;

  listar(
    parametros: { tipo?: TipoMovimiento; desde?: string; hasta?: string; limite?: number } = {},
  ): Observable<{ data: Movimiento[] }> {
    return this.http.get<{ data: Movimiento[] }>(this.url, { params: { ...parametros } });
  }

  crear(movimiento: NuevoMovimiento): Observable<{ data: Movimiento }> {
    return this.http.post<{ data: Movimiento }>(this.url, movimiento);
  }

  eliminar(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
}
