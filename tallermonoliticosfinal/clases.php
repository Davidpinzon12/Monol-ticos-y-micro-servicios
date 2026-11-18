<?php
class BaseDatos
{
    const DSN = 'mysql:host=127.0.0.1;dbname=alquiler_vehiculos_db;charset=utf8mb4';
    const USUARIO = 'root';
    const CLAVE = '';
    private static $pdo = null;

    public static function consultar($sql, $params = array())
    {
        if (self::$pdo === null) {
            try {
                self::$pdo = new PDO(self::DSN, self::USUARIO, self::CLAVE, array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ));
            } catch (PDOException $ex) {
                throw new RuntimeException('No se pudo conectar a MySQL. Enciende MySQL en XAMPP e importa bd/alquiler_vehiculos_db.sql en phpMyAdmin.');
            }
        }
        $st = self::$pdo->prepare($sql);
        $st->execute($params);
        return $st;
    }
}

abstract class Vehiculo
{
    const DISPONIBLE = 'disponible';
    const ALQUILADO = 'alquilado';
    const MANTENIMIENTO = 'mantenimiento';

    abstract public function tarifaDiaria();

    public static function crear($categoria)
    {
        $c = strtolower(trim($categoria));
        if ($c === 'moto') { return new Moto(); }
        if ($c === 'suv') { return new Suv(); }
        return new Auto();
    }
}

class Auto extends Vehiculo
{
    public function tarifaDiaria() { return 90000; }
}

class Suv extends Auto
{
    public function tarifaDiaria() { return 130000; }
}

class Moto extends Vehiculo
{
    public function tarifaDiaria() { return 45000; }
}

class GestorVehiculos
{
    public function registrar($marca, $modelo, $anio, $categoria)
    {
        if (trim($marca) === '' || trim($modelo) === '') {
            throw new InvalidArgumentException('Marca y modelo son obligatorios.');
        }
        if ($anio < 1980 || $anio > (int)date('Y') + 1) {
            throw new InvalidArgumentException('El año no es válido.');
        }
        BaseDatos::consultar('INSERT INTO vehiculos (marca, modelo, anio, categoria) VALUES (?, ?, ?, ?)',
            array(trim($marca), trim($modelo), $anio, $categoria));
    }

    public function todos()
    {
        return BaseDatos::consultar('SELECT * FROM vehiculos ORDER BY id DESC')->fetchAll();
    }

    public function porEstado($estado)
    {
        return BaseDatos::consultar('SELECT * FROM vehiculos WHERE estado = ?', array($estado))->fetchAll();
    }

    public function disponiblesEntre($inicio, $fin)
    {
        if (!strtotime($inicio) || !strtotime($fin) || $fin < $inicio) {
            throw new InvalidArgumentException('La fecha final no puede ser anterior a la inicial.');
        }
        return BaseDatos::consultar(
            "SELECT * FROM vehiculos v WHERE v.estado <> 'mantenimiento' AND NOT EXISTS (SELECT 1 FROM reservas r WHERE r.vehiculo_id = v.id AND r.estado = 'activa' AND r.fecha_inicio <= ? AND r.fecha_fin >= ?)",
            array($fin, $inicio))->fetchAll();
    }

    public function alternarMantenimiento($id)
    {
        $v = BaseDatos::consultar('SELECT estado FROM vehiculos WHERE id = ?', array($id))->fetch();
        if (!$v) { throw new InvalidArgumentException('El vehículo no existe.'); }
        if ($v['estado'] === Vehiculo::ALQUILADO) {
            throw new InvalidArgumentException('Un vehículo alquilado no puede pasar a mantenimiento.');
        }
        if ($v['estado'] === Vehiculo::MANTENIMIENTO) {
            BaseDatos::consultar('UPDATE vehiculos SET estado = ? WHERE id = ?', array(Vehiculo::DISPONIBLE, $id));
            $this->recalcularEstado($id);
        } else {
            BaseDatos::consultar('UPDATE vehiculos SET estado = ? WHERE id = ?', array(Vehiculo::MANTENIMIENTO, $id));
        }
    }

    // Estado automático: alquilado si hoy cae dentro de una reserva activa; si no, disponible.
    public function recalcularEstado($id)
    {
        $v = BaseDatos::consultar('SELECT estado FROM vehiculos WHERE id = ?', array($id))->fetch();
        if (!$v || $v['estado'] === Vehiculo::MANTENIMIENTO) { return; }
        $hoy = date('Y-m-d');
        $n = BaseDatos::consultar("SELECT COUNT(*) FROM reservas WHERE vehiculo_id = ? AND estado = 'activa' AND fecha_inicio <= ? AND fecha_fin >= ?",
            array($id, $hoy, $hoy))->fetchColumn();
        BaseDatos::consultar('UPDATE vehiculos SET estado = ? WHERE id = ?', array($n > 0 ? Vehiculo::ALQUILADO : Vehiculo::DISPONIBLE, $id));
    }
}

class GestorClientes
{
    public function registrar($nombre, $telefono, $correo, $licencia)
    {
        if (trim($nombre) === '' || trim($licencia) === '') {
            throw new InvalidArgumentException('Nombre y licencia son obligatorios.');
        }
        if (trim($correo) !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo no es válido.');
        }
        BaseDatos::consultar('INSERT INTO clientes (nombre, telefono, correo, numero_licencia) VALUES (?, ?, ?, ?)',
            array(trim($nombre), trim($telefono), trim($correo), trim($licencia)));
    }

    public function todos()
    {
        return BaseDatos::consultar('SELECT * FROM clientes ORDER BY nombre')->fetchAll();
    }
}

class GestorReservas
{
    public function reservar($vehiculoId, $clienteId, $inicio, $fin)
    {
        $v = BaseDatos::consultar('SELECT * FROM vehiculos WHERE id = ?', array($vehiculoId))->fetch();
        if (!$v) { throw new InvalidArgumentException('Elige un vehículo.'); }
        if (!BaseDatos::consultar('SELECT id FROM clientes WHERE id = ?', array($clienteId))->fetch()) {
            throw new InvalidArgumentException('Elige un cliente.');
        }
        if (!strtotime($inicio) || !strtotime($fin) || $fin < $inicio) {
            throw new InvalidArgumentException('La fecha de fin no puede ser anterior a la de inicio.');
        }
        if ($v['estado'] === Vehiculo::MANTENIMIENTO) {
            throw new InvalidArgumentException('El vehículo está en mantenimiento.');
        }
        $cruces = BaseDatos::consultar("SELECT COUNT(*) FROM reservas WHERE vehiculo_id = ? AND estado = 'activa' AND fecha_inicio <= ? AND fecha_fin >= ?",
            array($vehiculoId, $fin, $inicio))->fetchColumn();
        if ($cruces > 0) {
            throw new InvalidArgumentException('Ese vehículo ya tiene una reserva que se cruza con esas fechas.');
        }
        BaseDatos::consultar('INSERT INTO reservas (cliente_id, vehiculo_id, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?)',
            array($clienteId, $vehiculoId, $inicio, $fin));
        (new GestorVehiculos())->recalcularEstado($vehiculoId);
    }

    // $nuevoEstado: 'completada' (devolución) o 'cancelada'
    public function cerrar($reservaId, $nuevoEstado)
    {
        $r = BaseDatos::consultar('SELECT * FROM reservas WHERE id = ?', array($reservaId))->fetch();
        if (!$r || $r['estado'] !== 'activa') { throw new InvalidArgumentException('Esa reserva no está activa.'); }
        BaseDatos::consultar('UPDATE reservas SET estado = ? WHERE id = ?', array($nuevoEstado, $reservaId));
        (new GestorVehiculos())->recalcularEstado($r['vehiculo_id']);
    }

    public function listar($columna = null, $id = 0, $soloActivas = false)
    {
        $sql = 'SELECT r.*, v.marca, v.modelo, v.categoria, c.nombre FROM reservas r JOIN vehiculos v ON v.id = r.vehiculo_id JOIN clientes c ON c.id = r.cliente_id WHERE 1 = 1';
        $params = array();
        if ($soloActivas) { $sql .= " AND r.estado = 'activa'"; }
        if ($columna === 'vehiculo' || $columna === 'cliente') {
            $sql .= ' AND r.' . $columna . '_id = ?';
            $params[] = $id;
        }
        return BaseDatos::consultar($sql . ' ORDER BY r.id DESC', $params)->fetchAll();
    }

    public static function total($r)
    {
        $dias = (int)((strtotime($r['fecha_fin']) - strtotime($r['fecha_inicio'])) / 86400) + 1;
        return $dias * Vehiculo::crear($r['categoria'])->tarifaDiaria();
    }
}
