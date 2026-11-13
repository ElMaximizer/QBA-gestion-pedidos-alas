<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Transferencia;
use App\Core\Vista;
use App\Models\ComprobanteTransferencia;
use App\Models\PedidosCliente;
use DomainException;
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

        $aviso = (string) ($_SESSION['qba']['aviso_transferencia_cliente'] ?? '');
        $errorPago = (string) ($_SESSION['qba']['error_transferencia_cliente'] ?? '');
        unset($_SESSION['qba']['aviso_transferencia_cliente'], $_SESSION['qba']['error_transferencia_cliente']);

        Vista::mostrar('cliente-pedidos', [
            'tituloPagina' => 'Mis pedidos | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'pedidos' => $pedidos,
            'transferencia' => Transferencia::configuracion(),
            'avisoTransferencia' => $aviso,
            'errorTransferencia' => $errorPago,
        ]);
    }

    public function informarTransferencia(): void
    {
        header('Cache-Control: no-store');
        $cliente = Sesion::cliente();
        if ($cliente === null) {
            Http::redirigir('cliente-login');
        }

        try {
            Sesion::validarCsrf($_POST['csrf'] ?? null);
            $idPedido = filter_var($_POST['id_pedido'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if ($idPedido === false) {
                throw new DomainException('Seleccioná un pedido válido.');
            }

            $archivo = $_FILES['comprobante'] ?? null;
            if (!is_array($archivo) || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK
                || !is_string($archivo['tmp_name'] ?? null) || !is_uploaded_file($archivo['tmp_name'])) {
                throw new DomainException('Adjuntá la imagen del comprobante para avisar al equipo.');
            }
            $tamano = (int) ($archivo['size'] ?? 0);
            if ($tamano < 1 || $tamano > 2 * 1024 * 1024) {
                throw new DomainException('La imagen debe pesar como máximo 2 MB.');
            }
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
            if (!is_string($mime) || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new DomainException('Adjuntá una imagen JPG, PNG o WebP.');
            }
            $imagen = getimagesize($archivo['tmp_name']);
            if (!is_array($imagen) || ($imagen['mime'] ?? '') !== $mime) {
                throw new DomainException('El archivo adjunto no parece ser una imagen válida.');
            }
            $contenido = file_get_contents($archivo['tmp_name']);
            if (!is_string($contenido)) {
                throw new DomainException('No pudimos leer el comprobante. Elegí la imagen nuevamente.');
            }
            (new ComprobanteTransferencia(Conexion::obtener()))->guardar(
                (int) $idPedido,
                (int) $cliente['id_cliente'],
                $mime,
                $contenido,
            );
            $_SESSION['qba']['aviso_transferencia_cliente'] =
                'Avisamos al equipo. El pago queda pendiente de aprobación hasta que confirme la recepción.';
        } catch (DomainException $excepcion) {
            $_SESSION['qba']['error_transferencia_cliente'] = $excepcion->getMessage();
        } catch (Throwable $excepcion) {
            error_log('Transferencia del cliente: falló el aviso (' . $excepcion::class . ').');
            $_SESSION['qba']['error_transferencia_cliente'] =
                'No pudimos avisar al equipo. Intentá nuevamente desde Mis pedidos.';
        }

        Http::redirigir('cliente-pedidos');
    }
}
