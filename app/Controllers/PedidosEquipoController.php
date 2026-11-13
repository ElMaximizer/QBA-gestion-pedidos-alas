<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Http;
use App\Core\Conexion;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ConsultaPedidos;
use App\Models\Pedido;
use DomainException;
use Throwable;

final class PedidosEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        $puedeRegistrarSena = in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true);
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$puedeRegistrarSena) {
                http_response_code(403);
                Vista::mostrar('error', ['tituloPagina' => 'Acceso no permitido',
                    'mensaje' => 'Solo administrador y dueño pueden registrar la seña.',
                    'layoutEquipo' => true, 'rutaVolver' => 'equipo-pedidos', 'textoVolver' => 'Volver a pedidos']);
                return;
            }

            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $idPedido = filter_var(Http::texto($_POST, 'id_pedido'), FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]);
                $valorSena = $_POST['sena_recibida'] ?? null;
                if (!$idPedido || !is_string($valorSena) || !in_array($valorSena, ['0', '1'], true)) {
                    throw new DomainException('Revisá el pedido y el estado de la seña.');
                }

                (new ConsultaPedidos(Conexion::obtener()))->registrarSena($idPedido, $valorSena === '1');
                $_SESSION['qba']['aviso_pedidos'] = $valorSena === '1'
                    ? 'Se registró que el pedido recibió la seña.' : 'Se registró que el pedido no recibió la seña.';
                Http::redirigir('equipo-pedidos');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU05: falló el registro de la seña (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar el estado de la seña. Intentá nuevamente.';
            }
        }

        $aviso = $_SESSION['qba']['aviso_pedidos'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_pedidos']);
        }

        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado');
        $fecha = Http::texto($_GET, 'fecha');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$pagina) {
            throw new \DomainException('Seleccioná una página válida.');
        }

        $resultado = (new ConsultaPedidos(Conexion::obtener()))->listar($busqueda, $estado, $fecha, $pagina);
        $resultado['pedidos'] = array_map(static fn (array $pedido): array => $pedido + [
            'puede_modificar' => Pedido::puedeModificar($usuario['rol'], $pedido['estado']),
        ], $resultado['pedidos']);
        Vista::mostrar('equipo-pedidos', $resultado + [
            'tituloPagina' => 'Consultar pedidos | Panadería Rustic',
            'seccionActiva' => 'pedidos',
            'layoutEquipo' => true,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'fecha' => $fecha,
            'puedeRegistrarSena' => $puedeRegistrarSena,
            'puedeRegistrarPedido' => in_array($usuario['rol'], ['administrador', 'dueno'], true),
            'estados' => Pedido::ESTADOS,
            'aviso' => $aviso,
            'error' => $error,
        ]);
    }
}
