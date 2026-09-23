import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { PreferenciasPage } from './preferencias.page';
import { PreferenciasPageRoutingModule } from './preferencias-routing.module';

@NgModule({
  imports: [CommonModule, ReactiveFormsModule, IonicModule, PreferenciasPageRoutingModule],
  declarations: [PreferenciasPage],
})
export class PreferenciasPageModule {}
