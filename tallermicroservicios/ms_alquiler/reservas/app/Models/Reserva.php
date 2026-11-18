<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $table = 'reservas';
    protected $fillable = ['cliente_id', 'vehiculo_id', 'fecha_inicio', 'fecha_fin'];
}
