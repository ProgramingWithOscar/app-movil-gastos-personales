import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { PresupuestoBarraComponent } from '../shared/presupuesto-barra.component';
import { PresupuestosPage } from './presupuestos.page';
import { PresupuestosPageRoutingModule } from './presupuestos-routing.module';

@NgModule({
  imports: [
    CommonModule,
    ReactiveFormsModule,
    IonicModule,
    PresupuestosPageRoutingModule,
    PresupuestoBarraComponent,
  ],
  declarations: [PresupuestosPage],
})
export class PresupuestosPageModule {}
