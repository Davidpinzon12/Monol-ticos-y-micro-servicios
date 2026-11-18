<?php
function e($t) { return htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8'); }
function volver($mensaje)
{
    header('Location: ' . basename($_SERVER['SCRIPT_NAME']) . '?m=' . urlencode($mensaje));
    exit;
}
function pesos($n) { return '$' . number_format($n, 0, ',', '.'); }
set_exception_handler(function ($ex) {
    echo '<p style="font-family:sans-serif;padding:1rem">' . htmlspecialchars($ex->getMessage()) . '</p>';
});
function ficha($v, $extra = '', $i = 0)
{
    $moto = strtolower($v['categoria']) === 'moto' ? ' moto' : '';
    $tono = ($v['id'] * 67) % 360;
    return '<article class="ficha" style="--i:' . (int)$i . ';--tono:' . $tono . '"><div class="foto"><div class="auto' . $moto . '"><b class="rueda r1"></b><b class="rueda r2"></b></div></div>'
        . '<div><h3>' . e($v['marca'] . ' ' . $v['modelo']) . '</h3><p>' . e($v['anio']) . ' · ' . e($v['categoria']) . '</p>'
        . '<span class="precio">' . pesos(Vehiculo::crear($v['categoria'])->tarifaDiaria()) . ' / día</span>' . $extra . '</div>'
        . '<span class="sello ' . e($v['estado']) . '">' . e($v['estado']) . '</span></article>';
}
