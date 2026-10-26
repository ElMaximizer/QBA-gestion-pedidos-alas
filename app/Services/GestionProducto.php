<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Producto;
use PDO;
use Throwable;

final class GestionProducto
{
    private readonly Producto $productos;
    private readonly ImagenProducto $imagenes;

    public function __construct(PDO $db)
    {
        $this->productos = new Producto($db);
        $this->imagenes = new ImagenProducto();
    }

    public function guardar(array $datos, ?array $archivo = null, ?int $id = null, string $version = ''): int
    {
        $datos = Producto::validar($datos);
        $imagen = $this->imagenes->guardar($archivo);
        try {
            $resultado = $this->productos->guardarValidado($datos, $imagen, $id, $version);
        } catch (Throwable $error) {
            if ($imagen !== null) {
                $this->imagenes->retirar($imagen);
            }
            throw $error;
        }
        if ($imagen !== null) {
            $this->imagenes->retirar($resultado['imagen_anterior']);
        }
        return $resultado['id_producto'];
    }

    public function eliminar(int $id, string $version): void
    {
        $this->imagenes->retirar($this->productos->archivar($id, $version));
    }
}
