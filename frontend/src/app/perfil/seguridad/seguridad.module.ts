import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { SeguridadPage } from './seguridad.page';
import { SeguridadPageRoutingModule } from './seguridad-routing.module';

@NgModule({
  imports: [CommonModule, ReactiveFormsModule, IonicModule, SeguridadPageRoutingModule],
  declarations: [SeguridadPage],
})
export class SeguridadPageModule {}
