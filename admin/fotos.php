<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$foto_edit = [
    'foto_id'           => '',
    'titulo'            => '',
    'descripcion'       => '',
    'fecha_publicacion' => date('Y-m-d'),
    'url_foto'          => ''
];

$dir_fotos = 'uploads/fotos/';

// Crear carpeta de almacenamiento si no existe
if (!is_dir($dir_fotos)) {
    mkdir($dir_fotos, 0777, true);
}

// 1. CARGAR REGISTRO PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM fotos WHERE foto_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $foto_edit = $res->fetch_assoc();
        $modo_edicion = true;
    }
}

// 2. ELIMINAR FOTO Y SU ARCHIVO FÍSICO
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    $res = $conexion->query("SELECT url_foto FROM fotos WHERE foto_id = $id");
    if ($res && $res->num_rows > 0) {
        $f = $res->fetch_assoc();
        if (!empty($f['url_foto'])) {
            if (file_exists($dir_fotos . $f['url_foto'])) {
                unlink($dir_fotos . $f['url_foto']);
            } elseif (file_exists('uploads/' . $f['url_foto'])) {
                unlink('uploads/' . $f['url_foto']);
            } elseif (file_exists($f['url_foto'])) {
                unlink($f['url_foto']);
            }
        }
    }

    $conexion->query("DELETE FROM fotos WHERE foto_id = $id");
    header("Location: fotos.php");
    exit();
}

// 3. GUARDAR (CREAR O ACTUALIZAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['guardar_foto']) || isset($_POST['crear_foto']))) {
    $foto_id           = isset($_POST['foto_id']) ? intval($_POST['foto_id']) : 0;
    $titulo            = $conexion->real_escape_string(trim($_POST['titulo']));
    $descripcion       = $conexion->real_escape_string(trim($_POST['descripcion']));
    $fecha_publicacion = $conexion->real_escape_string($_POST['fecha_publicacion']);
    $usuario_id        = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1);

    $foto_sql_update = "";
    $nueva_foto      = "";

    // Procesar archivo de imagen si fue subido
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($ext, $extensiones_permitidas)) {
            $nueva_foto = "foto_" . time() . "_" . rand(100, 999) . "." . $ext;

            // Si está en modo edición, borrar la foto antigua del servidor
            if ($foto_id > 0) {
                $res_old = $conexion->query("SELECT url_foto FROM fotos WHERE foto_id = $foto_id");
                if ($res_old && $res_old->num_rows > 0) {
                    $old_img = $res_old->fetch_assoc()['url_foto'];
                    if (!empty($old_img)) {
                        if (file_exists($dir_fotos . $old_img)) unlink($dir_fotos . $old_img);
                        elseif (file_exists('uploads/' . $old_img)) unlink('uploads/' . $old_img);
                        elseif (file_exists($old_img)) unlink($old_img);
                    }
                }
            }

            move_uploaded_file($_FILES['foto']['tmp_name'], $dir_fotos . $nueva_foto);
            $foto_sql_update = ", url_foto = '$nueva_foto'";
        } else {
            $mensaje = "<div class='alert alert-warning'>Formato de imagen no permitido. Usa JPG, JPEG, PNG, GIF o WEBP.</div>";
        }
    }

    if (empty($mensaje)) {
        if ($foto_id > 0) {
            // --- ACTUALIZAR REGISTRO ---
            $sql = "UPDATE fotos SET
                        titulo = '$titulo',
                        descripcion = '$descripcion',
                        fecha_publicacion = '$fecha_publicacion'
                        $foto_sql_update
                    WHERE foto_id = $foto_id";

            if ($conexion->query($sql)) {
                $mensaje = "<div class='alert alert-success'>¡Fotografía actualizada exitosamente!</div>";
                $modo_edicion = false;
                $foto_edit = ['foto_id' => '', 'titulo' => '', 'descripcion' => '', 'fecha_publicacion' => date('Y-m-d'), 'url_foto' => ''];
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
            }
        } else {
            // --- INSERTAR NUEVO REGISTRO ---
            if (!empty($nueva_foto)) {
                $sql = "INSERT INTO fotos (titulo, url_foto, descripcion, fecha_publicacion, usuario_id, vistas)
                        VALUES ('$titulo', '$nueva_foto', '$descripcion', '$fecha_publicacion', $usuario_id, 0)";

                if ($conexion->query($sql)) {
                    $mensaje = "<div class='alert alert-success'>¡Foto registrada exitosamente en el sistema!</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al guardar en la base de datos: " . $conexion->error . "</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-warning'>Por favor selecciona un archivo de imagen válido.</div>";
            }
        }
    }
}

// OBTENER LISTA COMPLETA DE LA TABLA FOTOS
$fotos = $conexion->query("
    SELECT f.*, u.nombre_completo
    FROM fotos f
    LEFT JOIN usuarios u ON f.usuario_id = u.user_id
    ORDER BY f.foto_id DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Fotos - Panel Administrativo</title>
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
            <li class="nav-item"><a class="nav-link" href="boletines.php"><i class="mdi mdi-book-open-page-variant menu-icon"></i><span class="menu-title">Boletines</span></a></li>
            <li class="nav-item"><a class="nav-link" href="podcasts.php"><i class="mdi mdi-microphone menu-icon"></i><span class="menu-title">Podcasts</span></a></li>
            <li class="nav-item"><a class="nav-link" href="videos.php"><i class="mdi mdi-video menu-icon"></i><span class="menu-title">Videos</span></a></li>
            <li class="nav-item"><a class="nav-link" href="pdf.php"><i class="mdi mdi-file-pdf menu-icon"></i><span class="menu-title">PDFs</span></a></li>

            <!-- Pestaña Activa: Fotos -->
            <li class="nav-item active"><a class="nav-link" href="fotos.php"><i class="mdi mdi-image menu-icon"></i><span class="menu-title">Fotos</span></a></li>

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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Fotografía' : 'Registrar Nueva Imagen'; ?></h4>
              <form action="fotos.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="foto_id" value="<?php echo $foto_edit['foto_id']; ?>">

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Título / Nombre de la Foto *</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($foto_edit['titulo']); ?>" placeholder="Ej: Fotografía de portada" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Fecha de Publicación *</label>
                      <input type="date" name="fecha_publicacion" class="form-control" value="<?php echo htmlspecialchars($foto_edit['fecha_publicacion']); ?>" required>
                    </div>
                  </div>
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Archivo de Imagen <?php echo $modo_edicion ? '(Opcional al editar)' : '*'; ?></label>
                      <input type="file" name="foto" class="form-control-file d-block" accept="image/*" <?php echo $modo_edicion ? '' : 'required'; ?>>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Descripción / Pie de foto</label>
                  <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalles o notas sobre la fotografía..."><?php echo htmlspecialchars($foto_edit['descripcion']); ?></textarea>
                </div>

                <button type="submit" name="guardar_foto" class="btn btn-warning text-white">
                  <?php echo $modo_edicion ? 'Actualizar Fotografía' : 'Subir a Fotos'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="fotos.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Histórico de Fotos Registradas</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Vista Previa</th>
                      <th>Título</th>
                      <th>Fecha</th>
                      <th>Usuario</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($fotos && $fotos->num_rows > 0): ?>
                      <?php while($f = $fotos->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <?php
                            $ruta_img = '';
                            if (!empty($f['url_foto'])) {
                                if (file_exists('uploads/fotos/' . $f['url_foto'])) {
                                    $ruta_img = 'uploads/fotos/' . $f['url_foto'];
                                } elseif (file_exists('uploads/' . $f['url_foto'])) {
                                    $ruta_img = 'uploads/' . $f['url_foto'];
                                } elseif (file_exists($f['url_foto'])) {
                                    $ruta_img = $f['url_foto'];
                                }
                            }

                            if (!empty($ruta_img)) {
                                echo '<a href="' . htmlspecialchars($ruta_img) . '" target="_blank"><img src="' . htmlspecialchars($ruta_img) . '" alt="Foto" class="img-thumbnail" style="width: 65px; height: 65px; object-fit: cover;"></a>';
                            } else {
                                echo '<span class="badge badge-light">Sin imagen</span>';
                            }
                          ?>
                        </td>
                        <td>
                          <strong><?php echo htmlspecialchars($f['titulo']); ?></strong>
                          <?php if(!empty($f['descripcion'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($f['descripcion'], 0, 80, "...")); ?></small>
                          <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($f['fecha_publicacion']); ?></td>
                        <td><?php echo htmlspecialchars($f['nombre_completo'] ?? 'Administrador'); ?></td>
                        <td><span class="badge badge-warning text-white"><?php echo intval($f['vistas']); ?></span></td>
                        <td>
                          <a href="fotos.php?editar=<?php echo $f['foto_id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="fotos.php?eliminar=<?php echo $f['foto_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta foto del registro?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No hay fotografías registradas aún en la base de datos.</td>
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
