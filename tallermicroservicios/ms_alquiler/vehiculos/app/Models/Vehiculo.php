<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Modelo (la M de MVC): Eloquent mapea la tabla vehiculos
class Vehiculo extends Model
{
    protected $table = 'vehiculos';
    protected $fillable = ['marca', 'modelo', 'anio', 'categoria'];
    protected $appends = ['tarifa_diaria'];

    // Atributo calculado: la tarifa depende de la categoría
    public function getTarifaDiariaAttribute()
    {
        $c = strtolower(trim((string)$this->categoria));
        return $c === 'moto' ? 45000 : ($c === 'suv' ? 130000 : 90000);
    }
}
