import { Injectable } from '@angular/core';
import { Preferences } from '@capacitor/preferences';

/**
 * Guarda el token de sesión.
 *
 * En nativo, Preferences usa el almacenamiento del sistema (SharedPreferences
 * en Android, UserDefaults en iOS). En navegador cae a localStorage.
 *
 * Todas las operaciones son tolerantes a fallo: si el almacenamiento no está
 * disponible, la app debe arrancar igual y simplemente pedir credenciales.
 */
@Injectable({ providedIn: 'root' })
export class TokenStorage {
  private static readonly CLAVE = 'auth_token';

  async obtener(): Promise<string | null> {
    try {
      const { value } = await Preferences.get({ key: TokenStorage.CLAVE });
      return value ?? null;
    } catch (error) {
      console.error('[TokenStorage] no se pudo leer el token', error);
      return null;
    }
  }

  async guardar(token: string): Promise<void> {
    try {
      await Preferences.set({ key: TokenStorage.CLAVE, value: token });
    } catch (error) {
      console.error('[TokenStorage] no se pudo guardar el token', error);
    }
  }

  async borrar(): Promise<void> {
    try {
      await Preferences.remove({ key: TokenStorage.CLAVE });
    } catch (error) {
      console.error('[TokenStorage] no se pudo borrar el token', error);
    }
  }
}
