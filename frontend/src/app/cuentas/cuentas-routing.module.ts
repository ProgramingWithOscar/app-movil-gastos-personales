import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { CuentaDetallePage } from './cuenta-detalle.page';
import { CuentasPage } from './cuentas.page';

const routes: Routes = [
  { path: '', component: CuentasPage },
  { path: ':id', component: CuentaDetallePage },
];

@NgModule({
  imports: [RouterModule.forChild(routes)],
  exports: [RouterModule],
})
export class CuentasPageRoutingModule {}
