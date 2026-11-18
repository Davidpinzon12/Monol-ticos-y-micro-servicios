<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/clases.php';
$titulo = 'Bienvenido a la calle del alquiler';
$g = new GestorVehiculos();
$disp = $g->porEstado(Vehiculo::DISPONIBLE);
$alq = count($g->porEstado(Vehiculo::ALQUILADO));
$mant = count($g->porEstado(Vehiculo::MANTENIMIENTO));
$locales = array(
    array('vehiculos.php', '🚗', 'VEHÍCULOS', 'var(--rosa)', 'var(--sol)', 250),
    array('clientes.php', '👤', 'CLIENTES', 'var(--lima)', 'var(--rosa)', 215),
    array('reservas.php', '📅', 'RESERVAS', 'var(--cian)', 'var(--nar)', 240),
    array('disponibilidad.php', '🔍', 'DISPONIBLES', 'var(--nar)', 'var(--cian)', 225),
    array('historial.php', '📜', 'HISTORIAL', 'var(--mor)', 'var(--lima)', 255),
);
require_once __DIR__ . '/includes/header.php';
?>
<div class="negocios">
    <?php foreach ($locales as $l): ?>
        <a class="local" href="<?= $l[0] ?>" style="--c:<?= $l[3] ?>;--t:<?= $l[4] ?>;--h:<?= $l[5] ?>px">
            <span class="rotulo"><?= $l[2] ?></span>
            <span class="toldo"></span>
            <span class="vitrina"><?= $l[1] ?></span>
            <span class="entrada"></span>
        </a>
    <?php endforeach; ?>
</div>
<section class="cifras">
    <div class="ok"><b><?= count($disp) ?></b> libres</div>
    <div class="uso"><b><?= $alq ?></b> alquilados</div>
    <div class="mant"><b><?= $mant ?></b> taller</div>
</section>
<h2>Disponibles hoy</h2>
<?php if (!$disp): ?>
    <p class="vacio">No hay vehículos disponibles. Registra uno en «Vehículos».</p>
<?php else: ?>
<div class="catalogo">
    <?php foreach ($disp as $i => $v) { echo ficha($v, '', $i); } ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
