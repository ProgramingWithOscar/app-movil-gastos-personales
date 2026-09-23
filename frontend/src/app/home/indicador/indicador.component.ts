import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { IonicModule } from '@ionic/angular/lazy';

/**
 * Tono del indicador según lo que significa el dato, no según su signo.
 *
 * En gastos, bajar es una buena noticia: por eso el verde va con la reducción,
 * al revés de lo que sería en un indicador de ingresos.
 */
export type TonoIndicador = 'positivo' | 'negativo' | 'neutro';

@Component({
  selector: 'app-indicador',
  standalone: true,
  imports: [CommonModule, IonicModule],
  template: `
    <div class="indicador" [attr.data-tono]="tono">
      <div class="indicador__cabecera">
        <ion-icon [name]="icono"></ion-icon>
        <span class="indicador__etiqueta">{{ etiqueta }}</span>
      </div>

      <p class="indicador__valor">{{ valor }}</p>

      @if (detalle) {
        <p class="indicador__detalle">{{ detalle }}</p>
      }
    </div>
  `,
  styles: [
    `
      :host {
        display: block;
        min-width: 0;
      }

      .indicador {
        height: 100%;
        padding: 0.7rem 0.6rem;
        border: 1px solid var(--gp-borde);
        border-radius: var(--gp-radio);
        background: var(--gp-superficie);
        box-shadow: var(--gp-sombra-suave);
      }

      .indicador__cabecera {
        display: flex;
        align-items: flex-start;
        gap: 0.28rem;
        margin-bottom: 0.3rem;
        color: var(--gp-texto-suave);
      }

      .indicador__cabecera ion-icon {
        font-size: 15px;
      }

      .indicador__etiqueta {
        font-size: 0.66rem;
        font-weight: 600;
        line-height: 1.25;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        // Se permite una segunda línea: cortar "Mayor gasto" con puntos
        // suspensivos hacía ilegible la etiqueta.
        overflow-wrap: anywhere;
      }

      .indicador__valor {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.25;
        letter-spacing: -0.015em;
        overflow-wrap: anywhere;
      }

      .indicador__detalle {
        margin: 0.15rem 0 0;
        font-size: 0.68rem;
        line-height: 1.3;
        color: var(--gp-texto-suave);
        overflow-wrap: anywhere;
      }

      .indicador[data-tono='positivo'] .indicador__cabecera ion-icon,
      .indicador[data-tono='positivo'] .indicador__valor {
        color: var(--ion-color-success);
      }

      .indicador[data-tono='negativo'] .indicador__cabecera ion-icon,
      .indicador[data-tono='negativo'] .indicador__valor {
        color: var(--ion-color-danger);
      }
    `,
  ],
})
export class IndicadorComponent {
  @Input({ required: true }) etiqueta!: string;
  @Input({ required: true }) valor!: string;
  @Input() detalle?: string;
  @Input() icono = 'analytics-outline';
  @Input() tono: TonoIndicador = 'neutro';
}
