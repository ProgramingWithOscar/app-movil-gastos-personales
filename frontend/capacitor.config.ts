import type { CapacitorConfig } from '@capacitor/cli';
import { KeyboardResize } from '@capacitor/keyboard';

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
    /**
     * El splash nativo es solo el color de fondo de la marca, sin logo ni
     * spinner: lo que se ve es el splash de la app, que sí está animado.
     *
     * `launchAutoHide: false` lo mantiene hasta que AppComponent lo oculta, ya
     * con la WebView pintada. Así no hay ni un fotograma en blanco entre los
     * dos, y como comparten fondo (#F3F9F6, también en `values/colors.xml`), el
     * relevo no se nota.
     */
    SplashScreen: {
      launchAutoHide: false,
      backgroundColor: '#F3F9F6',
      showSpinner: false,
      androidScaleType: 'CENTER_CROP',
    },

    /**
     * `native` hace que Android encoja la WebView al abrirse el teclado, en vez
     * de dejarlo encima tapando lo que hay debajo. Es el valor por defecto del
     * plugin, pero escrito: de él depende que las hojas suban, y un día que
     * alguien lo cambie sin querer el fallo sería difícil de atribuir.
     *
     * Subir el campo enfocado dentro de la hoja es cosa aparte, de la
     * directiva appCampoVisible: estas hojas no usan ion-content, así que el
     * asistente de teclado de Ionic no las alcanza.
     */
    Keyboard: {
      resize: KeyboardResize.Native,
      resizeOnFullScreen: true,
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
