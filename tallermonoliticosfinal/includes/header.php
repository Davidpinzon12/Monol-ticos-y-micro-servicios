<?php
$actual = basename($_SERVER['SCRIPT_NAME']);
$menu = array('index.php' => 'Inicio', 'vehiculos.php' => 'Vehículos', 'clientes.php' => 'Clientes', 'reservas.php' => 'Reservas', 'disponibilidad.php' => 'Disponibilidad', 'historial.php' => 'Historial');
$col = array('#ff8a2b', '#ff5fa2', '#b57bff', '#ffd90f', '#33c9ff', '#8cdb3a', '#e8412f');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · Gestor de Alquiler</title>
<link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<div class="fondo" aria-hidden="true">
    <span class="sol"></span>
    <span class="nube n1"></span><span class="nube n2"></span><span class="nube n3"></span>
    <div class="avion"><b>¡ALQUILA YA! ✈</b></div>
    <div class="ciudad"><?php for ($k = 0; $k < 24; $k++): ?><i style="--w:<?= 70 + ($k * 37) % 50 ?>px;--h:<?= 90 + ($k * 53) % 110 ?>px;--c:<?= $col[$k % 7] ?>"></i><?php endfor; ?></div>
    <i class="arbol" style="left:3%"></i><i class="arbol" style="left:44%;--d:-2s"></i><i class="arbol" style="left:90%;--d:-4s"></i>
    <div class="farola" style="left:22%"></div><div class="farola" style="left:66%"></div>
    <div class="semaforo"><u></u><u></u><u></u></div>
    <div class="acera"></div>
</div>
<div class="ruta" aria-hidden="true">
    <div class="auto a1"><b class="rueda r1"></b><b class="rueda r2"></b></div>
    <div class="auto a2"><b class="rueda r1"></b><b class="rueda r2"></b></div>
    <div class="auto a3 izq"><b class="rueda r1"></b><b class="rueda r2"></b></div>
    <div class="auto a4 izq"><b class="rueda r1"></b><b class="rueda r2"></b></div>
</div>
<header class="top">
    <strong>🚗 Gestor de Alquiler</strong>
    <nav>
        <?php foreach ($menu as $archivo => $texto): ?>
            <a href="<?= $archivo ?>"<?= $archivo === $actual ? ' class="activo"' : '' ?>><?= $texto ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
<h1><?= e($titulo) ?></h1>
<?php if (isset($_GET['m'])): ?><p class="aviso"><?= e($_GET['m']) ?></p><?php endif; ?>
<?php if (!empty($error)): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
