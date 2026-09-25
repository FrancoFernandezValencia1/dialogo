<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Requerir inicio de sesión
function requerir_login() {
    if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
        header("Location: index.php");
        exit();
    }
}

// Requerir roles específicos (ej. ['admin'])
function requerir_rol($roles_permitidos = []) {
    requerir_login();
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        header("Location: home.php?error=acceso_denegado");
        exit();
    }
}

// Helper para ocultar o mostrar menú de administración en el header
function es_admin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
?>
