import { Capacitor } from '@capacitor/core';

import { environment } from '../../environments/environment';

/** URL base de la API según dónde corra la app. */
export const API_URL = Capacitor.isNativePlatform()
  ? environment.nativeApiUrl
  : environment.apiUrl;
