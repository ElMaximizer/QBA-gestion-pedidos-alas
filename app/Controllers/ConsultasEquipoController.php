<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Rutas;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\ConsultaContacto;
use DomainException;
use Throwable;

final class ConsultasEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();
        try {
            $estado = Http::texto($_GET, 'estado');
            $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($pagina === false || !in_array($estado, ['', 'nuevas', 'leidas'], true)) {
                throw new DomainException('Revisá el filtro y la página de consultas.');
            }
            $modelo = new ConsultaContacto(Conexion::obtener());
            $error = '';
            $aviso = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    Sesion::validarCsrf($_POST['csrf'] ?? null);
                    $id = filter_var(Http::texto($_POST, 'id_mensaje'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($id === false || Http::texto($_POST, 'accion') !== 'marcar-leida') {
                        throw new DomainException('Elegí una consulta válida.');
                    }
                    $modelo->marcarLeida($id);
                    $_SESSION['qba']['aviso_consultas'] = 'La consulta quedó marcada como leída.';
                    header('Location: ' . Rutas::url('equipo-consultas', ['estado' => $estado, 'pagina' => $pagina]), true, 303);
                    return;
                } catch (DomainException $excepcion) {
                    http_response_code(422);
                    $error = $excepcion->getMessage();
                }
            } else {
                $aviso = (string) ($_SESSION['qba']['aviso_consultas'] ?? '');
                unset($_SESSION['qba']['aviso_consultas']);
            }
            Vista::mostrar('equipo-consultas', [
                'tituloPagina' => 'Consultas recibidas | Panadería Rustic', 'layoutEquipo' => true, 'seccionActiva' => 'consultas',
                'bandeja' => $modelo->listar($estado, $pagina), 'estado' => $estado, 'error' => $error, 'aviso' => $aviso,
                'rutaBandeja' => 'equipo-consultas', 'parametrosBandeja' => ['estado' => $estado],
            ]);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            self::error($excepcion->getMessage());
        } catch (Throwable $excepcion) {
            error_log('Consultas del equipo: falló la operación (' . $excepcion::class . ').');
            http_response_code(503);
            self::error('No pudimos cargar las consultas. Intentá nuevamente en unos minutos.');
        }
    }

    private static function error(string $mensaje): void
    {
        Vista::mostrar('error', ['tituloPagina' => 'Consultas recibidas', 'mensaje' => $mensaje,
            'layoutEquipo' => true, 'rutaVolver' => 'equipo-panel', 'textoVolver' => 'Volver al panel']);
    }
}
