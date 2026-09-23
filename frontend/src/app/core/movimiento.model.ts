export type TipoMovimiento = 'gasto' | 'ingreso';

export interface CuentaDelMovimiento {
  id: number;
  nombre: string;
  color: string;
  icono: string;
}

export interface Movimiento {
  id: number;
  cuenta?: CuentaDelMovimiento;
  tipo: TipoMovimiento;
  descripcion: string;
  monto: number;
  categoria: string;
  fecha: string;
  created_at?: string;
}

export interface NuevoMovimiento {
  /** Obligatorio desde el Módulo 3: un movimiento sale o entra a alguna parte. */
  cuenta_id: number;
  tipo: TipoMovimiento;
  descripcion: string;
  monto: number;
  categoria: string;
  fecha: string;
}

export type Periodo = 'dia' | 'semana' | 'mes' | 'anio';

export interface CategoriaResumen {
  categoria: string;
  total: number;
  cantidad: number;
  porcentaje: number;
}
