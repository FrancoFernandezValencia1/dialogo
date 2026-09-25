<?php
require_once("conexion.php");

$token = $_GET['token'] ?? '';
$mensaje = "";
$valido = false;

if (!empty($token)) {
    $stmt = $conexion->prepare("SELECT user_id FROM usuarios WHERE token_recuperacion = ? AND token_expira > NOW() AND activo = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $valido = true;
    } else {
        $mensaje = "El enlace es inválido o ya expiró.";
    }
} else {
    $mensaje = "Token no especificado.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valido) {
    $nueva_pass = $_POST['password'];

    if (strlen($nueva_pass) >= 6) {
        // Generación de Hash BCRYPT exigido en la rúbrica
        $hash = password_hash($nueva_pass, PASSWORD_BCRYPT);

        $update = $conexion->prepare("UPDATE usuarios SET password_hash = ?, token_recuperacion = NULL, token_expira = NULL, updated_at = NOW() WHERE token_recuperacion = ?");
        $update->bind_param("ss", $hash, $token);

        if ($update->execute()) {
            $mensaje = "¡Contraseña actualizada con éxito! <a href='index.php'>Iniciar Sesión</a>";
            $valido = false;
        }
    } else {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title text-center mb-4">Nueva Contraseña</h4>
                    <?php if ($mensaje): ?>
                        <div class="alert alert-info"><?php echo $mensaje; ?></div>
                    <?php endif; ?>

                    <?php if ($valido): ?>
                    <form method="POST">
                        <div class="form-group">
                            <label>Escribe tu nueva contraseña</label>
                            <input type="password" name="password" class="form-control" minlength="6" required placeholder="Mínimo 6 caracteres">
                        </div>
                        <button type="submit" class="btn btn-success btn-block">Guardar Nueva Contraseña</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
