/**
 * Interruptores de funcionalidad.
 *
 * `correo` queda apagado mientras no haya un proveedor SMTP: las pantallas
 * existen y compilan, pero no se ofrecen porque el backend aún no envía nada.
 */
export const FUNCIONES = {
  correo: false,
} as const;
