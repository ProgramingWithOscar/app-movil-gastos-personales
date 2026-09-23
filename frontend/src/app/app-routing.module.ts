import { NgModule } from '@angular/core';
import { PreloadAllModules, RouterModule, Routes } from '@angular/router';

import { authGuard, guestGuard } from './core/auth';

const routes: Routes = [
  {
    path: 'bienvenida',
    canActivate: [guestGuard],
    loadChildren: () => import('./bienvenida/bienvenida.module').then((m) => m.BienvenidaPageModule),
  },
  {
    path: 'login',
    canActivate: [guestGuard],
    loadChildren: () => import('./auth/login/login.module').then((m) => m.LoginPageModule),
  },
  {
    path: 'registro',
    canActivate: [guestGuard],
    loadChildren: () => import('./auth/registro/registro.module').then((m) => m.RegistroPageModule),
  },
  {
    path: 'recuperar',
    canActivate: [guestGuard],
    loadChildren: () => import('./auth/recuperar/recuperar.module').then((m) => m.RecuperarPageModule),
  },
  {
    path: 'perfil/preferencias',
    canActivate: [authGuard],
    loadChildren: () =>
      import('./perfil/preferencias/preferencias.module').then((m) => m.PreferenciasPageModule),
  },
  {
    path: 'perfil/seguridad',
    canActivate: [authGuard],
    loadChildren: () =>
      import('./perfil/seguridad/seguridad.module').then((m) => m.SeguridadPageModule),
  },
  {
    path: 'cuentas',
    canActivate: [authGuard],
    loadChildren: () => import('./cuentas/cuentas.module').then((m) => m.CuentasPageModule),
  },
  {
    path: 'tabs',
    canActivate: [authGuard],
    loadChildren: () => import('./tabs/tabs.module').then((m) => m.TabsPageModule),
  },
  { path: '', redirectTo: 'tabs/inicio', pathMatch: 'full' },
  { path: '**', redirectTo: 'tabs/inicio' },
];

@NgModule({
  imports: [RouterModule.forRoot(routes, { preloadingStrategy: PreloadAllModules })],
  exports: [RouterModule],
})
export class AppRoutingModule {}
