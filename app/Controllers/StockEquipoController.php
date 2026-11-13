<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\StockMateriaPrima;
use DomainException;
use Throwable;

final class StockEquipoController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true)) {
            http_response_code(403);
            Vista::mostrar('error', ['tituloPagina' => 'Acceso no permitido',
                'mensaje' => 'El control de stock está disponible para administrador y dueño.',
                'layoutEquipo' => true, 'rutaVolver' => 'equipo-panel', 'textoVolver' => 'Volver al panel']);
            return;
        }

        $modelo = new StockMateriaPrima(Conexion::obtener());
        $error = '';
        $valores = ['nombre' => '', 'unidad' => 'kg', 'stock_actual' => '0', 'stock_minimo' => '0'];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $accion = Http::texto($_POST, 'accion');
                if ($accion === 'agregar') {
                    foreach (array_keys($valores) as $campo) {
                        $valores[$campo] = Http::texto($_POST, $campo);
                    }
                    $modelo->agregar($valores);
                    $_SESSION['qba']['aviso_stock'] = 'Materia prima agregada al inventario.';
                } elseif ($accion === 'actualizar') {
                    $id = filter_var(Http::texto($_POST, 'id_materia'), FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]);
                    if (!$id) {
                        throw new DomainException('Seleccioná una materia prima válida.');
                    }
                    $modelo->actualizar($id, [
                        'stock_actual' => Http::texto($_POST, 'stock_actual'),
                        'stock_minimo' => Http::texto($_POST, 'stock_minimo'),
                    ], Http::texto($_POST, 'version'));
                    $_SESSION['qba']['aviso_stock'] = 'Stock actualizado correctamente.';
                } else {
                    throw new DomainException('Seleccioná una acción válida.');
                }
                Http::redirigir('equipo-stock');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU07: falló la operación de stock (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar el cambio de stock. Intentá nuevamente.';
            }
        }

        $aviso = $_SESSION['qba']['aviso_stock'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_stock']);
        }
        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]);
        if (!$pagina) {
            throw new DomainException('Seleccioná una página válida.');
        }
        $resultado = $modelo->listar($busqueda, $estado, $pagina);
        Vista::mostrar('equipo-stock', $resultado + [
            'tituloPagina' => 'Controlar stock | Panadería Rustic', 'seccionActiva' => 'stock', 'layoutEquipo' => true,
            'busqueda' => $busqueda, 'estado' => $estado, 'aviso' => $aviso, 'error' => $error,
            'valores' => $valores, 'unidades' => StockMateriaPrima::UNIDADES,
        ]);
    }
}
