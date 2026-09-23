import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { API_URL } from './api.config';
import { Cuenta, NuevaCuenta, RespuestaCuentas } from './cuenta.model';
import { Movimiento } from './movimiento.model';

@Injectable({ providedIn: 'root' })
export class CuentasService {
  private readonly http = inject(HttpClient);
  private readonly url = `${API_URL}/cuentas`;

  /** Los saldos vienen calculados del servidor: la app no suma nada. */
  listar(incluirArchivadas = false): Observable<RespuestaCuentas> {
    return this.http.get<RespuestaCuentas>(this.url, {
      params: incluirArchivadas ? { incluir_archivadas: 1 } : {},
    });
  }

  crear(cuenta: NuevaCuenta): Observable<{ data: Cuenta }> {
    return this.http.post<{ data: Cuenta }>(this.url, cuenta);
  }

  actualizar(id: number, cuenta: NuevaCuenta): Observable<{ data: Cuenta }> {
    return this.http.put<{ data: Cuenta }>(`${this.url}/${id}`, cuenta);
  }

  /** Archiva si tiene movimientos; solo borra de verdad si nunca tuvo ninguno. */
  archivar(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.url}/${id}`);
  }

  marcarFavorita(id: number): Observable<{ data: Cuenta }> {
    return this.http.put<{ data: Cuenta }>(`${this.url}/${id}/favorita`, {});
  }

  movimientos(id: number, pagina = 1): Observable<{ data: Movimiento[]; meta: { total: number; last_page: number } }> {
    return this.http.get<{ data: Movimiento[]; meta: { total: number; last_page: number } }>(
      `${this.url}/${id}/movimientos`,
      { params: { page: pagina } },
    );
  }
}
