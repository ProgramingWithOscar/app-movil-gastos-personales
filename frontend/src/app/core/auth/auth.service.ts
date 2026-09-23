import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { Capacitor } from '@capacitor/core';
import { firstValueFrom } from 'rxjs';

import { API_URL } from '../api.config';
import { TokenStorage } from './token-storage.service';
import { Credenciales, DatosRegistro, RespuestaAuth, Sesion, Usuario } from './usuario.model';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly storage = inject(TokenStorage);

  private readonly _usuario = signal<Usuario | null>(null);
  private readonly _token = signal<string | null>(null);
  private readonly _cargando = signal(true);

  readonly usuario = this._usuario.asReadonly();
  readonly cargando = this._cargando.asReadonly();
  readonly autenticado = computed(() => this._token() !== null);
  readonly esAdmin = computed(() => this._usuario()?.role === 'admin');

  get token(): string | null {
    return this._token();
  }

  async registrar(datos: DatosRegistro): Promise<Usuario> {
    const respuesta = await firstValueFrom(
      this.http.post<RespuestaAuth>(`${API_URL}/auth/register`, {
        ...datos,
        device_name: this.nombreDispositivo(),
      }),
    );

    return this.establecerSesion(respuesta);
  }

  async iniciarSesion(credenciales: Credenciales): Promise<Usuario> {
    const respuesta = await firstValueFrom(
      this.http.post<RespuestaAuth>(`${API_URL}/auth/login`, {
        ...credenciales,
        device_name: this.nombreDispositivo(),
      }),
    );

    return this.establecerSesion(respuesta);
  }

  async actualizarPerfil(cambios: Partial<Usuario>): Promise<Usuario> {
    const { data } = await firstValueFrom(
      this.http.put<{ data: Usuario }>(`${API_URL}/user`, cambios),
    );

    this._usuario.set(data);
    return data;
  }

  async cambiarContrasena(datos: {
    current_password: string;
    password: string;
    password_confirmation: string;
  }): Promise<void> {
    await firstValueFrom(this.http.put(`${API_URL}/user/password`, datos));
  }

  async actualizarNotificaciones(preferences: Record<string, boolean>): Promise<Usuario> {
    const { data } = await firstValueFrom(
      this.http.put<{ data: Usuario }>(`${API_URL}/user/notifications`, { preferences }),
    );

    this._usuario.set(data);
    return data;
  }

  async listarSesiones(): Promise<Sesion[]> {
    const { data } = await firstValueFrom(
      this.http.get<{ data: Sesion[] }>(`${API_URL}/user/sessions`),
    );

    return data;
  }

  async revocarSesion(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_URL}/user/sessions/${id}`));
  }

  async cerrarOtrasSesiones(): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_URL}/user/sessions`));
  }

  async eliminarCuenta(password: string): Promise<void> {
    await firstValueFrom(this.http.delete(`${API_URL}/user`, { body: { password } }));
    await this.limpiarSesion();
  }

  async recuperarContrasena(email: string): Promise<void> {
    await firstValueFrom(this.http.post(`${API_URL}/auth/forgot-password`, { email }));
  }

  async reenviarVerificacion(): Promise<void> {
    await firstValueFrom(this.http.post(`${API_URL}/auth/email/resend`, {}));
  }

  async cerrarSesion(): Promise<void> {
    try {
      await firstValueFrom(this.http.post(`${API_URL}/auth/logout`, {}));
    } catch {
      // Si el token ya no es válido en el servidor, da igual: lo importante
      // es dejar limpio el dispositivo.
    }

    await this.limpiarSesion();
  }

  /**
   * Se llama al arrancar la app. Si hay token guardado, recupera el usuario;
   * si el token ya no sirve, deja la sesión cerrada sin romper el arranque.
   */
  async restaurarSesion(): Promise<void> {
    this._cargando.set(true);

    try {
      const token = await this.storage.obtener();

      if (!token) {
        return;
      }

      this._token.set(token);

      const { data } = await firstValueFrom(
        this.http.get<{ data: Usuario }>(`${API_URL}/user`),
      );

      this._usuario.set(data);
    } catch {
      await this.limpiarSesion();
    } finally {
      this._cargando.set(false);
    }
  }

  /** La llama el interceptor cuando el servidor responde 401. */
  async limpiarSesion(): Promise<void> {
    this._token.set(null);
    this._usuario.set(null);
    await this.storage.borrar();
  }

  actualizarUsuario(usuario: Usuario): void {
    this._usuario.set(usuario);
  }

  private async establecerSesion(respuesta: RespuestaAuth): Promise<Usuario> {
    await this.storage.guardar(respuesta.token);
    this._token.set(respuesta.token);
    this._usuario.set(respuesta.user);
    this._cargando.set(false);

    return respuesta.user;
  }

  private nombreDispositivo(): string {
    const plataforma = Capacitor.getPlatform();

    return plataforma === 'web'
      ? `Navegador (${navigator.platform || 'web'})`
      : `${plataforma} · ${navigator.userAgent.slice(0, 60)}`;
  }
}
