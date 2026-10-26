<?php
declare(strict_types=1);
namespace App\Models;
use DomainException; use PDO;
final class ConsultaContacto {
 public const MOTIVOS=['Consulta sobre productos','Disponibilidad','Pedidos para compartir','Tortas y celebraciones','Otra consulta'];
 public function __construct(private readonly PDO $db) {}
 public function guardar(array $datos): void {
  $nombre=$datos['nombre']??null;$email=$datos['email']??null;$motivo=$datos['motivo']??null;$mensaje=$datos['mensaje']??null;
  if(!is_string($nombre)||!mb_check_encoding($nombre,'UTF-8')||mb_strlen($nombre)<2||mb_strlen($nombre)>120
   ||!is_string($email)||strlen($email)>190||filter_var($email,FILTER_VALIDATE_EMAIL)===false
   ||!is_string($motivo)||!in_array($motivo,self::MOTIVOS,true)
   ||!is_string($mensaje)||!mb_check_encoding($mensaje,'UTF-8')||mb_strlen($mensaje)<5||mb_strlen($mensaje)>5000) throw new DomainException('Revisá tu nombre, correo, motivo y mensaje.');
  if(time()-(int)($_SESSION['qba']['contacto_ultimo_envio']??0)<30) throw new DomainException('Esperá unos segundos antes de enviar otra consulta.');
  $q=$this->db->prepare('INSERT INTO mensajes_contacto (nombre,email,motivo,mensaje) VALUES (?,?,?,?)');
  $q->execute([trim($nombre),strtolower(trim($email)),$motivo,trim($mensaje)]);$_SESSION['qba']['contacto_ultimo_envio']=time();
 }
}
