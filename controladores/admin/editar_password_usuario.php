<?php
require_once '../../conexion/bd.php';
require_once '../../conexion/session.php';
require_once '../../helpers/Encriptar.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file(__DIR__ . '/../../.env'); // Carga las variables de entorno

// Verificar que tenga rol de administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_usuario'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    //Recibir datos
    $id_usuario = Crypto::decrypt(trim($_POST['id_usuario']));
    $errores = [];

    // Validacion y sanitizacion
    if (empty($id_usuario)) {
        $errores[] = "El id del usuario es obligatorio";
    } elseif (!is_numeric($id_usuario)) {
        $errores[] = "El id del usuario debe ser numerico";
    } elseif ($id_usuario <= 0) {
        $errores[] = "El id del usuario debe ser mayor a cero";
    }

    // Verificar que el usuario a editar existe Y está activo en la base de datos
    // (No se deben poder editar la contraseña de usuarios que han sido desactivados/eliminados)
    if (empty($errores)) {
        try {
            $sql = $conexion->prepare("SELECT id,email FROM usuarios WHERE id=:id AND estado='activo' LIMIT 1");
            $sql->bindParam(":id", $id_usuario, PDO::PARAM_INT);
            $sql->execute();
            $usuario_existe = $sql->fetch(PDO::FETCH_OBJ);
            if (!$usuario_existe) {
                $errores[] = "El usuario no existe o ha sido desactivado y no puede editar la contraseña.";
            }
        } catch (PDOException $e) {
            $errores[] = "Error de base de datos al verificar existencia: " . $e->getMessage();
        }
    }

    // Si no hay errores procedemos a editar la contraseña del usuario
    if (empty($errores)) {
        // Generar contraseña aleatoria segura de 8 caracteres
        $nuevaPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $conexion->beginTransaction();

            // 1. Actualizar contraseña en BD
            $sql = $conexion->prepare("UPDATE usuarios SET password=:password WHERE id=:id_usuario");
            $sql->bindParam(":password", $hashPassword, PDO::PARAM_STR);
            $sql->bindParam(":id_usuario", $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            // 2. Registrar Notificación al Usuario Afectado
            $mensaje_audit = "El administrador " . $_SESSION['nombre'] . " ha restablecido tu contraseña del sistema, revisa tu bandeja de entrada.";
            $usuario_destino = $usuario_existe->id;
            $tipo = "sistema";
            $leido = "no";

            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, :tipo, :mensaje, :leida)");
            $sql_notif->bindParam(":id_usuario", $usuario_destino, PDO::PARAM_INT);
            $sql_notif->bindParam(":tipo", $tipo, PDO::PARAM_STR);
            $sql_notif->bindParam(":mensaje", $mensaje_audit, PDO::PARAM_STR);
            $sql_notif->bindParam(":leida", $leido, PDO::PARAM_STR);
            $sql_notif->execute();

            // 3. Enviar correo con PHPMailer
            $mail = new PHPMailer(true);

            // Configuramos PHPMailer
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $env['MAIL_USER'];
            $mail->Password   = $env['MAIL_PASSWORD'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Opciones para evitar errores de certificado SSL en entorno local (XAMPP)
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );

            $mail->setFrom($env['MAIL_USER'], 'Sistema de Gestion Reservas Deportivas');
            $mail->addAddress($usuario_existe->email);

            $mail->isHTML(true);
            $mail->Subject = 'Nueva Contraseña - Sistema de Gestión Reservas Deportivas';
            $mail->Body    = "
                <div style='background-color: #0c1620; padding: 50px 20px; font-family: \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background: #111e2c; border: 1px solid rgba(34, 211, 238, 0.3); border-radius: 32px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);'>
                        
                        <!-- Header con Logo -->
                        <div style='padding: 40px 30px; background: linear-gradient(135deg, rgba(34, 211, 238, 0.1), transparent); border-bottom: 1px solid rgba(34, 211, 238, 0.1); text-align: center;'>
                            <div style='margin-bottom: 15px;'>
                                <span style='font-size: 40px; color: #22D3EE;'>⚽</span>
                            </div>
                            <h1 style='margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; text-transform: uppercase; letter-spacing: 3px;'>
                                <span style='color: #22D3EE;'>Sport</span>Reserve
                            </h1>
                            <p style='margin: 10px 0 0 0; font-size: 12px; color: #6b95af; text-transform: uppercase; letter-spacing: 2px;'>Gestión Deportiva de Alto Nivel</p>
                        </div>

                        <!-- Contenido Principal -->
                        <div style='padding: 50px 40px; color: #eef5ff;'>
                            <h2 style='margin-top: 0; color: #ffffff; font-size: 22px; font-weight: 700;'>Hola,</h2>
                            <p style='line-height: 1.8; color: #9bc0d4; font-size: 16px;'>Un administrador del sistema ha restablecido tu clave de acceso por seguridad. A continuación, encontrarás tus nuevas credenciales temporales:</p>
                            
                            <!-- Box de Credenciales -->
                            <div style='margin: 40px 0; background: rgba(0, 0, 0, 0.3); border: 2px solid #22D3EE; border-radius: 20px; padding: 30px; text-align: center; position: relative;'>
                                <p style='margin: 0 0 12px 0; font-size: 11px; color: #22D3EE; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px;'>Nueva Contraseña Temporal</p>
                                <p style='margin: 0; font-size: 36px; font-weight: 800; color: #ffffff; letter-spacing: 6px; font-family: \"Courier New\", monospace;'>{$nuevaPassword}</p>
                            </div>

                            <div style='background: rgba(249, 115, 22, 0.1); border-left: 4px solid #f97316; padding: 20px; border-radius: 12px; margin-bottom: 30px;'>
                                <p style='margin: 0; font-size: 14px; color: #fdba74; line-height: 1.6;'>
                                    <strong>🔒 Acción Requerida:</strong> Por razones de seguridad, te recomendamos cambiar esta contraseña inmediatamente después de iniciar sesión desde la sección <strong>\"Cambiar Contraseña\"</strong> de tu perfil.
                                </p>
                            </div>

                            <div style='text-align: center;'>
                                <a href='{$env['BASE_URL']}' style='display: inline-block; background: linear-gradient(95deg, #10b981, #06b6d4); color: #ffffff; text-decoration: none; padding: 18px 40px; border-radius: 50px; font-weight: 700; font-size: 16px; box-shadow: 0 10px 20px -5px rgba(6, 182, 212, 0.5);'>Acceder al Sistema</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div style='padding: 30px; background: rgba(0, 0, 0, 0.2); border-top: 1px solid rgba(34, 211, 238, 0.1); text-align: center;'>
                            <p style='margin: 0; font-size: 13px; color: #6b95af;'>
                                © 2026 SportReserve • v1.0<br>
                                <span style='font-size: 11px; opacity: 0.7;'>Este es un mensaje automático, por favor no respondas.</span>
                            </p>
                        </div>
                    </div>
                </div>
            ";

            $mail->send();

            // Si llegamos aquí, todo salió bien
            $conexion->commit();

            $_SESSION['exito'] = "Contraseña actualizada y enviada correctamente a: " . $usuario_existe->email;
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        } catch (Exception $e) {
            // Si el correo falla o la BD falla, revertimos TODO
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Reset Password para {$usuario_existe->email}: " . $e->getMessage());

            // Detectar si el error fue de PHPMailer
            $msg_error = "Error al procesar la solicitud.";
            if (isset($mail) && !empty($mail->ErrorInfo)) {
                $msg_error = "Error al enviar el correo. Detalle técnico: " . $mail->ErrorInfo;
            } else {
                $msg_error = "Error: " . $e->getMessage();
            }

            $_SESSION['errores'] = [$msg_error];
            header("Location: ../../admin/gestion_usuarios.php");
            exit();
        }
    } else {
        $_SESSION['errores'] = $errores;
        header("Location: ../../admin/gestion_usuarios.php");
        exit;
    }
} else {
    $_SESSION['errores'] = ["Error en el envio del formulario"];
    header("Location: ../../admin/gestion_usuarios.php");
    exit;
}
