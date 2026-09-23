<?php

namespace Tests\Feature\Dashboard;

use App\Models\Movimiento;
use App\Models\User;
use App\Services\CalculadorPeriodos;
use App\Services\ResumenMovimientos;
use App\Support\Periodo;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumenMovimientosTest extends TestCase
{
    use RefreshDatabase;

    private ResumenMovimientos $resumen;
    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-10 12:00:00', 'America/Bogota'));

        $this->resumen = new ResumenMovimientos;
        $this->usuario = User::factory()->create(['timezone' => 'America/Bogota']);
    }

    private function periodo(string $tipo = 'mes'): Periodo
    {
        return (new CalculadorPeriodos)->paraUsuario($this->usuario, $tipo);
    }

    private function gasto(string $fecha, float $monto, string $categoria = 'otros'): void
    {
        Movimiento::factory()->create([
            'tipo' => 'gasto',
            'user_id' => $this->usuario->id,
            'fecha' => $fecha,
            'monto' => $monto,
            'categoria' => $categoria,
        ]);
    }

    public function test_suma_y_agrupa_por_categoria(): void
    {
        $this->gasto('2026-08-02', 60000, 'alimentacion');
        $this->gasto('2026-08-05', 40000, 'alimentacion');
        $this->gasto('2026-08-07', 100000, 'servicios');

        $datos = $this->resumen->calcular($this->usuario, $this->periodo());

        $this->assertSame(200000.0, $datos['total']);
        $this->assertSame(3, $datos['movimientos']);
        // Las dos suman 100 000: el empate lo rompe el número de movimientos,
        // y alimentación tiene dos. Antes esto esperaba `servicios` y pasaba
        // por el orden que devolviera la base, no porque estuviera decidido.
        $this->assertSame('alimentacion', $datos['categoria_principal']['categoria']);
        $this->assertSame(50.0, $datos['por_categoria'][0]['porcentaje']);
        $this->assertSame(50.0, $datos['por_categoria'][1]['porcentaje']);
    }

    public function test_el_total_es_igual_a_la_suma_de_las_categorias(): void
    {
        $this->gasto('2026-08-02', 33333.33, 'alimentacion');
        $this->gasto('2026-08-03', 33333.33, 'transporte');
        $this->gasto('2026-08-04', 33333.34, 'salud');

        $datos = $this->resumen->calcular($this->usuario, $this->periodo());

        $this->assertSame(
            $datos['total'],
            round(array_sum(array_column($datos['por_categoria'], 'total')), 2),
        );
    }

    public function test_promedia_entre_dias_transcurridos_no_entre_dias_del_mes(): void
    {
        $this->gasto('2026-08-01', 100000);

        $datos = $this->resumen->calcular($this->usuario, $this->periodo());

        // Estamos a día 10: 100000 / 10, no 100000 / 31.
        $this->assertSame(10000.0, $datos['promedio_diario']);
    }

    public function test_proyecta_el_periodo_en_curso_y_no_el_cerrado(): void
    {
        $this->gasto('2026-08-01', 100000);

        $enCurso = $this->resumen->calcular($this->usuario, $this->periodo());
        $this->assertSame(310000.0, $enCurso['proyeccion_fin_periodo']);

        $cerrado = $this->resumen->calcular(
            $this->usuario,
            (new CalculadorPeriodos)->calcular('America/Bogota', 'personalizado', '2026-07-01', '2026-07-31'),
        );
        $this->assertSame($cerrado['total'], $cerrado['proyeccion_fin_periodo']);
    }

    public function test_ignora_los_gastos_fuera_del_periodo_y_los_de_otros_usuarios(): void
    {
        $this->gasto('2026-08-05', 50000);
        $this->gasto('2026-07-31', 999999);
        Movimiento::factory()->create(['fecha' => '2026-08-05', 'monto' => 888888]);

        $datos = $this->resumen->calcular($this->usuario, $this->periodo());

        $this->assertSame(50000.0, $datos['total']);
    }

    public function test_un_periodo_sin_gastos_no_divide_por_cero(): void
    {
        $datos = $this->resumen->calcular($this->usuario, $this->periodo());

        $this->assertSame(0.0, $datos['total']);
        $this->assertSame(0.0, $datos['promedio_diario']);
        $this->assertSame([], $datos['por_categoria']);
        $this->assertNull($datos['categoria_principal']);
    }

    public function test_calcula_la_variacion_frente_al_mes_anterior(): void
    {
        $this->gasto('2026-07-10', 200000);
        $this->gasto('2026-08-05', 150000);

        $comparacion = $this->resumen->comparar($this->usuario, $this->periodo(), 150000);

        $this->assertSame(200000.0, $comparacion['periodo_anterior']['total']);
        $this->assertSame(-50000.0, $comparacion['diferencia']);
        $this->assertSame(-25.0, $comparacion['variacion_porcentual']);
        $this->assertSame('baja', $comparacion['tendencia']);
    }

    public function test_sin_gasto_previo_no_inventa_un_porcentaje(): void
    {
        $this->gasto('2026-08-05', 150000);

        $comparacion = $this->resumen->comparar($this->usuario, $this->periodo(), 150000);

        $this->assertNull($comparacion['variacion_porcentual']);
        $this->assertSame('sin_referencia', $comparacion['tendencia']);
    }

    public function test_dos_periodos_vacios_no_son_una_variacion_indefinida(): void
    {
        $comparacion = $this->resumen->comparar($this->usuario, $this->periodo(), 0);

        $this->assertSame(0.0, $comparacion['variacion_porcentual']);
        $this->assertSame('igual', $comparacion['tendencia']);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
}
