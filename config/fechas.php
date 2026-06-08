<?php
/**
 * Utilidades de fechas para reservaciones (día de la semana → próxima fecha).
 */

const DIAS_SEMANA_MAP = [
    'Lunes'     => 1,
    'Martes'    => 2,
    'Miércoles' => 3,
    'Jueves'    => 4,
    'Viernes'   => 5,
    'Sábado'    => 6,
];

function proximaFechaPorDiaSemana(string $diaSemana, ?DateTime $desde = null): ?string
{
    if (!isset(DIAS_SEMANA_MAP[$diaSemana])) {
        return null;
    }

    $desde = $desde ?? new DateTime('today');
    $target = DIAS_SEMANA_MAP[$diaSemana];
    $current = (int) $desde->format('N'); // 1=Lunes … 7=Domingo

    $diff = $target - $current;
    if ($diff < 0) {
        $diff += 7;
    }

    $fecha = clone $desde;
    $fecha->modify("+{$diff} days");

    return $fecha->format('Y-m-d');
}

/**
 * Genera las próximas N ocurrencias de un horario semanal.
 */
function fechasParaHorario(string $diaSemana, int $cantidad, ?DateTime $desde = null): array
{
    $fechas = [];
    $cursor = $desde ?? new DateTime('today');

    for ($i = 0; $i < $cantidad; $i++) {
        $f = proximaFechaPorDiaSemana($diaSemana, $cursor);
        if (!$f) {
            break;
        }
        $fechas[] = $f;
        $cursor = new DateTime($f);
        $cursor->modify('+7 days');
    }

    return $fechas;
}
