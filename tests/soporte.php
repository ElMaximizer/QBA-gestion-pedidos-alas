<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

function comprobar(bool $condicion, string $descripcion): void
{
    global $comprobaciones;
    if (!$condicion) {
        throw new RuntimeException($descripcion);
    }
    $comprobaciones++;
}

function rechaza(callable $operacion, string $descripcion): void
{
    try {
        $operacion();
    } catch (DomainException) {
        comprobar(true, $descripcion);
        return;
    }
    throw new RuntimeException($descripcion);
}

function oculto(string $html, string $nombre): string
{
    $documento = new DOMDocument();
    @$documento->loadHTML('<?xml encoding="UTF-8">' . $html);
    $entrada = (new DOMXPath($documento))->query('//input[@name="' . $nombre . '"]')->item(0);
    if (!$entrada) {
        throw new RuntimeException('No se encontró el campo ' . $nombre);
    }
    return $entrada->getAttribute('value');
}

/** Base, servidor y sesiones exclusivos de cada ejecución de pruebas. */
final class EntornoPruebas
{
    public readonly PDO $db;
    public readonly string $base;
    private readonly string $directorio;
    private string $direccion = '';
    private mixed $servidor = null;
    private bool $baseCreada = false;
    private bool $cerrado = false;

    public function __construct(string $historia, private readonly array $correo = [])
    {
        if (!preg_match('/^(?:hu[0-9]{2}|equipo)$/D', $historia)) {
            throw new InvalidArgumentException('Historia de prueba inválida.');
        }
        $this->base = 'qba_' . $historia . '_test_' . bin2hex(random_bytes(6));
        $this->directorio = sys_get_temp_dir() . '/qba-' . $historia . '-' . bin2hex(random_bytes(6));
        $config = require dirname(__DIR__) . '/config/database.php';
        try {
            mkdir($this->directorio);
            $this->db = new PDO("mysql:host={$config['host']};port={$config['port']};charset=utf8mb4",
                $config['user'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
            $this->db->exec("CREATE DATABASE `{$this->base}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->baseCreada = true;
            $this->db->exec("USE `{$this->base}`");
            foreach (['estructura.sql', 'clientes.sql', 'acceso-clientes.sql', 'equipo.sql', 'acceso-equipo.sql', 'productos_demo.sql'] as $archivo) {
                $this->db->exec(file_get_contents(dirname(__DIR__) . '/database/' . $archivo));
            }
        } catch (Throwable $error) {
            $this->cerrar();
            throw $error;
        }
    }

    public function iniciarServidor(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $error, $mensaje);
        if (!$socket) {
            throw new RuntimeException($mensaje);
        }
        $this->direccion = stream_socket_get_name($socket, false);
        fclose($socket);
        $this->servidor = proc_open([PHP_BINARY, '-d', 'session.save_path="' . $this->directorio . '"',
            '-d', 'session.serialize_handler=php',
            '-S', $this->direccion, '-t', dirname(__DIR__) . '/public', dirname(__DIR__) . '/public/router.php'], [0 => ['pipe', 'r'],
                1 => ['file', $this->directorio . '/servidor.log', 'a'],
                2 => ['file', $this->directorio . '/errores.log', 'a']], $tuberias, dirname(__DIR__),
            array_replace(getenv(), ['QBA_DB_NAME' => $this->base, 'QBA_MAIL_TRANSPORT' => 'local'], $this->correo), ['bypass_shell' => true]);
        if (!is_resource($this->servidor)) {
            throw new RuntimeException('No se pudo iniciar el servidor de pruebas.');
        }
        fclose($tuberias[0]);
        $inicio = microtime(true);
        do {
            [$estado] = $this->solicitar('/');
            if ($estado !== 0) {
                return;
            }
            usleep(50000);
        } while (microtime(true) - $inicio < 5);
        throw new RuntimeException('El servidor de pruebas no respondió.');
    }

    public function solicitar(string $ruta, ?array $campos = null, ?string $sesion = null): array
    {
        $curl = curl_init($this->url() . '/' . ltrim($ruta, '/'));
        curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_CONNECTTIMEOUT => 1, CURLOPT_TIMEOUT => 5, CURLOPT_FOLLOWLOCATION => false]);
        if ($sesion === null) {
            curl_setopt_array($curl, [CURLOPT_COOKIEFILE => $this->directorio . '/cookies',
                CURLOPT_COOKIEJAR => $this->directorio . '/cookies']);
        } else {
            curl_setopt($curl, CURLOPT_COOKIE, 'QBA_SESSION=' . $sesion);
        }
        if ($campos !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($campos));
        }
        $respuesta = curl_exec($curl);
        $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        return [$estado, (string) $respuesta];
    }

    public function url(): string
    {
        return 'http://' . $this->direccion;
    }

    public function idSesion(): string
    {
        $id = '';
        foreach (file($this->directorio . '/cookies', FILE_IGNORE_NEW_LINES) as $linea) {
            $campos = explode("\t", $linea);
            if (count($campos) === 7 && $campos[5] === 'QBA_SESSION') {
                $id = $campos[6];
            }
        }
        if (!preg_match('/^[a-zA-Z0-9,-]+$/D', $id)) {
            throw new RuntimeException('No se encontró la sesión de pruebas.');
        }
        return $id;
    }

    /** Lee o modifica únicamente la sesión del servidor temporal para probar su vencimiento. */
    public function sesion(?callable $actualizar = null): array
    {
        $archivo = $this->directorio . '/sess_' . $this->idSesion();
        $contenido = file_get_contents($archivo);
        if (!str_starts_with($contenido, 'qba|')) {
            throw new RuntimeException('La sesión de pruebas no tiene el formato esperado.');
        }
        $datos = unserialize(substr($contenido, 4), ['allowed_classes' => false]);
        if ($actualizar !== null) {
            $datos = $actualizar($datos);
            file_put_contents($archivo, 'qba|' . serialize($datos));
        }
        return $datos;
    }

    public function cli(string $archivo, array $argumentos): array
    {
        $proceso = proc_open([PHP_BINARY, dirname(__DIR__) . '/' . $archivo, ...$argumentos],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tuberias, dirname(__DIR__),
            array_replace(getenv(), ['QBA_DB_NAME' => $this->base, 'QBA_MAIL_TRANSPORT' => 'local'], $this->correo), ['bypass_shell' => true]);
        fclose($tuberias[0]);
        $salida = stream_get_contents($tuberias[1]);
        $error = stream_get_contents($tuberias[2]);
        fclose($tuberias[1]);
        fclose($tuberias[2]);
        return [proc_close($proceso), $salida, $error];
    }

    public function errores(): string
    {
        return is_file($this->directorio . '/errores.log') ? file_get_contents($this->directorio . '/errores.log') : '';
    }

    public function cerrar(): void
    {
        if ($this->cerrado) {
            return;
        }
        $this->cerrado = true;
        if (is_resource($this->servidor)) {
            proc_terminate($this->servidor);
            proc_close($this->servidor);
        }
        if ($this->baseCreada && preg_match('/^qba_(?:hu[0-9]{2}|equipo)_test_[a-f0-9]{12}$/D', $this->base)) {
            $this->db->exec("DROP DATABASE `{$this->base}`");
        }
        if (is_dir($this->directorio)) {
            foreach (glob($this->directorio . '/*') as $archivo) {
                unlink($archivo);
            }
            rmdir($this->directorio);
        }
    }
}
