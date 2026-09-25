<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$podcast_edit = [
    'podcast_id'  => '',
    'titulo'      => '',
    'descripcion' => '',
    'audio_url'   => '',
    'imagen_url'  => ''
];

// Directorio para uploads
$dir_uploads = "uploads/podcasts/";
if (!file_exists($dir_uploads)) {
    @mkdir($dir_uploads, 0777, true);
}

// 1. Función Embed Spotify
function obtener_embed_spotify($url) {
    if (preg_match('#spotify\.com/(?:embed/)?(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?(track|episode|show|album|playlist)/([a-zA-Z0-9]+)#i', $url, $matches)) {
        return "https://open.spotify.com/embed/" . $matches[1] . "/" . $matches[2];
    }
    return $url;
}

// 2. Función Embed YouTube
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

// DETECTAR SI EL ARCHIVO SUPERÓ EL LÍMITE DE TAMANO DE PHP (post_max_size)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
    $mensaje = "<div class='alert alert-danger'><strong>Error:</strong> El archivo subido excede el límite de tamaño permitido por la configuración de PHP (post_max_size). Intente con un archivo más liviano o use un enlace externo de Spotify/YouTube.</div>";
}

// CARGAR REGISTRO PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM podcasts WHERE podcast_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $podcast_edit = [
            'podcast_id'  => $row['podcast_id'] ?? '',
            'titulo'      => $row['titulo'] ?? '',
            'descripcion' => $row['descripcion'] ?? '',
            'audio_url'   => $row['url_embed'] ?? '',
            'imagen_url'  => $row['imagen_url'] ?? ''
        ];
        $modo_edicion = true;
    }
}

// ELIMINAR PODCAST
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conexion->query("DELETE FROM podcasts WHERE podcast_id = $id");
    header("Location: podcasts.php");
    exit();
}

// GUARDAR (CREAR O ACTUALIZAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_podcast'])) {
    $id_val      = intval($_POST['podcast_id']);
    $titulo      = $conexion->real_escape_string(trim($_POST['titulo']));
    $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
    $audio_url   = $conexion->real_escape_string(trim($_POST['audio_url']));
    $imagen_url  = $conexion->real_escape_string(trim($_POST['imagen_url']));
    $usuario_id  = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1);

    // PRESERVAR VALORES PREVIOS AL EDITAR SI LOS CAMPOS DE ARCHIVO/URL QUEDAN VACÍOS
    if ($id_val > 0) {
        $res_old = $conexion->query("SELECT url_embed, imagen_url FROM podcasts WHERE podcast_id = $id_val");
        if ($res_old && $old = $res_old->fetch_assoc()) {
            if (empty($audio_url) && (!isset($_FILES['archivo_audio']) || $_FILES['archivo_audio']['error'] !== UPLOAD_ERR_OK)) {
                $audio_url = $conexion->real_escape_string($old['url_embed']);
            }
            if (empty($imagen_url) && (!isset($_FILES['archivo_imagen']) || $_FILES['archivo_imagen']['error'] !== UPLOAD_ERR_OK)) {
                $imagen_url = $conexion->real_escape_string($old['imagen_url']);
            }
        }
    }

    // Convertir enlaces de Spotify o YouTube a Embed
    if (!empty($audio_url)) {
        if (strpos($audio_url, 'spotify.com') !== false) {
            $audio_url = obtener_embed_spotify($audio_url);
        } elseif (strpos($audio_url, 'youtube.com') !== false || strpos($audio_url, 'youtu.be') !== false) {
            $audio_url = obtener_embed_youtube($audio_url);
        }
    }

    // SUBIDA DE ARCHIVO DE AUDIO LOCAL
    if (isset($_FILES['archivo_audio']) && $_FILES['archivo_audio']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['archivo_audio']['name'], PATHINFO_EXTENSION));
        $ext_permitidas = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
        if (in_array($ext, $ext_permitidas)) {
            $nombre_audio = time() . '_audio_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['archivo_audio']['tmp_name'], $dir_uploads . $nombre_audio)) {
                $audio_url = $dir_uploads . $nombre_audio;
            } else {
                $mensaje .= "<div class='alert alert-danger'>Error al mover el archivo de audio. Verifique permisos de la carpeta uploads/podcasts/</div>";
            }
        } else {
            $mensaje .= "<div class='alert alert-warning'>Formato de audio no permitido. Use MP3, WAV, OGG o M4A.</div>";
        }
    } elseif (isset($_FILES['archivo_audio']) && $_FILES['archivo_audio']['error'] !== UPLOAD_ERR_NO_FILE) {
        $mensaje .= "<div class='alert alert-danger'>Error al subir audio. Código PHP: " . $_FILES['archivo_audio']['error'] . "</div>";
    }

    // SUBIDA DE ARCHIVO DE IMAGEN LOCAL
    if (isset($_FILES['archivo_imagen']) && $_FILES['archivo_imagen']['error'] === UPLOAD_ERR_OK) {
        $ext_img = strtolower(pathinfo($_FILES['archivo_imagen']['name'], PATHINFO_EXTENSION));
        $ext_img_permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($ext_img, $ext_img_permitidas)) {
            $nombre_img = time() . '_portada_' . uniqid() . '.' . $ext_img;
            if (move_uploaded_file($_FILES['archivo_imagen']['tmp_name'], $dir_uploads . $nombre_img)) {
                $imagen_url = $dir_uploads . $nombre_img;
            } else {
                $mensaje .= "<div class='alert alert-danger'>Error al mover la imagen de portada.</div>";
            }
        } else {
            $mensaje .= "<div class='alert alert-warning'>Formato de imagen no permitido. Use JPG, PNG o WEBP.</div>";
        }
    } elseif (isset($_FILES['archivo_imagen']) && $_FILES['archivo_imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
        $mensaje .= "<div class='alert alert-danger'>Error al subir imagen. Código PHP: " . $_FILES['archivo_imagen']['error'] . "</div>";
    }

    if (!empty($titulo)) {
        if ($id_val > 0) {
            // ACTUALIZAR REGISTRO
            $sql = "UPDATE podcasts SET
                        titulo = '$titulo',
                        descripcion = '$descripcion',
                        url_embed = '$audio_url',
                        imagen_url = '$imagen_url'
                    WHERE podcast_id = $id_val";

            if ($conexion->query($sql)) {
                $mensaje = "<div class='alert alert-success'>¡Podcast actualizado exitosamente!</div>";
                $modo_edicion = false;
                $podcast_edit = ['podcast_id' => '', 'titulo' => '', 'descripcion' => '', 'audio_url' => '', 'imagen_url' => ''];
            } else {
                $mensaje = "<div class='alert alert-danger'>Error SQL al actualizar: " . $conexion->error . "</div>";
            }
        } else {
            // INSERTAR REGISTRO (Coincidencia exacta 1 a 1 con tu base de datos)
            $sql = "INSERT INTO podcasts (titulo, descripcion, url_embed, imagen_url, fecha_publicacion, usuario_id, created_at, vistas)
                    VALUES ('$titulo', '$descripcion', '$audio_url', '$imagen_url', CURDATE(), $usuario_id, NOW(), 0)";

            if ($conexion->query($sql)) {
                $mensaje = "<div class='alert alert-success'>¡Podcast publicado exitosamente!</div>";
                $podcast_edit = ['podcast_id' => '', 'titulo' => '', 'descripcion' => '', 'audio_url' => '', 'imagen_url' => ''];
            } else {
                $mensaje = "<div class='alert alert-danger'>Error SQL al guardar: " . $conexion->error . "</div>";
            }
        }
    } else {
        $mensaje = "<div class='alert alert-warning'>Por favor ingrese el título del podcast.</div>";
    }
}

// CONSULTA DE REGISTROS DE LA BASE DE DATOS
$lista_podcasts = $conexion->query("SELECT * FROM podcasts ORDER BY podcast_id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Podcasts - Panel Administrativo</title>
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

            <li class="nav-item active"><a class="nav-link" href="podcasts.php"><i class="mdi mdi-microphone menu-icon"></i><span class="menu-title">Podcasts</span></a></li>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Podcast' : 'Publicar Nuevo Podcast'; ?></h4>
              <form action="podcasts.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="podcast_id" value="<?php echo $podcast_edit['podcast_id']; ?>">

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label>Título del Podcast *</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($podcast_edit['titulo']); ?>" required placeholder="Ej. Episodio 01: El futuro del desarrollo local">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-12">
                    <div class="form-group">
                      <label>Descripción / Resumen</label>
                      <textarea name="descripcion" class="form-control" rows="3" placeholder="Resumen del contenido del episodio..."><?php echo htmlspecialchars($podcast_edit['descripcion']); ?></textarea>
                    </div>
                  </div>
                </div>

                <!-- AUDIO -->
                <div class="row bg-light p-3 rounded mb-3">
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-spotify"></i> / <i class="mdi mdi-youtube"></i> Enlace de Audio (Spotify, YouTube o URL directa)</label>
                      <input type="text" name="audio_url" class="form-control" value="<?php echo htmlspecialchars($podcast_edit['audio_url']); ?>" placeholder="https://open.spotify.com/episode/... o https://youtube.com/watch?v=...">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-upload"></i> O subir archivo MP3 local</label>
                      <input type="file" name="archivo_audio" class="form-control-file d-block" accept="audio/*">
                    </div>
                  </div>
                </div>

                <!-- PORTADA / IMAGEN -->
                <div class="row bg-light p-3 rounded mb-3 align-items-center">
                  <div class="col-md-5">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-link-variant"></i> URL Externa de la Portada</label>
                      <input type="text" name="imagen_url" class="form-control" value="<?php echo htmlspecialchars($podcast_edit['imagen_url']); ?>" placeholder="https://ejemplo.com/imagen.jpg">
                    </div>
                  </div>
                  <div class="col-md-5">
                    <div class="form-group mb-2">
                      <label class="font-weight-bold"><i class="mdi mdi-upload"></i> O subir archivo de imagen local</label>
                      <input type="file" name="archivo_imagen" class="form-control-file d-block" accept="image/*">
                    </div>
                  </div>
                  <div class="col-md-2 text-center">
                    <?php if (!empty($podcast_edit['imagen_url'])): ?>
                      <label class="font-weight-bold d-block mb-1">Portada Actual</label>
                      <img src="<?php echo htmlspecialchars($podcast_edit['imagen_url']); ?>" alt="Portada" style="max-height: 60px; max-width: 100%; object-fit: cover; border-radius: 6px; border: 1px solid #ccc;">
                    <?php endif; ?>
                  </div>
                </div>

                <button type="submit" name="guardar_podcast" class="btn btn-warning text-white">
                  <?php echo $modo_edicion ? 'Actualizar Podcast' : 'Publicar Podcast'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="podcasts.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <!-- TABLA DE REGISTROS -->
          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Podcasts Registrados</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Portada</th>
                      <th>Título / Descripción</th>
                      <th>Reproductor</th>
                      <th>Fecha Pub.</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($lista_podcasts && $lista_podcasts->num_rows > 0): ?>
                      <?php while($pod = $lista_podcasts->fetch_assoc()): ?>
                        <?php
                          $p_id     = $pod['podcast_id'];
                          $p_tit    = $pod['titulo'] ?? 'Sin título';
                          $p_desc   = $pod['descripcion'] ?? '';
                          $p_audio  = $pod['url_embed'] ?? '';
                          $p_img    = $pod['imagen_url'] ?? '';
                          $p_fecha  = $pod['fecha_publicacion'] ?? '-';
                          $p_vistas = $pod['vistas'] ?? 0;

                          $es_spotify = (strpos($p_audio, 'spotify.com') !== false);
                          $es_youtube = (strpos($p_audio, 'youtube.com') !== false || strpos($p_audio, 'youtu.be') !== false);
                        ?>
                      <tr>
                        <td style="width: 70px;">
                          <?php if (!empty($p_img) && (strpos($p_img, 'http') === 0 || file_exists($p_img))): ?>
                            <img src="<?php echo htmlspecialchars($p_img); ?>" alt="Portada" style="width: 50px; height: 50px; object-fit: cover; border-radius: 6px;">
                          <?php else: ?>
                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                              <i class="mdi mdi-microphone mdi-24px"></i>
                            </div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <strong><?php echo htmlspecialchars($p_tit); ?></strong>
                          <?php if(!empty($p_desc)): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($p_desc, 0, 85, "...")); ?></small>
                          <?php endif; ?>
                        </td>
                        <td style="min-width: 250px;">
                          <?php if (!empty($p_audio)): ?>
                            <?php if ($es_spotify): ?>
                              <iframe src="<?php echo htmlspecialchars(obtener_embed_spotify($p_audio)); ?>" width="100%" height="80" frameborder="0" allowtransparency="true" allow="encrypted-media" style="border-radius: 8px;"></iframe>
                            <?php elseif ($es_youtube): ?>
                              <iframe width="220" height="115" src="<?php echo htmlspecialchars(obtener_embed_youtube($p_audio)); ?>" frameborder="0" allowfullscreen style="border-radius: 8px;"></iframe>
                            <?php elseif (preg_match('/\.(mp3|wav|ogg|m4a)$/i', $p_audio) || strpos($p_audio, 'uploads/') !== false): ?>
                              <audio controls style="height: 35px; width: 100%;">
                                <source src="<?php echo htmlspecialchars($p_audio); ?>">
                                Tu navegador no soporta el reproductor.
                              </audio>
                            <?php else: ?>
                              <a href="<?php echo htmlspecialchars($p_audio); ?>" target="_blank" class="btn btn-outline-info btn-xs">
                                <i class="mdi mdi-open-in-new"></i> Escuchar Enlace
                              </a>
                            <?php endif; ?>
                          <?php else: ?>
                            <span class="badge badge-warning text-white">Sin audio</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <small class="text-muted"><?php echo htmlspecialchars($p_fecha); ?></small>
                        </td>
                        <td>
                          <span class="badge badge-info"><?php echo intval($p_vistas); ?> vistas</span>
                        </td>
                        <td>
                          <a href="podcasts.php?editar=<?php echo $p_id; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="podcasts.php?eliminar=<?php echo $p_id; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea eliminar este podcast?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="6" class="text-center py-3 text-muted">No hay podcasts registrados.</td>
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
