<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Conexion;
use App\Core\Http;
use App\Core\Sesion;
use App\Core\Vista;
use App\Models\Carrito;
use App\Models\Pedido;
use App\Models\Producto;
use DomainException;
use Throwable;

final class RegistroPedidoEquipoController
{
    public function index(): void
    {
        $this->formulario(false);
    }

    public function editar(): void
    {
        $this->formulario(true);
    }

    private function formulario(bool $esEdicion): void
    {
        header('Cache-Control: no-store');
        $usuario = Sesion::requerirEquipo();
        if (!in_array($usuario['rol'], ['empleado', 'administrador', 'dueno'], true)) {
            http_response_code(403);
            Vista::mostrar('error', [
                'tituloPagina' => 'Acceso no permitido',
                'layoutEquipo' => true,
                'mensaje' => $esEdicion
                    ? 'Solo administrador y dueño pueden modificar pedidos.'
                    : 'El registro de pedidos está disponible para el equipo aprobado.',
                'rutaVolver' => 'equipo-pedidos',
                'textoVolver' => 'Volver a pedidos',
            ]);
            return;
        }

        $formularios = Sesion::formulariosVigentes('formularios_pedido_equipo');
        $modelo = new Pedido(Conexion::obtener());
        $idEdicion = null;
        $pedido = null;
        if ($esEdicion) {
            $idEdicion = filter_var(Http::texto($_GET, 'id'), FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if (!$idEdicion) {
                throw new DomainException('Seleccioná un pedido válido del listado.');
            }
            $pedido = $modelo->buscarGestion($idEdicion);
            if ($pedido === null) {
                http_response_code(404);
                Vista::mostrar('error', [
                    'tituloPagina' => 'Pedido no encontrado',
                    'layoutEquipo' => true,
                    'mensaje' => 'No encontramos ese pedido.',
                    'rutaVolver' => 'equipo-pedidos',
                    'textoVolver' => 'Volver a pedidos',
                ]);
                return;
            }
            if (!Pedido::puedeModificar($usuario['rol'], $pedido['estado'])) {
                $tokenEnviado =
                    $_SERVER['REQUEST_METHOD'] === 'POST' &&
                    is_string($_POST['pedido_token'] ?? null)
                        ? $_POST['pedido_token']
                        : '';
                $guardado = $formularios[$tokenEnviado] ?? null;
                // Un envío ya confirmado puede volver al listado sin repetir la modificación.
                if (
                    ($guardado['id_edicion'] ?? null) !== $idEdicion ||
                    ($guardado['pedido'] ?? null) !== $idEdicion
                ) {
                    http_response_code(403);
                    Vista::mostrar('error', [
                        'tituloPagina' => 'Acceso no permitido',
                        'layoutEquipo' => true,
                        'mensaje' =>
                            'Solo el dueño puede corregir pedidos entregados o cancelados.',
                        'rutaVolver' => 'equipo-pedidos',
                        'textoVolver' => 'Volver a pedidos',
                    ]);
                    return;
                }
            }
        }
        $productos = Pedido::opcionesGestion(Producto::catalogo()->listar(), $pedido);
        $precios = array_column($productos, 'precio', 'id_producto');
        $valores = [
            'cliente_nombre' => '',
            'telefono' => '',
            'fecha_retiro' => date('Y-m-d', strtotime('+1 day')),
            'hora_retiro' => '10:00',
            'modalidad_entrega' => 'retiro',
            'direccion_entrega' => '',
            'notas' => '',
            'estado' => 'Pendiente',
            'motivo' => '',
            'lineas' => [['id_producto' => '', 'cantidad' => '1']],
        ];
        if ($pedido !== null) {
            foreach (
                ['cliente_nombre', 'telefono', 'fecha_retiro', 'hora_retiro', 'modalidad_entrega', 'direccion_entrega', 'notas', 'estado']
                as $campo
            ) {
                $valores[$campo] = $pedido[$campo];
            }
            $valores['lineas'] = [];
            foreach ($pedido['lineas'] as $linea) {
                $valores['lineas'][] = [
                    'id_producto' => $linea['clave_producto'],
                    'cantidad' => (string) $linea['cantidad'],
                ];
            }
        }
        $token = '';
        $error = '';
        $puedeConfirmar = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Sesion::validarCsrf($_POST['csrf'] ?? null);
                $token = Http::texto($_POST, 'pedido_token');
                $formulario = $formularios[$token] ?? null;
                if ($formulario === null || ($formulario['id_edicion'] ?? null) !== $idEdicion) {
                    throw new DomainException(
                        'El formulario venció. Volvé a abrir un nuevo pedido.',
                    );
                }
                if ($formulario['pedido'] !== null) {
                    $this->confirmar((int) $formulario['pedido'], $esEdicion);
                }
                $puedeConfirmar = true;
                foreach (
                    ['cliente_nombre', 'telefono', 'fecha_retiro', 'hora_retiro', 'modalidad_entrega', 'direccion_entrega', 'notas']
                    as $campo
                ) {
                    $valores[$campo] = Http::texto($_POST, $campo);
                }
                $valores['lineas'] = $this->leerLineas($_POST['lineas'] ?? null);
                if ($esEdicion) {
                    $valores['estado'] = Http::texto($_POST, 'estado');
                    $valores['motivo'] = Http::texto($_POST, 'motivo');
                    if (!hash_equals($formulario['version'], Pedido::version($pedido))) {
                        $puedeConfirmar = false;
                        throw new DomainException(
                            'El pedido fue modificado desde otra pantalla. Volvé a abrirlo para revisar los cambios antes de guardar.',
                        );
                    }
                }
                if (isset($_POST['quitar_linea'])) {
                    $indice = filter_var(Http::texto($_POST, 'quitar_linea'), FILTER_VALIDATE_INT, [
                        'options' => ['min_range' => 0],
                    ]);
                    if ($indice === false || !isset($valores['lineas'][$indice])) {
                        throw new DomainException('Seleccioná una línea válida para quitar.');
                    }
                    if (count($valores['lineas']) > 1) {
                        unset($valores['lineas'][$indice]);
                        $valores['lineas'] = array_values($valores['lineas']);
                    }
                } elseif (Http::texto($_POST, 'accion') === 'agregar-linea') {
                    if (count($valores['lineas']) >= Pedido::MAXIMO_PRODUCTOS) {
                        throw new DomainException('El pedido admite hasta 50 productos distintos.');
                    }
                    $valores['lineas'][] = ['id_producto' => '', 'cantidad' => '1'];
                } elseif (Http::texto($_POST, 'accion', 'registrar') === 'registrar') {
                    $cantidades = Pedido::cantidadesDesdeLineas($valores['lineas'], $esEdicion);
                    $esperadas = [];
                    foreach ($cantidades as $id => $cantidad) {
                        if (!isset($formulario['precios'][$id])) {
                            throw new DomainException(
                                'El catálogo cambió. Revisá los productos y precios antes de confirmar nuevamente.',
                            );
                        }
                        $esperadas[] = [
                            'id_producto' => $id,
                            'cantidad' => $cantidad,
                            'precio' => $formulario['precios'][$id],
                        ];
                    }
                    if ($esEdicion) {
                        $modelo->modificar(
                            $idEdicion,
                            $valores,
                            $cantidades,
                            Carrito::firma($esperadas),
                            $formulario['version'],
                            $token,
                            (int) $usuario['id_usuario'],
                            $valores['motivo'],
                        );
                        $id = $idEdicion;
                    } else {
                        $id = $modelo->crear(
                            $valores,
                            $cantidades,
                            Carrito::firma($esperadas),
                            $token,
                            null,
                            (int) $usuario['id_usuario'],
                        );
                    }
                    $formularios[$token]['pedido'] = $id;
                    $_SESSION['qba']['formularios_pedido_equipo'] = $formularios;
                    $this->confirmar($id, $esEdicion);
                } else {
                    throw new DomainException('Seleccioná una acción válida.');
                }
            } catch (DomainException $excepcion) {
                http_response_code(422);
                $error = $excepcion->getMessage();
            } catch (Throwable $excepcion) {
                error_log(
                    ($esEdicion ? 'HU23' : 'HU01') .
                        ': falló el guardado del pedido (' .
                        $excepcion::class .
                        ').',
                );
                http_response_code(503);
                $error =
                    'No pudimos guardar el pedido. Conservamos los datos del formulario; intentá nuevamente.';
            }
        } else {
            $token = bin2hex(random_bytes(32));
            $formularios = array_slice($formularios, -9, null, true);
            $formularios[$token] = [
                'creado' => time(),
                'precios' => $precios,
                'pedido' => null,
                'id_edicion' => $idEdicion,
                'version' => $pedido === null ? null : Pedido::version($pedido),
            ];
            $puedeConfirmar = true;
        }

        if ($esEdicion && $puedeConfirmar && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $actual = $modelo->buscarGestion($idEdicion);
            if (
                $actual === null ||
                !hash_equals($formularios[$token]['version'], Pedido::version($actual))
            ) {
                $puedeConfirmar = false;
                $error =
                    'El pedido fue modificado desde otra pantalla. Volvé a abrirlo para revisar los cambios antes de guardar.';
            }
        }
        // Los precios que se muestran quedan como referencia para el siguiente envío.
        if ($puedeConfirmar) {
            $formularios[$token]['precios'] = $precios;
        }
        $_SESSION['qba']['formularios_pedido_equipo'] = $formularios;
        $resumenPedido = $this->prepararResumen($productos, $valores['lineas']);
        $historial = null;
        if ($esEdicion) {
            $pagina = filter_var(Http::texto($_GET, 'historial_pagina', '1'), FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);
            if (!$pagina) {
                throw new DomainException('Seleccioná una página válida del historial.');
            }
            $historial = $modelo->historial($idEdicion, $pagina);
        }
        $rutaFormulario = 'equipo-pedido-registrar';
        $parametrosFormulario = [];
        $descripcionCliente =
            'El cliente no necesita tener una cuenta para un pedido tomado por el equipo.';

        if ($esEdicion) {
            $rutaFormulario = 'equipo-pedido-editar';
            $parametrosFormulario = ['id' => $idEdicion];
            $descripcionCliente = 'La cuenta del cliente asociada al pedido se conserva al editar.';

            if ($pedido['id_cliente'] === null) {
                $descripcionCliente = 'Este pedido no tiene una cuenta de cliente asociada.';
            }
        }

        Vista::mostrar('equipo-pedido-form', [
            'tituloPagina' =>
                ($esEdicion ? 'Modificar pedido #' . $idEdicion : 'Registrar pedido') .
                ' | Panadería Rustic',
            'layoutEquipo' => true,
            'seccionActiva' => 'pedidos',
            'valores' => $valores,
            'productos' => $productos,
            'precios' => $precios,
            'error' => $error,
            'token' => $token,
            'puedeConfirmar' => $puedeConfirmar,
            'resumen' => $resumenPedido['lineas'],
            'total' => $resumenPedido['total'],
            'resumenCompleto' => $resumenPedido['completo'],
            'maximoProductos' => Pedido::MAXIMO_PRODUCTOS,
            'maximaCantidad' => Pedido::MAXIMA_CANTIDAD,
            'hoy' => date('Y-m-d'),
            'esEdicion' => $esEdicion,
            'pedido' => $pedido,
            'estados' => Pedido::ESTADOS,
            'historial' => $historial,
            'rutaFormulario' => $rutaFormulario,
            'parametrosFormulario' => $parametrosFormulario,
            'descripcionCliente' => $descripcionCliente,
        ]);
    }

    private function prepararResumen(array $productos, array $lineas): array
    {
        $productosPorId = array_column($productos, null, 'id_producto');
        $resumen = ['lineas' => [], 'total' => 0, 'completo' => true];

        foreach ($lineas as $linea) {
            $producto = $productosPorId[$linea['id_producto']] ?? null;
            $cantidad = filter_var($linea['cantidad'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => Pedido::MAXIMA_CANTIDAD],
            ]);

            if ($producto === null || !$cantidad) {
                $resumen['completo'] = false;
                continue;
            }

            $subtotal = $producto['precio'] * $cantidad;
            $resumen['lineas'][] = [
                'nombre' => $producto['nombre'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
            ];
            $resumen['total'] += $subtotal;
        }

        return $resumen;
    }

    private function leerLineas(mixed $lineas): array
    {
        if (
            !is_array($lineas) ||
            !array_is_list($lineas) ||
            !$lineas ||
            count($lineas) > Pedido::MAXIMO_PRODUCTOS
        ) {
            throw new DomainException('Revisá las líneas del pedido (entre 1 y 50 productos).');
        }
        $valores = [];
        foreach ($lineas as $linea) {
            if (!is_array($linea)) {
                throw new DomainException('Revisá los productos del pedido.');
            }
            $valores[] = [
                'id_producto' => Http::texto($linea, 'id_producto'),
                'cantidad' => Http::texto($linea, 'cantidad'),
            ];
        }
        return $valores;
    }

    private function confirmar(int $id, bool $esEdicion): never
    {
        $_SESSION['qba']['aviso_pedidos'] = $esEdicion
            ? 'Pedido #' . $id . ' actualizado correctamente. Los cambios quedaron guardados.'
            : 'Pedido #' . $id . ' registrado correctamente. Quedó pendiente de preparación.';
        Http::redirigir('equipo-pedidos');
    }
}
