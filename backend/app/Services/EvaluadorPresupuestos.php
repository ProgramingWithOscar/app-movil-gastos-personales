<?php

namespace App\Services;

use App\Enums\TipoMovimiento;
use App\Models\Movimiento;
use App\Models\Presupuesto;
use App\Models\User;
use App\Support\Periodo;

/**
 * Cruza cada presupuesto con lo gastado en su período.
 *
 * El período de un presupuesto es suyo, no el que el usuario esté mirando en el
 * dashboard: un tope mensual se evalúa contra el mes aunque la pantalla esté en
 * "día". Mostrarlo contra otro rango daría un porcentaje que no significa nada.
 */
class EvaluadorPresupuestos
{
    public function __construct(private readonly CalculadorPeriodos $periodos) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function evaluar(User $usuario): array
    {
        $presupuestos = Presupuesto::withoutGlobalScopes()
            ->where('user_id', $usuario->id)
            ->where('activo', true)
            ->orderByRaw('categoria IS NULL DESC')
            ->orderBy('categoria')
            ->get();

        if ($presupuestos->isEmpty()) {
            return [];
        }

        $zona = $usuario->timezone ?: 'UTC';

        return $presupuestos->map(function (Presupuesto $presupuesto) use ($usuario, $zona) {
            $periodo = $this->periodos->calcular($zona, $presupuesto->periodo);
            $gastado = $this->gastado($usuario, $periodo, $presupuesto->categoria);
            $limite = (float) $presupuesto->monto;

            return [
                'id' => $presupuesto->id,
                'categoria' => $presupuesto->categoria,
                'es_general' => $presupuesto->esGeneral(),
                'periodo' => $presupuesto->periodo,
                'limite' => $limite,
                'gastado' => $gastado,
                'disponible' => round($limite - $gastado, 2),
                'porcentaje' => $this->porcentaje($gastado, $limite),
                'dias_restantes' => $this->diasRestantes($periodo),
                'proyeccion' => $this->proyeccion($periodo, $gastado),
                'umbral_alerta' => $presupuesto->umbral_alerta,
                'estado' => $this->estado($gastado, $limite, $presupuesto->umbral_alerta, $periodo),
            ];
        })->all();
    }

    /**
     * Estado de un presupuesto.
     *
     * `en_riesgo` es el que más valor aporta: avisa antes de superarlo, cuando
     * al ritmo actual se va a acabar el dinero antes que el período. Enterarse
     * cuando ya se superó no permite corregir nada.
     */
    private function estado(float $gastado, float $limite, int $umbral, Periodo $periodo): string
    {
        if ($limite <= 0.0) {
            return 'sin_limite';
        }

        $consumido = $gastado * 100 / $limite;

        if ($consumido >= 100) {
            return 'superado';
        }

        if ($this->proyeccion($periodo, $gastado) > $limite) {
            return 'en_riesgo';
        }

        return $consumido >= $umbral ? 'cerca' : 'al_dia';
    }

    private function proyeccion(Periodo $periodo, float $gastado): float
    {
        $transcurridos = $periodo->diasTranscurridos();

        if (! $periodo->enCurso() || $transcurridos <= 0) {
            return $gastado;
        }

        return round($gastado / $transcurridos * $periodo->dias(), 2);
    }

    private function diasRestantes(Periodo $periodo): int
    {
        return max(0, $periodo->dias() - $periodo->diasTranscurridos());
    }

    private function porcentaje(float $gastado, float $limite): float
    {
        if ($limite <= 0.0) {
            return 0.0;
        }

        return round($gastado * 100 / $limite, 1);
    }

    private function gastado(User $usuario, Periodo $periodo, ?string $categoria): float
    {
        [$desde, $hasta] = $periodo->comoFechas();

        return round((float) Movimiento::withoutGlobalScopes()
            ->where('user_id', $usuario->id)
            ->where('tipo', TipoMovimiento::Gasto)
            ->when($categoria !== null, fn ($q) => $q->where('categoria', $categoria))
            ->whereBetween('fecha', [$desde, $hasta])
            ->sum('monto'), 2);
    }
}
