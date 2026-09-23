import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { RegistroPage } from './registro.page';
import { RegistroPageRoutingModule } from './registro-routing.module';

@NgModule({
  imports: [CommonModule, ReactiveFormsModule, IonicModule, RegistroPageRoutingModule],
  declarations: [RegistroPage],
})
export class RegistroPageModule {}
