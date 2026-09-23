import { AfterViewInit, Component, ElementRef, inject, signal, viewChild } from '@angular/core';
import { Router } from '@angular/router';
import { Preferences } from '@capacitor/preferences';

/** Marca que el usuario ya recorrió el onboarding en este dispositivo. */
const CLAVE_VISTO = 'onboarding_visto';

interface Pantalla {
  imagen: string;
  titulo: string;
  texto: string;
}

/**
 * Puerta de entrada: presenta la app en tres pantallas y ofrece los dos
 * caminos.
 *
 * Esta ruta no es solo la primera visita. El guard, el interceptor y el cierre
 * de sesión mandan aquí cada vez que alguien se queda sin sesión, así que a
 * quien ya recorrió el onboarding se le abre directamente en la última
 * pantalla, la que tiene los botones. Volver a pedirle tres deslizamientos para
 * llegar al login sería un castigo por haber caducado la sesión.
 */
@Component({
  selector: 'app-bienvenida',
  templateUrl: './bienvenida.page.html',
  styleUrl: './bienvenida.page.scss',
  standalone: false,
})
export class BienvenidaPage implements AfterViewInit {
  private readonly router = inject(Router);
  private readonly pista = viewChild.required<ElementRef<HTMLElement>>('pista');

  readonly pantallas: Pantalla[] = [
    {
      imagen: 'assets/imagenes/onboarding-1-registra-gastos.png',
      titulo: 'Registra en segundos',
      texto: 'Anota un gasto o un ingreso en menos de diez segundos, y elige de qué cuenta sale.',
    },
    {
      imagen: 'assets/imagenes/onboarding-2-analiza-gastos.png',
      titulo: 'Mira a dónde va tu dinero',
      texto: 'Cada mes, en qué categorías se te va y cómo cambia respecto al mes anterior.',
    },
    {
      imagen: 'assets/imagenes/onboarding-3-cumple-metas.png',
      titulo: 'Ponte un límite y cúmplelo',
      texto: 'Te avisamos cuando vas camino de pasarte, no cuando ya no hay nada que hacer.',
    },
  ];

  readonly actual = signal(0);

  /**
   * Hasta que no se sabe en qué pantalla hay que abrir, no se enseña nada: si
   * no, al volver del logout se vería la primera y un salto seco a la tercera.
   */
  readonly listo = signal(false);

  get ultima(): number {
    return this.pantallas.length - 1;
  }

  esUltima(): boolean {
    return this.actual() === this.ultima;
  }

  async ngAfterViewInit(): Promise<void> {
    if (await this.yaLoVio()) {
      this.irA(this.ultima, 'instant');
    }

    this.listo.set(true);
  }

  /** El scroll nativo es la fuente de la verdad: el gesto no pasa por aquí. */
  alDesplazar(): void {
    const elemento = this.pista().nativeElement;
    const indice = Math.round(elemento.scrollLeft / elemento.clientWidth);

    if (indice === this.actual()) {
      return;
    }

    this.actual.set(indice);

    if (indice === this.ultima) {
      void this.marcarVisto();
    }
  }

  irA(indice: number, comportamiento: ScrollBehavior = 'smooth'): void {
    const elemento = this.pista().nativeElement;

    elemento.scrollTo({ left: indice * elemento.clientWidth, behavior: comportamiento });
    this.actual.set(indice);
  }

  saltar(): void {
    this.irA(this.ultima);
    void this.marcarVisto();
  }

  avanzar(): void {
    if (this.esUltima()) {
      void this.router.navigateByUrl('/registro');

      return;
    }

    this.irA(this.actual() + 1);
  }

  private async yaLoVio(): Promise<boolean> {
    try {
      const { value } = await Preferences.get({ key: CLAVE_VISTO });

      return value === '1';
    } catch {
      // Sin persistencia se recorre el onboarding otra vez: molesto, no roto.
      return false;
    }
  }

  private async marcarVisto(): Promise<void> {
    try {
      await Preferences.set({ key: CLAVE_VISTO, value: '1' });
    } catch {
      // Igual que arriba: no es motivo para interrumpir nada.
    }
  }
}
