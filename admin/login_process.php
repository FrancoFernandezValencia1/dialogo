<?php
session_start();
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {

        // Buscamos al usuario por su email
        $stmt = $conexion->prepare("SELECT user_id, nombre_completo, email, password_hash, rol, activo FROM usuarios WHERE email = ?");

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($usuario = $resultado->fetch_assoc()) {

                // 1. Validar si la cuenta está activa
                if (isset($usuario['activo']) && $usuario['activo'] == 0) {
                    header("Location: index.php?error=inactivo");
                    exit();
                }

                $password_correcta = false;

                // 2. Comprobar con BCRYPT (Punto 1 de la Rúbrica)
                if (password_verify($password, $usuario['password_hash'])) {
                    $password_correcta = true;
                }
                // Fallback para contraseñas antiguas en SHA256 y auto-migración a BCRYPT
                elseif ($usuario['password_hash'] === hash('sha256', $password)) {
                    $password_correcta = true;
                    $nuevo_hash = password_hash($password, PASSWORD_BCRYPT);
                    $upd = $conexion->prepare("UPDATE usuarios SET password_hash = ? WHERE user_id = ?");
                    $upd->bind_param("si", $nuevo_hash, $usuario['user_id']);
                    $upd->execute();
                }

                if ($password_correcta) {
                    // Guardamos las variables de sesión clave para el control de roles
                    $_SESSION['admin_logged'] = true;
                    $_SESSION['user_id']      = $usuario['user_id'];
                    $_SESSION['usuario']      = $usuario['nombre_completo'];
                    $_SESSION['rol']          = $usuario['rol']; // 'admin' o 'periodista'

                    header("Location: home.php");
                    exit();
                }
            }

            // Credenciales incorrectas
            header("Location: index.php?error=1");
            exit();
            $stmt->close();
        } else {
            die("Error en la consulta: " . $conexion->error);
        }
    } else {
        header("Location: index.php?error=1");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>
