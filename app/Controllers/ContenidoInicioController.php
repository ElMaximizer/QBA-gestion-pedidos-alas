<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Producto;
use DomainException;
use Throwable;
final class ContenidoInicioController
{
    public function index(): void
    {
        header('Cache-Control: no-store');
        Sesion::requerirEquipo();
        if (!Sesion::administraProductos()) {
            http_response_code(403);
            Vista::mostrar('error', ['tituloPagina' => 'Acceso no permitido', 'mensaje' => 'Solo administrador y dueño pueden elegir el contenido del inicio.', 'layoutEquipo' => true, 'rutaVolver' => 'equipo-panel', 'textoVolver' => 'Volver al panel']);
            return;
        }
        $modelo = Producto::catalogo();
        $productos = $modelo->listarGestionInicio();
        $seleccionFavoritos = array_values(array_map(static fn (array $p): int => $p['id_producto'], array_filter($productos, static fn (array $p): bool => (int) $p['destacado'] === 1)));
        $seleccionPortada = array_values(array_map(static fn (array $p): int => $p['id_producto'], array_filter($productos, static fn (array $p): bool => $p['portada_orden'] !== null)));
        if ($seleccionPortada === []) { $seleccionPortada = array_slice($seleccionFavoritos, 0, 3); }
        $error = '';
        $aviso = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $seleccionFavoritos = $this->ids($_POST['favoritos'] ?? []);
                $seleccionPortada = $this->ids($_POST['portada'] ?? []);
                $modelo->guardarSeleccionInicio($seleccionFavoritos, $seleccionPortada);
                $_SESSION['qba']['aviso_contenido_inicio'] = 'Guardamos los favoritos y las imágenes del carrusel.';
                Http::redirigir('equipo-contenido-inicio');
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log('Contenido del inicio: falló el guardado (' . $excepcion::class . ').');
                http_response_code(503);
                $error = 'No pudimos guardar la selección. Intentá nuevamente.';
            }
        } else {
            $aviso = (string) ($_SESSION['qba']['aviso_contenido_inicio'] ?? '');
            unset($_SESSION['qba']['aviso_contenido_inicio']);
        }
        Vista::mostrar('equipo-contenido-inicio', ['tituloPagina' => 'Contenido del inicio | Panadería Alas', 'seccionActiva' => 'resumen', 'productos' => $productos, 'seleccionFavoritos' => $seleccionFavoritos, 'seleccionPortada' => $seleccionPortada, 'error' => $error, 'aviso' => $aviso]);
    }
    private function ids(mixed $entrada): array
    {
        if (!is_array($entrada)) { throw new DomainException('Revisá la selección de productos.'); }
        $ids = [];
        foreach ($entrada as $valor) {
            if (!is_string($valor) || !ctype_digit($valor) || (int) $valor < 1) { throw new DomainException('Revisá la selección de productos.'); }
            $id = (int) $valor;
            if (in_array($id, $ids, true)) { throw new DomainException('Hay productos repetidos en la selección.'); }
            $ids[] = $id;
        }
        return $ids;
    }
}