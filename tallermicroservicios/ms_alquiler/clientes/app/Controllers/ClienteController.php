<?php
namespace App\Controllers;

use App\Models\Cliente;
use App\Views\Vista;

class ClienteController
{
    public function listar($req, $res)
    {
        return Vista::responder($res, Cliente::orderBy('nombre')->get());
    }

    public function mostrar($req, $res, $args)
    {
        $c = Cliente::find($args['id']);
        return $c ? Vista::responder($res, $c) : Vista::error($res, 'Cliente no encontrado', 404);
    }

    public function crear($req, $res)
    {
        $d = (array)$req->getParsedBody();
        if (trim($d['nombre'] ?? '') === '' || trim($d['numero_licencia'] ?? '') === '') {
            return Vista::error($res, 'Nombre y número de licencia son obligatorios', 422);
        }
        if (trim($d['correo'] ?? '') !== '' && !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            return Vista::error($res, 'El correo no es válido', 422);
        }
        $c = Cliente::create([
            'nombre' => trim($d['nombre']), 'telefono' => trim($d['telefono'] ?? ''),
            'correo' => trim($d['correo'] ?? ''), 'numero_licencia' => trim($d['numero_licencia']),
        ]);
        return Vista::responder($res, Cliente::find($c->id), 201);
    }
}
