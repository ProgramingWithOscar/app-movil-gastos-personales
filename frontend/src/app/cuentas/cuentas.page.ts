import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { AlertController, ToastController } from '@ionic/angular';

import { mensajeDeError } from '../core/auth';
import { Cuenta, ResumenCuentas, TipoCuenta, TipoCuentaCatalogo } from '../core/cuenta.model';
import { CuentasService } from '../core/cuentas.service';

@Component({
  selector: 'app-cuentas',
  templateUrl: './cuentas.page.html',
  styleUrl: './cuentas.page.scss',
  standalone: false,
})
export class CuentasPage {
  private readonly servicio = inject(CuentasService);
  private readonly fb = inject(FormBuilder);
  private readonly toastCtrl = inject(ToastController);
  private readonly alertCtrl = inject(AlertController);

  readonly cuentas = signal<Cuenta[]>([]);
  readonly tipos = signal<TipoCuentaCatalogo[]>([]);
  readonly resumen = signal<ResumenCuentas | null>(null);
  readonly cargando = signal(true);
  readonly guardando = signal(false);
  readonly modalAbierto = signal(false);
  readonly editando = signal<Cuenta | null>(null);
  readonly montoTexto = signal('');
  readonly verArchivadas = signal(false);

  private readonly formatoMiles = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });

  readonly activas = computed(() => this.cuentas().filter((c) => !c.archivada));
  readonly archivadas = computed(() => this.cuentas().filter((c) => c.archivada));
  readonly hayDeuda = computed(() => (this.resumen()?.deuda ?? 0) > 0);

  readonly form = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(80)]],
    tipo: ['efectivo' as TipoCuenta, [Validators.required]],
    saldo_inicial: [0],
    favorita: [false],
  });

  ionViewWillEnter(): void {
    this.cargar();
  }

  cargar(evento?: { target: { complete: () => void } }): void {
    this.cargando.set(true);

    // Se piden siempre con archivadas: así alternar la sección no cuesta otra
    // petición, y son pocas.
    this.servicio.listar(true).subscribe({
      next: ({ data, resumen, tipos }) => {
        this.cuentas.set(data);
        this.resumen.set(resumen);
        this.tipos.set(tipos);
        this.cargando.set(false);
        evento?.target.complete();
      },
      error: (error) => {
        this.cargando.set(false);
        evento?.target.complete();
        void this.avisar(mensajeDeError(error, 'No se pudieron cargar tus cuentas.'), 'danger');
      },
    });
  }

  abrir(cuenta?: Cuenta): void {
    this.editando.set(cuenta ?? null);
    this.montoTexto.set(cuenta ? this.formatoMiles.format(cuenta.saldo_inicial) : '');

    this.form.reset({
      nombre: cuenta?.nombre ?? '',
      tipo: cuenta?.tipo ?? 'efectivo',
      saldo_inicial: cuenta?.saldo_inicial ?? 0,
      favorita: cuenta?.favorita ?? false,
    });

    this.modalAbierto.set(true);
  }

  cerrar(): void {
    this.modalAbierto.set(false);
  }

  elegirTipo(tipo: TipoCuenta): void {
    this.form.controls.tipo.setValue(tipo);
  }

  /** Acepta el signo: una tarjeta de crédito arranca en negativo. */
  alEscribirSaldo(evento: Event): void {
    const entrada = evento.target as HTMLInputElement;
    const negativo = entrada.value.trim().startsWith('-');
    const digitos = entrada.value.replace(/\D/g, '');

    if (digitos === '') {
      this.montoTexto.set(negativo ? '-' : '');
      this.form.controls.saldo_inicial.setValue(0);
      entrada.value = negativo ? '-' : '';
      return;
    }

    const numero = Number(digitos) * (negativo ? -1 : 1);
    const formateado = this.formatoMiles.format(numero);

    this.montoTexto.set(formateado);
    this.form.controls.saldo_inicial.setValue(numero);
    entrada.value = formateado;
  }

  guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const datos = this.form.getRawValue();
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
        void this.avisar(actual ? 'Cuenta actualizada.' : 'Cuenta creada.', 'success');
      },
      error: (error) => {
        this.guardando.set(false);
        void this.avisar(mensajeDeError(error, 'No se pudo guardar la cuenta.'), 'danger');
      },
    });
  }

  marcarFavorita(cuenta: Cuenta): void {
    if (cuenta.favorita) {
      return;
    }

    this.servicio.marcarFavorita(cuenta.id).subscribe({
      next: () => {
        this.cargar();
        void this.avisar(`${cuenta.nombre} es ahora tu cuenta por defecto.`, 'success');
      },
      error: (error) => void this.avisar(mensajeDeError(error, 'No se pudo marcar.'), 'danger'),
    });
  }

  /**
   * El texto cambia según tenga historial o no: archivar y borrar no son lo
   * mismo, y la persona debe saber cuál de las dos va a ocurrir.
   */
  async confirmarArchivar(cuenta: Cuenta): Promise<void> {
    const tieneHistorial = (cuenta.movimientos ?? 0) > 0;

    const alerta = await this.alertCtrl.create({
      header: tieneHistorial ? 'Archivar cuenta' : 'Eliminar cuenta',
      message: tieneHistorial
        ? `"${cuenta.nombre}" tiene ${cuenta.movimientos} movimiento(s). Se archivará: deja de sumar en tu saldo y no podrás registrar en ella, pero conserva todo su historial.`
        : `"${cuenta.nombre}" no tiene ningún movimiento, así que se eliminará por completo. Esta acción no se puede deshacer.`,
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        {
          text: tieneHistorial ? 'Archivar' : 'Eliminar',
          role: 'destructive',
          handler: () => this.archivar(cuenta),
        },
      ],
    });

    await alerta.present();
  }

  private archivar(cuenta: Cuenta): void {
    this.servicio.archivar(cuenta.id).subscribe({
      next: ({ message }) => {
        this.cargar();
        void this.avisar(message, 'medium');
      },
      error: (error) => void this.avisar(mensajeDeError(error, 'No se pudo completar.'), 'danger'),
    });
  }

  private async avisar(mensaje: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({ message: mensaje, duration: 2600, color });
    await toast.present();
  }
}
