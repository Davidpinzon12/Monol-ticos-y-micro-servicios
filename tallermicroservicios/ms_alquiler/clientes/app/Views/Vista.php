<?php
namespace App\Views;

// Vista (la V de MVC): en una API la "vista" es la representación JSON con su código HTTP
class Vista
{
    public static function responder($res, $datos, $codigo = 200)
    {
        $res->getBody()->write(json_encode($datos, JSON_UNESCAPED_UNICODE));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($codigo);
    }

    public static function error($res, $mensaje, $codigo)
    {
        return self::responder($res, ['error' => $mensaje], $codigo);
    }
}
