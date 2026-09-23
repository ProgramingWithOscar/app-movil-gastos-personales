import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { RecuperarPage } from './recuperar.page';
import { RecuperarPageRoutingModule } from './recuperar-routing.module';

@NgModule({
  imports: [CommonModule, ReactiveFormsModule, IonicModule, RecuperarPageRoutingModule],
  declarations: [RecuperarPage],
})
export class RecuperarPageModule {}
