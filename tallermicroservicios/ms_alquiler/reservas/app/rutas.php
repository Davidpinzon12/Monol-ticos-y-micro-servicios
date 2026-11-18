<?php
$c = 'App\Controllers\ReservaController';
$app->get('/reservas', "$c:listar");
$app->post('/reservas', "$c:crear");
$app->put('/reservas/{id}/devolver', "$c:devolver");
$app->put('/reservas/{id}/cancelar', "$c:cancelar");
$app->get('/disponibilidad', "$c:disponibilidad");
