<?php

namespace Tests\Unit;

use App\Services\CalculadorPeriodos;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Tests\TestCase;

class CalculadorPeriodosTest extends TestCase
{
    private CalculadorPeriodos $calculador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculador = new CalculadorPeriodos;
    }

    public function test_usa_la_zona_horaria_del_usuario_y_no_la_del_servidor(): void
    {
        // 04:30 UTC del día 24 es todavía el 23 en Bogotá (UTC-5).
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-24 04:30:00', 'UTC'));

        $bogota = $this->calculador->calcular('America/Bogota', 'dia');
        $utc = $this->calculador->calcular('UTC', 'dia');

        $this->assertSame('2026-08-23', $bogota->desde->toDateString());
        $this->assertSame('2026-08-24', $utc->desde->toDateString());
    }

    public function test_calcula_los_rangos_de_cada_tipo(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-23 10:00:00', 'America/Bogota'));

        $this->assertSame(
            ['2026-08-01', '2026-08-31'],
            $this->calculador->calcular('America/Bogota', 'mes')->comoFechas(),
        );

        $this->assertSame(
            ['2026-01-01', '2026-12-31'],
            $this->calculador->calcular('America/Bogota', 'anio')->comoFechas(),
        );

        // 23 de agosto de 2026 es domingo: la semana va del 17 al 23.
        $this->assertSame(
            ['2026-08-17', '2026-08-23'],
            $this->calculador->calcular('America/Bogota', 'semana')->comoFechas(),
        );
    }

    public function test_cuenta_solo_los_dias_ya_transcurridos(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-05 15:00:00', 'America/Bogota'));

        $mes = $this->calculador->calcular('America/Bogota', 'mes');

        $this->assertSame(31, $mes->dias());
        $this->assertSame(5, $mes->diasTranscurridos());
        $this->assertTrue($mes->enCurso());
    }

    public function test_un_periodo_cerrado_tiene_todos_sus_dias_transcurridos(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-05 15:00:00', 'America/Bogota'));

        $julio = $this->calculador->calcular('America/Bogota', 'personalizado', '2026-07-01', '2026-07-31');

        $this->assertSame(31, $julio->diasTranscurridos());
        $this->assertFalse($julio->enCurso());
    }

    public function test_el_mes_anterior_es_el_mes_completo_aunque_dure_distinto(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-15 10:00:00', 'America/Bogota'));

        $anterior = $this->calculador->calcular('America/Bogota', 'mes')->anterior();

        $this->assertSame(['2026-02-01', '2026-02-28'], $anterior->comoFechas());
    }

    public function test_un_rango_personalizado_se_compara_con_otro_de_su_misma_longitud(): void
    {
        $anterior = $this->calculador
            ->calcular('America/Bogota', 'personalizado', '2026-08-10', '2026-08-19')
            ->anterior();

        $this->assertSame(['2026-07-31', '2026-08-09'], $anterior->comoFechas());
    }

    public function test_rechaza_un_rango_invertido(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculador->calcular('America/Bogota', 'personalizado', '2026-08-20', '2026-08-01');
    }

    public function test_rechaza_un_rango_de_mas_de_un_anio(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculador->calcular('America/Bogota', 'personalizado', '2025-01-01', '2026-06-01');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }
}
