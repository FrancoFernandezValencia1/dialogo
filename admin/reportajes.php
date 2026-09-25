<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$reportaje_edit = [
    'reportaje_id'      => '',
    'titulo'            => '',
    'resumen_corto'     => '',
    'desarrollo'        => '',
    'fecha_publicacion' => date('Y-m-d'),
    'autor_id'          => '',
    'es_destacado'      => 0
];

// Definir carpeta de destino
$carpeta_destino = "images/fotos/";
if (!file_exists($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

// 1. CARGAR REPORTAJE PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM reportajes WHERE reportaje_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $reportaje_edit = $res->fetch_assoc();
        $modo_edicion = true;
    }
}

// 2. ELIMINAR REPORTAJE
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    if ($id > 0) {
        $res_fotos = $conexion->query("SELECT url_foto FROM reportajes_fotos WHERE reportaje_id = $id");
        if ($res_fotos) {
            while ($f = $res_fotos->fetch_assoc()) {
                if (!empty($f['url_foto']) && file_exists($f['url_foto'])) {
                    unlink($f['url_foto']);
                }
            }
        }
        $conexion->query("DELETE FROM reportajes_fotos WHERE reportaje_id = $id");
        $conexion->query("DELETE FROM reportajes WHERE reportaje_id = $id");
    }

    header("Location: reportajes.php");
    exit();
}

// 3. CREAR O ACTUALIZAR REPORTAJE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_reportaje'])) {
    $reportaje_id      = intval($_POST['reportaje_id']);
    $titulo            = $conexion->real_escape_string($_POST['titulo']);
    $resumen_corto     = $conexion->real_escape_string($_POST['resumen_corto']);
    $desarrollo        = $conexion->real_escape_string($_POST['desarrollo']);
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $autor_id          = !empty($_POST['autor_id']) ? intval($_POST['autor_id']) : "NULL";
    $es_destacado      = isset($_POST['es_destacado']) ? 1 : 0;
    $usuario_id        = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    if ($reportaje_id > 0) {
        // ACTUALIZAR REGISTRO EXISTENTE
        $sql = "UPDATE reportajes SET
                    titulo = '$titulo',
                    resumen_corto = '$resumen_corto',
                    desarrollo = '$desarrollo',
                    fecha_publicacion = '$fecha_publicacion',
                    autor_id = $autor_id,
                    es_destacado = $es_destacado
                WHERE reportaje_id = $reportaje_id";

        if ($conexion->query($sql)) {
            $mensaje = "<div class='alert alert-success'>¡Reportaje actualizado exitosamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
        }
    } else {
        // CREAR NUEVO REGISTRO
        $sql = "INSERT INTO reportajes (titulo, resumen_corto, desarrollo, fecha_publicacion, es_destacado, autor_id, usuario_id, vistas)
                VALUES ('$titulo', '$resumen_corto', '$desarrollo', '$fecha_publicacion', $es_destacado, $autor_id, $usuario_id, 0)";

        if ($conexion->query($sql)) {
            $reportaje_id = $conexion->insert_id;
            $mensaje = "<div class='alert alert-success'>¡Reportaje publicado exitosamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
        }
    }

    // PROCESAR FOTOS (tanto para nuevo como al editar si sube imágenes)
    if ($reportaje_id > 0 && isset($_FILES['fotos']) && !empty($_FILES['fotos']['name'][0])) {
        foreach ($_FILES['fotos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['fotos']['error'][$key] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['fotos']['name'][$key], PATHINFO_EXTENSION);
                $nombre_archivo = "rep_" . time() . "_" . $key . "_" . rand(100, 999) . "." . $ext;
                $url_foto = $carpeta_destino . $nombre_archivo;

                if (move_uploaded_file($tmp_name, $url_foto)) {
                    $es_principal = ($key === 0) ? 1 : 0;

                    $conexion->query("INSERT INTO reportajes_fotos (reportaje_id, url_foto, orden, es_principal)
                                     VALUES ($reportaje_id, '$url_foto', $key, $es_principal)");

                    $desc_foto = $conexion->real_escape_string("Foto del reportaje: " . $titulo);
                    $conexion->query("INSERT INTO fotos (titulo, url_foto, descripcion, fecha_publicacion, usuario_id, vistas)
                                     VALUES ('$titulo', '$url_foto', '$desc_foto', '$fecha_publicacion', $usuario_id, 0)");
                }
            }
        }
    }
}

// CONSULTA AUTORES
$autores = $conexion->query("
    SELECT autor_id,
           IF(usar_nickname = 1 AND nickname IS NOT NULL AND nickname != '',
              nickname,
              TRIM(CONCAT_WS(' ', nombre, ap_paterno, ap_materno))
           ) AS nombre_mostrar
    FROM autores
    ORDER BY nombre_mostrar ASC
");

// CONSULTA REPORTAJES
$reportajes = $conexion->query("
    SELECT r.*,
           IF(a.usar_nickname = 1 AND a.nickname IS NOT NULL AND a.nickname != '',
              a.nickname,
              TRIM(CONCAT_WS(' ', a.nombre, a.ap_paterno, a.ap_materno))
           ) AS nombre_autor
    FROM reportajes r
    LEFT JOIN autores a ON r.autor_id = a.autor_id
    ORDER BY r.reportaje_id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Reportajes - Panel Administrativo</title>
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

            <!-- Pestaña Activa: Reportajes -->
            <li class="nav-item active"><a class="nav-link" href="reportajes.php"><i class="mdi mdi-file-document menu-icon"></i><span class="menu-title">Reportajes</span></a></li>
            <li class="nav-item"><a class="nav-link" href="autores.php"><i class="mdi mdi-account-edit menu-icon"></i><span class="menu-title">Autores</span></a></li>
            <li class="nav-item"><a class="nav-link" href="noticias.php"><i class="mdi mdi-newspaper menu-icon"></i><span class="menu-title">Noticias</span></a></li>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Reportaje' : 'Crear Nuevo Reportaje'; ?></h4>
              <form action="reportajes.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="reportaje_id" value="<?php echo $reportaje_edit['reportaje_id']; ?>">

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Título del Reportaje</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($reportaje_edit['titulo']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Autor</label>
                      <select name="autor_id" class="form-control">
                        <option value="">-- Seleccionar Autor --</option>
                        <?php while($a = $autores->fetch_assoc()): ?>
                          <option value="<?php echo $a['autor_id']; ?>" <?php echo ($reportaje_edit['autor_id'] == $a['autor_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($a['nombre_mostrar']); ?>
                          </option>
                        <?php endwhile; ?>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Fecha de Publicación</label>
                      <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo $reportaje_edit['fecha_publicacion']; ?>" required>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Resumen Corto</label>
                  <textarea name="resumen_corto" class="form-control" rows="2" required><?php echo htmlspecialchars($reportaje_edit['resumen_corto']); ?></textarea>
                </div>

                <div class="form-group">
                  <label>Desarrollo / Contenido Completo</label>
                  <textarea name="desarrollo" class="form-control" rows="5" required><?php echo htmlspecialchars($reportaje_edit['desarrollo']); ?></textarea>
                </div>

                <div class="row align-items-center">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Galería de Fotos <?php echo $modo_edicion ? '(Opcional: Agregar más imágenes)' : '(Se guardarán en images/fotos/)'; ?></label>
                      <input type="file" name="fotos[]" class="form-control-file d-block" multiple accept="image/*">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="p-2 border rounded bg-light mt-3">
                      <label style="cursor: pointer; display: flex; align-items: center; margin-bottom: 0; font-weight: bold; color: #333;">
                        <input type="checkbox" name="es_destacado" value="1" <?php echo ($reportaje_edit['es_destacado'] == 1) ? 'checked' : ''; ?> style="width: 20px; height: 20px; margin-right: 10px; cursor: pointer;">
                        Marcar este reportaje como Destacado en Portada
                      </label>
                    </div>
                  </div>
                </div>

                <button type="submit" name="guardar_reportaje" class="btn btn-warning text-white mt-4">
                  <?php echo $modo_edicion ? 'Actualizar Reportaje' : 'Guardar Reportaje'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="reportajes.php" class="btn btn-light mt-4">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Reportajes Registrados</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Título</th>
                      <th>Autor</th>
                      <th>Fecha</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($reportajes && $reportajes->num_rows > 0): ?>
                      <?php while($r = $reportajes->fetch_assoc()): ?>
                      <tr>
                        <td><?php echo $r['reportaje_id']; ?></td>
                        <td>
                          <strong><?php echo htmlspecialchars($r['titulo']); ?></strong>
                          <?php if($r['es_destacado'] == 1): ?>
                            <span class="badge badge-warning text-white ml-1">Destacado</span>
                          <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['nombre_autor'] ?? 'Sin autor'); ?></td>
                        <td><?php echo $r['fecha_publicacion']; ?></td>
                        <td><span class="badge badge-warning text-white"><?php echo $r['vistas']; ?></span></td>
                        <td>
                          <a href="reportajes.php?editar=<?php echo $r['reportaje_id']; ?>" class="btn btn-primary btn-sm">
                             Editar
                          </a>
                          <a href="reportajes.php?eliminar=<?php echo $r['reportaje_id']; ?>"
                             class="btn btn-danger btn-sm"
                             onclick="return confirm('¿Estás seguro de que deseas eliminar este reportaje?');">
                             Eliminar
                          </a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No hay reportajes registrados.</td>
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
