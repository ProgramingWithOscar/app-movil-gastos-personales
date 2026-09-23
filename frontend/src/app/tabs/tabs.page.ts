import { Component, inject } from '@angular/core';
import { Router } from '@angular/router';

import { AccionesService } from '../core/acciones.service';

@Component({
  selector: 'app-tabs',
  templateUrl: './tabs.page.html',
  styleUrl: './tabs.page.scss',
  standalone: false,
})
export class TabsPage {
  private readonly acciones = inject(AccionesService);
  private readonly router = inject(Router);

  /**
   * El "+" no es una pestaña: lleva a inicio y pide abrir la hoja de acciones.
   */
  async registrar(): Promise<void> {
    await this.router.navigateByUrl('/tabs/inicio');
    this.acciones.abrir();
  }
}
