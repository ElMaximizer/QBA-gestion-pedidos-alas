<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\PedidosCliente;
use Throwable;

final class PedidosClienteController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            Http::redirigir('cliente-login');
        }

        try {
            $pedidos = (new PedidosCliente(Conexion::obtener()))->listar((int) $cliente['id_cliente']);
        } catch (Throwable $error) {
            error_log('HU20: falló la consulta del historial (' . $error::class . ').');
            http_response_code(503);
            Vista::mostrar('error', [
                'tituloPagina' => 'Mis pedidos | Panadería Rustic',
                'mensaje' => 'No pudimos cargar tus pedidos en este momento. Intentá nuevamente.',
                'rutaVolver' => 'inicio',
                'textoVolver' => 'Volver al inicio',
            ]);
            return;
        }

        Vista::mostrar('cliente-pedidos', [
            'tituloPagina' => 'Mis pedidos | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'pedidos' => $pedidos,
        ]);
    }
}
