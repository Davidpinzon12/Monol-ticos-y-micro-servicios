<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/clases.php';
$titulo = 'Disponibilidad por fechas';
$error = '';
$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$libres = array();
try {
    $libres = (new GestorVehiculos())->disponiblesEntre($desde, $hasta);
} catch (InvalidArgumentException $ex) {
    $error = $ex->getMessage();
}
require_once __DIR__ . '/includes/header.php';
?>
<form method="get" class="form">
    <label>Desde <input type="date" name="desde" value="<?= e($desde) ?>"></label>
    <label>Hasta <input type="date" name="hasta" value="<?= e($hasta) ?>"></label>
    <button>Consultar</button>
</form>
<div class="catalogo">
    <?php foreach ($libres as $i => $v) { echo ficha($v, '', $i); } ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
