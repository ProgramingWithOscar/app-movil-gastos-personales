import { NgModule, provideAppInitializer, inject } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';
import { RouteReuseStrategy } from '@angular/router';
import { provideHttpClient, withInterceptors } from '@angular/common/http';

import { IonicModule, IonicRouteStrategy } from '@ionic/angular/lazy';

import { AppComponent } from './app.component';
import { AppRoutingModule } from './app-routing.module';
import { AuthService, authInterceptor } from './core/auth';
import { SplashComponent } from './shared/splash.component';

@NgModule({
  declarations: [AppComponent],
  imports: [SplashComponent, BrowserModule, IonicModule.forRoot({
      // Material Design en todas las plataformas: un solo lenguaje visual,
      // como en Flutter. Sin esto, iOS usaría el modo cupertino.
      mode: 'md',
      rippleEffect: true,
    }), AppRoutingModule],
  providers: [
    { provide: RouteReuseStrategy, useClass: IonicRouteStrategy },
    provideHttpClient(withInterceptors([authInterceptor])),
    // Restaura la sesión guardada antes de que se resuelva la primera ruta,
    // para que el guard no rebote al usuario nada más abrir la app.
    provideAppInitializer(() => inject(AuthService).restaurarSesion()),
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
