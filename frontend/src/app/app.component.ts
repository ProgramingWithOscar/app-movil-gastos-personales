import { Component, OnInit, inject, signal } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';

import { AuthService } from './core/auth';

/**
 * Tiempo mínimo que el splash está en pantalla. Sin él, cuando la sesión se
 * restaura rápido, la animación se ve como un parpadeo: peor que no tenerla.
 */
const MINIMO_EN_PANTALLA_MS = 1400;

const espera = (ms: number) => new Promise((resolver) => setTimeout(resolver, ms));

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent implements OnInit {
  private readonly auth = inject(AuthService);

  readonly splashVisible = signal(true);
  readonly splashCerrando = signal(false);

  async ngOnInit(): Promise<void> {
    const entrada = Date.now();

    // El splash nativo es un color plano, el mismo que el fondo de este. Se
    // oculta en cuanto la WebView pinta, y el relevo no se ve.
    if (Capacitor.isNativePlatform()) {
      await SplashScreen.hide({ fadeOutDuration: 200 });
    }

    while (this.auth.cargando()) {
      await espera(50);
    }

    await espera(Math.max(0, MINIMO_EN_PANTALLA_MS - (Date.now() - entrada)));

    this.splashCerrando.set(true);
  }
}
