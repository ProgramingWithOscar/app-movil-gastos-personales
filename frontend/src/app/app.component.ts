import { Component, OnInit, inject } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { SplashScreen } from '@capacitor/splash-screen';

import { AuthService } from './core/auth';

@Component({
  selector: 'app-root',
  templateUrl: 'app.component.html',
  styleUrls: ['app.component.scss'],
  standalone: false,
})
export class AppComponent implements OnInit {
  private readonly auth = inject(AuthService);

  /**
   * El splash nativo se oculta a mano (launchAutoHide: false) cuando la sesión
   * ya está resuelta: así no se ve un parpadeo entre el splash y el login.
   */
  async ngOnInit(): Promise<void> {
    if (!Capacitor.isNativePlatform()) {
      return;
    }

    while (this.auth.cargando()) {
      await new Promise((resolver) => setTimeout(resolver, 50));
    }

    await SplashScreen.hide({ fadeOutDuration: 250 });
  }
}
