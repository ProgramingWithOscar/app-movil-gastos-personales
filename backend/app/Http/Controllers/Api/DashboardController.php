<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoMovimiento;
use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardRequest;
use App\Http\Resources\DashboardResource;
use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Services\CalculadorPeriodos;
use App\Services\EvaluadorPresupuestos;
use App\Services\ResumenMovimientos;
use App\Services\SaldosCuentas;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DashboardController extends Controller
{
    public function __construct(
        private readonly CalculadorPeriodos $periodos,
        private readonly ResumenMovimientos $resumen,
        private readonly EvaluadorPresupuestos $presupuestos,
        private readonly SaldosCuentas $saldos,
    ) {}

    /**
     * Todo lo que pinta la pantalla principal, en una sola petición: en móvil,
     * dos llamadas en serie sobre una red lenta se notan.
     */
    public function __invoke(DashboardRequest $request): DashboardResource
    {
        $usuario = $request->user();

        try {
            $periodo = $this->periodos->paraUsuario(
                $usuario,
                $request->periodo(),
                $request->input('desde'),
                $request->input('hasta'),
            );
        } catch (InvalidArgumentException $error) {
            // El calculador valida reglas que el FormRequest no puede ver, como
            // la longitud máxima del rango. Se traduce a un error de validación
            // para que la app reciba siempre la misma forma de respuesta.
            throw ValidationException::withMessages(['periodo' => [$error->getMessage()]]);
        }

        $gastos = $this->resumen->calcular($usuario, $periodo, TipoMovimiento::Gasto);
        $ingresos = $this->resumen->calcular($usuario, $periodo, TipoMovimiento::Ingreso);

        return new DashboardResource([
            // Distingue "nunca has registrado nada" de "no hubo movimientos en
            // este período": la app necesita mensajes distintos para cada caso.
            'tiene_movimientos' => Movimiento::query()->exists(),
            // Responde "cuánto tengo", que es la mitad de la pregunta que
            // define el producto y que hasta ahora la app no sabía contestar.
            'saldo' => $this->saldo($usuario),
            'periodo' => $periodo->aArray(),
            'gastos' => $gastos,
            'ingresos' => $ingresos,
            'balance' => $this->resumen->balance($ingresos['total'], $gastos['total']),
            'comparacion' => $this->resumen->comparar($usuario, $periodo, $gastos['total'], TipoMovimiento::Gasto),
            'comparacion_ingresos' => $this->resumen->comparar($usuario, $periodo, $ingresos['total'], TipoMovimiento::Ingreso),
            // Los presupuestos se evalúan contra su propio período, no contra
            // el que muestra la pantalla: un tope mensual mirado en la vista de
            // "día" daría un porcentaje sin sentido.
            'presupuestos' => $this->presupuestos->evaluar($usuario),
            'movimientos_recientes' => $this->recientes($request->cuantosMovimientos()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function saldo($usuario): array
    {
        $cuentas = Cuenta::query()->activas()->get();

        return $this->saldos->resumen(
            $usuario,
            $cuentas,
            $this->saldos->porUsuario($usuario, $cuentas),
        ) + ['cuentas' => $cuentas->count()];
    }

    private function recientes(int $cuantos)
    {
        return Movimiento::query()
            ->with('cuenta')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($cuantos)
            ->get();
    }
}
