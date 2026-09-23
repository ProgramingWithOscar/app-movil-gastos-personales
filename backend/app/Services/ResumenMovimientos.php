<?php

namespace App\Services;

use App\Enums\TipoMovimiento;
use App\Models\Movimiento;
use App\Models\User;
use App\Support\Periodo;

/**
 * Agrega los movimientos de un período.
 *
 * Todo se calcula aquí, en un único sitio, para que el total del encabezado y la
 * suma de las categorías no puedan discrepar: si esos dos números no cuadran,
 * la pantalla entera pierde credibilidad.
 */
class ResumenMovimientos
{
    /** Decimales de los porcentajes, iguales para el gráfico y para la lista. */
    private const DECIMALES = 1;

    /**
     * @return array<string, mixed>
     */
    public function calcular(User $usuario, Periodo $periodo, TipoMovimiento $tipo = TipoMovimiento::Gasto): array
    {
        $filas = $this->agregarPorCategoria($usuario, $periodo, $tipo);
        $total = round((float) $filas->sum('total'), 2);

        $categorias = $filas->map(fn ($fila) => [
            'categoria' => $fila->categoria,
            'total' => round((float) $fila->total, 2),
            'cantidad' => (int) $fila->cantidad,
            'porcentaje' => $this->porcentaje((float) $fila->total, $total),
        ])->values();

        $diasTranscurridos = $periodo->diasTranscurridos();
        $promedio = $diasTranscurridos > 0 ? round($total / $diasTranscurridos, 2) : 0.0;

        return [
            'total' => $total,
            'movimientos' => (int) $filas->sum('cantidad'),
            'promedio_diario' => $promedio,
            'proyeccion_fin_periodo' => $this->proyeccion($periodo, $total, $promedio),
            'por_categoria' => $categorias->all(),
            'categoria_principal' => $categorias->first(),
        ];
    }

    /**
     * Balance del período y tasa de ahorro.
     *
     * @return array<string, mixed>
     */
    public function balance(float $ingresos, float $gastos): array
    {
        return [
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'balance' => round($ingresos - $gastos, 2),
            'tasa_ahorro' => $this->tasaAhorro($ingresos, $gastos),
        ];
    }

    /**
     * Porcentaje de los ingresos que no se gastó.
     *
     * Sin ingresos no hay tasa que calcular: dividir por cero daría infinito y
     * mostrar 0 % haría creer que se ahorró nada cuando en realidad no hay con
     * qué comparar. Se devuelve `null` y la app dice que falta el dato.
     */
    private function tasaAhorro(float $ingresos, float $gastos): ?float
    {
        if ($ingresos <= 0.0) {
            return null;
        }

        return round(($ingresos - $gastos) * 100 / $ingresos, self::DECIMALES);
    }

    /**
     * Compara un período con el inmediatamente anterior.
     *
     * @return array<string, mixed>
     */
    public function comparar(
        User $usuario,
        Periodo $periodo,
        float $totalActual,
        TipoMovimiento $tipo = TipoMovimiento::Gasto,
    ): array {
        $anterior = $periodo->anterior();
        $totalAnterior = round((float) $this->consulta($usuario, $anterior, $tipo)->sum('monto'), 2);

        return [
            'periodo_anterior' => [
                'desde' => $anterior->desde->toDateString(),
                'hasta' => $anterior->hasta->toDateString(),
                'total' => $totalAnterior,
            ],
            'diferencia' => round($totalActual - $totalAnterior, 2),
            'variacion_porcentual' => $this->variacion($totalActual, $totalAnterior),
            'tendencia' => $this->tendencia($totalActual, $totalAnterior),
        ];
    }

    /**
     * Sin movimiento previo no hay porcentaje que calcular: devolver 100 % o
     * infinito sería inventarse una referencia que no existe.
     */
    private function variacion(float $actual, float $anterior): ?float
    {
        if ($anterior <= 0.0) {
            return $actual > 0.0 ? null : 0.0;
        }

        return round(($actual - $anterior) * 100 / $anterior, self::DECIMALES);
    }

    private function tendencia(float $actual, float $anterior): string
    {
        if ($anterior <= 0.0) {
            return $actual > 0.0 ? 'sin_referencia' : 'igual';
        }

        return match (true) {
            $actual > $anterior => 'sube',
            $actual < $anterior => 'baja',
            default => 'igual',
        };
    }

    /**
     * Extrapolar solo tiene sentido mientras el período corre. Uno ya cerrado
     * no va a recibir más movimientos: su proyección es su total.
     */
    private function proyeccion(Periodo $periodo, float $total, float $promedio): float
    {
        if (! $periodo->enCurso()) {
            return $total;
        }

        return round($promedio * $periodo->dias(), 2);
    }

    private function porcentaje(float $parte, float $total): float
    {
        if ($total <= 0.0) {
            return 0.0;
        }

        return round($parte * 100 / $total, self::DECIMALES);
    }

    private function agregarPorCategoria(User $usuario, Periodo $periodo, TipoMovimiento $tipo)
    {
        return $this->consulta($usuario, $periodo, $tipo)
            ->selectRaw('categoria, SUM(monto) as total, COUNT(*) as cantidad')
            ->groupBy('categoria')
            ->orderByDesc('total')
            // Desempate explícito. Con solo `total` dos categorías igualadas
            // salían en el orden que quisiera la base, así que la "categoría
            // dominante" del dashboard podía cambiar sola entre recargas sin
            // que hubiera pasado nada. A igual gasto manda la que tiene más
            // movimientos, y si también empatan, el alfabético: cualquiera de
            // las dos vale, lo que no vale es que sea distinta cada vez.
            ->orderByDesc('cantidad')
            ->orderBy('categoria')
            ->get();
    }

    private function consulta(User $usuario, Periodo $periodo, TipoMovimiento $tipo)
    {
        [$desde, $hasta] = $periodo->comoFechas();

        // El scope global de propietario no aplica fuera de una petición
        // autenticada, así que el filtro por usuario va explícito.
        return Movimiento::withoutGlobalScopes()
            ->where('user_id', $usuario->id)
            ->where('tipo', $tipo)
            ->whereBetween('fecha', [$desde, $hasta]);
    }
}
