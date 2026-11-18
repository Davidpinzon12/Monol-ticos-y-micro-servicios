<?php
$c = 'App\Controllers\ClienteController';
$app->get('/clientes', "$c:listar");
$app->get('/clientes/{id}', "$c:mostrar");
$app->post('/clientes', "$c:crear");
