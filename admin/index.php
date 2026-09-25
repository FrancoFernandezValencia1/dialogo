<?php
session_start();

// Si el usuario ya está autenticado, lo envía a home.php
if (isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true) {
    header("Location: home.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Iniciar Sesión - Dashboard</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="shortcut icon" href="images/favicon.png" />
</head>

<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="main-panel">
        <div class="content-wrapper d-flex align-items-center auth px-0">
          <div class="row w-100 mx-0">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left py-5 px-4 px-sm-5">
                <div class="brand-logo">
                  <img src="images/logo.png" alt="logo">
                </div>
                <h4>¡Hola! Comencemos</h4>
                <h6 class="font-weight-light">Inicia sesión para continuar.</h6>

                <?php if (isset($_GET['error'])): ?>
                  <?php if ($_GET['error'] == 'inactivo'): ?>
                    <div class="alert alert-warning p-2 text-center mt-3" role="alert" style="font-size: 14px;">
                      Tu cuenta se encuentra desactivada.
                    </div>
                  <?php else: ?>
                    <div class="alert alert-danger p-2 text-center mt-3" role="alert" style="font-size: 14px;">
                      Usuario o contraseña incorrectos.
                    </div>
                  <?php endif; ?>
                <?php endif; ?>

                <form class="pt-3" action="login_process.php" method="POST">
                  <div class="form-group">
                    <input type="text" name="username" class="form-control form-control-lg" id="exampleInputUsername1" placeholder="Correo / Usuario" required>
                  </div>

                  <div class="form-group">
                    <input type="password" name="password" class="form-control form-control-lg" id="exampleInputPassword1" placeholder="Contraseña" required>
                  </div>

                  <div class="mt-3">
                    <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">INICIAR SESIÓN</button>
                  </div>

                  <div class="my-2 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                      <label class="form-check-label text-muted">
                        <input type="checkbox" class="form-check-input">
                        Mantenerme conectado
                      </label>
                    </div>
                    <!-- Enlace directo al módulo de recuperación -->
                    <a href="recuperar.php" class="auth-link text-black">¿Olvidaste tu contraseña?</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="vendors/base/vendor.bundle.base.js"></script>
  <script src="js/template.js"></script>
</body>

</html>
