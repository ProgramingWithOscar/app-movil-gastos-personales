import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, from, switchMap, throwError } from 'rxjs';

import { AuthService } from './auth.service';

/** Rutas donde un 401 es una respuesta legítima, no una sesión caducada. */
const RUTAS_PUBLICAS = ['/auth/login', '/auth/register'];

export const authInterceptor: HttpInterceptorFn = (peticion, siguiente) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const token = auth.token;

  const conToken = token
    ? peticion.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
    : peticion;

  return siguiente(conToken).pipe(
    catchError((error: HttpErrorResponse) => {
      const esRutaPublica = RUTAS_PUBLICAS.some((ruta) => peticion.url.includes(ruta));

      if (error.status === 401 && !esRutaPublica) {
        return from(auth.limpiarSesion()).pipe(
          switchMap(() => from(router.navigateByUrl('/bienvenida'))),
          switchMap(() => throwError(() => error)),
        );
      }

      return throwError(() => error);
    }),
  );
};
