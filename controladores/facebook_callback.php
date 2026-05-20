<?php
// Configurar cookies de sesión para OAuth (debe coincidir con facebook_login.php)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false, // Cambiar a true en producción con HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();
require_once '../config/oauth_config.php';
require_once '../conexion/bd.php';

// Verificar estado CSRF
if (!isset($_GET['state'])) {
    $_SESSION['errores'] = ['Error de seguridad: No se recibió el parámetro de estado'];
    header('Location: ../login.php');
    exit;
}

if (!isset($_SESSION['oauth_state'])) {
    $_SESSION['errores'] = ['Error de seguridad: La sesión expiró. Por favor, intenta nuevamente.'];
    header('Location: ../login.php');
    exit;
}

if ($_GET['state'] !== $_SESSION['oauth_state']) {
    $_SESSION['errores'] = ['Error de seguridad: El estado no coincide'];
    header('Location: ../login.php');
    exit;
}

// Limpiar el estado usado
unset($_SESSION['oauth_state']);

if (!isset($_GET['code'])) {
    $_SESSION['errores'] = ['Error de autorización'];
    header('Location: ../login.php');
    exit;
}

try {
    // Obtener token
    $tokenUrl = 'https://graph.facebook.com/v18.0/oauth/access_token?' . http_build_query([
        'client_id' => FACEBOOK_APP_ID,
        'client_secret' => FACEBOOK_APP_SECRET,
        'code' => $_GET['code'],
        'redirect_uri' => FACEBOOK_REDIRECT_URI
    ]);

    $tokenData = json_decode(file_get_contents($tokenUrl), true);

    if (!isset($tokenData['access_token'])) {
        throw new Exception('No se obtuvo token');
    }

    // Obtener datos del usuario
    $userUrl = 'https://graph.facebook.com/v18.0/me?' . http_build_query([
        'fields' => 'id,name,email',
        'access_token' => $tokenData['access_token']
    ]);

    $userData = json_decode(file_get_contents($userUrl), true);

    if (empty($userData['email'])) {
        throw new Exception('No se pudo obtener email');
    }

    // Buscar usuario existente
    $sql = $conexion->prepare("SELECT * FROM usuarios WHERE email = ? OR facebook_id = ?");
    $sql->execute([$userData['email'], $userData['id']]);
    $usuario = $sql->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Verificar que solo los clientes puedan iniciar sesión con Facebook
        if ($usuario['rol'] !== 'cliente') {
            throw new Exception('El inicio de sesión con Facebook solo está habilitado para clientes.');
        }

        // Actualizar facebook_id si no existe
        if (empty($usuario['facebook_id'])) {
            $update = $conexion->prepare("UPDATE usuarios SET facebook_id = ? WHERE id = ?");
            $update->execute([$userData['id'], $usuario['id']]);
        }
    } else {
        // Crear nuevo usuario (solo cliente)
        $insert = $conexion->prepare("
            INSERT INTO usuarios (nombre, email, facebook_id, rol) 
            VALUES (?, ?, ?, 'cliente')
        ");
        $insert->execute([
            $userData['name'],
            $userData['email'],
            $userData['id']
        ]);

        $sql = $conexion->prepare("SELECT * FROM usuarios WHERE id = ?");
        $sql->execute([$conexion->lastInsertId()]);
        $usuario = $sql->fetch(PDO::FETCH_ASSOC);
    }

    // Iniciar sesión (usando sintaxis de array, ya que fetch fue con PDO::FETCH_ASSOC)
     $_SESSION['id_usuario'] = $usuario['id'];
     $_SESSION['nombre'] = $usuario['nombre'];
     $_SESSION['rol'] = $usuario['rol'];
     $_SESSION['logueado'] = true;

    // Redirigir según rol
    switch ($usuario['rol']) {
        case 'admin':
            header('Location: ../admin/dash_admin.php');
            break;
        case 'recepcionista':
            header('Location: ../recepcionista/dash_recepcionista.php');
            break;
        case 'cliente':
            header('Location: ../cliente/dash_cliente.php');
            break;
        default:
            header('Location: ../login.php');
    }
    exit;
} catch (Exception $e) {
    $_SESSION['errores'] = ['Error: ' . $e->getMessage()];
    header('Location: ../login.php');
    exit;
}
