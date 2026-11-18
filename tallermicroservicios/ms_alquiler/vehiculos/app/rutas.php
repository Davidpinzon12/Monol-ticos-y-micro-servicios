<?php
$c = 'App\Controllers\VehiculoController';
$app->get('/vehiculos', "$c:listar");
$app->get('/vehiculos/{id}', "$c:mostrar");
$app->post('/vehiculos', "$c:crear");
$app->put('/vehiculos/{id}/estado', "$c:cambiarEstado");
