<?php
namespace App\Controllers;

use App\Http;
use App\Models\Reserva;
use App\Views\Vista;

class ReservaController
{
    const VEHICULOS = 'http://127.0.0.1:8001';
    const CLIENTES = 'http://127.0.0.1:8002';

    public function listar($req, $res)
    {
        $p = $req->getQueryParams();
        $q = Reserva::query();
        foreach (['cliente_id', 'vehiculo_id', 'estado'] as $campo) {
            if (!empty($p[$campo])) { $q->where($campo, $p[$campo]); }
        }
        return Vista::responder($res, $q->orderBy('id', 'desc')->get());
    }

    public function crear($req, $res)
    {
        $d = (array)$req->getParsedBody();
        $cid = (int)($d['cliente_id'] ?? 0);
        $vid = (int)($d['vehiculo_id'] ?? 0);
        $ini = $d['fecha_inicio'] ?? '';
        $fin = $d['fecha_fin'] ?? '';
        if (!strtotime($ini) || !strtotime($fin) || $fin < $ini) {
            return Vista::error($res, 'Fechas no válidas: el fin no puede ser anterior al inicio', 422);
        }
        if (Http::llamar('GET', self::CLIENTES . '/clientes/' . $cid)['codigo'] !== 200) {
            return Vista::error($res, 'El cliente no existe', 404);
        }
        $v = Http::llamar('GET', self::VEHICULOS . '/vehiculos/' . $vid);
        if ($v['codigo'] !== 200) { return Vista::error($res, 'El vehículo no existe', 404); }
        if ($v['datos']['estado'] === 'mantenimiento') { return Vista::error($res, 'El vehículo está en mantenimiento', 409); }
        if ($this->cruza($vid, $ini, $fin)) {
            return Vista::error($res, 'Ese vehículo ya tiene una reserva que se cruza con esas fechas', 409);
        }
        $r = Reserva::create(['cliente_id' => $cid, 'vehiculo_id' => $vid, 'fecha_inicio' => $ini, 'fecha_fin' => $fin]);
        $this->actualizarEstadoVehiculo($vid);
        return Vista::responder($res, Reserva::find($r->id), 201);
    }

    public function devolver($req, $res, $args)
    {
        return $this->cerrar($res, $args['id'], 'completada');
    }

    public function cancelar($req, $res, $args)
    {
        return $this->cerrar($res, $args['id'], 'cancelada');
    }

    // Vehículos libres en un rango: pide la flota al servicio de vehículos y descarta los que se cruzan
    public function disponibilidad($req, $res)
    {
        $p = $req->getQueryParams();
        $ini = $p['desde'] ?? date('Y-m-d');
        $fin = $p['hasta'] ?? $ini;
        if (!strtotime($ini) || !strtotime($fin) || $fin < $ini) {
            return Vista::error($res, 'La fecha final no puede ser anterior a la inicial', 422);
        }
        $flota = Http::llamar('GET', self::VEHICULOS . '/vehiculos')['datos'] ?? [];
        $libres = array_filter($flota, fn($v) => $v['estado'] !== 'mantenimiento' && !$this->cruza($v['id'], $ini, $fin));
        return Vista::responder($res, array_values($libres));
    }

    private function cerrar($res, $id, $nuevoEstado)
    {
        $r = Reserva::find($id);
        if (!$r || $r->estado !== 'activa') { return Vista::error($res, 'Esa reserva no está activa', 404); }
        $r->estado = $nuevoEstado;
        $r->save();
        $this->actualizarEstadoVehiculo($r->vehiculo_id);
        return Vista::responder($res, $r);
    }

    private function cruza($vid, $ini, $fin)
    {
        return Reserva::where('vehiculo_id', $vid)->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $fin)->where('fecha_fin', '>=', $ini)->exists();
    }

    // Estado automático: alquilado si hoy cae en una reserva activa; si no, disponible
    private function actualizarEstadoVehiculo($vid)
    {
        $v = Http::llamar('GET', self::VEHICULOS . '/vehiculos/' . $vid);
        if ($v['codigo'] !== 200 || $v['datos']['estado'] === 'mantenimiento') { return; }
        $hoy = date('Y-m-d');
        $ocupado = Reserva::where('vehiculo_id', $vid)->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $hoy)->where('fecha_fin', '>=', $hoy)->exists();
        Http::llamar('PUT', self::VEHICULOS . '/vehiculos/' . $vid . '/estado', ['estado' => $ocupado ? 'alquilado' : 'disponible']);
    }
}
