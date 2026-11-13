<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\EntregaMercaderia;
use App\Models\StockMateriaPrima;
use DomainException;
use Throwable;

final class EntregasMercaderiaController
{
    public function index(): void
    {
        if ($this->autorizar() === null) {
            return;
        }
        $filtrosListado = $this->leerFiltrosListado();
        $busqueda = $filtrosListado['q'] ?? '';
        $pagina = $filtrosListado['pagina'] ?? 1;
        $resultado = (new EntregaMercaderia(Conexion::obtener()))->listar($busqueda, $pagina);
        if ($resultado['pagina'] > 1) {
            $filtrosListado['pagina'] = $resultado['pagina'];
        } else {
            unset($filtrosListado['pagina']);
        }
        $aviso = $_SESSION['qba']['aviso_mercaderia'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_mercaderia']);
        }
        Vista::mostrar(
            'equipo-entregas-mercaderia',
            $resultado + [
                'tituloPagina' => 'Entregas de mercadería | Panadería Rustic',
                'layoutEquipo' => true,
                'seccionActiva' => 'stock',
                'busqueda' => $busqueda,
                'aviso' => $aviso,
                'filtrosListado' => $filtrosListado,
            ],
        );
    }

    public function registrar(): void
    {
        $usuario = $this->autorizar();
        if ($usuario === null) {
            return;
        }
        $filtrosListado = $this->leerFiltrosListado();
        $valores = [
            'proveedor' => '',
            'fecha_recepcion' => date('Y-m-d'),
            'comprobante' => '',
            'observaciones' => '',
            'lineas' => [['id_materia' => '', 'cantidad' => '']],
        ];
        $error = '';
        $formularios = $_SESSION['qba']['formularios_mercaderia'] ?? [];
        foreach ($formularios as $tokenFormulario => $creado) {
            if ($creado < time() - 1800) {
                unset($formularios[$tokenFormulario]);
            }
        }
        $token = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $token = Http::texto($_POST, 'token_formulario');
                if (!isset($formularios[$token])) {
                    throw new DomainException(
                        'El formulario venció. Abrí un nuevo registro de entrega.',
                    );
                }
                foreach (
                    ['proveedor', 'fecha_recepcion', 'comprobante', 'observaciones']
                    as $campo
                ) {
                    $valores[$campo] = Http::texto($_POST, $campo);
                }
                $lineas = $_POST['lineas'] ?? null;
                if (
                    !is_array($lineas) ||
                    !array_is_list($lineas) ||
                    count($lineas) < 1 ||
                    count($lineas) > EntregaMercaderia::MAXIMO_LINEAS
                ) {
                    throw new DomainException(
                        'Revisá las materias primas de la entrega (máximo ' .
                            EntregaMercaderia::MAXIMO_LINEAS .
                            ').',
                    );
                }
                $nuevas = [];
                foreach ($lineas as $linea) {
                    if (!is_array($linea)) {
                        throw new DomainException('Revisá las materias primas de la entrega.');
                    }
                    $nuevas[] = [
                        'id_materia' => Http::texto($linea, 'id_materia'),
                        'cantidad' => Http::texto($linea, 'cantidad'),
                    ];
                }
                $valores['lineas'] = $nuevas;
                if (isset($_POST['quitar_linea'])) {
                    $indice = filter_var(Http::texto($_POST, 'quitar_linea'), FILTER_VALIDATE_INT, [
                        'options' => ['min_range' => 0],
                    ]);
                    if ($indice === false || !isset($nuevas[$indice])) {
                        throw new DomainException('Seleccioná una fila válida.');
                    }
                    if (count($nuevas) > 1) {
                        unset($valores['lineas'][$indice]);
                        $valores['lineas'] = array_values($valores['lineas']);
                    }
                } elseif (Http::texto($_POST, 'accion') === 'agregar-linea') {
                    if (count($nuevas) >= EntregaMercaderia::MAXIMO_LINEAS) {
                        throw new DomainException('La entrega admite hasta 20 materias primas.');
                    }
                    $valores['lineas'][] = ['id_materia' => '', 'cantidad' => ''];
                } elseif (Http::texto($_POST, 'accion') === 'registrar') {
                    $id = (new EntregaMercaderia(Conexion::obtener()))->registrar(
                        $valores,
                        (int) $usuario['id_usuario'],
                        $token,
                    );
                    $_SESSION['qba']['aviso_mercaderia'] =
                        'Entrega MER-' .
                        str_pad((string) $id, 3, '0', STR_PAD_LEFT) .
                        ' registrada. El stock quedó actualizado.';
                    Http::redirigir('equipo-mercaderia', $filtrosListado);
                } else {
                    throw new DomainException('Seleccioná una acción válida.');
                }
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU09: falló el registro de mercadería (' . $excepcion::class . ').');
                http_response_code(503);
                $error =
                    'No pudimos registrar la entrega. El stock no se modificó. Intentá nuevamente.';
            }
        } else {
            $token = bin2hex(random_bytes(32));
            $formularios = array_slice($formularios, -9, null, true);
            $formularios[$token] = time();
        }
        $_SESSION['qba']['formularios_mercaderia'] = $formularios;
        Vista::mostrar('equipo-mercaderia-form', [
            'tituloPagina' => 'Registrar mercadería | Panadería Rustic',
            'layoutEquipo' => true,
            'seccionActiva' => 'stock',
            'valores' => $valores,
            'error' => $error,
            'token' => $token,
            'materias' => (new StockMateriaPrima(Conexion::obtener()))->disponibles(),
            'maximoLineas' => EntregaMercaderia::MAXIMO_LINEAS,
            'hoy' => date('Y-m-d'),
            'filtrosListado' => $filtrosListado,
        ]);
    }

    private function leerFiltrosListado(): array
    {
        $busqueda = Http::texto($_GET, 'q');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (!$pagina || !mb_check_encoding($busqueda, 'UTF-8') || mb_strlen($busqueda) > 120) {
            throw new DomainException('Revisá la búsqueda y la página seleccionada.');
        }
        $filtros = [];
        if ($busqueda !== '') {
            $filtros['q'] = $busqueda;
        }
        if ($pagina > 1) {
            $filtros['pagina'] = $pagina;
        }
        return $filtros;
    }

    private function autorizar(): ?array
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (in_array($usuario['rol'] ?? null, ['administrador', 'dueno'], true)) {
            return $usuario;
        }
        http_response_code(403);
        Vista::mostrar('error', [
            'tituloPagina' => 'Acceso no permitido',
            'mensaje' => 'El registro de mercadería está disponible para administrador y dueño.',
            'layoutEquipo' => true,
            'rutaVolver' => 'equipo-panel',
            'textoVolver' => 'Volver al panel',
        ]);
        return null;
    }
}
