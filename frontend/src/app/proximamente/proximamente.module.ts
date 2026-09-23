import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { IonicModule } from '@ionic/angular/lazy';

import { ProximamentePage } from './proximamente.page';
import { ProximamentePageRoutingModule } from './proximamente-routing.module';

@NgModule({
  imports: [CommonModule, IonicModule, ProximamentePageRoutingModule],
  declarations: [ProximamentePage],
})
export class ProximamentePageModule {}
