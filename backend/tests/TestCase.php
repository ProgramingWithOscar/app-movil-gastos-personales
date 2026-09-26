<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cortafuegos: la suite no corre si no está apuntando a sqlite en memoria.
     *
     * `RefreshDatabase` trunca todas las tablas de la conexión que encuentre.
     * Si esa conexión es la de desarrollo, se lleva por delante los datos de
     * trabajo, y lo hace en silencio: los tests pasan igual.
     *
     * Ya ocurrió una vez. `phpunit.xml` pide sqlite, pero docker-compose
     * inyecta DB_CONNECTION=mysql en el contenedor y, con la configuración
     * cacheada en bootstrap/cache/config.php, ni las variables de phpunit ni
     * el .env llegan a mirarse. Confiar solo en la configuración deja el fallo
     * a merced de un archivo de caché que nadie recuerda que existe.
     *
     * Se comprueba en `setUpTraits` porque es lo último que ocurre antes de que
     * los traits —RefreshDatabase entre ellos— hagan nada.
     */
    protected function setUpTraits(): array
    {
        $conexion = config('database.default');

        if ($conexion !== 'sqlite') {
            throw new RuntimeException(
                "Las pruebas iban a correr contra la conexión '{$conexion}', no sqlite. ".
                'Se abortan antes de que RefreshDatabase borre esa base. '.
                'Casi seguro hay configuración cacheada: '.
                'docker compose exec backend php artisan config:clear'
            );
        }

        return parent::setUpTraits();
    }
}
