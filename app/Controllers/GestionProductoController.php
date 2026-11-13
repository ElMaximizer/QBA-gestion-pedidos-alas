<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Producto;
use App\Services\GestionProducto;
use DomainException;
use Throwable;

final class GestionProductoController
{
    public function index(string $error = ''): void
    {
        if (!$this->autorizar()) {
            return;
        }
        $filtrosListado = $this->leerFiltrosListado();
        $busqueda = $filtrosListado['q'] ?? '';
        $estado = $filtrosListado['estado'] ?? '1';
        $pagina = $filtrosListado['pagina'] ?? 1;
        $resultado = Producto::catalogo()->listarGestion($busqueda, $estado, $pagina);
        if ($resultado['pagina'] > 1) {
            $filtrosListado['pagina'] = $resultado['pagina'];
        } else {
            unset($filtrosListado['pagina']);
        }
        $aviso = $_SESSION['qba']['aviso_productos'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_productos']);
        }
        Vista::mostrar(
            'equipo-productos',
            $resultado + [
                'tituloPagina' => 'Gestión de productos | Panadería Rustic',
                'seccionActiva' => 'productos',
                'busqueda' => $busqueda,
                'estado' => $estado,
                'aviso' => $aviso,
                'error' => $error,
                'filtrosListado' => $filtrosListado,
            ],
        );
    }

    public function agregar(): void
    {
        $this->formulario(false);
    }

    public function editar(): void
    {
        $this->formulario(true);
    }

    private function formulario(bool $esEdicion): void
    {
        if (!$this->autorizar()) {
            return;
        }
        $filtrosListado = $this->leerFiltrosListado();
        $producto = $esEdicion ? $this->buscar($filtrosListado) : null;
        if ($esEdicion && $producto === null) {
            return;
        }
        $id = $producto['id_producto'] ?? null;
        $valores =
            $producto === null
                ? [
                    'nombre' => '',
                    'categoria' => 'Panes',
                    'descripcion' => '',
                    'precio' => '',
                    'presentacion' => 'Unidad',
                    'ingredientes' => '',
                    'alergenos' => '',
                    'activo' => '1',
                    'imagen' => '',
                ]
                : array_replace($producto, [
                    'precio' => sprintf(
                        '%d.%02d',
                        intdiv($producto['precio'], 100),
                        $producto['precio'] % 100,
                    ),
                    'imagen' => $producto['imagen_guardada'],
                ]);
        $version = $producto === null ? '' : Producto::version($producto);
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                foreach (
                    [
                        'nombre',
                        'categoria',
                        'descripcion',
                        'precio',
                        'presentacion',
                        'ingredientes',
                        'alergenos',
                        'activo',
                    ]
                    as $campo
                ) {
                    $valores[$campo] = Http::texto($_POST, $campo);
                }
                $version = Http::texto($_POST, 'version');
                $archivo = $_FILES['imagen'] ?? null;
                if ($archivo !== null && !is_array($archivo)) {
                    throw new DomainException('Revisá la imagen seleccionada.');
                }
                $id = (new GestionProducto(Conexion::obtener()))->guardar(
                    $valores,
                    $archivo,
                    $id,
                    $version,
                );
                $_SESSION['qba']['aviso_productos'] = $esEdicion
                    ? 'Producto PRD-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT) . ' actualizado correctamente.'
                    : 'Producto agregado correctamente: PRD-' .
                        str_pad((string) $id, 3, '0', STR_PAD_LEFT) .
                        '.';
                Http::redirigir('equipo-productos', $filtrosListado);
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU06: falló el guardado del producto (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar el producto en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('equipo-producto-form', [
            'tituloPagina' => ($esEdicion ? 'Editar' : 'Agregar') . ' producto | Panadería Rustic',
            'seccionActiva' => 'productos',
            'valores' => $valores,
            'id' => $id,
            'version' => $version,
            'error' => $error,
            'esEdicion' => $esEdicion,
            'imagen' => Producto::imagen($valores),
            'categorias' => Producto::CATEGORIAS,
            'filtrosListado' => $filtrosListado,
        ]);
    }

    public function estado(): void
    {
        if (!$this->autorizar()) {
            return;
        }
        $filtros = $this->leerFiltrosListado();
        $producto = $this->buscar($filtros);
        if ($producto === null) {
            return;
        }
        try {
            Sesion::validarCsrf($_POST['csrf'] ?? null);
            $activo = Http::texto($_POST, 'activo');
            Producto::catalogo()->cambiarEstado(
                $producto['id_producto'],
                $activo,
                Http::texto($_POST, 'version'),
            );
            $_SESSION['qba']['aviso_productos'] = $activo === '1'
                ? 'Producto activado correctamente. Ya está disponible en el catálogo.'
                : 'Producto desactivado correctamente. Podés consultarlo con el filtro Inactivos.';
            Http::redirigir('equipo-productos', $filtros);
        } catch (DomainException $excepcion) {
            http_response_code(422);
            $error = $excepcion->getMessage();
        } catch (Throwable $excepcion) {
            error_log('HU06: falló el cambio de estado del producto (' . $excepcion::class . ').');
            http_response_code(503);
            $error = 'No pudimos cambiar el estado del producto en este momento. Intentá nuevamente.';
        }
        $this->index($error);
    }

    public function eliminar(): void
    {
        if (!$this->autorizar()) {
            return;
        }
        $filtrosListado = $this->leerFiltrosListado();
        $producto = $this->buscar($filtrosListado);
        if ($producto === null) {
            return;
        }
        $version = Producto::version($producto);
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $version = Http::texto($_POST, 'version');
                (new GestionProducto(Conexion::obtener()))->eliminar(
                    $producto['id_producto'],
                    $version,
                );
                $_SESSION['qba']['aviso_productos'] =
                    'Producto PRD-' . str_pad((string) $producto['id_producto'], 3, '0', STR_PAD_LEFT) . ' eliminado del catálogo. Los pedidos anteriores conservan su información.';
                Http::redirigir('equipo-productos', $filtrosListado);
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU06: falló la eliminación del producto (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos eliminar el producto en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('equipo-producto-eliminar', [
            'tituloPagina' => 'Eliminar producto | Panadería Rustic',
            'seccionActiva' => 'productos',
            'producto' => $producto,
            'version' => $version,
            'error' => $error,
            'filtrosListado' => $filtrosListado,
        ]);
    }

    private function leerFiltrosListado(): array
    {
        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado', '1');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if (
            !$pagina
            || !mb_check_encoding($busqueda, 'UTF-8')
            || mb_strlen($busqueda) > 120
            || !in_array($estado, ['', '0', '1'], true)
        ) {
            throw new DomainException('Revisá la búsqueda, el estado y la página seleccionada.');
        }
        $filtros = [];
        if ($busqueda !== '') {
            $filtros['q'] = $busqueda;
        }
        if ($estado !== '1') {
            $filtros['estado'] = $estado;
        }
        if ($pagina > 1) {
            $filtros['pagina'] = $pagina;
        }
        return $filtros;
    }

    private function buscar(array $filtrosListado): ?array
    {
        $id = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $producto = $id ? Producto::catalogo()->buscarGestion($id) : null;
        if ($producto === null) {
            http_response_code(404);
            Vista::mostrar('error', [
                'tituloPagina' => 'Producto no encontrado',
                'mensaje' => 'Seleccioná un producto disponible del listado.',
                'layoutEquipo' => true,
                'rutaVolver' => 'equipo-productos',
                'textoVolver' => 'Volver a productos',
                'parametrosVolver' => $filtrosListado,
            ]);
        }
        return $producto;
    }

    private function autorizar(): bool
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();
        if (Sesion::administraProductos()) {
            return true;
        }
        http_response_code(403);
        Vista::mostrar('error', [
            'tituloPagina' => 'Acceso no permitido',
            'mensaje' => 'La gestión de productos está disponible para administrador y dueño.',
            'layoutEquipo' => true,
            'rutaVolver' => 'equipo-panel',
            'textoVolver' => 'Volver al panel',
        ]);
        return false;
    }
}
