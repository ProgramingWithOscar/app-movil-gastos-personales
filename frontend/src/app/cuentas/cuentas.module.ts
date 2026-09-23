import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { CuentaDetallePage } from './cuenta-detalle.page';
import { CuentasPage } from './cuentas.page';
import { CuentasPageRoutingModule } from './cuentas-routing.module';

@NgModule({
  imports: [CommonModule, ReactiveFormsModule, IonicModule, CuentasPageRoutingModule],
  declarations: [CuentasPage, CuentaDetallePage],
})
export class CuentasPageModule {}
