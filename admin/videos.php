<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$video_edit = [
    'video_id'    => '',
    'titulo'      => '',
    'descripcion' => '',
    'video_url'   => '',
    'imagen_url'  => ''
];

// Directorio para uploads de videos e imágenes
$dir_uploads = "uploads/videos/";
if (!file_exists($dir_uploads)) {
    mkdir($dir_uploads, 0777, true);
}

// Función auxiliar para URL embed de YouTube
function obtener_embed_youtube($url) {
    if (strpos($url, 'youtube.com/watch') !== false) {
        parse_str(parse_url($url, PHP_URL_QUERY), $vars);
        if (isset($vars['v'])) {
            return "https://www.youtube.com/embed/" . $vars['v'];
        }
    } elseif (strpos($url, 'youtu.be/') !== false) {
        $id = basename(parse_url($url, PHP_URL_PATH));
        return "https://www.youtube.com/embed/" . $id;
    }
    return $url;
}

// 1. CARGAR REGISTRO PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM videos WHERE video_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $video_edit = [
            'video_id'    => $row['video_id'] ?? '',
            'titulo'      => $row['titulo'] ?? '',
            'descripcion' => $row['descripcion'] ?? '',
            'video_url'   => $row['url_embed'] ?? '',
            'imagen_url'  => $row['imagen_url'] ?? ''
        ];
        $modo_edicion = true;
    }
}

// 2. ELIMINAR VIDEO
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conexion->query("DELETE FROM videos WHERE video_id = $id");
    header("Location: videos.php");
    exit();
}

// 3. GUARDAR (CREAR O ACTUALIZAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_video'])) {
    $id_val      = intval($_POST['video_id']);
    $titulo      = $conexion->real_escape_string(trim($_POST['titulo']));
    $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));

    $video_url   = $conexion->real_escape_string(trim($_POST['video_url']));
    $imagen_url  = $conexion->real_escape_string(trim($_POST['imagen_url']));
    $usuario_id  = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1);

    // Convertir enlace de YouTube a formato Embed automáticamente
    if (!empty($video_url)) {
        $video_url = obtener_embed_youtube($video_url);
    }

    // Subida de Archivo Video Local (Si selecciona un archivo)
    if (isset($_FILES['archivo_video']) && $_FILES['archivo_video']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['archivo_video']['name'], PATHINFO_EXTENSION));
        $ext_permitidas = ['mp4', 'webm', 'ogv', 'mov', 'avi', 'mkv'];
        if (in_array($ext, $ext_permitidas)) {
            $nombre_video = time() . '_vid_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['archivo_video']['tmp_name'], $dir_uploads . $nombre_video)) {
                $video_url = $dir_uploads . $nombre_video;
            }
        }
    }

    // Subida de Imagen Portada/Miniatura Local
    if (isset($_FILES['archivo_imagen']) && $_FILES['archivo_imagen']['error'] === UPLOAD_ERR_OK) {
        $ext_img = strtolower(pathinfo($_FILES['archivo_imagen']['name'], PATHINFO_EXTENSION));
        $ext_img_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext_img, $ext_img_permitidas)) {
            $nombre_img = time() . '_portada_' . uniqid() . '.' . $ext_img;
            if (move_uploaded_file($_FILES['archivo_imagen']['tmp_name'], $dir_uploads . $nombre_img)) {
                $imagen_url = $dir_uploads . $nombre_img;
            }
        }
    }

    if (!empty($titulo)) {
        if ($id_val > 0) {
            // ACTUALIZAR REGISTRO
            $sql = "UPDATE videos SET
                        titulo = '$titulo',
                        descripcion = '$descripcion',
                        url_embed = '$video_url',
                        imagen_url = '$imagen_url'
                    WHERE video_id = $id_val";

            if ($conexion->query($sql)) {
                $mensaje = "<div class='alert alert-success'>¡Video actualizado exitosamente!</div>";
                $modo_edicion = false;
                $video_edit = ['video_id' => '', 'titulo' => '', 'descripcion' => '', 'video_url' => '', 'imagen_url' => ''];
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
            }
        } else {
            // INSERTAR REGISTRO
            $sql = "INSERT INTO videos (titulo, descripcion, url_embed, imagen_url, fecha_publicacion, usuario_id, created_at, vistas)
                    VALUES ('$titulo', '$descripcion', '$video_url', '$imagen_url', CURDATE(), $usuario_id, NOW(), 0)";

            if ($conexion->query($sql)) {
                $mensaje = "<div class='alert alert-success'>¡Video publicado exitosamente!</div>";
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
            }
        }
    } else {
        $mensaje = "<div class='alert alert-warning'>Por favor ingrese el título del video.</div>";
    }
}

// CONSULTA DE REGISTROS
$lista_videos = $conexion->query("SELECT * FROM videos ORDER BY video_id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Videos - Panel Administrativo</title>
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

            <li class="nav-item active"><a class="nav-link" href="videos.php"><i class="mdi mdi-video menu-icon"></i><span class="menu-title">Videos</span></a></li>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Video' : 'Publicar Nuevo Video'; ?></h4>
              <form action="videos.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="video_id" value="<?php echo $video_edit['video_id']; ?>">

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label>Título del Video *</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($video_edit['titulo']); ?>" required placeholder="Ej. Entrevista Especial sobre Desarrollo Rural">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label>Descripción / Detalle</label>
                      <textarea name="descripcion" class="form-control" rows="3" placeholder="Resumen o detalles del video..."><?php echo htmlspecialchars($video_edit['descripcion']); ?></textarea>
                    </div>
                  </div>
                </div>

                <div class="row bg-light p-3 rounded mb-3">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-youtube"></i> / <i class="mdi mdi-link-variant"></i> Enlace o URL de Video (YouTube, Vimeo, MP4)</label>
                      <input type="text" name="video_url" class="form-control" value="<?php echo htmlspecialchars($video_edit['video_url']); ?>" placeholder="https://www.youtube.com/watch?v=... o URL directa">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-upload"></i> O subir archivo de video local (MP4, WEBM)</label>
                      <input type="file" name="archivo_video" class="form-control-file d-block" accept="video/*">
                    </div>
                  </div>
                </div>

                <div class="row bg-light p-3 rounded mb-3">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-link-variant"></i> Enlace / URL Externa de la Miniatura</label>
                      <input type="text" name="imagen_url" class="form-control" value="<?php echo htmlspecialchars($video_edit['imagen_url']); ?>" placeholder="https://ejemplo.com/miniatura.jpg">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-upload"></i> O subir imagen de miniatura local</label>
                      <input type="file" name="archivo_imagen" class="form-control-file d-block" accept="image/*">
                    </div>
                  </div>
                </div>

                <button type="submit" name="guardar_video" class="btn btn-warning text-white">
                  <?php echo $modo_edicion ? 'Actualizar Video' : 'Publicar Video'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="videos.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Videos Registrados</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Miniatura</th>
                      <th>Título / Descripción</th>
                      <th>Visualización / Reproductor</th>
                      <th>Fecha Pub.</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($lista_videos && $lista_videos->num_rows > 0): ?>
                      <?php while($vid = $lista_videos->fetch_assoc()): ?>
                        <?php
                          $v_id     = $vid['video_id'];
                          $v_tit    = $vid['titulo'] ?? 'Sin título';
                          $v_desc   = $vid['descripcion'] ?? '';
                          $v_url    = $vid['url_embed'] ?? '';
                          $v_img    = $vid['imagen_url'] ?? '';
                          $v_fecha  = $vid['fecha_publicacion'] ?? '-';
                          $v_vistas = $vid['vistas'] ?? 0;

                          $es_youtube = (strpos($v_url, 'youtube.com') !== false || strpos($v_url, 'youtu.be') !== false);
                          $embed_url  = $es_youtube ? obtener_embed_youtube($v_url) : $v_url;
                        ?>
                      <tr>
                        <td style="width: 80px;">
                          <?php if (!empty($v_img)): ?>
                            <img src="<?php echo htmlspecialchars($v_img); ?>" alt="Miniatura" style="width: 70px; height: 45px; object-fit: cover; border-radius: 4px;">
                          <?php else: ?>
                            <div class="bg-dark text-white rounded d-flex align-items-center justify-content-center" style="width: 70px; height: 45px;">
                              <i class="mdi mdi-play-circle mdi-24px"></i>
                            </div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <strong><?php echo htmlspecialchars($v_tit); ?></strong>
                          <?php if(!empty($v_desc)): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($v_desc, 0, 80, "...")); ?></small>
                          <?php endif; ?>
                        </td>
                        <td style="min-width: 220px;">
                          <?php if (!empty($v_url)): ?>
                            <?php if ($es_youtube): ?>
                              <iframe width="200" height="110" src="<?php echo htmlspecialchars($embed_url); ?>" frameborder="0" allowfullscreen style="border-radius: 6px;"></iframe>
                            <?php elseif (preg_match('/\.(mp4|webm|ogv)$/i', $v_url) || strpos($v_url, 'uploads/') !== false): ?>
                              <video controls width="200" height="110" style="border-radius: 6px; background: #000;">
                                <source src="<?php echo htmlspecialchars($v_url); ?>">
                                Tu navegador no soporta el reproductor.
                              </video>
                            <?php else: ?>
                              <a href="<?php echo htmlspecialchars($v_url); ?>" target="_blank" class="btn btn-outline-info btn-xs">
                                <i class="mdi mdi-open-in-new"></i> Ver Enlace
                              </a>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="badge badge-warning text-white">Sin video</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <small class="text-muted"><?php echo htmlspecialchars($v_fecha); ?></small>
                        </td>
                        <td>
                          <span class="badge badge-info"><?php echo intval($v_vistas); ?> vistas</span>
                        </td>
                        <td>
                          <a href="videos.php?editar=<?php echo $v_id; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="videos.php?eliminar=<?php echo $v_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea eliminar este video?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No hay videos registrados.</td>
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
