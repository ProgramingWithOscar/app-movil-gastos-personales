import { HttpErrorResponse } from '@angular/common/http';

/**
 * Traduce un error HTTP a un mensaje para la persona que usa la app.
 *
 * Distingue explícitamente el fallo de red del de credenciales: son problemas
 * distintos y la persona necesita saber cuál tiene delante.
 */
export function mensajeDeError(error: unknown, porDefecto = 'No se pudo completar la operación.'): string {
  if (!(error instanceof HttpErrorResponse)) {
    return porDefecto;
  }

  if (error.status === 0) {
    return 'No hay conexión con el servidor. Revisa tu internet e inténtalo de nuevo.';
  }

  if (error.status === 422) {
    const errores = error.error?.errors as Record<string, string[]> | undefined;
    const primero = errores ? Object.values(errores)[0]?.[0] : undefined;

    return primero ?? 'Revisa los datos: hay algo que no es válido.';
  }

  const conocidos: Record<number, string> = {
    401: 'Correo o contraseña incorrectos.',
    403: 'Esta cuenta está suspendida.',
    404: 'No encontramos lo que buscabas.',
    429: 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.',
    500: 'El servidor tuvo un problema. Inténtalo más tarde.',
  };

  return conocidos[error.status] ?? porDefecto;
}
