<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/clases.php';
$titulo = 'Historial de alquileres';
$gv = new GestorVehiculos();
$gc = new GestorClientes();
$gr = new GestorReservas();
$vid = (int)($_GET['vehiculo'] ?? 0);
$cid = (int)($_GET['cliente'] ?? 0);
$filas = $gr->listar($vid ? 'vehiculo' : ($cid ? 'cliente' : null), $vid ?: $cid);
require_once __DIR__ . '/includes/header.php';
?>
<form method="get" class="form">
    <label>Por vehículo <select name="vehiculo"><option value="0">Todos</option>
        <?php foreach ($gv->todos() as $v): ?><option value="<?= (int)$v['id'] ?>"<?= $vid === (int)$v['id'] ? ' selected' : '' ?>><?= e($v['marca'] . ' ' . $v['modelo']) ?></option><?php endforeach; ?>
    </select></label>
    <label>Por cliente <select name="cliente"><option value="0">Todos</option>
        <?php foreach ($gc->todos() as $c): ?><option value="<?= (int)$c['id'] ?>"<?= $cid === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
    </select></label>
    <button>Consultar</button>
</form>
<table>
    <tr><th>Vehículo</th><th>Cliente</th><th>Periodo</th><th>Total</th><th>Estado</th></tr>
    <?php foreach ($filas as $r): ?>
        <tr><td><?= e($r['marca'] . ' ' . $r['modelo']) ?></td><td><?= e($r['nombre']) ?></td><td><?= e($r['fecha_inicio'] . ' a ' . $r['fecha_fin']) ?></td><td><?= pesos(GestorReservas::total($r)) ?></td><td><?= e($r['estado']) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
