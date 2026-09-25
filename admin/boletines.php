<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$boletin_edit = [
    'boletin_id'        => '',
    'numero_boletin'    => '',
    'titulo'            => '',
    'resumen'           => '',
    'foto_portada_url'  => '',
    'archivo_pdf_url'   => '',
    'fecha_publicacion' => date('Y-m-d')
];

// Función para obtener la ruta absoluta real de un archivo alojado (raíz o uploads/)
function obtener_ruta_real($nombre_archivo) {
    if (empty($nombre_archivo)) return null;
    if (file_exists(__DIR__ . '/' . $nombre_archivo)) {
        return __DIR__ . '/' . $nombre_archivo;
    }
    if (file_exists(__DIR__ . '/uploads/' . $nombre_archivo)) {
        return __DIR__ . '/uploads/' . $nombre_archivo;
    }
    return null;
}

// 1. CARGAR BOLETÍN PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM boletines WHERE boletin_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $boletin_edit = $res->fetch_assoc();
        $modo_edicion = true;
    }
}

// 2. ELIMINAR BOLETÍN Y SUS ARCHIVOS
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    // Obtener datos de archivos para borrarlos físicamente
    $res = $conexion->query("SELECT foto_portada_url, archivo_pdf_url FROM boletines WHERE boletin_id = $id");
    if ($res && $res->num_rows > 0) {
        $b = $res->fetch_assoc();

        $ruta_img = obtener_ruta_real($b['foto_portada_url']);
        if ($ruta_img && is_file($ruta_img)) unlink($ruta_img);

        $ruta_pdf = obtener_ruta_real($b['archivo_pdf_url']);
        if ($ruta_pdf && is_file($ruta_pdf)) unlink($ruta_pdf);
    }

    $conexion->query("DELETE FROM boletines WHERE boletin_id = $id");
    header("Location: boletines.php");
    exit();
}

// 3. CREAR O ACTUALIZAR BOLETÍN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_boletin'])) {
    $boletin_id        = intval($_POST['boletin_id']);
    $numero_boletin    = intval($_POST['numero_boletin']);
    $titulo            = $conexion->real_escape_string($_POST['titulo']);
    $resumen           = $conexion->real_escape_string($_POST['resumen']);
    $fecha_publicacion = $_POST['fecha_publicacion'];
    $usuario_id        = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1;

    if ($boletin_id > 0) {
        // --- ACTUALIZAR BOLETÍN EXISTENTE ---

        // Obtener datos antiguos
        $res_ant = $conexion->query("SELECT foto_portada_url, archivo_pdf_url FROM boletines WHERE boletin_id = $boletin_id");
        $datos_ant = $res_ant ? $res_ant->fetch_assoc() : null;

        $sql_extra = "";

        // Subir nueva Portada si se seleccionó una
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            // Eliminar imagen anterior
            if ($datos_ant && !empty($datos_ant['foto_portada_url'])) {
                $ruta_ant = obtener_ruta_real($datos_ant['foto_portada_url']);
                if ($ruta_ant && is_file($ruta_ant)) unlink($ruta_ant);
            }
            // Subir nueva
            $ext = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
            $nueva_portada = "bol_port_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['portada']['tmp_name'], $nueva_portada)) {
                $sql_extra .= ", foto_portada_url = '$nueva_portada'";
            }
        }

        // Subir nuevo PDF si se seleccionó uno
        $nuevo_pdf_subido = false;
        $archivo_pdf_url_nuevo = "";
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            // Eliminar PDF anterior
            if ($datos_ant && !empty($datos_ant['archivo_pdf_url'])) {
                $ruta_ant_pdf = obtener_ruta_real($datos_ant['archivo_pdf_url']);
                if ($ruta_ant_pdf && is_file($ruta_ant_pdf)) unlink($ruta_ant_pdf);
            }
            // Subir nuevo
            $ext_pdf = pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION);
            $archivo_pdf_url_nuevo = "bol_doc_" . time() . "." . $ext_pdf;
            if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $archivo_pdf_url_nuevo)) {
                $sql_extra .= ", archivo_pdf_url = '$archivo_pdf_url_nuevo'";
                $nuevo_pdf_subido = true;
            }
        }

        $sql = "UPDATE boletines SET
                    numero_boletin = $numero_boletin,
                    titulo = '$titulo',
                    resumen = '$resumen',
                    fecha_publicacion = '$fecha_publicacion'
                    $sql_extra
                WHERE boletin_id = $boletin_id";

        if ($conexion->query($sql)) {
            // Si subió un nuevo PDF, actualizar o registrar en la tabla 'pdfs'
            if ($nuevo_pdf_subido) {
                $conexion->query("INSERT INTO pdfs (titulo, url_archivo, descripcion, usuario_id)
                                 VALUES ('Boletín N° $numero_boletin - $titulo', '$archivo_pdf_url_nuevo', '$resumen', $usuario_id)");
            }

            $mensaje = "<div class='alert alert-success'>¡Boletín actualizado exitosamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
        }

    } else {
        // --- CREAR NUEVO BOLETÍN ---

        // 1. Subir Foto de Portada DIRECTO EN LA RAÍZ
        $foto_portada_url = "";
        if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['portada']['name'], PATHINFO_EXTENSION);
            $foto_portada_url = "bol_port_" . time() . "." . $ext;
            move_uploaded_file($_FILES['portada']['tmp_name'], $foto_portada_url);
        }

        // 2. Subir Archivo PDF DIRECTO EN LA RAÍZ
        $archivo_pdf_url = "";
        if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
            $ext_pdf = pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION);
            $archivo_pdf_url = "bol_doc_" . time() . "." . $ext_pdf;
            move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $archivo_pdf_url);
        }

        // 3. Insertar en la tabla 'boletines'
        $sql = "INSERT INTO boletines (numero_boletin, titulo, resumen, foto_portada_url, archivo_pdf_url, fecha_publicacion, usuario_id, vistas)
                VALUES ($numero_boletin, '$titulo', '$resumen', '$foto_portada_url', '$archivo_pdf_url', '$fecha_publicacion', $usuario_id, 0)";

        if ($conexion->query($sql)) {
            // 4. Si se subió un PDF, registrarlo también en la tabla 'pdfs'
            if (!empty($archivo_pdf_url)) {
                $conexion->query("INSERT INTO pdfs (titulo, url_archivo, descripcion, usuario_id)
                                 VALUES ('Boletín N° $numero_boletin - $titulo', '$archivo_pdf_url', '$resumen', $usuario_id)");
            }

            $mensaje = "<div class='alert alert-success'>¡Boletín publicado exitosamente!</div>";
        } else {
            $mensaje = "<div class='alert alert-danger'>Error: " . $conexion->error . "</div>";
        }
    }
}

// CONSULTA GENERAL DE BOLETINES
$boletines = $conexion->query("SELECT * FROM boletines ORDER BY boletin_id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Boletines - Panel Administrativo</title>
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
            <li class="nav-item"><a class="nav-link" href="noticias.php"><i class="mdi mdi-newspaper menu-icon"></i><span class="menu-title">Noticias</span></a></li>

            <!-- Pestaña Activa: Boletines -->
            <li class="nav-item active"><a class="nav-link" href="boletines.php"><i class="mdi mdi-book-open-page-variant menu-icon"></i><span class="menu-title">Boletines</span></a></li>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Boletín' : 'Publicar Nuevo Boletín'; ?></h4>
              <form action="boletines.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="boletin_id" value="<?php echo $boletin_edit['boletin_id']; ?>">

                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>N° Boletín</label>
                      <input type="number" name="numero_boletin" class="form-control" value="<?php echo htmlspecialchars($boletin_edit['numero_boletin']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-5">
                    <div class="form-group">
                      <label>Título del Boletín</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($boletin_edit['titulo']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Fecha de Publicación</label>
                      <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo $boletin_edit['fecha_publicacion']; ?>" required>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Portada (Imagen) <?php echo $modo_edicion ? '(Opcional)' : ''; ?></label>
                      <input type="file" name="portada" class="form-control-file d-block" accept="image/*">
                      <?php if($modo_edicion && !empty($boletin_edit['foto_portada_url'])): ?>
                        <?php
                          $img_preview = file_exists($boletin_edit['foto_portada_url']) ? $boletin_edit['foto_portada_url'] : 'uploads/' . $boletin_edit['foto_portada_url'];
                        ?>
                        <div class="mt-2">
                          <small class="text-muted d-block">Portada actual:</small>
                          <img src="<?php echo $img_preview; ?>" width="60" height="60" style="object-fit:cover;" class="rounded border">
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Documento PDF <?php echo $modo_edicion ? '(Opcional se mantiene el actual)' : ''; ?></label>
                      <input type="file" name="archivo_pdf" class="form-control-file d-block" accept=".pdf" <?php echo $modo_edicion ? '' : 'required'; ?>>
                      <?php if($modo_edicion && !empty($boletin_edit['archivo_pdf_url'])): ?>
                        <?php
                          $pdf_preview = file_exists($boletin_edit['archivo_pdf_url']) ? $boletin_edit['archivo_pdf_url'] : 'uploads/' . $boletin_edit['archivo_pdf_url'];
                        ?>
                        <div class="mt-2">
                          <small class="text-muted d-block">PDF actual:</small>
                          <a href="<?php echo $pdf_preview; ?>" target="_blank" class="btn btn-outline-secondary btn-xs"><i class="mdi mdi-file-pdf"></i> Ver PDF subido</a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Resumen / Descripción</label>
                  <textarea name="resumen" class="form-control" rows="3"><?php echo htmlspecialchars($boletin_edit['resumen']); ?></textarea>
                </div>

                <button type="submit" name="guardar_boletin" class="btn btn-warning text-white">
                  <?php echo $modo_edicion ? 'Actualizar Boletín' : 'Publicar Boletín'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="boletines.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Boletines Registrados</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>N°</th>
                      <th>Portada</th>
                      <th>Título</th>
                      <th>Archivo</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($boletines && $boletines->num_rows > 0): ?>
                      <?php while($b = $boletines->fetch_assoc()): ?>
                      <tr>
                        <td><strong><?php echo $b['numero_boletin']; ?></strong></td>
                        <td>
                          <?php
                            if (!empty($b['foto_portada_url'])) {
                                $ruta_img = file_exists($b['foto_portada_url']) ? $b['foto_portada_url'] : 'uploads/' . $b['foto_portada_url'];
                                echo '<img src="' . $ruta_img . '" alt="Portada" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">';
                            } else {
                                echo '<span class="badge badge-light">Sin portada</span>';
                            }
                          ?>
                        </td>
                        <td><?php echo htmlspecialchars($b['titulo']); ?></td>
                        <td>
                          <?php
                            if (!empty($b['archivo_pdf_url'])) {
                                $ruta_pdf = file_exists($b['archivo_pdf_url']) ? $b['archivo_pdf_url'] : 'uploads/' . $b['archivo_pdf_url'];
                                echo '<a href="' . $ruta_pdf . '" target="_blank" class="btn btn-outline-secondary btn-sm"><i class="mdi mdi-file-pdf"></i> PDF</a>';
                            }
                          ?>
                        </td>
                        <td><span class="badge badge-warning text-white"><?php echo $b['vistas']; ?></span></td>
                        <td>
                          <a href="boletines.php?editar=<?php echo $b['boletin_id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="boletines.php?eliminar=<?php echo $b['boletin_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar boletín?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No hay boletines registrados.</td>
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
