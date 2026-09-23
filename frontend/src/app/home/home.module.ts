import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonicModule } from '@ionic/angular/lazy';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HomePage } from './home.page';
import { IndicadorComponent } from './indicador/indicador.component';
import { PresupuestoBarraComponent } from '../shared/presupuesto-barra.component';

import { HomePageRoutingModule } from './home-routing.module';


@NgModule({
  imports: [
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    IonicModule,
    HomePageRoutingModule,
    IndicadorComponent,
    PresupuestoBarraComponent,
  ],
  declarations: [HomePage]
})
export class HomePageModule {}
