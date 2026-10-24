# PHPMailer — instalación manual

Versión: **7.1.1**. Los archivos de esta carpeta se incluyen en el repositorio
para que el equipo pueda usar el proyecto directamente después de descargarlo.

- [Repositorio oficial](https://github.com/PHPMailer/PHPMailer).
- [Publicación de esta versión](https://github.com/PHPMailer/PHPMailer/releases/tag/v7.1.1).
- [ZIP utilizado](https://github.com/PHPMailer/PHPMailer/archive/refs/tags/v7.1.1.zip).
- SHA-256 del ZIP: `c45a1ad58cb835921827b9ec2e86a55f5abdbdb6b17345fd1ab55a7dfd31119f`.

Se copiaron sin modificaciones `src/Exception.php`, `src/PHPMailer.php` y
`src/SMTP.php`, necesarios para enviar mediante SMTP con contraseña de
aplicación. Se conservan `LICENSE`, `COMMITMENT` y `VERSION` del paquete oficial.
La licencia es LGPL 2.1; los avisos de autoría permanecen en los archivos.

`app/bootstrap.php` carga las clases del espacio de nombres
`PHPMailer\PHPMailer` desde esta carpeta cuando se necesitan.

Para actualizar, descargar una publicación oficial, reemplazar los mismos
archivos por los de esa versión y actualizar VERSION y esta nota. Revisar los
cambios de la publicación y ejecutar `tests/correo.php`, `tests/registro.php` y
`tests/pedidos.php` antes de integrar la actualización. El código propio de
envío permanece en `app/Services/CorreoVerificacion.php`.
