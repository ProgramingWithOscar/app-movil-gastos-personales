export type TipoCuenta =
  | 'efectivo'
  | 'bancaria'
  | 'ahorro'
  | 'corriente'
  | 'billetera'
  | 'tarjeta_credito'
  | 'tarjeta_debito'
  | 'inversion'
  | 'otra';

export interface Cuenta {
  id: number;
  nombre: string;
  tipo: TipoCuenta;
  tipo_nombre: string;
  /** `false` en tarjetas de crédito: guardan deuda, no dinero propio. */
  es_dinero: boolean;
  moneda: string;
  saldo_inicial: number;
  saldo_actual: number;
  color: string;
  icono: string;
  favorita: boolean;
  archivada: boolean;
  orden: number;
  movimientos?: number;
  created_at: string | null;
}

export interface NuevaCuenta {
  nombre: string;
  tipo: TipoCuenta;
  saldo_inicial: number;
  favorita?: boolean;
}

/** Un tipo del catálogo que envía el backend, con su icono y color. */
export interface TipoCuentaCatalogo {
  id: TipoCuenta;
  nombre: string;
  icono: string;
  color: string;
  es_dinero: boolean;
}

export interface ResumenCuentas {
  moneda: string;
  saldo_total: number;
  deuda: number;
  patrimonio_neto: number;
  cuentas_en_otra_moneda: number;
}

export interface RespuestaCuentas {
  data: Cuenta[];
  resumen: ResumenCuentas;
  tipos: TipoCuentaCatalogo[];
}
