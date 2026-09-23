/**
 * Genera src/environments/environment.ts a partir del archivo .env.
 * Se ejecuta automáticamente antes de cada `npm start` y `npm run build`.
 */
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const raiz = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const rutaEnv = resolve(raiz, '.env');
const destino = resolve(raiz, 'src/environments/environment.ts');

if (!existsSync(rutaEnv)) {
  console.error('\n✖ Falta el archivo .env. Copia .env.example a .env y ajusta las URLs.\n');
  process.exit(1);
}

const vars = Object.fromEntries(
  readFileSync(rutaEnv, 'utf8')
    .split('\n')
    .map((linea) => linea.trim())
    .filter((linea) => linea && !linea.startsWith('#'))
    .map((linea) => {
      const separador = linea.indexOf('=');
      return [linea.slice(0, separador).trim(), linea.slice(separador + 1).trim()];
    }),
);

// Las variables de entorno ganan al .env: sirve para compilar una vez con otra
// URL (por ejemplo la IP de la máquina en la red local) sin tocar el archivo.
for (const clave of ['API_URL', 'API_URL_NATIVE']) {
  if (process.env[clave]) {
    vars[clave] = process.env[clave];
  }
}

const requeridas = ['API_URL', 'API_URL_NATIVE'];
const faltantes = requeridas.filter((clave) => !vars[clave]);

if (faltantes.length > 0) {
  console.error(`\n✖ Faltan variables en .env: ${faltantes.join(', ')}\n`);
  process.exit(1);
}

const produccion = process.env.NODE_ENV === 'production' || process.argv.includes('--prod');

// La versión que enseña el splash sale del `versionName` de Android, que es la
// que el usuario ve en los ajustes del teléfono. Tenerla en dos sitios acaba
// siempre igual: uno de los dos se queda viejo.
const rutaGradle = resolve(raiz, 'android/app/build.gradle');
const version = existsSync(rutaGradle)
  ? (readFileSync(rutaGradle, 'utf8').match(/versionName\s+"([^"]+)"/)?.[1] ?? '0.0')
  : '0.0';

writeFileSync(
  destino,
  `// ARCHIVO GENERADO — no lo edites a mano.
// Se regenera desde .env al ejecutar npm start / npm run build.
export const environment = {
  production: ${produccion},
  apiUrl: '${vars['API_URL']}',
  nativeApiUrl: '${vars['API_URL_NATIVE']}',
  version: '${version}',
};
`,
);

console.log(`✔ environment.ts generado → ${vars['API_URL']} (nativo: ${vars['API_URL_NATIVE']})`);
