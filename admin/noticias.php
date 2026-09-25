<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$noticia_edit = [
    'noticia_id'        => '',
    'titulo'            => '',
    'resumen'           => '',
    'foto_url'          => '',
    'link_externo'      => '',
    'fecha_publicacion' => date('Y-m-d')
];

$dir_uploads = __DIR__ . '/uploads/';

// 1. CARGAR NOTICIA PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM noticias WHERE noticia_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $noticia_edit = $res->fetch_assoc();
        $modo_edicion = true;
    }
}

// 2. ELIMINAR NOTICIA
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Obtener imagen para borrarla del servidor
    $res = $conexion->query("SELECT foto_url FROM noticias WHERE noticia_id = $id");
    if ($res && $res->num_rows > 0) {
        $n = $res->fetch_assoc();
        if (!empty($n['foto_url']) && file_exists($dir_uploads . $n['foto_url'])) {
            unlink($dir_uploads . $n['foto_url']);
        }
    }

    $conexion->query("DELETE FROM noticias WHERE noticia_id = $id");
    header("Location: noticias.php");
    exit();
}

// 3. CREAR O ACTUALIZAR NOTICIA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_noticia'])) {
    $noticia_id        = intval($_POST['noticia_id']);
    $titulo            = $conexion->real_escape_string($_POST['titulo']);
    $resumen           = $conexion->real_escape_string($_POST['resumen']);
    $link_externo      = $conexion->real_escape_string($_POST['link_externo']);
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $usuario_id        = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    // Crear carpeta uploads si no existe
    if (!is_dir($dir_uploads)) {
        mkdir($dir_uploads, 0777, true);
    }

    if ($noticia_id > 0) {
        // ACTUALIZAR REGISTRO EXISTENTE
        $sql_foto = "";

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            // Eliminar la foto anterior si existe
            $res_foto = $conexion->query("SELECT foto_url FROM noticias WHERE noticia_id = $noticia_id");
            if ($res_foto && $res_foto->num_rows > 0) {
                $foto_ant = $res_foto->fetch_assoc()['foto_url'];
                if (!empty($foto_ant) && file_exists($dir_uploads . $foto_ant)) {
                    unlink($dir_uploads . $foto_ant);
                }
            }

            // Subir nueva foto
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $nueva_foto = "not_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir_uploads . $nueva_foto)) {
                $sql_foto = ", foto_url = '$nueva_foto'";
            }
        }

        $sql = "UPDATE noticias SET
                    titulo = '$titulo',
                    resumen = '$resumen',
                    link_externo = '$link_externo',
                    fecha_publicacion = '$fecha_publicacion'
                    $sql_foto
                WHERE noticia_id = $noticia_id";

        if ($conexion->query($sql)) {
            $mensaje = "<div class='alert alert-success'>¡Noticia actualizada correctamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
        }

    } else {
        // CREAR NUEVO REGISTRO
        $foto_url = "";
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
            $foto_url = "not_" . time() . "." . $ext;
            move_uploaded_file($_FILES['foto']['tmp_name'], $dir_uploads . $foto_url);
        }

        $sql = "INSERT INTO noticias (titulo, resumen, foto_url, link_externo, fecha_publicacion, usuario_id, vistas)
                VALUES ('$titulo', '$resumen', '$foto_url', '$link_externo', '$fecha_publicacion', $usuario_id, 0)";

        if ($conexion->query($sql)) {
            $mensaje = "<div class='alert alert-success'>¡Noticia publicada correctamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
        }
    }
}

// CONSULTA GENERAL DE NOTICIAS
$noticias = $conexion->query("SELECT * FROM noticias ORDER BY noticia_id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Noticias - Panel Administrativo</title>
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
            <li class="nav-item"><a class="nav-link" href="autores.php"><i class="mdi mdi-account-edit menu-icon"></i><span class="menu-title">Autores</span></a></li>

            <!-- Pestaña Activa: Noticias -->
            <li class="nav-item active"><a class="nav-link" href="noticias.php"><i class="mdi mdi-newspaper menu-icon"></i><span class="menu-title">Noticias</span></a></li>
            <li class="nav-item"><a class="nav-link" href="boletines.php"><i class="mdi mdi-book-open-page-variant menu-icon"></i><span class="menu-title">Boletines</span></a></li>
            <li class="nav-item"><a class="nav-link" href="podcasts.php"><i class="mdi mdi-microphone menu-icon"></i><span class="menu-title">Podcasts</span></a></li>
            <li class="nav-item"><a class="nav-link" href="videos.php"><i class="mdi mdi-video menu-icon"></i><span class="menu-title">Videos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="pdf.php"><i class="mdi mdi-file-pdf menu-icon"></i><span class="menu-title">PDFs</span></a></li>
            <li class="nav-item"><a class="nav-link" href="fotos.php"><i class="mdi mdi-image menu-icon"></i><span class="menu-title">Fotos</span></a></li>

            <?php if (es_admin()): ?>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Noticia' : 'Publicar Nueva Noticia'; ?></h4>
              <form action="noticias.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="noticia_id" value="<?php echo $noticia_edit['noticia_id']; ?>">

                <div class="row">
                  <div class="col-md-8">
                    <div class="form-group">
                      <label>Título de la Noticia</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($noticia_edit['titulo']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Fecha de Publicación</label>
                      <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo $noticia_edit['fecha_publicacion']; ?>" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Imagen / Foto Destacada <?php echo $modo_edicion ? '(Opcional si no deseas cambiarla)' : ''; ?></label>
                      <input type="file" name="foto" class="form-control-file d-block" accept="image/*">
                      <?php if($modo_edicion && !empty($noticia_edit['foto_url'])): ?>
                        <div class="mt-2">
                          <small class="text-muted d-block">Foto actual:</small>
                          <img src="uploads/<?php echo $noticia_edit['foto_url']; ?>" width="60" height="60" style="object-fit:cover;" class="rounded border">
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Enlace Externo (Opcional)</label>
                      <input type="url" name="link_externo" class="form-control" value="<?php echo htmlspecialchars($noticia_edit['link_externo']); ?>" placeholder="https://...">
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Resumen / Bajada</label>
                  <textarea name="resumen" class="form-control" rows="3" required><?php echo htmlspecialchars($noticia_edit['resumen']); ?></textarea>
                </div>

                <button type="submit" name="guardar_noticia" class="btn btn-primary">
                  <?php echo $modo_edicion ? 'Actualizar Noticia' : 'Publicar Noticia'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="noticias.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Noticias Registradas</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Foto</th>
                      <th>Título</th>
                      <th>Enlace</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($noticias && $noticias->num_rows > 0): ?>
                      <?php while($n = $noticias->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <?php if($n['foto_url'] && file_exists('uploads/' . $n['foto_url'])): ?>
                            <img src="uploads/<?php echo $n['foto_url']; ?>" width="40" height="40" style="object-fit:cover;" class="rounded">
                          <?php else: ?>
                            <span class="text-muted">-</span>
                          <?php endif; ?>
                        </td>
                        <td><strong><?php echo htmlspecialchars($n['titulo']); ?></strong></td>
                        <td>
                          <?php if($n['link_externo']): ?>
                            <a href="<?php echo $n['link_externo']; ?>" target="_blank">Ver Enlace</a>
                          <?php else: ?>
                            -
                          <?php endif; ?>
                        </td>
                        <td><span class="badge badge-primary"><?php echo $n['vistas']; ?></span></td>
                        <td>
                          <a href="noticias.php?editar=<?php echo $n['noticia_id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="noticias.php?eliminar=<?php echo $n['noticia_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Deseas eliminar esta noticia?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No hay noticias registradas.</td>
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
