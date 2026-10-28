<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/soporte.php';

use App\Models\AccesoCliente;
use App\Models\Carrito;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Producto;

$entorno = null;
$comprobaciones = 0;
$correcto = true;

try {
    $entorno = new EntornoPruebas('hu17');
    $db = $entorno->db;
    $clientes = new Cliente($db);
    $acceso = new AccesoCliente($db);
    $password = ' ClaveSegura17! ';
    $email = 'cliente.uno@example.test';
    $primero = $clientes->registrar('Cliente <uno>', $email, $password, $password);
    $clientes->verificar($email, $primero['codigo']);
    $segundo = $clientes->registrar('Cliente dos', 'cliente.dos@example.test', $password, $password);
    $clientes->verificar($segundo['email'], $segundo['codigo']);
    $pendiente = $clientes->registrar('Pendiente', 'pendiente@example.test', $password, $password);
    $mensaje = static function (callable $operacion): string {
        try {
            $operacion();
        } catch (DomainException $error) {
            return $error->getMessage();
        }
        throw new RuntimeException('La operación debía rechazar el acceso.');
    };
    $nuevoCookie = static function (string $respuesta): string {
        if (!preg_match('/Set-Cookie: QBA_SESSION=([^;\r\n]+)/i', $respuesta, $coincidencia)) {
            throw new RuntimeException('No se renovó la cookie de sesión.');
        }
        return $coincidencia[1];
    };
    $limite = static function (string $correo) use ($db): array|false {
        $consulta = $db->prepare('SELECT * FROM intentos_acceso_cliente WHERE clave_email = ?');
        $consulta->execute([hash('sha256', strtolower($correo))]);
        return $consulta->fetch();
    };
    rechaza(fn () => $acceso->autenticar('invalido', $password), 'Valida el correo');
    $usuario = $acceso->autenticar('  CLIENTE.UNO@EXAMPLE.TEST ', $password);
    comprobar((int) $usuario['id_cliente'] === $primero['id_cliente'] && !isset($usuario['password_hash']), 'Normaliza el correo y devuelve la identidad sin el hash');
    $generico = $mensaje(fn () => $acceso->autenticar($email, 'Incorrecta17!'));
    comprobar($generico === $mensaje(fn () => $acceso->autenticar('inexistente@example.test', 'Incorrecta17!')), 'Una cuenta inexistente y una contraseña incorrecta tienen el mismo error');
    rechaza(fn () => $acceso->autenticar($email, trim($password)), 'Conserva los espacios de la contraseña');
    rechaza(fn () => $acceso->autenticar($email, "Clave\0Invalida"), 'Rechaza contraseñas con byte nulo');
    $acceso->autenticar($email, $password);
    comprobar($limite($email) === false, 'Un acceso correcto reinicia los intentos');
    for ($intento = 1; $intento <= 6; $intento++) {
        rechaza(fn () => (new AccesoCliente($db))->autenticar(strtoupper($email), 'Incorrecta17!'), 'Persiste un intento fallido entre instancias');
    }
    comprobar((int) $limite($email)['intentos'] === 6 && $limite($email)['bloqueado_hasta'] !== null, 'Seis fallos bloquean temporalmente el acceso');
    comprobar(str_contains($mensaje(fn () => $acceso->autenticar($email, $password)), 'Esperá un minuto'), 'El bloqueo también rechaza la contraseña correcta');
    $consulta = $db->prepare('UPDATE intentos_acceso_cliente SET bloqueado_hasta = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 SECOND) WHERE clave_email = ?');
    $consulta->execute([hash('sha256', $email)]);
    rechaza(fn () => $acceso->autenticar($email, 'Incorrecta17!'), 'Después del bloqueo puede comenzar un nuevo intento');
    comprobar((int) $limite($email)['intentos'] === 1, 'El bloqueo vencido no mantiene un contador agotado');
    $db->exec('UPDATE intentos_acceso_cliente SET actualizado_en = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 11 MINUTE)');
    rechaza(fn () => $acceso->autenticar($email, 'Incorrecta17!'), 'Rechaza una contraseña incorrecta después de vencer la ventana');
    comprobar((int) $limite($email)['intentos'] === 1, 'Los intentos aislados vencen después de diez minutos');
    $acceso->autenticar($email, $password);
    $consulta = $db->prepare('UPDATE clientes SET password_hash = ? WHERE id_cliente = ?');
    $consulta->execute([password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]), $primero['id_cliente']]);
    $acceso->autenticar($email, $password);
    $hash = $db->query('SELECT password_hash FROM clientes WHERE id_cliente = ' . $primero['id_cliente'])->fetchColumn();
    comprobar(!password_needs_rehash($hash, PASSWORD_DEFAULT), 'Actualiza un hash antiguo después de autenticar');
    $passwordLargo = str_repeat('a', 72);
    $largo = $clientes->registrar('Límite de bcrypt', 'largo@example.test', $passwordLargo, $passwordLargo);
    $clientes->verificar($largo['email'], $largo['codigo']);
    rechaza(fn () => $acceso->autenticar($largo['email'], $passwordLargo . 'extra'), 'No acepta una contraseña más larga por truncamiento de bcrypt');
    comprobar($acceso->buscarVerificado($pendiente['id_cliente']) === null, 'No devuelve una cuenta pendiente como identidad autenticada');

    $_SESSION = [];
    $carrito = new Carrito(new Producto($db));
    $carrito->actualizar('agregar', '1', '1');
    $datos = ['cliente_nombre' => 'Cliente prueba', 'telefono' => '099123456', 'fecha_retiro' => date('Y-m-d', strtotime('+2 days')),
        'hora_retiro' => '10:00', 'notas' => ''];
    $pedidos = new Pedido($db);
    $firma = Carrito::firma($carrito->articulos());
    rechaza(fn () => $pedidos->crear($datos, Carrito::cantidades(), $firma, bin2hex(random_bytes(32)), $pendiente['id_cliente']), 'Un pedido no se asocia a una cuenta sin verificar');
    $tokenModelo = bin2hex(random_bytes(32));
    $pedidoModelo = $pedidos->crear($datos, Carrito::cantidades(), $firma, $tokenModelo, $primero['id_cliente']);
    comprobar($pedidos->crear($datos, Carrito::cantidades(), $firma, $tokenModelo, $primero['id_cliente']) === $pedidoModelo, 'Conserva la idempotencia para el mismo cliente');
    rechaza(fn () => $pedidos->crear($datos, Carrito::cantidades(), $firma, $tokenModelo, $segundo['id_cliente']), 'No devuelve un pedido de otra identidad con un token repetido');
    rechaza(fn () => $pedidos->crear($datos, Carrito::cantidades(), $firma, $tokenModelo), 'Un invitado no recupera un pedido autenticado con otro token de sesión');

    $entorno->iniciarServidor();
    $solicitar = $entorno->solicitar(...);
    [$estado, $html] = $solicitar('/clientes/login');
    comprobar($estado === 200 && str_contains($html, 'Acceso para clientes'), 'Muestra el acceso de clientes');
    comprobar(str_contains($html, 'registro.css') && str_contains($html, 'js/registro.js') && str_contains($html, 'autocomplete="current-password"'), 'Reutiliza estilos y el control de contraseña con autocompletado de acceso');
    comprobar(str_contains($html, 'Cache-Control: no-store'), 'El acceso no se almacena en caché');
    $anonima = $entorno->idSesion();
    $csrf = oculto($html, 'csrf');
    $campos = ['csrf' => $csrf, 'email' => $email, 'password' => $password];
    comprobar($solicitar('/clientes/salir')[0] === 405, 'Cerrar sesión exige POST');
    comprobar($solicitar('/clientes/login', array_replace($campos, ['csrf' => 'incorrecto']))[0] === 422, 'El acceso exige CSRF');
    comprobar($solicitar('/clientes/login', array_replace($campos, ['email' => ['x']]))[0] === 422, 'Rechaza un correo malformado');
    comprobar($solicitar('/clientes/login', array_replace($campos, ['password' => ['x']]))[0] === 422, 'Rechaza una contraseña malformada');
    [, $htmlError] = $solicitar('/clientes/login', array_replace($campos, ['email' => '<script>prueba</script>']));
    comprobar(str_contains($htmlError, '&lt;script&gt;prueba&lt;/script&gt;') && !str_contains($htmlError, '<script>prueba</script>'), 'Escapa el correo recuperado tras un error');
    comprobar(!str_contains($htmlError, $password), 'No devuelve la contraseña en el HTML');
    comprobar($solicitar('/clientes/login', array_replace($campos, ['password' => 'Incorrecta17!']))[0] === 422, 'Una contraseña incorrecta no inicia sesión');
    comprobar(!isset($entorno->sesion()['cliente_id']), 'El fallo no guarda una identidad en la sesión');
    [, $htmlError] = $solicitar('/clientes/login', array_replace($campos, ['email' => $pendiente['email'], 'password' => 'Incorrecta17!']));
    comprobar(!str_contains($htmlError, 'Verificar mi cuenta con un código'), 'Una contraseña incorrecta no revela que la cuenta está pendiente');
    [$estado, $htmlError] = $solicitar('/clientes/login', array_replace($campos, ['email' => $pendiente['email']]));
    comprobar($estado === 422 && str_contains($htmlError, 'Verificar mi cuenta con un código'), 'Una cuenta pendiente recibe un enlace para completar la verificación');
    comprobar(!isset($entorno->sesion()['cliente_id']), 'Una cuenta pendiente no obtiene una sesión autenticada');
    $solicitar('/carrito', ['csrf' => $csrf, 'accion' => 'agregar', 'id_producto' => '1', 'cantidad' => '2']);
    [, $checkoutAntes] = $solicitar('/pedidos/realizar');
    $tokenAnterior = oculto($checkoutAntes, 'pedido_token');
    [$estado, $respuesta] = $solicitar('/clientes/login', $campos + ['continuar' => 'https://example.test']);
    comprobar($estado === 303 && str_contains($respuesta, 'Location: /'), 'Autentica sin aceptar redirecciones externas');
    $autenticada = $nuevoCookie($respuesta);
    comprobar($autenticada !== $anonima, 'Renueva el identificador de sesión al autenticar');
    comprobar(str_contains(strtolower($respuesta), 'httponly') && str_contains(strtolower($respuesta), 'samesite=lax'), 'La cookie conserva sus protecciones');
    $sesion = $entorno->sesion();
    comprobar($sesion['cliente_id'] === $primero['id_cliente'] && $sesion['csrf'] !== $csrf, 'Guarda solo la identidad verificada y renueva CSRF');
    comprobar(!isset($sesion['registro_pendiente_email']) && !isset($sesion['password']) && !isset($sesion['formularios']), 'Descarta datos de verificación y formularios de la identidad anterior');
    [, $inicio] = $solicitar('/');
    comprobar(str_contains($inicio, 'Cliente &lt;uno&gt;') && !str_contains($inicio, 'Cliente <uno>'), 'Muestra el nombre autenticado escapado en el menú');
    [, $ajena] = $solicitar('/', null, $anonima);
    comprobar(str_contains($ajena, 'Acceso clientes') && !str_contains($ajena, 'Cliente &lt;uno&gt;'), 'La cookie anterior no obtiene la identidad autenticada');
    comprobar($solicitar('/clientes/login')[0] === 303, 'Un cliente autenticado vuelve al inicio');
    $csrfNuevo = oculto($inicio, 'csrf');
    comprobar($solicitar('/clientes/salir', ['csrf' => $csrf])[0] === 422, 'Un CSRF anterior no puede cerrar la sesión');
    [, $carritoWeb] = $solicitar('/carrito');
    comprobar(str_contains($carritoWeb, 'Carrito <span class="carrito__cantidad">2</span>'), 'Iniciar sesión conserva el carrito');
    [, $checkout] = $solicitar('/pedidos/realizar');
    comprobar(oculto($checkout, 'cliente_nombre') === 'Cliente <uno>', 'Precarga el nombre de la cuenta');
    $confirmar = $datos + ['csrf' => $csrfNuevo, 'pedido_token' => $tokenAnterior, 'id_cliente' => (string) $segundo['id_cliente']];
    comprobar($solicitar('/pedidos/realizar', $confirmar)[0] === 422, 'Invalida los formularios de pedido abiertos antes de autenticar');
    $confirmar['pedido_token'] = oculto($checkout, 'pedido_token');
    comprobar($solicitar('/pedidos/realizar', $confirmar)[0] === 303, 'Guarda el pedido autenticado');
    $ultimo = $db->query('SELECT * FROM pedidos ORDER BY id_pedido DESC LIMIT 1')->fetch();
    comprobar((int) $ultimo['id_cliente'] === $primero['id_cliente'], 'La identidad del pedido sale de la sesión e ignora un id enviado por el navegador');
    comprobar($solicitar('/pedidos/confirmado')[0] === 200, 'La sesión ve su confirmación');
    $solicitar('/carrito', ['csrf' => $csrfNuevo, 'accion' => 'agregar', 'id_producto' => '2', 'cantidad' => '1']);
    [$estado, $respuesta] = $solicitar('/clientes/salir', ['csrf' => $csrfNuevo]);
    comprobar($estado === 303 && $nuevoCookie($respuesta) !== $autenticada, 'Cerrar sesión renueva la cookie y redirige');
    comprobar(!isset($entorno->sesion()['cliente_id']) && !isset($entorno->sesion()['pedido_confirmado']), 'Cerrar sesión descarta identidad y confirmaciones');
    comprobar($solicitar('/pedidos/confirmado')[0] === 303, 'No conserva la confirmación después de salir');
    [, $inicio] = $solicitar('/');
    comprobar(str_contains($inicio, 'Acceso clientes') && str_contains($inicio, 'Carrito <span class="carrito__cantidad">1</span>'), 'Cerrar sesión conserva el carrito y recupera el enlace de acceso');
    [, $ajena] = $solicitar('/', null, $autenticada);
    comprobar(!str_contains($ajena, 'Cliente &lt;uno&gt;'), 'La cookie cerrada no permite volver a autenticarse');
    $campos['csrf'] = oculto($solicitar('/clientes/login')[1], 'csrf');
    $campos['email'] = $segundo['email'];
    comprobar($solicitar('/clientes/login', $campos)[0] === 303, 'Puede acceder otra cuenta después del cierre');
    comprobar($solicitar('/pedidos/confirmado')[0] === 303, 'Otra cuenta no hereda confirmaciones previas');
    $entorno->sesion(static fn (array $datos): array => array_replace($datos, ['cliente_ultimo_acceso' => time() - 1801]));
    [, $inicio] = $solicitar('/');
    comprobar(str_contains($inicio, 'Acceso clientes') && !isset($entorno->sesion()['cliente_id']), 'La identidad vence después de treinta minutos sin actividad');
    $campos['csrf'] = oculto($solicitar('/clientes/login')[1], 'csrf');
    $solicitar('/clientes/login', $campos);
    $db->exec('UPDATE clientes SET verificado = 0 WHERE id_cliente = ' . $segundo['id_cliente']);
    [, $inicio] = $solicitar('/');
    comprobar(str_contains($inicio, 'Acceso clientes') && !isset($entorno->sesion()['cliente_id']), 'Revalida la cuenta en la base y descarta una identidad que dejó de estar verificada');
    comprobar((int) $db->query('SELECT COUNT(*) FROM correos_locales')->fetchColumn() === 0, 'Iniciar sesión no genera correos ni códigos');
    comprobar(!str_contains($entorno->errores(), $password) && !preg_match('/Fatal error:|Warning:|Notice:|Deprecated:/', $entorno->errores()), 'El flujo no registra contraseñas ni errores PHP');
    echo "Correcto: $comprobaciones comprobaciones de HU17.\n";
} catch (Throwable $error) {
    $correcto = false;
    fwrite(STDERR, $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    fwrite(STDERR, $entorno?->errores() ?? '');
} finally {
    $entorno?->cerrar();
}
exit($correcto ? 0 : 1);
