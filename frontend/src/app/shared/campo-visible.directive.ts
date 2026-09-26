import { Directive, ElementRef, HostListener, OnDestroy, inject } from '@angular/core';

/**
 * Mantiene a la vista el campo que se está escribiendo cuando sale el teclado.
 *
 * Las hojas de la app son `ion-modal` con un `div` propio haciendo de scroll, y
 * el asistente de teclado de Ionic solo actúa dentro de un `ion-content`. Sin
 * esto, en una hoja larga —crear una cuenta— el teclado tapaba el saldo y se
 * escribía a ciegas.
 *
 * Dos cosas que hay que saber para entender el arreglo:
 *
 * 1. No basta con `scrollIntoView`. La hoja conserva la altura que calculó al
 *    abrirse, así que cuando el teclado encoge la ventana es la hoja entera la
 *    que queda por debajo del teclado. Primero hay que acotarla a lo que queda
 *    visible; eso crea el scroll, y entonces sí se puede acercar el campo.
 *
 * 2. El estado lo lleva `document.activeElement`, no un campo guardado en un
 *    `focusin`. La primera versión sí lo guardaba y se pisaba a sí misma: el
 *    `focusout` lo borraba antes de que corriera el ajuste pendiente, y la
 *    hoja se soltaba justo cuando había que acotarla. Preguntar quién tiene el
 *    foco en el momento de ajustar no tiene ese problema.
 */
@Directive({
  selector: '[appCampoVisible]',
  standalone: true,
})
export class CampoVisibleDirective implements OnDestroy {
  private readonly host = inject<ElementRef<HTMLElement>>(ElementRef);

  private temporizador?: ReturnType<typeof setTimeout>;

  private readonly alCambiarElArea = () => this.ajustar();

  constructor() {
    // Es `visualViewport` lo que encoge al abrirse el teclado, y esperarlo a él
    // en vez de a un número fijo de milisegundos va al ritmo de cada teléfono.
    window.visualViewport?.addEventListener('resize', this.alCambiarElArea);
  }

  ngOnDestroy(): void {
    window.visualViewport?.removeEventListener('resize', this.alCambiarElArea);
    clearTimeout(this.temporizador);
  }

  /**
   * Con el teclado ya abierto, saltar de un campo a otro no cambia el área, así
   * que no llega el evento de arriba y hace falta este empujón.
   */
  @HostListener('focusin')
  @HostListener('focusout')
  alCambiarElFoco(): void {
    clearTimeout(this.temporizador);
    this.temporizador = setTimeout(() => this.ajustar(), 350);
  }

  private ajustar(): void {
    const caja = this.host.nativeElement;
    const campo = document.activeElement as HTMLElement | null;

    const escribiendo =
      campo &&
      caja.contains(campo) &&
      campo.matches('input, textarea, ion-input, ion-textarea');

    if (!escribiendo) {
      this.soltarAltura();

      return;
    }

    const visible = window.visualViewport?.height ?? window.innerHeight;
    const desde = caja.getBoundingClientRect().top;

    caja.style.maxHeight = `${Math.max(120, visible - desde)}px`;
    caja.style.overflowY = 'auto';

    campo.scrollIntoView({ block: 'center', behavior: 'smooth' });
  }

  private soltarAltura(): void {
    this.host.nativeElement.style.removeProperty('max-height');
    this.host.nativeElement.style.removeProperty('overflow-y');
  }
}
