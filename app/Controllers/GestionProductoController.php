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
    public function index(): void
    {
        if (!$this->autorizar()) {
            return;
        }
        $busqueda = Http::texto($_GET, 'q');
        $estado = Http::texto($_GET, 'estado', '1');
        $pagina = filter_var(Http::texto($_GET, 'pagina', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$pagina) {
            throw new DomainException('Seleccioná una página válida.');
        }
        $resultado = Producto::catalogo()->listarGestion($busqueda, $estado, $pagina);
        $aviso = $_SESSION['qba']['aviso_productos'] ?? '';
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            unset($_SESSION['qba']['aviso_productos']);
        }
        Vista::mostrar('equipo-productos', $resultado + ['tituloPagina' => 'Gestión de productos | Panadería Alas',
            'seccionActiva' => 'productos', 'busqueda' => $busqueda, 'estado' => $estado, 'aviso' => $aviso]);
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
        $producto = $esEdicion ? $this->buscar() : null;
        if ($esEdicion && $producto === null) {
            return;
        }
        $id = $producto['id_producto'] ?? null;
        $valores = $producto === null ? ['nombre' => '', 'categoria' => 'Panes', 'descripcion' => '',
            'precio' => '', 'presentacion' => 'Unidad', 'ingredientes' => '', 'alergenos' => '', 'activo' => '1', 'imagen' => '']
            : array_replace($producto, ['precio' => sprintf('%d.%02d', intdiv($producto['precio'], 100), $producto['precio'] % 100),
                'imagen' => $producto['imagen_guardada']]);
        $version = $producto === null ? '' : Producto::version($producto);
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                foreach (['nombre', 'categoria', 'descripcion', 'precio', 'presentacion', 'ingredientes', 'alergenos', 'activo'] as $campo) {
                    $valores[$campo] = Http::texto($_POST, $campo);
                }
                $version = Http::texto($_POST, 'version');
                $archivo = $_FILES['imagen'] ?? null;
                if ($archivo !== null && !is_array($archivo)) {
                    throw new DomainException('Revisá la imagen seleccionada.');
                }
                $id = (new GestionProducto(Conexion::obtener()))->guardar($valores, $archivo, $id, $version);
                $_SESSION['qba']['aviso_productos'] = $esEdicion ? 'Producto actualizado correctamente.'
                    : 'Producto agregado correctamente: PRD-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT) . '.';
                Http::redirigir('equipo-productos');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU06: falló el guardado del producto (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar el producto en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('equipo-producto-form', ['tituloPagina' => ($esEdicion ? 'Editar' : 'Agregar') . ' producto | Panadería Alas',
            'seccionActiva' => 'productos', 'valores' => $valores, 'id' => $id, 'version' => $version,
            'error' => $error, 'esEdicion' => $esEdicion, 'imagen' => Producto::imagen($valores), 'categorias' => Producto::CATEGORIAS]);
    }

    public function eliminar(): void
    {
        if (!$this->autorizar() || ($producto = $this->buscar()) === null) {
            return;
        }
        $version = Producto::version($producto);
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $version = Http::texto($_POST, 'version');
                (new GestionProducto(Conexion::obtener()))->eliminar($producto['id_producto'], $version);
                $_SESSION['qba']['aviso_productos'] = 'Producto eliminado del catálogo. Los pedidos anteriores conservan su información.';
                Http::redirigir('equipo-productos');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('HU06: falló la eliminación del producto (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos eliminar el producto en este momento. Intentá nuevamente.';
            }
        }
        Vista::mostrar('equipo-producto-eliminar', ['tituloPagina' => 'Eliminar producto | Panadería Alas',
            'seccionActiva' => 'productos', 'producto' => $producto, 'version' => $version, 'error' => $error]);
    }

    private function buscar(): ?array
    {
        $id = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $producto = $id ? Producto::catalogo()->buscarGestion($id) : null;
        if ($producto === null) {
            http_response_code(404);
            Vista::mostrar('error', ['tituloPagina' => 'Producto no encontrado', 'mensaje' => 'Seleccioná un producto disponible del listado.',
                'layoutEquipo' => true, 'rutaVolver' => 'equipo-productos', 'textoVolver' => 'Volver a productos']);
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
        Vista::mostrar('error', ['tituloPagina' => 'Acceso no permitido', 'mensaje' => 'La gestión de productos está disponible para administrador y dueño.',
            'layoutEquipo' => true, 'rutaVolver' => 'equipo-panel', 'textoVolver' => 'Volver al panel']);
        return false;
    }
}
