import { CategoriaResumen, Movimiento, Periodo } from './movimiento.model';

export type Tendencia = 'sube' | 'baja' | 'igual' | 'sin_referencia';

export interface RangoPeriodo {
  tipo: Periodo | 'personalizado';
  desde: string;
  hasta: string;
  dias: number;
  dias_transcurridos: number;
  en_curso: boolean;
}

export interface ResumenTipo {
  total: number;
  movimientos: number;
  promedio_diario: number;
  proyeccion_fin_periodo: number;
  por_categoria: CategoriaResumen[];
  categoria_principal: CategoriaResumen | null;
}

export interface Balance {
  ingresos: number;
  gastos: number;
  balance: number;
  /** `null` cuando no hubo ingresos: no hay base sobre la que calcular. */
  tasa_ahorro: number | null;
}

export interface Comparacion {
  periodo_anterior: { desde: string; hasta: string; total: number };
  diferencia: number;
  /** `null` cuando no hubo movimientos antes: no hay referencia. */
  variacion_porcentual: number | null;
  tendencia: Tendencia;
}

export type EstadoPresupuesto = 'al_dia' | 'cerca' | 'en_riesgo' | 'superado' | 'sin_limite';

export interface PresupuestoEvaluado {
  id: number;
  categoria: string | null;
  es_general: boolean;
  periodo: 'semana' | 'mes';
  limite: number;
  gastado: number;
  disponible: number;
  porcentaje: number;
  dias_restantes: number;
  proyeccion: number;
  umbral_alerta: number;
  estado: EstadoPresupuesto;
}

export interface SaldoGlobal {
  moneda: string;
  saldo_total: number;
  /** Suma de lo que se debe en tarjetas de crédito. */
  deuda: number;
  patrimonio_neto: number;
  cuentas_en_otra_moneda: number;
  cuentas: number;
}

export interface Dashboard {
  tiene_movimientos: boolean;
  saldo: SaldoGlobal;
  periodo: RangoPeriodo;
  gastos: ResumenTipo;
  ingresos: ResumenTipo;
  balance: Balance;
  comparacion: Comparacion;
  comparacion_ingresos: Comparacion;
  presupuestos: PresupuestoEvaluado[];
  movimientos_recientes: Movimiento[];
}
