<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/clases.php';
$titulo = 'Vehículos';
$error = '';
$g = new GestorVehiculos();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['accion'] ?? '') === 'mantenimiento') {
            $g->alternarMantenimiento((int)($_POST['id'] ?? 0));
            volver('Estado actualizado.');
        }
        $g->registrar($_POST['marca'] ?? '', $_POST['modelo'] ?? '', (int)($_POST['anio'] ?? 0), trim($_POST['categoria'] ?? ''));
        volver('Vehículo registrado.');
    } catch (InvalidArgumentException $ex) {
        $error = $ex->getMessage();
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<form method="post" class="form">
    <label>Marca <input name="marca" required></label>
    <label>Modelo <input name="modelo" required></label>
    <label>Año <input type="number" name="anio" value="<?= date('Y') ?>" required></label>
    <label>Categoría <select name="categoria"><option>Económico</option><option>SUV</option><option>Moto</option></select></label>
    <button>Registrar vehículo</button>
</form>
<div class="catalogo">
<?php foreach ($g->todos() as $i => $v) {
    $btn = '<form method="post" class="inline"><input type="hidden" name="accion" value="mantenimiento"><input type="hidden" name="id" value="' . (int)$v['id'] . '"><button class="suave">Mantenimiento on/off</button></form>';
    echo ficha($v, $btn, $i);
} ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
