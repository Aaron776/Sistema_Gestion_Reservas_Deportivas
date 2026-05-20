<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$env = parse_ini_file(__DIR__ . '/../.env'); // Carga las variables de entorno

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $email = trim($_POST['email']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email)>100){
        $errores[]="El email es muy largo";
    }

    

    // Verificar si el usuario que el usuario exista,este activo y que su rol sea cliente
    if(empty($errores)){
        $sql=$conexion->prepare("SELECT estado,rol FROM usuarios WHERE email=:email AND rol='cliente' AND estado='activo'");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->execute();
        $cliente_existe=$sql->fetch(PDO::FETCH_OBJ);
        if(empty($cliente_existe)){
            $errores[]="No se ha encontrado un cliente activo con ese correo";
        }
    }
    
    // Si no hay errores, crear una contraseña temporal y enviar al correo del usaurio cliente
    if(empty($errores)){
       // Generar contraseña aleatoria segura de 8 caracteres
        $nuevaPassword = substr(bin2hex(random_bytes(4)), 0, 8);
        $hashPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);

        try {
            $conexion->beginTransaction();

            // 1. Actualizar contraseña en BD
            $sql = $conexion->prepare("UPDATE usuarios SET password=:password WHERE email=:email");
            $sql->bindParam(":password", $hashPassword, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();

            // 2. Enviar correo con PHPMailer
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

            $mail->setFrom($env['MAIL_USER'], 'SportReserve - Seguridad');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperación de Acceso - SportReserve';
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
                            <p style='margin: 10px 0 0 0; font-size: 12px; color: #6b95af; text-transform: uppercase; letter-spacing: 2px;'>Recuperación de Contraseña</p>
                        </div>

                        <!-- Contenido Principal -->
                        <div style='padding: 50px 40px; color: #eef5ff;'>
                            <h2 style='margin-top: 0; color: #ffffff; font-size: 22px; font-weight: 700;'>Solicitud de Acceso,</h2>
                            <p style='line-height: 1.8; color: #9bc0d4; font-size: 16px;'>Hemos recibido una solicitud para restablecer tu contraseña. A continuación, te proporcionamos una nueva clave temporal para que puedas volver a ingresar:</p>
                            
                            <!-- Box de Credenciales -->
                            <div style='margin: 40px 0; background: rgba(0, 0, 0, 0.3); border: 2px solid #22D3EE; border-radius: 20px; padding: 30px; text-align: center; position: relative;'>
                                <p style='margin: 0 0 12px 0; font-size: 11px; color: #22D3EE; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px;'>Nueva Contraseña Temporal</p>
                                <p style='margin: 0; font-size: 36px; font-weight: 800; color: #ffffff; letter-spacing: 6px; font-family: \"Courier New\", monospace;'>{$nuevaPassword}</p>
                            </div>

                            <div style='background: rgba(249, 115, 22, 0.1); border-left: 4px solid #f97316; padding: 20px; border-radius: 12px; margin-bottom: 30px;'>
                                <p style='margin: 0; font-size: 14px; color: #fdba74; line-height: 1.6;'>
                                    <strong>🔒 Seguridad:</strong> Por favor, utiliza esta clave para iniciar sesión y cámbiala lo antes posible desde la configuración de tu cuenta.
                                </p>
                            </div>

                            <div style='text-align: center;'>
                                <a href='{$env['BASE_URL']}' style='display: inline-block; background: linear-gradient(95deg, #10b981, #06b6d4); color: #ffffff; text-decoration: none; padding: 18px 40px; border-radius: 50px; font-weight: 700; font-size: 16px; box-shadow: 0 10px 20px -5px rgba(6, 182, 212, 0.5);'>Volver a SportReserve</a>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div style='padding: 30px; background: rgba(0, 0, 0, 0.2); border-top: 1px solid rgba(34, 211, 238, 0.1); text-align: center;'>
                            <p style='margin: 0; font-size: 13px; color: #6b95af;'>
                                © 2026 SportReserve • v1.0<br>
                                <span style='font-size: 11px; opacity: 0.7;'>Si no solicitaste este cambio, por favor ignora este correo.</span>
                            </p>
                        </div>
                    </div>
                </div>
            ";

            $mail->send();

            // Si llegamos aquí, todo salió bien
            $conexion->commit();

            $_SESSION['exito'] = "Contraseña actualizada, revise su correo electronico para verificarla";
            header("Location: ../recuperar_password.php");
            exit();
        } catch (Exception $e) {
            // Si el correo falla o la BD falla, revertimos TODO
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log("Error en Reset Password para {$email}: " . $e->getMessage());

            // Detectar si el error fue de PHPMailer
            $msg_error = "Error al procesar la solicitud.";
            if (isset($mail) && !empty($mail->ErrorInfo)) {
                $msg_error = "Error al enviar el correo. Detalle técnico: " . $mail->ErrorInfo;
            } else {
                $msg_error = "Error: " . $e->getMessage();
            }

            $_SESSION['errores'] = [$msg_error];
            header("Location: ../recuperar_password.php");
            exit();
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../recuperar_password.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al recuperar la contraseña"];
    header("Location: ../recuperar_password.php");
    exit;
}



?>