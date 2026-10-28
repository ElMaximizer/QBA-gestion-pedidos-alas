<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Conexion; use App\Core\Http; use App\Core\Sesion; use App\Core\Vista;
use App\Models\ConsultaContacto; use DomainException; use Throwable;
final class ContactoController {
 public function index(): void {
  $datos=['nombre'=>'','email'=>'','motivo'=>'','mensaje'=>'']; $error='';
  if($_SERVER['REQUEST_METHOD']==='POST') try {
   Sesion::validarCsrf($_POST['csrf']??null);
   foreach(array_keys($datos) as $campo) $datos[$campo]=Http::texto($_POST,$campo);
   (new ConsultaContacto(Conexion::obtener()))->guardar($datos);
   $_SESSION['qba']['contacto_enviado']=true; Http::redirigir('contacto-enviado');
  } catch(DomainException $e) {http_response_code(422);$error=$e->getMessage();}
    catch(Throwable $e) {error_log((string)$e);http_response_code(503);$error='No pudimos guardar tu consulta en este momento. Intentá nuevamente.';}
  Vista::mostrar('contacto',['tituloPagina'=>'Contacto | Panadería R U S T I C','seccionActiva'=>'contacto','datos'=>$datos,'error'=>$error,'motivos'=>ConsultaContacto::MOTIVOS]);
 }
 public function enviado(): void {
  if(($_SESSION['qba']['contacto_enviado']??false)!==true) Http::redirigir('contacto');
  unset($_SESSION['qba']['contacto_enviado']);
  Vista::mostrar('contacto-enviado',['tituloPagina'=>'Consulta enviada | Panadería R U S T I C','seccionActiva'=>'contacto']);
 }
}
