import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Output, booleanAttribute, input } from '@angular/core';

import { environment } from '../../environments/environment';

/**
 * Pantalla de arranque de la app.
 *
 * El splash nativo es solo el color de marca, sin contenido: en cuanto la
 * WebView pinta, el que se ve es este. Por eso ambos comparten el mismo fondo
 * —el de `--splash-fondo` y el de `drawable/splash.xml`—, y el relevo entre uno
 * y otro no se nota.
 *
 * Se mantiene claro siempre, también en modo oscuro: es identidad de marca, no
 * una pantalla de la interfaz.
 */
@Component({
  selector: 'app-splash',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="splash" [class.splash--cerrando]="cerrando()" (animationend)="alTerminar($event)">
      <span class="splash__circulo splash__circulo--arriba" aria-hidden="true"></span>
      <span class="splash__circulo splash__circulo--abajo" aria-hidden="true"></span>

      <div class="splash__centro">
        <img class="splash__logo" src="assets/imagenes/kuenta-icon.png" alt="" aria-hidden="true" />
        <h1 class="splash__marca">Kuenta</h1>
        <p class="splash__lema">Tus gastos, bajo control</p>
      </div>

      <span class="splash__version">v{{ version }}</span>
    </div>
  `,
  styles: [
    `
      :host {
        --splash-fondo: #f3f9f6;
        --splash-adorno: #e6f3ec;
        --splash-marca: #123d2c;
        --splash-lema: #4a7c64;
        --splash-version: #a9c4b7;

        position: fixed;
        inset: 0;
        z-index: 20000;
        display: block;
      }

      .splash {
        position: absolute;
        inset: 0;
        overflow: hidden;
        background: var(--splash-fondo);
        display: flex;
        align-items: center;
        justify-content: center;
      }

      /* Los dos círculos dan profundidad sin competir con el logo: mismo verde
         del fondo, apenas un tono por encima. */
      .splash__circulo {
        position: absolute;
        border-radius: 50%;
        background: var(--splash-adorno);
        transform: scale(0.6);
        opacity: 0;
        animation: adorno 1100ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
      }

      .splash__circulo--arriba {
        width: 128vw;
        height: 128vw;
        top: -74vw;
        right: -46vw;
      }

      .splash__circulo--abajo {
        width: 96vw;
        height: 96vw;
        bottom: -52vw;
        left: -44vw;
        animation-delay: 120ms;
      }

      .splash__centro {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        /* Un poco por encima del centro óptico: centrado exacto se ve bajo. */
        margin-bottom: 6vh;
      }

      .splash__logo {
        width: 132px;
        height: 132px;
        border-radius: 30px;
        box-shadow: 0 18px 40px rgba(5, 150, 105, 0.24);
        opacity: 0;
        transform: scale(0.82);
        animation: entra-logo 720ms cubic-bezier(0.34, 1.56, 0.64, 1) 140ms forwards;
      }

      .splash__marca {
        margin: 26px 0 0;
        font-size: 42px;
        font-weight: 800;
        letter-spacing: -1px;
        color: var(--splash-marca);
        opacity: 0;
        transform: translateY(14px);
        animation: entra-texto 560ms cubic-bezier(0.22, 1, 0.36, 1) 420ms forwards;
      }

      .splash__lema {
        margin: 6px 0 0;
        font-size: 15px;
        font-weight: 500;
        color: var(--splash-lema);
        opacity: 0;
        transform: translateY(14px);
        animation: entra-texto 560ms cubic-bezier(0.22, 1, 0.36, 1) 540ms forwards;
      }

      .splash__version {
        position: absolute;
        left: 0;
        right: 0;
        bottom: calc(24px + env(safe-area-inset-bottom));
        text-align: center;
        font-size: 12px;
        font-weight: 600;
        color: var(--splash-version);
        opacity: 0;
        animation: entra-texto 560ms ease-out 700ms forwards;
      }

      /* La salida la dispara el padre; el evento animationend avisa de que ya
         se puede quitar del DOM. */
      .splash--cerrando {
        animation: sale 380ms ease-in forwards;
      }

      @keyframes adorno {
        to {
          opacity: 1;
          transform: scale(1);
        }
      }

      @keyframes entra-logo {
        to {
          opacity: 1;
          transform: scale(1);
        }
      }

      @keyframes entra-texto {
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @keyframes sale {
        to {
          opacity: 0;
          transform: scale(1.06);
        }
      }

      @media (prefers-reduced-motion: reduce) {
        .splash__circulo,
        .splash__logo,
        .splash__marca,
        .splash__lema,
        .splash__version {
          animation-duration: 1ms;
          animation-delay: 0ms;
        }

        .splash--cerrando {
          animation-duration: 120ms;
        }
      }
    `,
  ],
})
export class SplashComponent {
  /** Lo pone el padre cuando la sesión ya está resuelta. */
  readonly cerrando = input(false, { transform: booleanAttribute });

  /** Se emite cuando la animación de salida terminó y ya se puede desmontar. */
  @Output() readonly cerrado = new EventEmitter<void>();

  readonly version = environment.version;

  alTerminar(evento: AnimationEvent): void {
    if (evento.animationName.includes('sale')) {
      this.cerrado.emit();
    }
  }
}
