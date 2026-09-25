<?php
// Ocultar errores directos para evitar rupturas de maquetación HTML
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

// Conexión a la BD desde la subcarpeta podcast/
@include("../admin/conexion.php");

// Función auxiliar para consultas seguras
function consulta_segura($conexion, $sql) {
    if (!isset($conexion) || !$conexion || @mysqli_connect_errno()) return false;
    $resultado = @mysqli_query($conexion, $sql);
    return ($resultado && $resultado instanceof mysqli_result) ? $resultado : false;
}

function es_url_externa($ruta) {
    return filter_var($ruta, FILTER_VALIDATE_URL) !== false;
}

// Función para obtener la ruta correcta de la imagen de portada
function obtener_ruta_imagen($imagen_url) {
    $imagen_url = trim($imagen_url);
    if (empty($imagen_url)) {
        return "../assets/reportaje-02-0.jpg"; // Imagen por defecto
    }

    if (es_url_externa($imagen_url)) {
        return $imagen_url;
    }

    $clean_img = ltrim($imagen_url, '/');
    $nombre_img = basename($clean_img);

    // Rutas de búsqueda local
    if (file_exists("../admin/uploads/podcasts/" . $nombre_img)) {
        return "../admin/uploads/podcasts/" . $nombre_img;
    } elseif (file_exists("../admin/uploads/" . $nombre_img)) {
        return "../admin/uploads/" . $nombre_img;
    } elseif (file_exists("../uploads/podcasts/" . $nombre_img)) {
        return "../uploads/podcasts/" . $nombre_img;
    } elseif (file_exists("../uploads/" . $nombre_img)) {
        return "../uploads/" . $nombre_img;
    } elseif (file_exists("../assets/" . $nombre_img)) {
        return "../assets/" . $nombre_img;
    } elseif (file_exists("../admin/" . $clean_img)) {
        return "../admin/" . $clean_img;
    } elseif (file_exists("../" . $clean_img)) {
        return "../" . $clean_img;
    }

    return (strpos($clean_img, 'admin/') === 0) ? "../" . $clean_img : "../admin/uploads/podcasts/" . $nombre_img;
}

// Función para generar el reproductor a partir del campo 'url_embed'
function generar_reproductor($url_embed, $is_featured = false) {
    $url_embed = trim($url_embed);
    if (empty($url_embed) || $url_embed === '#') {
        return '<p class="text-muted small mb-0">Audio no disponible</p>';
    }

    // 1. Si ya viene como un <iframe> HTML guardado directamente
    if (strpos($url_embed, '<iframe') !== false) {
        return $url_embed;
    }

    // 2. Si es Spotify (con o sin /embed/)
    if (strpos($url_embed, 'spotify.com') !== false) {
        $embed_url = $url_embed;
        if (strpos($url_embed, '/embed/') === false) {
            $embed_url = preg_replace('/https:\/\/open\.spotify\.com\/(episode|track)\/([a-zA-Z0-9]+).*/', 'https://open.spotify.com/embed/$1/$2', $url_embed);
        }
        $height = $is_featured ? "152" : "152";
        return '<iframe src="' . htmlspecialchars($embed_url) . '" width="100%" height="' . $height . '" frameborder="0" allowtransparency="true" allow="encrypted-media" style="border-radius:12px;"></iframe>';
    }

    // 3. Si es YouTube
    if (strpos($url_embed, 'youtube.com') !== false || strpos($url_embed, 'youtu.be') !== false) {
        if (preg_match('/(?:v=|\/embed\/|\/)([a-zA-Z0-9_-]{11})/', $url_embed, $matches)) {
            $youtube_id = $matches[1];
            return '<div class="embed-responsive embed-responsive-16by9 rounded"><iframe class="embed-responsive-item" src="https://www.youtube.com/embed/' . $youtube_id . '" allowfullscreen></iframe></div>';
        }
    }

    // 4. Si es un archivo de audio local o directo (.mp3, .wav, etc.)
    $ext = strtolower(pathinfo(parse_url($url_embed, PHP_URL_PATH), PATHINFO_EXTENSION));
    $extensiones_audio = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];

    if (in_array($ext, $extensiones_audio) || !es_url_externa($url_embed)) {
        $src_audio = $url_embed;
        if (!es_url_externa($url_embed)) {
            $clean_file = ltrim($url_embed, '/');
            $nombre_audio = basename($clean_file);

            if (file_exists("../admin/uploads/podcasts/" . $nombre_audio)) {
                $src_audio = "../admin/uploads/podcasts/" . $nombre_audio;
            } elseif (file_exists("../admin/uploads/" . $nombre_audio)) {
                $src_audio = "../admin/uploads/" . $nombre_audio;
            } elseif (file_exists("../uploads/podcasts/" . $nombre_audio)) {
                $src_audio = "../uploads/podcasts/" . $nombre_audio;
            } elseif (file_exists("../uploads/" . $nombre_audio)) {
                $src_audio = "../uploads/" . $nombre_audio;
            } else {
                $src_audio = "../admin/uploads/podcasts/" . $nombre_audio;
            }
        }

        $style = $is_featured ? 'width: 100%; height: 50px; outline: none;' : 'width: 100%; height: 45px; outline: none;';
        return '<audio controls style="' . $style . '" class="mt-2">
                    <source src="' . htmlspecialchars($src_audio) . '">
                    Tu navegador no soporta el reproductor de audio.
                </audio>';
    }

    // 5. Enlace externo genérico
    return '<a href="' . htmlspecialchars($url_embed) . '" target="_blank" class="btn btn-outline-primary btn-sm mt-2"><span class="fa fa-play-circle mr-1"></span> Escuchar Audio</a>';
}

// Configuración de Paginación Dinámica
$limite = 9;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $limite;

// Determinar el nombre de la tabla (podcasts o podcast)
$tabla_podcast = "podcasts";
$check_tabla = consulta_segura($conexion, "SHOW TABLES LIKE 'podcasts'");
if (!$check_tabla || mysqli_num_rows($check_tabla) == 0) {
    $tabla_podcast = "podcast";
}

// Contar total de podcasts
$total_registros = 0;
$res_total = consulta_segura($conexion, "SELECT COUNT(*) as total FROM $tabla_podcast");
if ($res_total && $f_tot = mysqli_fetch_assoc($res_total)) {
    $total_registros = (int)$f_tot['total'];
}
$total_paginas = ($total_registros > 0) ? ceil($total_registros / $limite) : 1;

// Consulta ordenada por fecha_publicacion / podcast_id
$res_podcasts = consulta_segura($conexion, "SELECT * FROM $tabla_podcast ORDER BY fecha_publicacion DESC, podcast_id DESC LIMIT $limite OFFSET $offset");

$meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");

// Portada destacada y lista
$podcast_portada = null;
$podcasts_lista = array();

if ($res_podcasts && mysqli_num_rows($res_podcasts) > 0) {
    while ($row = mysqli_fetch_assoc($res_podcasts)) {
        $podcasts_lista[] = $row;
    }

    // Si estamos en la página 1, el primer podcast es la PORTADA
    if ($pagina_actual === 1 && !empty($podcasts_lista)) {
        $podcast_portada = array_shift($podcasts_lista);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Podcasts - Diálogo y Desarrollo Perú</title>

    <!-- Google fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!-- CSS desde carpeta assets -->
    <link rel="stylesheet" href="../assets/style-starter.css">
</head>
<body>

<!-- HEADER / NAVBAR -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="../index.php">
              <img src="../assets/logo.png" alt="Diálogo y Desarrollo Perú" title="Diálogo y Desarrollo Perú" style="height:75px;">
          </a>
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Alternar navegación">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item"><a class="nav-link" href="../index.php">Inicio</a></li>
                  <li class="nav-item"><a class="nav-link" href="../index.php#noticias">Actualidad</a></li>
                  <li class="nav-item"><a class="nav-link" href="../reportajes/reportajes.php">Reportajes</a></li>
                  <li class="nav-item active"><a class="nav-link" href="podcast.php">Podcast <span class="sr-only">(actual)</span></a></li>
                  <li class="nav-item"><a class="nav-link" href="../boletines/boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="../alianza/index.php">Alianzas</a></li>
                  <li class="nav-item"><a class="nav-link" href="../sobre-dd/sobre-dd.php">Sobre D&amp;D</a></li>
                  <li class="ml-2">
                      <a href="../contacto.php" class="btn btn-style btn-outline-secondary">Contacto</a>
                  </li>
              </ul>
          </div>
      </nav>
  </div>
</header>
<!-- //HEADER -->

<!-- BREADCRUMB -->
<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Nuestros Podcasts</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="../index.php">Inicio</a></li>
                            <li class="active">Podcasts</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN DE PORTADA / DESTACADO -->
<?php if ($podcast_portada):
    $titulo_p = $podcast_portada['titulo'] ?: 'Episodio Reciente';
    $descripcion_p = $podcast_portada['descripcion'] ?: '';

    $fecha_raw_p = $podcast_portada['fecha_publicacion'] ?: $podcast_portada['created_at'] ?: '';
    $fecha_p = 'Episodio Reciente';
    if (!empty($fecha_raw_p) && $time = strtotime($fecha_raw_p)) {
        $fecha_p = date("d", $time) . " de " . $meses[date("n", $time) - 1] . " de " . date("Y", $time);
    }

    $url_embed_p = $podcast_portada['url_embed'] ?: '';
    $imagen_url_p = $podcast_portada['imagen_url'] ?: '';
    $src_img_p = obtener_ruta_imagen($imagen_url_p);
?>
<section class="w3l-homeblock1 py-5 bg-light">
    <div class="container py-md-3">
        <div class="header-section text-center mb-4">
            <span class="badge badge-primary px-3 py-2 text-uppercase mb-2" style="font-size:0.85rem; letter-spacing:1px;">Podcast Destacado / Portada</span>
        </div>
        <div class="card border-0 shadow-lg rounded-lg overflow-hidden">
            <div class="row no-gutters align-items-center">
                <div class="col-lg-6">
                    <img src="<?php echo htmlspecialchars($src_img_p); ?>" alt="<?php echo htmlspecialchars($titulo_p); ?>" class="img-fluid w-100" style="min-height: 360px; max-height: 420px; object-fit: cover;">
                </div>
                <div class="col-lg-6">
                    <div class="card-body p-lg-5 p-4">
                        <span class="text-muted small d-block mb-2"><i class="fa fa-calendar mr-1"></i> <?php echo htmlspecialchars($fecha_p); ?></span>
                        <h3 class="card-title font-weight-bold mb-3" style="font-size: 1.6rem; line-height: 1.3;">
                            <?php echo htmlspecialchars($titulo_p); ?>
                        </h3>
                        <?php if (!empty($descripcion_p)): ?>
                            <p class="card-text text-muted mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                                <?php echo htmlspecialchars($descripcion_p); ?>
                            </p>
                        <?php endif; ?>

                        <div class="audio-container pt-3 border-top">
                            <span class="font-weight-bold d-block mb-2 text-dark"><i class="fa fa-volume-up mr-1"></i> Reproducir Episodio:</span>
                            <?php echo generar_reproductor($url_embed_p, true); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- MAIN CONTENT / GRILLA DE PODCASTS -->
<div class="grids-block-5 py-5">
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <?php if ($podcast_portada): ?>
                <div class="header-section mb-4">
                    <h3 class="title-big" style="font-size: 1.5rem;">Más Episodios</h3>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php
                if (!empty($podcasts_lista)) {
                    foreach ($podcasts_lista as $pod) {
                        $titulo = $pod['titulo'] ?: 'Episodio de Podcast';
                        $descripcion = $pod['descripcion'] ?: '';

                        $fecha_raw = $pod['fecha_publicacion'] ?: $pod['created_at'] ?: '';
                        $fecha_formatted = 'Episodio Reciente';
                        if (!empty($fecha_raw) && $time = strtotime($fecha_raw)) {
                            $dia = date("d", $time);
                            $mes = $meses[date("n", $time) - 1];
                            $anio = date("Y", $time);
                            $fecha_formatted = "$dia de $mes de $anio";
                        }

                        $url_embed = $pod['url_embed'] ?: '';
                        $imagen_url = $pod['imagen_url'] ?: '';
                        $src_img = obtener_ruta_imagen($imagen_url);
                ?>
                    <div class="col-lg-4 col-md-6 grids5-info mb-5">
                        <div class="card border-0 shadow-sm rounded-lg overflow-hidden h-100">
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($src_img); ?>" alt="<?php echo htmlspecialchars($titulo); ?>" class="img-fluid w-100" style="height: 230px; object-fit: cover;">
                            </div>
                            <div class="blog-info p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <span class="text-muted small d-block mb-2"><i class="fa fa-calendar mr-1"></i> <?php echo htmlspecialchars($fecha_formatted); ?></span>
                                    <h4 class="card-title font-weight-bold mb-3" style="font-size: 1.15rem; line-height: 1.4;">
                                        <?php echo htmlspecialchars($titulo); ?>
                                    </h4>
                                    <?php if (!empty($descripcion)): ?>
                                        <p class="card-text text-muted mb-3" style="font-size: 0.9rem; line-height: 1.5;">
                                            <?php echo htmlspecialchars(substr($descripcion, 0, 110)) . (strlen($descripcion) > 110 ? '...' : ''); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="audio-container mt-3 pt-2 border-top">
                                    <?php echo generar_reproductor($url_embed, false); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else if (!$podcast_portada) {
                ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">No se encontraron episodios de podcast publicados.</div>
                    </div>
                <?php } ?>
            </div>

            <!-- PAGINACIÓN DINÁMICA -->
            <?php if ($total_paginas > 1): ?>
            <div class="pagination mt-5">
                <ul>
                    <?php if ($pagina_actual > 1): ?>
                        <li class="prev"><a href="podcast.php?pagina=<?php echo $pagina_actual - 1; ?>"><span class="fa fa-angle-double-left"></span> Anterior</a></li>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                        <li>
                            <a href="podcast.php?pagina=<?php echo $p; ?>" class="<?php echo ($p == $pagina_actual) ? 'active' : ''; ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="next"><a href="podcast.php?pagina=<?php echo $pagina_actual + 1; ?>">Siguiente <span class="fa fa-angle-double-right"></span></a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<!-- FOOTER -->
<section class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="row footer-top-29">
        <div class="col-lg-6 col-md-6 footer-list-29 footer-1">
          <h6 class="footer-title-29">Quiénes Somos</h6>
          <p>Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
          <div class="main-social-footer-29">
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="../assets/tiktokp.png" alt="TikTok"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
          <ul>
            <h6 class="footer-title-29">Contenido</h6>
            <li><a href="../actualidad.php">Noticias</a></li>
            <li><a href="../reportajes/reportajes.php">Reportajes</a></li>
            <li><a href="podcast.php">Podcast</a></li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-6 mt-lg-0 mt-5 footer-list-29 footer-3">
          <div class="properties">
            <h6 class="footer-title-29">Contacto</h6>
            <ul>
              <li><a href="mailto:info@dialogoydesarrollo.com.pe">info@dialogoydesarrollo.com.pe</a></li>
            </ul>
          </div>
        </div>
      </div>
      <div class="bottom-copies text-center">
        <p class="copy-footer-29">© <?php echo date("Y"); ?> Diálogo y Desarrollo Perú. Todos los derechos reservados | Diseñado por <a target="_blank" href="https://www.wsperu.info/">WebSoluciones</a></p>
      </div>
    </div>
  </div>

  <button onclick="topFunction()" id="movetop" title="Ir al inicio">
    <span class="fa fa-angle-up"></span>
  </button>
  <script>
    window.onscroll = function () { scrollFunction() };
    function scrollFunction() {
      if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        document.getElementById("movetop").style.display = "block";
      } else {
        document.getElementById("movetop").style.display = "none";
      }
    }
    function topFunction() {
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    }
  </script>
</section>

<!-- JS SCRIPTS desde carpeta assets -->
<script src="../assets/jquery-3.3.1.min.js"></script>
<script src="../assets/theme-change.js"></script>
<script src="../assets/bootstrap.min.js"></script>

<script>
  $(window).on("scroll", function () {
    var scroll = $(window).scrollTop();
    if (scroll >= 80) {
      $("#site-header").addClass("nav-fixed");
    } else {
      $("#site-header").removeClass("nav-fixed");
    }
  });

  $(".navbar-toggler").on("click", function () {
    $("header").toggleClass("active");
  });
</script>

</body>
</html>
