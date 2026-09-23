import { Component, OnInit, computed, effect, inject, signal } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AlertController, ToastController } from '@ionic/angular';
import { AccionesService } from '../core/acciones.service';
import { AuthService, mensajeDeError } from '../core/auth';
import { Categoria, CategoriasService } from '../core/categorias.service';
import { Cuenta, TipoCuenta, TipoCuentaCatalogo } from '../core/cuenta.model';
import { CuentasService } from '../core/cuentas.service';
import { Dashboard, PresupuestoEvaluado } from '../core/dashboard.model';
import { DashboardService } from '../core/dashboard.service';
import { FUNCIONES } from '../core/funcionalidades';
import { CategoriaResumen, Movimiento, Periodo, TipoMovimiento } from '../core/movimiento.model';
import { MovimientosService } from '../core/movimientos.service';
import { TonoIndicador } from './indicador/indicador.component';

interface AccionRapida {
  id: 'gasto' | 'ingreso' | 'transferencia';
  titulo: string;
  descripcion: string;
  icono: string;
  color: string;
  disponible: boolean;
}

/** Un trozo de la dona, ya calculado para pintarlo en SVG. */
interface Segmento extends CategoriaResumen {
  nombre: string;
  icono: string;
  color: string;
  longitud: number;
  desplazamiento: number;
}

@Component({
  selector: 'app-home',
  templateUrl: 'home.page.html',
  styleUrls: ['home.page.scss'],
  standalone: false,
})
export class HomePage implements OnInit {
  private readonly movimientosService = inject(MovimientosService);
  private readonly dashboardService = inject(DashboardService);
  private readonly cuentasService = inject(CuentasService);
  private readonly fb = inject(FormBuilder);
  private readonly toastCtrl = inject(ToastController);
  private readonly alertCtrl = inject(AlertController);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly accionesService = inject(AccionesService);
  private readonly categoriasService = inject(CategoriasService);

  /** Radio y perímetro del círculo del SVG. */
  readonly radio = 68;
  readonly perimetro = 2 * Math.PI * this.radio;

  readonly usuario = this.auth.usuario;
  readonly correoHabilitado = FUNCIONES.correo;

  readonly periodos: { id: Periodo; nombre: string }[] = [
    { id: 'dia', nombre: 'Día' },
    { id: 'semana', nombre: 'Semana' },
    { id: 'mes', nombre: 'Mes' },
    { id: 'anio', nombre: 'Año' },
  ];

  /** El catálogo lo sirve el backend: es quien lo traduce y quien lo valida. */
  readonly catalogo = this.categoriasService.catalogo;

  readonly accionesRapidas: AccionRapida[] = [
    { id: 'gasto', titulo: 'Gasto', descripcion: 'Dinero que sale', icono: 'arrow-down-outline', color: '#dc2626', disponible: true },
    { id: 'ingreso', titulo: 'Ingreso', descripcion: 'Dinero que entra', icono: 'arrow-up-outline', color: '#059669', disponible: true },
    { id: 'transferencia', titulo: 'Transferencia', descripcion: 'Entre tus cuentas', icono: 'swap-horizontal-outline', color: '#0284c7', disponible: false },
  ];

  readonly periodo = signal<Periodo>('mes');
  readonly vista = signal<TipoMovimiento>('gasto');

  /** Catálogo de categorías del tipo que se está viendo o creando. */
  readonly categorias = computed(() =>
    this.catalogo().filter((categoria) => categoria.tipos.includes(this.vista())),
  );
  readonly dashboard = signal<Dashboard | null>(null);

  readonly resumen = computed(() => {
    const datos = this.dashboard();

    if (!datos) {
      return null;
    }

    return this.vista() === 'ingreso' ? datos.ingresos : datos.gastos;
  });

  readonly balance = computed(() => this.dashboard()?.balance ?? null);

  readonly saldo = computed(() => this.dashboard()?.saldo ?? null);

  /** La fila de deuda solo aparece si hay tarjetas: una que siempre dice cero
   *  es ruido que compite con el número que importa. */
  readonly hayDeuda = computed(() => (this.saldo()?.deuda ?? 0) > 0);

  /** Solo los que piden atención: el resto vive en su pestaña. */
  readonly presupuestosDestacados = computed<PresupuestoEvaluado[]>(() => {
    const todos = this.dashboard()?.presupuestos ?? [];
    const urgentes = todos.filter((p) => p.estado === 'superado' || p.estado === 'en_riesgo');

    return (urgentes.length > 0 ? urgentes : todos).slice(0, 3);
  });

  readonly esIngreso = computed(() => this.vista() === 'ingreso');
  readonly recientes = computed(() => this.dashboard()?.movimientos_recientes ?? []);
  readonly cargando = signal(true);
  readonly errorCarga = signal<string | null>(null);

  readonly modalAbierto = signal(false);
  readonly accionesAbiertas = signal(false);
  readonly accionesListas = signal(false);
  readonly formularioListo = signal(false);
  readonly guardando = signal(false);
  readonly reenviando = signal(false);
  readonly montoTexto = signal('');

  /** Cuentas activas, para elegir de dónde sale o entra el dinero. */
  readonly cuentas = signal<Cuenta[]>([]);

  /** Catálogo de tipos con su icono y color, tal como lo envía el backend. */
  readonly tiposCuenta = signal<TipoCuentaCatalogo[]>([]);

  readonly nuevaCuentaAbierta = signal(false);
  readonly creandoCuenta = signal(false);
  readonly saldoTexto = signal('');

  private readonly formatoMiles = new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 });
  private abiertaEn = 0;

  /** "Buenos días" según la hora, con el nombre de pila. */
  readonly saludo = computed(() => {
    const hora = new Date().getHours();
    const momento = hora < 12 ? 'Buenos días' : hora < 19 ? 'Buenas tardes' : 'Buenas noches';
    const nombre = this.usuario()?.name?.split(' ')[0] ?? '';

    return nombre ? `${momento}, ${nombre}` : momento;
  });

  /** Nunca ha registrado nada, frente a "no gastó en este período". */
  readonly sinMovimientosNunca = computed(
    () => this.dashboard()?.tiene_movimientos === false,
  );

  readonly rotulo = computed(() => {
    const actual = this.periodos.find((p) => p.id === this.periodo());
    return actual ? actual.nombre.toLowerCase() : '';
  });

  /** Convierte los porcentajes en arcos del SVG, uno detrás de otro. */
  readonly segmentos = computed<Segmento[]>(() => {
    const categorias = this.resumen()?.por_categoria ?? [];
    let acumulado = 0;

    return categorias.map((fila) => {
      const catalogo = this.buscarCategoria(fila.categoria);
      const longitud = (fila.porcentaje / 100) * this.perimetro;
      const segmento: Segmento = {
        ...fila,
        nombre: catalogo.nombre,
        icono: catalogo.icono,
        color: catalogo.color,
        longitud,
        desplazamiento: -acumulado,
      };

      acumulado += longitud;
      return segmento;
    });
  });

  /** Formatos reutilizados: crearlos en cada render es caro y se nota al scrollear. */
  private readonly formatoMoneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0,
  });

  /**
   * Variación frente al período anterior.
   *
   * Cuando no hubo gasto antes, el backend manda `null`: no existe una
   * referencia con la que comparar y no vamos a inventarnos un 100 %.
   */
  readonly indicadorVariacion = computed(() => {
    const datos = this.dashboard();
    const comparacion = this.esIngreso() ? datos?.comparacion_ingresos : datos?.comparacion;

    if (!comparacion || comparacion.tendencia === 'sin_referencia') {
      return {
        valor: 'Sin referencia',
        detalle: this.esIngreso()
          ? 'No hubo ingresos en el período anterior'
          : 'No hubo gastos en el período anterior',
        icono: 'remove-outline',
        tono: 'neutro' as TonoIndicador,
      };
    }

    const variacion = comparacion.variacion_porcentual ?? 0;
    const signo = variacion > 0 ? '+' : '';

    return {
      valor: `${signo}${variacion}%`,
      detalle: `Antes ${this.formatoMoneda.format(comparacion.periodo_anterior.total)}`,
      icono: this.iconoTendencia(comparacion.tendencia),
      // El mismo movimiento significa lo contrario según el tipo: gastar menos
      // es una buena noticia, ingresar menos no lo es.
      tono: this.tonoSegunTendencia(comparacion.tendencia),
    };
  });

  readonly indicadorPromedio = computed(() => {
    const gastos = this.resumen();
    const periodo = this.dashboard()?.periodo;

    return {
      valor: this.formatoMoneda.format(gastos?.promedio_diario ?? 0),
      detalle: periodo ? `En ${periodo.dias_transcurridos} día(s)` : '',
      icono: 'calendar-outline',
      tono: 'neutro' as TonoIndicador,
    };
  });

  readonly etiquetaMayor = computed(() => (this.esIngreso() ? 'Mayor ingreso' : 'Mayor gasto'));

  readonly indicadorCategoria = computed(() => {
    const principal = this.resumen()?.categoria_principal;

    if (!principal) {
      return {
        valor: 'Sin datos',
        detalle: '',
        icono: 'pricetag-outline',
        tono: 'neutro' as TonoIndicador,
      };
    }

    return {
      valor: this.buscarCategoria(principal.categoria).nombre,
      detalle: `${principal.porcentaje}% del total`,
      icono: this.buscarCategoria(principal.categoria).icono,
      tono: 'neutro' as TonoIndicador,
    };
  });

  readonly form = this.fb.nonNullable.group({
    cuenta_id: [null as number | null, [Validators.required]],
    descripcion: ['', [Validators.required, Validators.maxLength(255)]],
    monto: [null as number | null, [Validators.required, Validators.min(0.01)]],
    categoria: ['alimentacion', [Validators.required]],
    fecha: [new Date().toISOString(), [Validators.required]],
  });

  /**
   * Contador ya atendido. El servicio es un singleton y su contador sobrevive
   * a la pantalla, así que hay que comparar contra lo último visto: si no, al
   * volver a entrar en Inicio la hoja se abriría sola.
   */
  private ultimaSolicitud = this.accionesService.solicitudes();

  constructor() {
    effect(() => {
      const solicitudes = this.accionesService.solicitudes();

      if (solicitudes > this.ultimaSolicitud) {
        this.ultimaSolicitud = solicitudes;
        this.abrirAcciones();
      }
    });
  }

  async ngOnInit(): Promise<void> {
    // Antes que el resto: sin catálogo, la lista de movimientos no sabe pintar
    // el nombre ni el color de ninguna categoría.
    await this.categoriasService.cargar();

    this.periodo.set(await this.dashboardService.periodoGuardado());
    this.cargar();
    this.cargarCuentas();
  }

  private cargarCuentas(): void {
    this.cuentasService.listar().subscribe({
      next: ({ data, tipos }) => {
        this.cuentas.set(data);
        this.tiposCuenta.set(tipos);
      },
      // Sin cuentas no se puede registrar nada, pero el dashboard sigue
      // leyéndose: no tiene sentido tumbar la pantalla entera por esto.
      error: () => this.cuentas.set([]),
    });
  }

  /**
   * Al volver a la pestaña los datos pueden estar viejos: alguien pudo
   * registrar un gasto desde otro dispositivo, o desde otra pantalla de aquí.
   */
  ionViewWillEnter(): void {
    if (this.dashboard() !== null) {
      this.cargar();
    }

    // También las cuentas: si acabas de crear una en su pantalla, tiene que
    // estar disponible al registrar sin recargar la app.
    this.cargarCuentas();
  }

  cargar(evento?: { target: { complete: () => void } }): void {
    this.cargando.set(true);

    this.dashboardService.cargar(this.periodo()).subscribe({
      next: ({ data }) => {
        this.dashboard.set(data);
        this.errorCarga.set(null);
        this.cargando.set(false);
        evento?.target.complete();
      },
      error: (error) => {
        this.cargando.set(false);
        evento?.target.complete();

        // Los datos anteriores se quedan en pantalla: es mejor información
        // ligeramente vieja que una pantalla en blanco.
        this.errorCarga.set(mensajeDeError(error, 'No se pudieron cargar tus datos.'));
      },
    });
  }

  cambiarPeriodo(periodo: Periodo): void {
    if (periodo === this.periodo()) {
      return;
    }

    this.periodo.set(periodo);
    void this.dashboardService.guardarPeriodo(periodo);
    this.cargar();
  }

  cambiarVista(vista: TipoMovimiento): void {
    this.vista.set(vista);
  }

  /** En gastos, bajar es bueno; en ingresos, subir. */
  private tonoSegunTendencia(tendencia: string): TonoIndicador {
    if (tendencia !== 'sube' && tendencia !== 'baja') {
      return 'neutro';
    }

    const buena = this.esIngreso() ? 'sube' : 'baja';

    return tendencia === buena ? 'positivo' : 'negativo';
  }

  private iconoTendencia(tendencia: string): string {
    return match(tendencia);

    function match(valor: string): string {
      switch (valor) {
        case 'sube':
          return 'trending-up-outline';
        case 'baja':
          return 'trending-down-outline';
        default:
          return 'remove-outline';
      }
    }
  }

  buscarCategoria(id: string): Categoria {
    return this.categoriasService.buscar(id);
  }

  iconoCategoria(id: string): string {
    return this.buscarCategoria(id).icono;
  }

  colorCategoria(id: string): string {
    return this.buscarCategoria(id).color;
  }

  abrirAcciones(): void {
    this.accionesListas.set(false);
    this.abiertaEn = Date.now();
    setTimeout(() => this.accionesAbiertas.set(true), 80);
  }

  cerrarAcciones(): void {
    this.accionesAbiertas.set(false);
    this.accionesListas.set(false);
  }

  async elegirAccion(accion: AccionRapida): Promise<void> {
    if (Date.now() - this.abiertaEn < 500) {
      return;
    }

    if (!accion.disponible) {
      await this.avisar('Esa opción todavía no está disponible.', 'medium');
      return;
    }

    this.cerrarAcciones();
    setTimeout(() => this.abrirModal(accion.id as TipoMovimiento), 220);
  }

  /** Tipo que se está creando en la hoja del formulario. */
  readonly tipoNuevo = signal<TipoMovimiento>('gasto');

  readonly categoriasFormulario = computed(() =>
    this.catalogo().filter((categoria) => categoria.tipos.includes(this.tipoNuevo())),
  );

  abrirModal(tipo: TipoMovimiento = 'gasto'): void {
    this.tipoNuevo.set(tipo);
    this.montoTexto.set('');
    this.formularioListo.set(false);
    this.form.reset({
      // La favorita viene preseleccionada: es la que se usa casi siempre.
      cuenta_id: this.cuentaPorDefecto(),
      descripcion: '',
      monto: null,
      categoria: this.categoriasFormulario()[0].id,
      fecha: new Date().toISOString(),
    });
    this.modalAbierto.set(true);
  }

  cerrarModal(): void {
    this.modalAbierto.set(false);
    this.formularioListo.set(false);
  }

  readonly formCuenta = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.minLength(2), Validators.maxLength(80)]],
    tipo: ['efectivo' as TipoCuenta, [Validators.required]],
    saldo_inicial: [0],
  });

  /**
   * Crea una cuenta sin salir del formulario de movimiento.
   *
   * Pide lo mismo que la pantalla de cuentas —nombre, tipo y saldo— para que
   * quede bien creada de una vez. Irse a otra pantalla, crearla y volver
   * perdiendo lo ya escrito es el camino largo para algo que ocurre justo
   * cuando te falta una cuenta.
   */
  abrirNuevaCuenta(): void {
    this.saldoTexto.set('');
    this.formCuenta.reset({ nombre: '', tipo: 'efectivo', saldo_inicial: 0 });
    this.nuevaCuentaAbierta.set(true);
  }

  cerrarNuevaCuenta(): void {
    this.nuevaCuentaAbierta.set(false);
  }

  elegirTipoCuenta(tipo: TipoCuenta): void {
    this.formCuenta.controls.tipo.setValue(tipo);
  }

  /** Acepta el signo: una tarjeta de crédito arranca en negativo. */
  alEscribirSaldo(evento: Event): void {
    const entrada = evento.target as HTMLInputElement;
    const negativo = entrada.value.trim().startsWith('-');
    const digitos = entrada.value.replace(/\D/g, '');

    if (digitos === '') {
      this.saldoTexto.set(negativo ? '-' : '');
      this.formCuenta.controls.saldo_inicial.setValue(0);
      entrada.value = negativo ? '-' : '';
      return;
    }

    const numero = Number(digitos) * (negativo ? -1 : 1);
    const formateado = this.formatoMiles.format(numero);

    this.saldoTexto.set(formateado);
    this.formCuenta.controls.saldo_inicial.setValue(numero);
    entrada.value = formateado;
  }

  guardarCuenta(): void {
    if (this.formCuenta.invalid) {
      this.formCuenta.markAllAsTouched();
      return;
    }

    this.creandoCuenta.set(true);

    this.cuentasService.crear(this.formCuenta.getRawValue()).subscribe({
      next: ({ data }) => {
        this.cuentas.update((actuales) => [...actuales, data]);
        // Queda elegida: es la que acabas de crear para este movimiento.
        this.form.controls.cuenta_id.setValue(data.id);
        this.creandoCuenta.set(false);
        this.cerrarNuevaCuenta();
        void this.avisar(`Cuenta "${data.nombre}" creada.`, 'success');
      },
      error: (error) => {
        this.creandoCuenta.set(false);
        void this.avisar(mensajeDeError(error, 'No se pudo crear la cuenta.'), 'danger');
      },
    });
  }

  private cuentaPorDefecto(): number | null {
    const cuentas = this.cuentas();

    return cuentas.find((c) => c.favorita)?.id ?? cuentas[0]?.id ?? null;
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

  elegirCategoria(id: string): void {
    this.form.controls.categoria.setValue(id);
  }

  guardar(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const { cuenta_id, descripcion, monto, categoria, fecha } = this.form.getRawValue();
    this.guardando.set(true);

    this.movimientosService
      .crear({
        cuenta_id: Number(cuenta_id),
        tipo: this.tipoNuevo(),
        descripcion,
        monto: Number(monto),
        categoria,
        fecha: fecha.slice(0, 10),
      })
      .subscribe({
        next: () => {
          this.guardando.set(false);
          this.cerrarModal();
          this.cargar();
          // El saldo de la cuenta cambió: hay que releerlo.
          this.cargarCuentas();
          void this.avisar(this.tipoNuevo() === 'ingreso' ? 'Ingreso registrado.' : 'Gasto registrado.', 'success');
        },
        error: () => {
          this.guardando.set(false);
          void this.avisar('No se pudo guardar el movimiento.', 'danger');
        },
      });
  }

  async confirmarEliminar(movimiento: Movimiento): Promise<void> {
    const alerta = await this.alertCtrl.create({
      header: movimiento.tipo === 'ingreso' ? 'Eliminar ingreso' : 'Eliminar gasto',
      message: `¿Seguro que quieres eliminar "${movimiento.descripcion}"?`,
      buttons: [
        { text: 'Cancelar', role: 'cancel' },
        { text: 'Eliminar', role: 'destructive', handler: () => this.eliminar(movimiento) },
      ],
    });

    await alerta.present();
  }

  async reenviarVerificacion(): Promise<void> {
    this.reenviando.set(true);

    try {
      await this.auth.reenviarVerificacion();
      await this.avisar('Te reenviamos el correo de verificación.', 'success');
    } catch (error) {
      await this.avisar(mensajeDeError(error, 'No se pudo reenviar el correo.'), 'danger');
    } finally {
      this.reenviando.set(false);
    }
  }

  private eliminar(movimiento: Movimiento): void {
    this.movimientosService.eliminar(movimiento.id).subscribe({
      next: () => {
        this.cargar();
        this.cargarCuentas();
        void this.avisar('Movimiento eliminado.', 'medium');
      },
      error: () => void this.avisar('No se pudo eliminar el movimiento.', 'danger'),
    });
  }

  private async avisar(mensaje: string, color: string): Promise<void> {
    const toast = await this.toastCtrl.create({ message: mensaje, duration: 2200, color, position: 'bottom' });
    await toast.present();
  }
}
