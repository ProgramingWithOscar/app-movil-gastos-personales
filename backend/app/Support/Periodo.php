<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Un rango de fechas cerrado, con la aritmética que el dashboard necesita.
 *
 * Los límites se guardan como instantes en la zona horaria de la persona, no
 * del servidor: alguien en Bogotá que registra un gasto a las 23:00 espera
 * verlo dentro de "hoy", no del día siguiente.
 */
final readonly class Periodo
{
    public function __construct(
        public string $tipo,
        public CarbonImmutable $desde,
        public CarbonImmutable $hasta,
    ) {}

    /** Días que abarca el período, ambos extremos incluidos. */
    public function dias(): int
    {
        return $this->desde->startOfDay()->diffInDays($this->hasta->startOfDay()) + 1;
    }

    /**
     * Días del período que ya ocurrieron.
     *
     * Es lo que hay que usar para promediar: dividir el gasto del mes entre 31
     * cuando vamos por el día 5 da un promedio falsamente bajo.
     */
    public function diasTranscurridos(): int
    {
        $hoy = CarbonImmutable::now($this->desde->timezone)->startOfDay();

        if ($hoy->lt($this->desde->startOfDay())) {
            return 0;
        }

        if ($hoy->gte($this->hasta->startOfDay())) {
            return $this->dias();
        }

        return $this->desde->startOfDay()->diffInDays($hoy) + 1;
    }

    /** ¿El período todavía no terminó? */
    public function enCurso(): bool
    {
        $hoy = CarbonImmutable::now($this->desde->timezone);

        return $hoy->between($this->desde, $this->hasta);
    }

    /**
     * El rango inmediatamente anterior del mismo tipo.
     *
     * Para tipos de calendario se retrocede una unidad —así febrero se compara
     * con enero completo, aunque duren distinto—. Para un rango personalizado se
     * desplaza hacia atrás exactamente su misma longitud.
     */
    public function anterior(): self
    {
        return match ($this->tipo) {
            'dia' => new self($this->tipo, $this->desde->subDay(), $this->hasta->subDay()),
            'semana' => new self($this->tipo, $this->desde->subWeek(), $this->hasta->subWeek()),
            'mes' => new self(
                $this->tipo,
                $this->desde->subMonthNoOverflow()->startOfMonth(),
                $this->desde->subMonthNoOverflow()->endOfMonth(),
            ),
            'anio' => new self(
                $this->tipo,
                $this->desde->subYear()->startOfYear(),
                $this->desde->subYear()->endOfYear(),
            ),
            default => new self(
                $this->tipo,
                $this->desde->subDays($this->dias()),
                $this->hasta->subDays($this->dias()),
            ),
        };
    }

    /** Los límites tal como se comparan contra la columna `fecha`. */
    public function comoFechas(): array
    {
        return [$this->desde->toDateString(), $this->hasta->toDateString()];
    }

    /**
     * @return array<string, mixed>
     */
    public function aArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'desde' => $this->desde->toDateString(),
            'hasta' => $this->hasta->toDateString(),
            'dias' => $this->dias(),
            'dias_transcurridos' => $this->diasTranscurridos(),
            'en_curso' => $this->enCurso(),
        ];
    }
}
