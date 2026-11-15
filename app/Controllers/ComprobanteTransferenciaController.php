<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Models\ComprobanteTransferencia;

final class ComprobanteTransferenciaController
{
    public function ver(): void
    {
        header('Cache-Control: no-store, private');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'] ?? '', ['empleado', 'administrador', 'dueno'], true)) {
            http_response_code(403);
            exit('No tenés permiso para revisar comprobantes.');
        }
        $idPedido = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($idPedido === false) {
            http_response_code(404);
            exit('Comprobante no encontrado.');
        }
        $comprobante = (new ComprobanteTransferencia(Conexion::obtener()))->obtener((int) $idPedido);
        if ($comprobante === null) {
            http_response_code(404);
            exit('Comprobante no encontrado.');
        }
        header('Content-Type: ' . $comprobante['tipo_mime']);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="comprobante-pedido-' . (int) $idPedido . '"');
        echo $comprobante['contenido'];
    }
}
