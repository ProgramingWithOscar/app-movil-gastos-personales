import { Component } from '@angular/core';

/** Puerta de entrada: presenta la app y ofrece los dos caminos. */
@Component({
  selector: 'app-bienvenida',
  templateUrl: './bienvenida.page.html',
  styleUrl: './bienvenida.page.scss',
  standalone: false,
})
export class BienvenidaPage {
  readonly ventajas = [
    { icono: 'flash-outline', texto: 'Registra un gasto en menos de diez segundos' },
    { icono: 'pie-chart-outline', texto: 'Mira en qué se te va el dinero cada mes' },
    { icono: 'shield-checkmark-outline', texto: 'Tus datos son solo tuyos' },
  ];
}
