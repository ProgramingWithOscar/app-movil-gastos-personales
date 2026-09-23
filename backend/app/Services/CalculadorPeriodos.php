<?php

namespace App\Services;

use App\Models\User;
use App\Support\Periodo;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Traduce "mes" o "semana" a un rango concreto de fechas.
 *
 * Vive aparte del dashboard porque presupuestos y reportes van a necesitar
 * exactamente el mismo cálculo, y dos implementaciones del mismo período serían
 * dos formas de que los números no cuadren entre pantallas.
 */
class CalculadorPeriodos
{
    public const TIPOS = ['dia', 'semana', 'mes', 'anio', 'personalizado'];

    /** Un rango personalizado más largo que esto no se permite. */
    public const MAXIMO_DIAS = 366;

    public function paraUsuario(
        User $usuario,
        string $tipo = 'mes',
        ?string $desde = null,
        ?string $hasta = null,
    ): Periodo {
        return $this->calcular($usuario->timezone ?: 'UTC', $tipo, $desde, $hasta);
    }

    public function calcular(
        string $zona,
        string $tipo = 'mes',
        ?string $desde = null,
        ?string $hasta = null,
    ): Periodo {
        if (! in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException("Período desconocido: {$tipo}");
        }

        $hoy = CarbonImmutable::now($zona);

        if ($tipo === 'personalizado') {
            return $this->personalizado($zona, $desde, $hasta);
        }

        [$inicio, $fin] = match ($tipo) {
            'dia' => [$hoy->startOfDay(), $hoy->endOfDay()],
            'semana' => [$hoy->startOfWeek(), $hoy->endOfWeek()],
            'anio' => [$hoy->startOfYear(), $hoy->endOfYear()],
            default => [$hoy->startOfMonth(), $hoy->endOfMonth()],
        };

        return new Periodo($tipo, $inicio, $fin);
    }

    private function personalizado(string $zona, ?string $desde, ?string $hasta): Periodo
    {
        if ($desde === null || $hasta === null) {
            throw new InvalidArgumentException('Un período personalizado necesita desde y hasta.');
        }

        $inicio = CarbonImmutable::parse($desde, $zona)->startOfDay();
        $fin = CarbonImmutable::parse($hasta, $zona)->endOfDay();

        if ($inicio->gt($fin)) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la final.');
        }

        if ($inicio->startOfDay()->diffInDays($fin->startOfDay()) + 1 > self::MAXIMO_DIAS) {
            throw new InvalidArgumentException('El rango no puede superar un año.');
        }

        return new Periodo('personalizado', $inicio, $fin);
    }
}
