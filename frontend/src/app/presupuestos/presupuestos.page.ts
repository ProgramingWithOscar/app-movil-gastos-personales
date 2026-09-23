import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { AlertController, ToastController } from '@ionic/angular';

import { mensajeDeError } from '../core/auth';
import { PresupuestoEvaluado } from '../core/dashboard.model';
import { PresupuestosService } from '../core/presupuestos.service';

@Component({
  selector: 'app-presupuestos',
  templateUrl: './presupuestos.page.html',
  styleUrl: './presupuestos.page.scss',
  standalone: false,
})
export class PresupuestosPage {
  private readonly servicio = inject(PresupuestosService);
  private readonly fb = inject(FormBuilder);
  private readonly toastCtrl = inject(ToastController);
  private readonly alertCtrl = inject(AlertController);

  readonly categorias = [
    { id: '', nombre: 'Todas las categorías (general)' },
    { id: 'alimentacion', nombre: 'Alimentación' },
    { id: 'transporte', nombre: 'Transporte' },
    { id: 'vivienda', nombre: 'Vivienda' },
    { id: 'servicios', nombre: 'Servicios' },
    { id: 'salud', nombre: 'Salud' },
    { id: 'entretenimiento', nombre: 'Ocio' },
    { id: 'compras', nombre: 'Compras' },
    { id: 'otros', nombre: 'Otros' },
  ];

  readonly presupuestos = signal<PresupuestoEvaluado[]>([]);
  readonly cargando = signal(true);
  readonly guardando = signal(false);
  readonly modalAbierto = signal(false);
  readonly editando = signal<PresupuestoEvaluado | null>(null);
  readonly montoTexto = signal('');

  private readonly formatoMiles = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

  /** Cuántos piden atención: es lo primero que se quiere saber al entrar. */
  readonly enProblemas = computed(
    () => this.presupuestos().filter((p) => p.estado === 'superado' || p.estado === 'en_riesgo').length,
  );

  readonly form = this.fb.nonNullable.group({
    categoria: [''],
    monto: [null as number | null, [Validators.required, Validators.min(1)]],
    periodo: ['mes' as 'semana' | 'mes', [Validators.required]],
    umbral_alerta: [80, [Validators.required, Validators.min(1), Validators.max(100)]],
  });

  ionViewWillEnter(): void {
    this.cargar();
  }

  cargar(evento?: { target: { complete: () => void } }): void {
    this.cargando.set(true);

    this.servicio.listar().subscribe({
      next: ({ data }) => {
        this.presupuestos.set(data);
        this.cargando.set(false);
        evento?.target.complete();
      },
      error: (error) => {
        this.cargando.set(false);
        evento?.target.complete();
        void this.avisar(mensajeDeError(error, 'No se pudieron cargar los presupuestos.'), 'danger');
      },
    });
  }

  abrir(presupuesto?: PresupuestoEvaluado): void {
    this.editando.set(presupuesto ?? null);
    this.montoTexto.set(presupuesto ? this.formatoMiles.format(presupuesto.limite) : '');

    this.form.reset({
      categoria: presupuesto?.categoria ?? '',
      monto: presupuesto?.limite ?? null,
      periodo: presupuesto?.periodo ?? 'mes',
      umbral_alerta: presupuesto?.umbral_alerta ?? 80,
    });

    this.modalAbierto.set(true);
  }

  cerrar(): void {
    this.modalAbierto.set(false);
  }

  alEscribirMonto(evento: Event): void {
    const entrada = evento.target as HTMLInputElement;
    const digitos = entrada.value.replace(/\D/g, '');

    if (digitos === '') {
      this.montoTexto.set('');
      this.form.controls.monto.setValue(null);
      entrada.value = '';
      return;
    }

    const numero = Number(digitos);
    const formateado = this.formatoMiles.format(numero);

    this.montoTexto.set(formateado);
    this.form.controls.monto.setValue(numero);
    entrada.value = formateado;
  }

  guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const { categoria, monto, periodo, umbral_alerta } = this.form.getRawValue();
    const datos = {
      // El backend distingue "sin categoría" (general) de una cadena vacía.
      categoria: categoria === '' ? null : categoria,
      monto: Number(monto),
      periodo,
      umbral_alerta,
    };

    const actual = this.editando();
    this.guardando.set(true);

    const peticion = actual
      ? this.servicio.actualizar(actual.id, datos)
      : this.servicio.crear(datos);

    peticion.subscribe({
      next: () => {
        this.guardando.set(false);
        this.cerrar();
        this.cargar();
        void this.avisar(actual ? 'Presupuesto actualizado.' : 'Presupuesto creado.', 'success');
      },
      error: (error) => {
        this.guardando.set(false);
        void this.avisar(
          mensajeDeError(error, 'No se pudo guardar. ¿Ya tienes uno para esa categoría?'),
          'danger',
        );
      },
    });
  }

  async confirmarEliminar(presupuesto: PresupuestoEvaluado): Promise<void> {
    const alerta = await this.alertCtrl.create({
      header: 'Eliminar presupuesto',
      message: '¿Seguro? Dejarás de recibir avisos sobre este límite.',
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        { text: 'Eliminar', role: 'destructive', handler: () => this.eliminar(presupuesto) },
      ],
    });

    await alerta.present();
  }

  private eliminar(presupuesto: PresupuestoEvaluado): void {
    this.servicio.eliminar(presupuesto.id).subscribe({
      next: () => {
        this.presupuestos.update((actuales) => actuales.filter((p) => p.id !== presupuesto.id));
        void this.avisar('Presupuesto eliminado.', 'medium');
      },
      error: () => void this.avisar('No se pudo eliminar.', 'danger'),
    });
  }

  private async avisar(mensaje: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({ message: mensaje, duration: 2400, color });
    await toast.present();
  }
}
