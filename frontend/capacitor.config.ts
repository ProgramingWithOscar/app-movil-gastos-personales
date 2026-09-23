import type { CapacitorConfig } from '@capacitor/cli';

/**
 * Si CAP_SERVER_URL está definida al compilar, la app no usa los archivos
 * empaquetados: carga la interfaz desde el dev-server. Eso es lo que permite
 * ver los cambios en vivo sin reinstalar el APK.
 *
 * Se activa con `npm run apk:dev`.
 */
const devServerUrl = process.env['CAP_SERVER_URL'];

/**
 * Permite que la WebView (servida en https://localhost) llame a un backend por
 * HTTP plano. Android lo bloquea por defecto: es "contenido mixto".
 *
 * Solo para desarrollo contra un backend local. En producción el backend va por
 * HTTPS y esto no hace falta.
 */
const permitirHttp = process.env['CAP_ALLOW_HTTP'] === 'true';

const config: CapacitorConfig = {
  appId: 'co.gastospersonales.app',
  appName: 'Gastos Personales',
  webDir: 'www',
  plugins: {
    SplashScreen: {
      launchAutoHide: false,
      backgroundColor: '#059669',
      androidSpinnerStyle: 'small',
      spinnerColor: '#ffffff',
      showSpinner: true,
      androidScaleType: 'CENTER_CROP',
    },
  },
  android: {
    allowMixedContent: permitirHttp,
  },
  server: devServerUrl
    ? { url: devServerUrl, cleartext: true, androidScheme: 'http' }
    : { androidScheme: 'https' },
};

export default config;
