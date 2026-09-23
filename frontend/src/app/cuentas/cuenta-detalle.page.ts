import { Component, computed, inject, signal } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

import { mensajeDeError } from '../core/auth';
import { Cuenta } from '../core/cuenta.model';
import { CuentasService } from '../core/cuentas.service';
import { Movimiento } from '../core/movimiento.model';

@Component({
  selector: 'app-cuenta-detalle',
  templateUrl: './cuenta-detalle.page.html',
  styleUrl: './cuentas.page.scss',
  standalone: false,
})
export class CuentaDetallePage {
  private readonly servicio = inject(CuentasService);
  private readonly ruta = inject(ActivatedRoute);

  private readonly id = Number(this.ruta.snapshot.paramMap.get('id'));

  readonly cuenta = signal<Cuenta | null>(null);
  readonly movimientos = signal<Movimiento[]>([]);
  readonly cargando = signal(true);
  readonly error = signal<string | null>(null);
  readonly pagina = signal(1);
  readonly ultimaPagina = signal(1);

  readonly hayMas = computed(() => this.pagina() < this.ultimaPagina());

  ionViewWillEnter(): void {
    this.pagina.set(1);
    this.movimientos.set([]);
    this.cargar();
  }

  private cargar(): void {
    this.cargando.set(true);

    this.servicio.listar(true).subscribe({
      next: ({ data }) => {
        this.cuenta.set(data.find((c) => c.id === this.id) ?? null);
        this.cargarMovimientos();
      },
      error: (error) => {
        this.cargando.set(false);
        this.error.set(mensajeDeError(error, 'No se pudo cargar la cuenta.'));
      },
    });
  }

  private cargarMovimientos(): void {
    this.servicio.movimientos(this.id, this.pagina()).subscribe({
      next: ({ data, meta }) => {
        // Se acumulan: al pedir más páginas no se pierde lo ya cargado.
        this.movimientos.update((actuales) => [...actuales, ...data]);
        this.ultimaPagina.set(meta.last_page);
        this.cargando.set(false);
        this.error.set(null);
      },
      error: (error) => {
        this.cargando.set(false);
        this.error.set(mensajeDeError(error, 'No se pudo cargar el historial.'));
      },
    });
  }

  cargarMas(evento?: { target: { complete: () => void } }): void {
    if (!this.hayMas()) {
      evento?.target.complete();
      return;
    }

    this.pagina.update((n) => n + 1);

    this.servicio.movimientos(this.id, this.pagina()).subscribe({
      next: ({ data, meta }) => {
        this.movimientos.update((actuales) => [...actuales, ...data]);
        this.ultimaPagina.set(meta.last_page);
        evento?.target.complete();
      },
      error: () => evento?.target.complete(),
    });
  }
}
