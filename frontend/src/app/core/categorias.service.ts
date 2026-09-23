import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Preferences } from '@capacitor/preferences';
import { firstValueFrom } from 'rxjs';

import { API_URL } from './api.config';
import { TipoMovimiento } from './movimiento.model';

export interface Categoria {
  id: string;
  nombre: string;
  icono: string;
  color: string;
  tipos: TipoMovimiento[];
}

const CLAVE_CACHE = 'categorias_catalogo';

/**
 * El catálogo de categorías, que vive en el servidor.
 *
 * Antes estaba escrito dentro de `home.page.ts`. Funcionaba porque solo hay un
 * cliente, pero el nombre visible tiene que cambiar con el idioma del perfil y
 * eso solo lo sabe el servidor; y una app vieja podía ofrecer una categoría que
 * ya no existe, que a partir de ahora la API rechaza.
 *
 * Se guarda una copia en el dispositivo para que la pantalla principal pueda
 * pintarse sin esperar a la red. La copia se refresca en segundo plano: una
 * categoría con el nombre de ayer es un problema menor que una pantalla en
 * blanco.
 */
@Injectable({ providedIn: 'root' })
export class CategoriasService {
  private readonly http = inject(HttpClient);

  private readonly _catalogo = signal<Categoria[]>([]);

  /** Señal para que las pantallas se repinten cuando llegue el catálogo. */
  readonly catalogo = this._catalogo.asReadonly();

  private cargado = false;

  /** Todas las categorías, de la memoria, del dispositivo o de la red. */
  async cargar(): Promise<Categoria[]> {
    if (this.cargado) {
      return this._catalogo();
    }

    const guardadas = await this.deLaCache();

    if (guardadas) {
      this.aplicar(guardadas);
      // Sin await: la pantalla ya puede pintarse con lo que había.
      void this.refrescar();

      return guardadas;
    }

    return this.refrescar();
  }

  /** Las de un tipo. `otros` sale en los dos, por eso se filtra por `tipos`. */
  de(tipo: TipoMovimiento): Categoria[] {
    return this._catalogo().filter((categoria) => categoria.tipos.includes(tipo));
  }

  /**
   * Una categoría por su id.
   *
   * Si no está —un movimiento guardado con una categoría que ya no existe—
   * devuelve una de emergencia con el id como nombre, para que la lista se
   * pinte igual. Perder una fila del historial sería peor que enseñarla fea.
   */
  buscar(id: string): Categoria {
    return (
      this._catalogo().find((categoria) => categoria.id === id) ?? {
        id,
        nombre: id,
        icono: 'pricetag-outline',
        color: '#6b7280',
        tipos: [],
      }
    );
  }

  private async refrescar(): Promise<Categoria[]> {
    try {
      const respuesta = await firstValueFrom(
        this.http.get<{ data: Categoria[] }>(`${API_URL}/categorias`),
      );

      this.aplicar(respuesta.data);
      await this.guardarEnCache(respuesta.data);

      return respuesta.data;
    } catch {
      // Sin red y sin copia no hay catálogo. Devolver una lista vacía deja el
      // formulario sin categorías, que es visible; inventarlas aquí crearía
      // movimientos que el servidor luego rechaza.
      return this._catalogo();
    }
  }

  private aplicar(categorias: Categoria[]): void {
    this._catalogo.set(categorias);
    this.cargado = true;
  }

  private async deLaCache(): Promise<Categoria[] | null> {
    try {
      const { value } = await Preferences.get({ key: CLAVE_CACHE });

      return value ? (JSON.parse(value) as Categoria[]) : null;
    } catch {
      return null;
    }
  }

  private async guardarEnCache(categorias: Categoria[]): Promise<void> {
    try {
      await Preferences.set({ key: CLAVE_CACHE, value: JSON.stringify(categorias) });
    } catch {
      // Sin persistencia se pide a la red cada arranque; no es motivo de error.
    }
  }
}
