<?php
namespace App\Controllers;

use App\Models\Vehiculo;
use App\Views\Vista;

// Controlador (la C de MVC): recibe la petición, usa el modelo y devuelve la vista
class VehiculoController
{
    public function listar($req, $res)
    {
        $q = Vehiculo::query();
        $estado = $req->getQueryParams()['estado'] ?? null;
        if ($estado) { $q->where('estado', $estado); }
        return Vista::responder($res, $q->orderBy('id', 'desc')->get());
    }

    public function mostrar($req, $res, $args)
    {
        $v = Vehiculo::find($args['id']);
        return $v ? Vista::responder($res, $v) : Vista::error($res, 'Vehículo no encontrado', 404);
    }

    public function crear($req, $res)
    {
        $d = (array)$req->getParsedBody();
        $anio = (int)($d['anio'] ?? 0);
        if (trim($d['marca'] ?? '') === '' || trim($d['modelo'] ?? '') === '' || $anio < 1980 || $anio > (int)date('Y') + 1) {
            return Vista::error($res, 'Marca, modelo y un año válido son obligatorios', 422);
        }
        $v = Vehiculo::create(['marca' => trim($d['marca']), 'modelo' => trim($d['modelo']), 'anio' => $anio, 'categoria' => $d['categoria'] ?? null]);
        return Vista::responder($res, Vehiculo::find($v->id), 201);
    }

    public function cambiarEstado($req, $res, $args)
    {
        $v = Vehiculo::find($args['id']);
        if (!$v) { return Vista::error($res, 'Vehículo no encontrado', 404); }
        $estado = ((array)$req->getParsedBody())['estado'] ?? '';
        if (!in_array($estado, ['disponible', 'alquilado', 'mantenimiento'], true)) {
            return Vista::error($res, 'Estado no válido', 422);
        }
        $v->estado = $estado;
        $v->save();
        return Vista::responder($res, $v);
    }
}
