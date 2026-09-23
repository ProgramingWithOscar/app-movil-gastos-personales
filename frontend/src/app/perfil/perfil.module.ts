import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { IonicModule } from '@ionic/angular/lazy';

import { PerfilPage } from './perfil.page';
import { PerfilPageRoutingModule } from './perfil-routing.module';

@NgModule({
  imports: [CommonModule, ReactiveFormsModule, IonicModule, PerfilPageRoutingModule],
  declarations: [PerfilPage],
})
export class PerfilPageModule {}
