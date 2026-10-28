<?php
declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use DomainException;
use PDO;
use Throwable;

final class Pedido
{
    public function __construct(private readonly PDO $db)
    {
    }

    public const MAXIMO_PRODUCTOS = 50;
    public const MAXIMA_CANTIDAD = 99;
    public const ESTADOS = ['Pendiente', 'En preparación', 'Listo para retirar', 'Entregado', 'Cancelado'];

    public function crear(array $datos, array $cantidades, string $firmaEsperada, string $token,
        ?int $idCliente = null, ?int $idUsuarioRegistro = null): int
    {
        self::validar($datos);
        if (!$cantidades || count($cantidades) > self::MAXIMO_PRODUCTOS) {
            throw new DomainException('Agregá entre 1 y 50 productos antes de confirmar el pedido.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)
            || ($idCliente !== null && $idUsuarioRegistro !== null)) {
            throw new DomainException('Volvé a abrir el formulario para registrar el pedido.');
        }
        $this->db->beginTransaction();
        try {
            if ($idUsuarioRegistro !== null) {
                $consulta = $this->db->prepare("SELECT id_usuario FROM usuarios_equipo WHERE id_usuario = ?
                    AND estado = 'aprobado' AND rol IN ('administrador', 'dueno') LOCK IN SHARE MODE");
                $consulta->execute([$idUsuarioRegistro]);
                if (!$consulta->fetchColumn()) {
                    throw new DomainException('Tu cuenta no tiene permiso para registrar pedidos del equipo.');
                }
            }
            $consulta = $this->db->prepare('SELECT id_pedido, id_cliente, id_usuario_registro FROM pedidos WHERE token_confirmacion = ?');
            $consulta->execute([$token]);
            if ($existente = $consulta->fetch()) {
                $propietario = $existente['id_cliente'] === null ? null : (int) $existente['id_cliente'];
                $responsable = $existente['id_usuario_registro'] === null ? null : (int) $existente['id_usuario_registro'];
                if ($propietario !== $idCliente || $responsable !== $idUsuarioRegistro) {
                    throw new DomainException('El formulario pertenece a otra cuenta. Volvé a abrir el pedido.');
                }
                $this->db->commit();
                return (int) $existente['id_pedido'];
            }
            if ($idCliente !== null) {
                $consulta = $this->db->prepare('SELECT id_cliente FROM clientes
                    WHERE id_cliente = ? AND verificado = 1 LOCK IN SHARE MODE');
                $consulta->execute([$idCliente]);
                if (!$consulta->fetchColumn()) {
                    throw new DomainException('La cuenta ya no está disponible. Volvé a iniciar sesión.');
                }
            }
            $productos = (new Producto($this->db))->buscarVarios(array_keys($cantidades), true);
            if (count($productos) !== count($cantidades)) {
                throw new DomainException('Un producto ya no está disponible. Revisá los productos del pedido.');
            }
            $lineas = [];
            $total = 0;
            foreach ($cantidades as $id => $cantidad) {
                if (!is_int($cantidad) || $cantidad < 1 || $cantidad > self::MAXIMA_CANTIDAD) {
                    throw new DomainException('Las cantidades deben estar entre 1 y 99.');
                }
                $linea = $productos[$id];
                $linea['cantidad'] = $cantidad;
                $total += $linea['precio'] * $cantidad;
                $lineas[] = $linea;
            }
            if (!hash_equals($firmaEsperada, Carrito::firma($lineas))) {
                throw new DomainException('Los productos o sus precios cambiaron. Revisá el pedido antes de confirmar nuevamente.');
            }
            if ($total > 9999999999) {
                throw new DomainException('El total supera el máximo permitido.');
            }
            $consulta = $this->db->prepare('INSERT INTO pedidos
                (id_cliente, id_usuario_registro, cliente_nombre, telefono, fecha_retiro, hora_retiro, notas, total, token_confirmacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $consulta->execute([$idCliente, $idUsuarioRegistro, $datos['cliente_nombre'], $datos['telefono'], $datos['fecha_retiro'],
                $datos['hora_retiro'], $datos['notas'], self::decimal($total), $token]);
            $idPedido = (int) $this->db->lastInsertId();
            $this->guardarDetalles($idPedido, $lineas);
            $this->db->commit();
            return $idPedido;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    /** Normaliza las líneas del formulario interno sin depender del carrito de la sesión. */
    public static function cantidadesDesdeLineas(array $lineas, bool $permitirHistoricos = false): array
    {
        if (!array_is_list($lineas) || !$lineas || count($lineas) > self::MAXIMO_PRODUCTOS) {
            throw new DomainException('El pedido admite entre 1 y 50 productos distintos.');
        }
        $cantidades = [];
        foreach ($lineas as $linea) {
            if (!is_array($linea)) throw new DomainException('Revisá los productos del pedido.');
            $id = filter_var($linea['id_producto'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if (!$id && $permitirHistoricos && is_string($linea['id_producto'] ?? null)
                && preg_match('/^historico:[1-9][0-9]*$/D', $linea['id_producto'])) {
                $id = $linea['id_producto'];
            }
            $cantidad = filter_var($linea['cantidad'] ?? null, FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => self::MAXIMA_CANTIDAD]]);
            if (!$id || !$cantidad) {
                throw new DomainException('Seleccioná un producto por línea y una cantidad entre 1 y 99.');
            }
            if (isset($cantidades[$id])) {
                throw new DomainException('Hay un producto repetido. Usá una sola línea por producto y ajustá su cantidad.');
            }
            $cantidades[$id] = $cantidad;
        }
        return $cantidades;
    }

    public static function validar(array $datos, bool $exigirRetiroFuturo = true): void
    {
        foreach (['cliente_nombre' => 120, 'telefono' => 30, 'fecha_retiro' => 10, 'hora_retiro' => 5, 'notas' => 2000] as $campo => $maximo) {
            $texto = $datos[$campo] ?? null;
            if (!is_string($texto) || !mb_check_encoding($texto, 'UTF-8') || mb_strlen($texto) > $maximo
                || ($campo !== 'notas' && trim($texto) === '')) {
                throw new DomainException('Completá los campos obligatorios y respetá sus longitudes máximas.');
            }
        }
        $telefono = $datos['telefono'];
        $digitos = preg_replace('/\D/', '', $telefono);
        if (!preg_match('/^\+?[\d\s()\-]+$/', $telefono) || strlen($digitos) < 6 || strlen($digitos) > 15) {
            throw new DomainException('Ingresá un teléfono de contacto válido.');
        }
        $fechaHora = $datos['fecha_retiro'] . ' ' . $datos['hora_retiro'];
        $retiro = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $fechaHora);
        if (!$retiro || $retiro->format('Y-m-d H:i') !== $fechaHora || (int) $retiro->format('Y') < 1000) {
            throw new DomainException('Ingresá una fecha y hora de retiro válidas.');
        }
        if ($exigirRetiroFuturo && $retiro <= new DateTimeImmutable()) {
            throw new DomainException('Ingresá una fecha y hora de retiro futuras y válidas.');
        }
    }

    public function buscarGestion(int $id, bool $bloquear = false): ?array
    {
        $consulta = $this->db->prepare('SELECT * FROM pedidos WHERE id_pedido = ?' . ($bloquear ? ' FOR UPDATE' : ''));
        $consulta->execute([$id]);
        $pedido = $consulta->fetch();
        if (!$pedido) return null;
        $consulta = $this->db->prepare('SELECT * FROM pedido_detalles WHERE id_pedido = ? ORDER BY id_detalle'
            . ($bloquear ? ' FOR UPDATE' : ''));
        $consulta->execute([$id]);
        $pedido['id_pedido'] = (int) $pedido['id_pedido'];
        $pedido['hora_retiro'] = substr($pedido['hora_retiro'], 0, 5);
        $pedido['total_centavos'] = self::centavos($pedido['total']);
        $pedido['lineas'] = [];
        foreach ($consulta->fetchAll() as $detalle) {
            $idProducto = $detalle['id_producto'] === null ? null : (int) $detalle['id_producto'];
            $pedido['lineas'][] = ['id_detalle' => (int) $detalle['id_detalle'], 'id_producto' => $idProducto,
                'clave_producto' => $idProducto === null ? 'historico:' . $detalle['id_detalle'] : (string) $idProducto,
                'nombre' => $detalle['nombre_producto'], 'cantidad' => (int) $detalle['cantidad'],
                'precio' => self::centavos($detalle['precio_unitario'])];
        }
        return $pedido;
    }

    /** Los productos ya pedidos conservan nombre y precio, incluso fuera del catálogo. */
    public static function opcionesGestion(array $productos, ?array $pedido): array
    {
        $opciones = array_column($productos, null, 'id_producto');
        foreach ($pedido['lineas'] ?? [] as $linea) {
            $clave = $linea['clave_producto'];
            $presentacion = $opciones[$clave]['presentacion'] ?? 'Histórico';
            $opciones[$clave] = ['id_producto' => $clave, 'nombre' => $linea['nombre'], 'precio' => $linea['precio'],
                'presentacion' => $presentacion . ' · precio del pedido'];
        }
        return array_values($opciones);
    }

    public static function version(array $pedido): string
    {
        return hash('sha256', serialize([self::instantanea($pedido), array_column($pedido['lineas'], 'id_detalle')]));
    }

    public static function puedeModificar(string $rol, string $estado): bool
    {
        return $rol === 'dueno' || ($rol === 'administrador'
            && in_array($estado, ['Pendiente', 'En preparación', 'Listo para retirar'], true));
    }

    public function modificar(int $id, array $datos, array $cantidades, string $firmaEsperada,
        string $version, string $token, int $idResponsable, string $motivo = ''): void
    {
        self::validar($datos, false);
        if (!in_array($datos['estado'] ?? null, self::ESTADOS, true) || !mb_check_encoding($motivo, 'UTF-8')
            || mb_strlen($motivo) > 500 || !$cantidades || count($cantidades) > self::MAXIMO_PRODUCTOS
            || !preg_match('/^[a-f0-9]{64}$/D', $token)) {
            throw new DomainException('Revisá el estado, los productos y el motivo de la modificación (hasta 500 caracteres).');
        }
        $this->db->beginTransaction();
        try {
            $consulta = $this->db->prepare("SELECT nombre, rol FROM usuarios_equipo WHERE id_usuario = ?
                AND estado = 'aprobado' AND rol IN ('administrador', 'dueno') LOCK IN SHARE MODE");
            $consulta->execute([$idResponsable]);
            $responsable = $consulta->fetch(PDO::FETCH_ASSOC);
            if ($responsable === false) throw new DomainException('Solo administrador y dueño pueden modificar pedidos.');
            $consulta = $this->db->prepare('SELECT id_pedido, id_usuario FROM pedido_cambios WHERE token_formulario = ?');
            $consulta->execute([$token]);
            if ($cambio = $consulta->fetch()) {
                if ((int) $cambio['id_pedido'] !== $id || (int) $cambio['id_usuario'] !== $idResponsable) {
                    throw new DomainException('El formulario pertenece a otro pedido o a otra cuenta.');
                }
                $this->db->commit();
                return;
            }
            $pedido = $this->buscarGestion($id, true);
            if ($pedido === null) throw new DomainException('El pedido ya no está disponible.');
            if (!self::puedeModificar($responsable['rol'], $pedido['estado'])) {
                throw new DomainException('Solo el dueño puede corregir pedidos entregados o cancelados.');
            }
            if (!hash_equals(self::version($pedido), $version)) {
                throw new DomainException('El pedido fue modificado desde otra pantalla. Volvé a abrirlo para revisar los cambios antes de guardar.');
            }
            $anteriores = array_column($pedido['lineas'], null, 'clave_producto');
            $idsNuevos = array_keys(array_diff_key($cantidades, $anteriores));
            foreach ($idsNuevos as $idProducto) {
                if (!is_int($idProducto) || $idProducto < 1) {
                    throw new DomainException('Solo podés conservar productos históricos que pertenecen a este pedido.');
                }
            }
            $nuevos = (new Producto($this->db))->buscarVarios($idsNuevos, true);
            if (count($nuevos) !== count($idsNuevos)) {
                throw new DomainException('Un producto nuevo ya no está disponible. Revisá las líneas del pedido.');
            }
            $lineas = [];
            $firma = [];
            $total = 0;
            foreach ($cantidades as $clave => $cantidad) {
                if (!is_int($cantidad) || $cantidad < 1 || $cantidad > self::MAXIMA_CANTIDAD) {
                    throw new DomainException('Las cantidades deben estar entre 1 y 99.');
                }
                $linea = $anteriores[$clave] ?? $nuevos[$clave];
                $linea['cantidad'] = $cantidad;
                $lineas[] = $linea;
                $total += $linea['precio'] * $cantidad;
                $firma[] = ['id_producto' => $clave, 'cantidad' => $cantidad, 'precio' => $linea['precio']];
            }
            if (!hash_equals($firmaEsperada, Carrito::firma($firma))) {
                throw new DomainException('El precio de un producto nuevo cambió. Revisá el total antes de confirmar nuevamente.');
            }
            if ($total > 9999999999) throw new DomainException('El total supera el máximo permitido.');
            $antes = self::instantanea($pedido);
            $despues = self::instantanea(array_replace($datos, ['lineas' => $lineas, 'total_centavos' => $total]));
            if ($antes === $despues) {
                $this->db->commit();
                return;
            }
            $consulta = $this->db->prepare('UPDATE pedidos SET cliente_nombre = ?, telefono = ?, fecha_retiro = ?,
                hora_retiro = ?, notas = ?, estado = ?, total = ? WHERE id_pedido = ?');
            $consulta->execute([$datos['cliente_nombre'], $datos['telefono'], $datos['fecha_retiro'], $datos['hora_retiro'],
                $datos['notas'], $datos['estado'], self::decimal($total), $id]);
            $this->db->prepare('DELETE FROM pedido_detalles WHERE id_pedido = ?')->execute([$id]);
            $this->guardarDetalles($id, $lineas);
            $consulta = $this->db->prepare('INSERT INTO pedido_cambios
                (id_pedido, id_usuario, nombre_responsable, motivo, antes, despues, token_formulario) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $consulta->execute([$id, $idResponsable, $responsable['nombre'], $motivo,
                json_encode($antes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                json_encode($despues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), $token]);
            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function historial(int $id, int $pagina = 1): array
    {
        if ($pagina < 1) throw new DomainException('Seleccioná una página válida del historial.');
        $consulta = $this->db->prepare('SELECT COUNT(*) FROM pedido_cambios WHERE id_pedido = ?');
        $consulta->execute([$id]);
        $total = (int) $consulta->fetchColumn();
        $paginas = max(1, (int) ceil($total / 10));
        $pagina = min($pagina, $paginas);
        $consulta = $this->db->prepare('SELECT nombre_responsable, motivo, antes, despues, UNIX_TIMESTAMP(creado_en) AS creado_en_unix
            FROM pedido_cambios WHERE id_pedido = ? ORDER BY id_cambio DESC LIMIT 10 OFFSET ?');
        $consulta->bindValue(1, $id, PDO::PARAM_INT);
        $consulta->bindValue(2, ($pagina - 1) * 10, PDO::PARAM_INT);
        $consulta->execute();
        $cambios = $consulta->fetchAll();
        foreach ($cambios as &$cambio) {
            foreach (['antes', 'despues'] as $momento) $cambio[$momento] = json_decode($cambio[$momento], true, 512, JSON_THROW_ON_ERROR);
        }
        unset($cambio);
        return ['cambios' => $cambios, 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas];
    }

    private static function instantanea(array $pedido): array
    {
        $datos = [];
        foreach (['cliente_nombre', 'telefono', 'fecha_retiro', 'hora_retiro', 'notas', 'estado'] as $campo) $datos[$campo] = $pedido[$campo];
        $datos['total_centavos'] = $pedido['total_centavos'];
        $datos['lineas'] = array_map(static fn (array $linea): array => [
            'id_producto' => $linea['id_producto'], 'nombre' => $linea['nombre'],
            'cantidad' => $linea['cantidad'], 'precio' => $linea['precio'],
        ], $pedido['lineas']);
        return $datos;
    }

    private function guardarDetalles(int $id, array $lineas): void
    {
        $detalle = $this->db->prepare('INSERT INTO pedido_detalles
            (id_pedido, id_producto, nombre_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)');
        foreach ($lineas as $linea) {
            $detalle->execute([$id, $linea['id_producto'], $linea['nombre'], $linea['cantidad'], self::decimal($linea['precio'])]);
        }
    }

    private static function centavos(string $decimal): int
    {
        [$entero, $fraccion] = array_pad(explode('.', $decimal, 2), 2, '0');
        return (int) $entero * 100 + (int) str_pad($fraccion, 2, '0');
    }

    private static function decimal(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }
}
