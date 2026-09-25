<?php
require_once("conexion.php");

// MODOS DE FUNCIONAMIENTO:
// true  = Muestra el enlace en pantalla (Ideal para XAMPP / Localhost / Pruebas)
// false = Envía el correo privado y oculta el enlace (Para hosting real)
$modo_desarrollo = true;

$mensaje = "";
$tipo = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // 1. Verificar si el usuario existe y está activo
        $stmt = $conexion->prepare("SELECT user_id, nombre_completo FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $usuario = $res->fetch_assoc();

            // 2. Generar token seguro y fecha de expiración (1 hora)
            $token = bin2hex(random_bytes(32));
            $expira = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // 3. Guardar token en la base de datos
            $update = $conexion->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expira = ?, updated_at = NOW() WHERE email = ?");
            $update->bind_param("sss", $token, $expira, $email);

            if ($update->execute()) {
                // 4. Construir el enlace
                $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $link = $protocolo . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/restablecer.php?token=" . $token;

                if ($modo_desarrollo) {
                    // MODO DESARROLLO: Muestra el enlace directo para hacer pruebas en Localhost
                    $mensaje = "<strong>[MODO PRUEBAS LOCAL]</strong> Enlace generado con éxito:<br><a href='$link' class='alert-link'>$link</a>";
                    $tipo = "warning";
                } else {
                    // MODO PRODUCCIÓN: Envía el correo
                    $asunto = "Restablecer Contrasena - Panel de Control";
                    $cuerpo = "Hola " . $usuario['nombre_completo'] . ",\n\n";
                    $cuerpo .= "Haz clic en el siguiente enlace para restablecer tu contraseña:\n\n" . $link;
                    $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'];

                    @mail($email, $asunto, $cuerpo, $headers);

                    $mensaje = "Si el correo está registrado en el sistema, recibirás un enlace con las instrucciones en tu bandeja de entrada.";
                    $tipo = "info";
                }
            }
        } else {
            if ($modo_desarrollo) {
                $mensaje = "El correo ingresado no existe en la base de datos o está inactivo.";
                $tipo = "danger";
            } else {
                $mensaje = "Si el correo está registrado en el sistema, recibirás un enlace con las instrucciones en tu bandeja de entrada.";
                $tipo = "info";
            }
        }
    } else {
        $mensaje = "Por favor, ingresa un correo electrónico válido.";
        $tipo = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light d-flex align-items-center vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="card-title text-center font-weight-bold mb-3">Recuperar Contraseña</h4>

                    <?php if ($mensaje): ?>
                        <div class="alert alert-<?php echo $tipo; ?> alert-dismissible fade show" role="alert">
                            <?php echo $mensaje; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="recuperar.php">
                        <div class="form-group">
                            <label for="email" class="font-weight-bold">Correo Electrónico registrado</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="admin@ejemplo.com">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold">Enviar / Generar Enlace</button>
                    </form>

                    <div class="text-center mt-4">
                        <a href="index.php" class="text-secondary small">← Volver al inicio de sesión</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
