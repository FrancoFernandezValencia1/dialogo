<?php
session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    header("Location: index.php");
    exit();
}

require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$autor_edit = ['autor_id' => '', 'nombre' => '', 'ap_paterno' => '', 'ap_materno' => '', 'nickname' => '', 'usar_nickname' => 0, 'biografia' => ''];

// 1. CARGAR DATOS PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM autores WHERE autor_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $autor_edit = $res->fetch_assoc();
        $modo_edicion = true;
    }
}

// 2. CREAR O ACTUALIZAR AUTOR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_autor'])) {
    $autor_id      = intval($_POST['autor_id']);
    $nombre        = $conexion->real_escape_string($_POST['nombre']);
    $ap_paterno    = $conexion->real_escape_string($_POST['ap_paterno']);
    $ap_materno    = $conexion->real_escape_string($_POST['ap_materno']);
    $nickname      = $conexion->real_escape_string($_POST['nickname']);
    $usar_nickname = isset($_POST['usar_nickname']) ? 1 : 0;
    $biografia     = $conexion->real_escape_string($_POST['biografia']);

    if ($autor_id > 0) {
        // ACTUALIZAR REGISTRO EXISTENTE
        $sql = "UPDATE autores SET
                nombre = '$nombre',
                ap_paterno = '$ap_paterno',
                ap_materno = '$ap_materno',
                nickname = '$nickname',
                usar_nickname = $usar_nickname,
                biografia = '$biografia'
                WHERE autor_id = $autor_id";
        if ($conexion->query($sql)) {
            $mensaje = "<div class='alert alert-success'>¡Autor actualizado con éxito!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
        }
    } else {
        // CREAR NUEVO REGISTRO
        $sql = "INSERT INTO autores (nombre, ap_paterno, ap_materno, nickname, usar_nickname, biografia)
                VALUES ('$nombre', '$ap_paterno', '$ap_materno', '$nickname', $usar_nickname, '$biografia')";
        if ($conexion->query($sql)) {
            $mensaje = "<div class='alert alert-success'>¡Autor registrado con éxito!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
        }
    }
}

// 3. CAMBIAR PREFERENCIA RÁPIDA (NickName <-> Nombre Real)
if (isset($_GET['cambiar_pref'])) {
    $id     = intval($_GET['cambiar_pref']);
    $estado = intval($_GET['estado']);
    $conexion->query("UPDATE autores SET usar_nickname = $estado WHERE autor_id = $id");
    header("Location: autores.php");
    exit();
}

// 4. ELIMINAR AUTOR
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conexion->query("DELETE FROM autores WHERE autor_id = $id");
    header("Location: autores.php");
    exit();
}

$autores = $conexion->query("SELECT * FROM autores ORDER BY nombre ASC, ap_paterno ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Autores - Panel Administrativo</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container-scroller">
  <div class="horizontal-menu">
    <nav class="navbar top-navbar col-lg-12 col-12 p-0">
      <div class="container-fluid">
        <div class="navbar-menu-wrapper d-flex align-items-center justify-content-between">

          <!-- LOGO CON TAMAÑO CONTROLADO -->
          <a class="navbar-brand brand-logo" href="home.php">
            <img src="images/logo.png" alt="logo" style="height: 40px; width: auto; object-fit: contain;" />
          </a>

          <div class="d-flex align-items-center">
            <span class="mr-3 font-weight-bold text-dark me-3">
              <?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? $_SESSION['nombre'] ?? 'Usuario'); ?>
            </span>
            <a class="btn btn-outline-danger btn-sm" href="logout.php">Cerrar Sesión</a>
          </div>
        </div>
      </div>
    </nav>
    <nav class="bottom-navbar">
      <div class="container">
        <ul class="nav page-navigation">
          <li class="nav-item"><a class="nav-link" href="home.php"><i class="mdi mdi-chart-bar menu-icon"></i><span class="menu-title">Métricas / Inicio</span></a></li>
          <li class="nav-item"><a class="nav-link" href="reportajes.php"><i class="mdi mdi-file-document menu-icon"></i><span class="menu-title">Reportajes</span></a></li>

            <!-- Pestaña Activa: Autores -->
            <li class="nav-item active"><a class="nav-link" href="autores.php"><i class="mdi mdi-account-edit menu-icon"></i><span class="menu-title">Autores</span></a></li>
            <li class="nav-item"><a class="nav-link" href="noticias.php"><i class="mdi mdi-newspaper menu-icon"></i><span class="menu-title">Noticias</span></a></li>
            <li class="nav-item"><a class="nav-link" href="boletines.php"><i class="mdi mdi-book-open-page-variant menu-icon"></i><span class="menu-title">Boletines</span></a></li>
            <li class="nav-item"><a class="nav-link" href="podcasts.php"><i class="mdi mdi-microphone menu-icon"></i><span class="menu-title">Podcasts</span></a></li>
            <li class="nav-item"><a class="nav-link" href="videos.php"><i class="mdi mdi-video menu-icon"></i><span class="menu-title">Videos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="pdf.php"><i class="mdi mdi-file-pdf menu-icon"></i><span class="menu-title">PDFs</span></a></li>
            <li class="nav-item"><a class="nav-link" href="fotos.php"><i class="mdi mdi-image menu-icon"></i><span class="menu-title">Fotos</span></a></li>

            <?php if (es_admin()): ?>
              <!-- Visible SOLO para Administradores -->
              <li class="nav-item"><a class="nav-link" href="usuarios.php"><i class="mdi mdi-account-group menu-icon"></i><span class="menu-title">Usuarios</span></a></li>
            <?php endif; ?>
          </ul>
        </div>
      </nav>
    </div>

    <div class="container-fluid page-body-wrapper">
      <div class="main-panel">
        <div class="content-wrapper">
          <?php echo $mensaje; ?>

          <div class="card mb-4">
            <div class="card-body">
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Autor' : 'Registrar Nuevo Autor'; ?></h4>
              <form action="autores.php" method="POST">
                <input type="hidden" name="autor_id" value="<?php echo $autor_edit['autor_id']; ?>">

                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Nombre(s)</label>
                      <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($autor_edit['nombre']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Apellido Paterno</label>
                      <input type="text" name="ap_paterno" class="form-control" value="<?php echo htmlspecialchars($autor_edit['ap_paterno']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Apellido Materno</label>
                      <input type="text" name="ap_materno" class="form-control" value="<?php echo htmlspecialchars($autor_edit['ap_materno']); ?>">
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>NickName (Seudónimo)</label>
                      <input type="text" name="nickname" class="form-control" value="<?php echo htmlspecialchars($autor_edit['nickname']); ?>">
                    </div>
                  </div>
                </div>

                <div class="row align-items-center mb-3">
                  <div class="col-md-6">
                    <div class="p-2 border rounded bg-light">
                      <label style="cursor: pointer; display: flex; align-items: center; margin-bottom: 0; font-weight: bold; font-size: 13px;">
                        <input type="checkbox" name="usar_nickname" value="1" <?php echo ($autor_edit['usar_nickname'] == 1) ? 'checked' : ''; ?> style="width: 18px; height: 18px; margin-right: 8px;">
                        Usar NickName públicamente
                      </label>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Biografía Resumida</label>
                  <textarea name="biografia" class="form-control" rows="3"><?php echo htmlspecialchars($autor_edit['biografia']); ?></textarea>
                </div>

                <button type="submit" name="guardar_autor" class="btn btn-info">
                  <?php echo $modo_edicion ? 'Actualizar Autor' : 'Guardar Autor'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="autores.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Autores Registrados</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Nombre Completo (Real)</th>
                      <th>NickName</th>
                      <th>Mostrar Públicamente</th>
                      <th>Biografía</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($autores && $autores->num_rows > 0): ?>
                      <?php while($a = $autores->fetch_assoc()): ?>
                      <?php $nombre_completo_real = trim($a['nombre'] . ' ' . $a['ap_paterno'] . ' ' . $a['ap_materno']); ?>
                      <tr>
                        <td><strong><?php echo htmlspecialchars($nombre_completo_real); ?></strong></td>
                        <td><?php echo !empty($a['nickname']) ? htmlspecialchars($a['nickname']) : '<span class="text-muted">Ninguno</span>'; ?></td>
                        <td>
                          <?php if($a['usar_nickname'] == 1 && !empty($a['nickname'])): ?>
                            <a href="autores.php?cambiar_pref=<?php echo $a['autor_id']; ?>&estado=0" class="btn btn-warning btn-sm text-white">Usando NickName</a>
                          <?php else: ?>
                            <a href="autores.php?cambiar_pref=<?php echo $a['autor_id']; ?>&estado=1" class="btn btn-secondary btn-sm">Usando Nombre Real</a>
                          <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($a['biografia']); ?></td>
                        <td>
                          <a href="autores.php?editar=<?php echo $a['autor_id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="autores.php?eliminar=<?php echo $a['autor_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar autor?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No hay autores registrados.</td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
  <script src="vendors/base/vendor.bundle.base.js"></script>
</body>
</html>
