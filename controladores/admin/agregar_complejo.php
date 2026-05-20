<?php
require_once '../../conexion/session.php';
require_once '../../conexion/bd.php';

// 1. Verificación de Rol (Solo administrador puede registrar)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../acceso_denegado.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nombre']) && isset($_POST['direccion']) && isset($_POST['descripcion']) && isset($_FILES['imagen']) && isset($_POST['telefono'])) {
    // Validar token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("Location: ../../acceso_denegado.php");
        exit;
    }

    // Recibir datos
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $descripcion = trim($_POST['descripcion']);
    $telefono = trim($_POST['telefono']);
    $errores=[];

    // Validacion y sanitizacion
    if(empty($nombre)){
        $errores[]="El nombre es requerido";
    }elseif(!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ0-9#.\- ]+$/", $nombre)){
        $errores[]="El nombre contiene caracteres no permitidos (solo letras, números, #, . y -)";
    }elseif(strlen($nombre)>100){
        $errores[]="El nombre es muy largo";
    }

    if(!empty($telefono)){
        if(!preg_match("/^[0-9]+$/", $telefono)){
            $errores[]="El telefono solo puede contener numeros";
        }elseif(strlen($telefono)>20){
            $errores[]="El telefono es muy largo";
        }
    }

    if(!empty($descripcion)){
        if(strlen($descripcion)>500){
            $errores[]="La descripcion es muy larga";
        }
    }

    if(!empty($direccion)){
        if(strlen($direccion)>255){
            $errores[]="La direccion es muy larga";
        }
    }

    // Manejamos la subida de la imagen caso de haber una
    $imagen_nombre = null; // Cambiado a null por defecto
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
         // Obtenemos la ruta temporal donde el servidor almacenó el archivo inmediatamente
        $imagen_temp = $_FILES['imagen']['tmp_name'];
        
        // Extraemos la extensión del archivo original (ej: jpg, png, gif)
        $imagen_extension = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);

        // VALIDAR TIPO DE ARCHIVO (solo imágenes permitidas)
        // Definimos las extensiones válidas para evitar subir archivos peligroso
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // Convertimos la extensión a minúsculas para comparar sin importar mayúsculas/minúsculas
        // Si la extensión NO está en el array de permitidas, agregamos error
        if (!in_array(strtolower($imagen_extension), $extensiones_permitidas)) {
            $errores[] = "El archivo debe ser una imagen válida (jpg, jpeg, png, gif, webp)";
        }
        
        // ---------------------------------------------------------
        // VALIDAR TAMAÑO (máximo 2MB)
        // ---------------------------------------------------------
        // Definimos el límite en bytes (2MB = 2097152 bytes)
        $tamano_maximo = 2097152;
        
        // Si el archivo supera el tamaño permitido, agregamos error
        if ($_FILES['imagen']['size'] > $tamano_maximo) {
            $errores[] = "La imagen no debe superar los 2MB";
        }

        // GENERAR NOMBRE ÚNICO PARA EL ARCHIVO
        $imagen_nombre = uniqid("complejo_", true) . "." . $imagen_extension;

        if (empty($errores)) {
            move_uploaded_file($imagen_temp, __DIR__ . "/../../app/fotos_complejos/" . $imagen_nombre);
        }
    }
        

    // Si no hay errores, registrar el complejo
    if(empty($errores)){
        try{
            $conexion->beginTransaction(); // Iniciamos la transacción

            // 1. Insertar el Complejo
            $sql=$conexion->prepare("INSERT INTO complejos (nombre, descripcion, imagen_url, direccion, telefono) 
                                   VALUES (:nombre, :descripcion, :imagen_url, :direccion, :telefono)");
            $sql->bindParam(":nombre", $nombre, PDO::PARAM_STR);
            $sql->bindParam(":descripcion", $descripcion, PDO::PARAM_STR);
            $sql->bindParam(":imagen_url", $imagen_nombre, PDO::PARAM_STR);
            $sql->bindParam(":direccion", $direccion, PDO::PARAM_STR);
            $sql->bindParam(":telefono", $telefono, PDO::PARAM_STR);
            $sql->execute();

            // 2. Obtener todos los destinatarios (Administradores y Recepcionistas)
            $stmt_users = $conexion->prepare("SELECT id FROM usuarios WHERE rol IN ('admin', 'recepcionista')");
            $stmt_users->execute();
            $usuarios_notif = $stmt_users->fetchAll(PDO::FETCH_OBJ);

            // 3. Registrar Notificación individual para cada uno
            $mensaje_notif = "El administrador " . $_SESSION['nombre'] . " ha registrado un nuevo complejo: " . $nombre;
            
            $sql_notif = $conexion->prepare("INSERT INTO notificaciones (usuario_id, tipo, mensaje, leida) 
                                           VALUES (:id_usuario, 'sistema', :mensaje, 'no')");

            foreach ($usuarios_notif as $user) {
                $sql_notif->bindParam(":id_usuario", $user->id, PDO::PARAM_INT);
                $sql_notif->bindParam(":mensaje", $mensaje_notif, PDO::PARAM_STR);
                $sql_notif->execute();
            }

            // Si todo salió bien, confirmamos los cambios
            $conexion->commit();

            $_SESSION['exito']="¡Complejo registrado exitosamente!";
            header("Location: ../../admin/agregar_complejo.php");
            exit;
        }catch(PDOException $e){
            // Si algo falla, revertimos los cambios en la BD
            if($conexion->inTransaction()){
                $conexion->rollBack();
            }
            
            // Si se subió una imagen, podríamos intentar borrarla para no dejar basura
            if($imagen_nombre && file_exists(__DIR__ . "/../../app/fotos_complejos/" . $imagen_nombre)){
                unlink(__DIR__ . "/../../app/fotos_complejos/" . $imagen_nombre);
            }

            error_log("Error en transacción de complejo: " . $e->getMessage());
            $errores[]="Error crítico al guardar en la base de datos. Se han revertido los cambios.";
            $_SESSION['errores']=$errores;
            header("Location: ../../admin/agregar_complejo.php");
            exit;
        }
    }else{
        $_SESSION['errores']=$errores;
        header("Location: ../../admin/agregar_complejo.php");
        exit;
    }
}else{
    $_SESSION['errores'] = ["Solicitud no válida o datos incompletos"];
    header("Location: ../../admin/gestion_complejos.php");
    exit;
}



?>