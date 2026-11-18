<?php
namespace App;

// Cliente HTTP mínimo: así un servicio le habla a otro
class Http
{
    public static function llamar($metodo, $url, $datos = null)
    {
        $opciones = ['http' => [
            'method' => $metodo,
            'ignore_errors' => true,
            'timeout' => 5,
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer 12345\r\n",
        ]];
        if ($datos !== null) { $opciones['http']['content'] = json_encode($datos); }
        $respuesta = @file_get_contents($url, false, stream_context_create($opciones));
        $codigo = 0;
        if (isset($http_response_header[0]) && preg_match('/ (\d{3})/', $http_response_header[0], $m)) {
            $codigo = (int)$m[1];
        }
        return ['codigo' => $codigo, 'datos' => json_decode($respuesta ?: 'null', true)];
    }
}
