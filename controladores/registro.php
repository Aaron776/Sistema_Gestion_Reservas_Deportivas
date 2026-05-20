<?php
require_once '../conexion/session.php';
require_once '../conexion/bd.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['telefono']) && isset($_POST['confirmar_password'])){
   // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmar_password = trim($_POST['confirmar_password']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ ]+$/", $nombre)){
        $errores[]="El nombre solo puede contener letras y espacios";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(empty($telefono)){
        $errores[]="El telefono es requerido";
    }elseif(!preg_match("/^[0-9]+$/", $telefono)){
        $errores[]="El telefono solo puede contener numeros";
    }elseif(strlen($telefono)>20){
        $errores[]="El telefono es muy largo";
    }

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

    if(empty($confirmar_password)){
        $errores[]="La confirmacion de la contraseña es requerida";
    }elseif($password !== $confirmar_password){
        $errores[]="Las contraseñas no coinciden";
    }

    // Verificar si existe otro usuario con el mimso email o telefono
    if(empty($errores)){
        $sql=$conexion->prepare("SELECT email,telefono FROM usuarios WHERE email=:email OR telefono=:telefono");
        $sql->bindParam(":email", $email, PDO::PARAM_STR);
        $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
        $sql->execute();
        $usuario_existe=$sql->fetch(PDO::FETCH_OBJ);
        if($usuario_existe){
            if($usuario_existe->email === $email){
                $errores[]="Ya existe un usuario con este email";
            }
            if($usuario_existe->telefono === $telefono){
                $errores[]="Ya existe un usuario con este telefono";
            }
        }
    }
    
    // Si no hay errores, registrar usaurio cliente
    if(empty($errores)){
        try{
            $password_hasheada=password_hash($password, PASSWORD_DEFAULT);
            $sql=$conexion->prepare("INSERT INTO usuarios (nombre,email,password,telefono,rol) VALUES (:nombre,:email,:password_hasheada,:telefono,'cliente')");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":email", $email, PDO::PARAM_STR);
            $sql->bindParam(":password_hasheada", $password_hasheada, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->execute();
            
            $_SESSION['exito'] = "Usuario registrado correctamente";
            header("Location: ../registro.php");
            exit;
        }catch(PDOException $e){
            error_log("Error al registrar usuario: " . $e->getMessage());
            $errores[]="Error al registrar usuario";
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../registro.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Error al registrar usuario"];
    header("Location: ../registro.php");
    exit;
}



?>