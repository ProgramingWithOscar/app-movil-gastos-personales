import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonicModule } from '@ionic/angular/lazy';

import { BienvenidaPage } from './bienvenida.page';
import { BienvenidaPageRoutingModule } from './bienvenida-routing.module';

@NgModule({
  imports: [CommonModule, IonicModule, BienvenidaPageRoutingModule],
  declarations: [BienvenidaPage],
})
export class BienvenidaPageModule {}
