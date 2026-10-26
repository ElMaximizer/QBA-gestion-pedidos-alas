<?php
declare(strict_types=1);

use App\Controllers\AccesoController;
use App\Controllers\AccesoEquipoController;
use App\Controllers\CarritoController;
use App\Controllers\ContactoController;
use App\Controllers\InicioController;
use App\Controllers\NosotrosController;
use App\Controllers\GestionProductoController;
use App\Controllers\PanelEquipoController;
use App\Controllers\PedidosEquipoController;
use App\Controllers\PedidoController;
use App\Controllers\PermisosEquipoController;
use App\Controllers\PersonalEquipoController;
use App\Controllers\ProductoController;
use App\Controllers\RegistroController;
use App\Controllers\RegistroEquipoController;
use App\Controllers\StockEquipoController;
use App\Controllers\EntregasMercaderiaController;

// Nombre interno => [dirección pública, controlador, acción, métodos].
return [
    'inicio' => ['', InicioController::class, 'index', ['GET', 'HEAD']],
    'nosotros' => ['nosotros', NosotrosController::class, 'index', ['GET', 'HEAD']],
    'contacto' => ['contacto', ContactoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'contacto-enviado' => ['contacto/enviado', ContactoController::class, 'enviado', ['GET', 'HEAD']],
    'productos' => ['productos', ProductoController::class, 'index', ['GET', 'HEAD']],
    'producto' => ['producto', ProductoController::class, 'ver', ['GET', 'HEAD']],
    'carrito' => ['carrito', CarritoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'realizar-pedido' => ['pedidos/realizar', PedidoController::class, 'crear', ['GET', 'HEAD', 'POST']],
    'pedido-confirmado' => ['pedidos/confirmado', PedidoController::class, 'confirmado', ['GET', 'HEAD']],
    'registro' => ['clientes/registro', RegistroController::class, 'index', ['GET', 'HEAD', 'POST']],
    'verificar-cuenta' => ['clientes/verificar', RegistroController::class, 'verificar', ['GET', 'HEAD', 'POST']],
    'registro-completado' => ['clientes/registro-completado', RegistroController::class, 'completado', ['GET', 'HEAD']],
    'cliente-login' => ['clientes/login', AccesoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'cerrar-sesion' => ['clientes/salir', AccesoController::class, 'salir', ['POST']],
    'equipo-registro' => ['equipo/registro', RegistroEquipoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'equipo-solicitud-enviada' => ['equipo/solicitud-enviada', RegistroEquipoController::class, 'enviado', ['GET', 'HEAD']],
    'equipo-login' => ['equipo/login', AccesoEquipoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'equipo-panel' => ['equipo/panel', PanelEquipoController::class, 'index', ['GET', 'HEAD']],
    'equipo-pedidos' => ['equipo/pedidos', PedidosEquipoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'equipo-permisos' => ['equipo/permisos', PermisosEquipoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'equipo-personal' => ['equipo/personal', PersonalEquipoController::class, 'index', ['GET', 'HEAD']],
    'equipo-cerrar-sesion' => ['equipo/salir', AccesoEquipoController::class, 'salir', ['POST']],
    'equipo-productos' => ['equipo/productos', GestionProductoController::class, 'index', ['GET', 'HEAD']],
    'equipo-stock' => ['equipo/stock', StockEquipoController::class, 'index', ['GET', 'HEAD', 'POST']],
    'equipo-mercaderia' => ['equipo/stock/entregas', EntregasMercaderiaController::class, 'index', ['GET', 'HEAD']],
    'mercaderia-registrar' => ['equipo/stock/entregas/registrar', EntregasMercaderiaController::class, 'registrar', ['GET', 'HEAD', 'POST']],
    'producto-agregar' => ['equipo/productos/agregar', GestionProductoController::class, 'agregar', ['GET', 'HEAD', 'POST']],
    'producto-editar' => ['equipo/productos/editar', GestionProductoController::class, 'editar', ['GET', 'HEAD', 'POST']],
    'producto-eliminar' => ['equipo/productos/eliminar', GestionProductoController::class, 'eliminar', ['GET', 'HEAD', 'POST']],
];
