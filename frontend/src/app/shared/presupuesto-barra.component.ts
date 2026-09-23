import { CommonModule } from '@angular/common';
import { Component, Input, computed, input } from '@angular/core';
import { IonicModule } from '@ionic/angular/lazy';

import { EstadoPresupuesto, PresupuestoEvaluado } from '../core/dashboard.model';

/** Etiquetas y color de cada estado, en un solo sitio. */
const ESTADOS: Record<EstadoPresupuesto, { texto: string; color: string }> = {
  al_dia: { texto: 'Al día', color: 'var(--ion-color-success)' },
  cerca: { texto: 'Cerca del límite', color: 'var(--ion-color-warning)' },
  en_riesgo: { texto: 'Vas a superarlo', color: 'var(--ion-color-warning)' },
  superado: { texto: 'Superado', color: 'var(--ion-color-danger)' },
  sin_limite: { texto: 'Sin límite', color: 'var(--gp-texto-suave)' },
};

@Component({
  selector: 'app-presupuesto-barra',
  standalone: true,
  imports: [CommonModule, IonicModule],
  template: `
    <div class="p" [style.--tono]="color()">
      <div class="p__fila">
        <strong class="p__nombre">{{ nombre() }}</strong>
        <span class="p__cifras">
          {{ presupuesto().gastado | currency: 'COP' : 'symbol-narrow' : '1.0-0' }}
          <span class="p__limite">
            / {{ presupuesto().limite | currency: 'COP' : 'symbol-narrow' : '1.0-0' }}
          </span>
        </span>
      </div>

      <div class="p__barra">
        <span class="p__relleno" [style.width.%]="ancho()"></span>
      </div>

      <div class="p__pie">
        <span class="p__estado">{{ estado().texto }}</span>
        <span>
          @if (presupuesto().disponible >= 0) {
            Quedan {{ presupuesto().disponible | currency: 'COP' : 'symbol-narrow' : '1.0-0' }}
          } @else {
            Te pasaste por
            {{ -presupuesto().disponible | currency: 'COP' : 'symbol-narrow' : '1.0-0' }}
          }
          · {{ presupuesto().dias_restantes }} día(s)
        </span>
      </div>
    </div>
  `,
  styles: [
    `
      :host {
        display: block;
      }

      .p__fila {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
      }

      .p__nombre {
        font-size: 0.92rem;
        font-weight: 650;
      }

      .p__cifras {
        font-size: 0.85rem;
        font-weight: 650;
        white-space: nowrap;
      }

      .p__limite {
        color: var(--gp-texto-suave);
        font-weight: 500;
      }

      .p__barra {
        height: 8px;
        border-radius: 999px;
        background: var(--gp-borde);
        overflow: hidden;
      }

      .p__relleno {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: var(--tono);
        transition: width 0.5s cubic-bezier(0.22, 1, 0.36, 1);
      }

      .p__pie {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        margin-top: 0.35rem;
        font-size: 0.74rem;
        color: var(--gp-texto-suave);
      }

      .p__estado {
        font-weight: 650;
        color: var(--tono);
      }
    `,
  ],
})
export class PresupuestoBarraComponent {
  readonly presupuesto = input.required<PresupuestoEvaluado>();

  readonly estado = computed(() => ESTADOS[this.presupuesto().estado]);
  readonly color = computed(() => this.estado().color);

  /** La barra se corta en 100 aunque el gasto lo supere: el exceso se dice con
   *  palabras, no estirando una barra fuera de su caja. */
  readonly ancho = computed(() => Math.min(100, this.presupuesto().porcentaje));

  readonly nombre = computed(() => {
    const p = this.presupuesto();

    if (p.es_general) {
      return p.periodo === 'semana' ? 'Presupuesto semanal' : 'Presupuesto mensual';
    }

    return p.categoria!.charAt(0).toUpperCase() + p.categoria!.slice(1);
  });
}
