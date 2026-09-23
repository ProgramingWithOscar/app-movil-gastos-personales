import { Component, inject } from '@angular/core';
import { ActivatedRoute } from '@angular/router';

/** Pantalla puente para las secciones que aún no existen. */
@Component({
  selector: 'app-proximamente',
  templateUrl: './proximamente.page.html',
  styleUrl: './proximamente.page.scss',
  standalone: false,
})
export class ProximamentePage {
  private readonly ruta = inject(ActivatedRoute);

  readonly titulo = this.ruta.snapshot.data['titulo'] ?? 'Próximamente';
  readonly icono = this.ruta.snapshot.data['icono'] ?? 'construct-outline';
  readonly descripcion = this.ruta.snapshot.data['descripcion'] ?? 'Estamos trabajando en esto.';
}
