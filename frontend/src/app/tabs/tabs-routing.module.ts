import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { TabsPage } from './tabs.page';

const routes: Routes = [
  {
    path: '',
    component: TabsPage,
    children: [
      {
        path: 'inicio',
        loadChildren: () => import('../home/home.module').then((m) => m.HomePageModule),
      },
      {
        path: 'movimientos',
        loadChildren: () =>
          import('../proximamente/proximamente.module').then((m) => m.ProximamentePageModule),
        data: {
          titulo: 'Movimientos',
          icono: 'swap-vertical-outline',
          descripcion:
            'Aquí verás el historial completo con búsqueda, filtros por categoría y rangos de fecha.',
        },
      },
      {
        path: 'presupuestos',
        loadChildren: () =>
          import('../presupuestos/presupuestos.module').then((m) => m.PresupuestosPageModule),
      },
      {
        path: 'perfil',
        loadChildren: () => import('../perfil/perfil.module').then((m) => m.PerfilPageModule),
      },
      { path: '', redirectTo: 'inicio', pathMatch: 'full' },
    ],
  },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class TabsPageRoutingModule {}
