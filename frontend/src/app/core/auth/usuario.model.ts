export type RolUsuario = 'user' | 'admin';
export type EstadoUsuario = 'active' | 'suspended';
export type TemaUsuario = 'system' | 'light' | 'dark';

export interface Usuario {
  id: number;
  name: string;
  email: string;
  email_verified: boolean;
  role: RolUsuario;
  status: EstadoUsuario;
  avatar_url: string | null;
  default_currency: string;
  country: string | null;
  timezone: string;
  locale: string;
  theme: TemaUsuario;
  notification_preferences: Record<string, boolean>;
  last_login_at: string | null;
  created_at: string | null;
}

export interface Credenciales {
  email: string;
  password: string;
  device_name?: string;
}

export interface DatosRegistro extends Credenciales {
  name: string;
  password_confirmation: string;
}

export interface RespuestaAuth {
  token: string;
  user: Usuario;
}

export interface Sesion {
  id: number;
  dispositivo: string;
  actual: boolean;
  ultimo_uso: string | null;
  creada: string | null;
}
