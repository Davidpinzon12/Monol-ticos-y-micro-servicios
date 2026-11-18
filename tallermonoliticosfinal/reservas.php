<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/clases.php';
$titulo = 'Reservas y devoluciones';
$error = '';
$gv = new GestorVehiculos();
$gc = new GestorClientes();
$gr = new GestorReservas();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $accion = $_POST['accion'] ?? '';
        if ($accion === 'devolver' || $accion === 'cancelar') {
            $gr->cerrar((int)($_POST['id'] ?? 0), $accion === 'devolver' ? 'completada' : 'cancelada');
            volver($accion === 'devolver' ? 'Vehículo devuelto: reserva completada.' : 'Reserva cancelada.');
        }
        $gr->reservar((int)($_POST['vehiculo'] ?? 0), (int)($_POST['cliente'] ?? 0), $_POST['inicio'] ?? '', $_POST['fin'] ?? '');
        volver('Reserva creada.');
    } catch (InvalidArgumentException $ex) {
        $error = $ex->getMessage();
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<form method="post" class="form">
    <label>Vehículo <select name="vehiculo">
        <?php foreach ($gv->todos() as $v): if ($v['estado'] === Vehiculo::MANTENIMIENTO) { continue; } ?>
            <option value="<?= (int)$v['id'] ?>"><?= e($v['marca'] . ' ' . $v['modelo']) ?></option>
        <?php endforeach; ?>
    </select></label>
    <label>Cliente <select name="cliente">
        <?php foreach ($gc->todos() as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['nombre']) ?></option><?php endforeach; ?>
    </select></label>
    <label>Desde <input type="date" name="inicio" value="<?= date('Y-m-d') ?>" required></label>
    <label>Hasta <input type="date" name="fin" value="<?= date('Y-m-d') ?>" required></label>
    <button>Reservar</button>
</form>
<h2>Reservas activas</h2>
<table>
    <tr><th>Vehículo</th><th>Cliente</th><th>Periodo</th><th>Total estimado</th><th></th></tr>
    <?php foreach ($gr->listar(null, 0, true) as $r): ?>
        <tr>
            <td><?= e($r['marca'] . ' ' . $r['modelo']) ?></td><td><?= e($r['nombre']) ?></td><td><?= e($r['fecha_inicio'] . ' a ' . $r['fecha_fin']) ?></td><td><?= pesos(GestorReservas::total($r)) ?></td>
            <td>
                <form method="post"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button name="accion" value="devolver" class="suave">Devolver</button>
                    <button name="accion" value="cancelar" class="suave">Cancelar</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
