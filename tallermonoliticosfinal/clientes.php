<?php
require_once __DIR__ . '/includes/funciones.php';
require_once __DIR__ . '/clases.php';
$titulo = 'Clientes';
$error = '';
$g = new GestorClientes();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $g->registrar($_POST['nombre'] ?? '', $_POST['telefono'] ?? '', $_POST['correo'] ?? '', $_POST['licencia'] ?? '');
        volver('Cliente registrado.');
    } catch (InvalidArgumentException $ex) {
        $error = $ex->getMessage();
    }
}
require_once __DIR__ . '/includes/header.php';
?>
<form method="post" class="form">
    <label>Nombre <input name="nombre" required></label>
    <label>Teléfono <input name="telefono"></label>
    <label>Correo <input type="email" name="correo"></label>
    <label>Licencia <input name="licencia" required></label>
    <button>Registrar cliente</button>
</form>
<table>
    <tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Licencia</th></tr>
    <?php foreach ($g->todos() as $c): ?>
        <tr><td><?= e($c['nombre']) ?></td><td><?= e($c['telefono']) ?></td><td><?= e($c['correo']) ?></td><td><?= e($c['numero_licencia']) ?></td></tr>
    <?php endforeach; ?>
</table>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
