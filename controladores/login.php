<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($email)){
        $errores[]="El email es requerido";
    }elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $errores[]="El email no es valido";
    }elseif(strlen($email)>100){
        $errores[]="El email es muy largo";
    }

    if(empty($password)){
        $errores[]="La contraseña es requerida";
    }elseif(strlen($password)<5){
        $errores[]="La contraseña debe tener al menos 5 caracteres";
    }

    // Verificar si el usuario que quiere ingresar esta en estado inactivo
    if(empty($errores)){
        $sql=$conexion->prepare("SELECT estado FROM usuarios WHERE email=:email");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->execute();
        $usuario_estado=$sql->fetch(PDO::FETCH_OBJ);
        if($usuario_estado && $usuario_estado->estado === 'inactivo'){
            $errores[]="Tu cuenta esta desactivada. Por favor, contacta al administrador.";
        }
    }
    
    // Si no hay errores, iniciar sesion
    if(empty($errores)){
        // Iniciar sesion
        try{
            $sql=$conexion->prepare("SELECT id,nombre,rol,password FROM usuarios WHERE email=:email AND estado='activo'");
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->execute();
            $usuario=$sql->fetch(PDO::FETCH_OBJ);

            if($usuario && password_verify($password, $usuario->password)){
                // 1. Actualizar el Ultimo acceso solo si la clave es correcta
                try {
                    $sqlAcceso = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
                    $sqlAcceso->bindParam(":id", $usuario->id, PDO::PARAM_INT);
                    $sqlAcceso->execute();
                } catch (PDOException $e) {
                    error_log("Error al actualizar el ultimo acceso: " . $e->getMessage());
                }

                // 2. Establecer variables de sesión
                $_SESSION['id_usuario']=$usuario->id;
                $_SESSION['nombre']=$usuario->nombre;
                $_SESSION['rol']=$usuario->rol;
                $_SESSION['logueado']=true;
                
                switch($usuario->rol){
                    case 'admin':
                        header("Location: ../admin/dash_admin.php");
                        break;
                    case 'cliente':
                        header("Location: ../cliente/dash_cliente.php");
                        break;
                    case 'recepcionista':
                        header("Location: ../recepcionista/dash_recepcionista.php");
                        break;
                    default:
                        header("Location: ../login.php");
                        break;
                }
                exit;
            }else{
                $_SESSION['errores'] = ["Credenciales incorrectas"]; 
                header("Location: ../login.php");
                exit;
            }
        }catch(PDOException $e){
            error_log("Error al iniciar sesión: " . $e->getMessage());
            $errores[]="Error al iniciar sesión";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../login.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al iniciar sesión"];
    header("Location: ../login.php");
    exit;
}



?>