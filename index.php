<?php
// Evitar que PHP 8 lance errores fatales si falta alguna tabla o columna
mysqli_report(MYSQLI_REPORT_OFF);
@include("admin/conexion.php");

// Función auxiliar para realizar consultas SQL seguras
function consulta_segura($conexion, $sql) {
    if (!isset($conexion) || !$conexion || @mysqli_connect_errno()) return false;
    $resultado = @mysqli_query($conexion, $sql);
    return ($resultado && $resultado instanceof mysqli_result) ? $resultado : false;
}

// Convertir enlaces de YouTube a formato Embed (Iframe)
function obtener_embed_youtube($url) {
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $matches)) {
        return 'https://www.youtube.com/embed/' . $matches[1];
    }
    return $url;
}

// Convertir enlaces de Spotify a formato Embed
function obtener_embed_spotify($url) {
    if (preg_match('#spotify\.com/(?:embed/)?(?:intl-[a-z]{2}(?:-[a-z]{2})?/)?(track|episode|show|album|playlist)/([a-zA-Z0-9]+)#i', $url, $matches)) {
        return "https://open.spotify.com/embed/" . $matches[1] . "/" . $matches[2];
    }
    return $url;
}

// Comprobar si es una URL externa
function es_url_externa($ruta) {
    return filter_var($ruta, FILTER_VALIDATE_URL) !== false;
}

// OBTIENE LA RUTA DE LA FOTO DEDUCIÉNDOLA DE LA BD O DEL ARRAY
function obtener_imagen_reportaje($conexion, $row) {
    if (!$row || !is_array($row)) return '';

    // 1. Revisar si la fila ya trae la columna con el nombre o ruta de la imagen
    $posibles_campos = [
        'url_foto', 'foto_url', 'foto_ruta', 'foto_archivo', 'foto_nombre', 'img_url',
        'img_ruta', 'img_archivo', 'foto_portada', 'foto', 'imagen', 'portada',
        'ruta_foto', 'url_imagen', 'ruta_imagen', 'archivo', 'imagen_url'
    ];

    foreach ($posibles_campos as $campo) {
        if (!empty($row[$campo]) && is_string($row[$campo]) && !is_numeric(trim($row[$campo]))) {
            return trim($row[$campo]);
        }
    }

    // 2. Si solo tenemos foto_id, buscar dinámicamente en las tablas de imágenes de la BD
    $foto_id = intval($row['foto_id'] ?? 0);
    if ($foto_id > 0 && isset($conexion) && $conexion) {
        // Probar en tabla 'fotos'
        $res = consulta_segura($conexion, "SELECT * FROM fotos WHERE foto_id = $foto_id OR id = $foto_id LIMIT 1");
        if ($res && $f = mysqli_fetch_assoc($res)) {
            foreach (['url_foto', 'url', 'ruta', 'archivo', 'nombre', 'foto', 'imagen', 'path', 'filename'] as $col) {
                if (!empty($f[$col]) && is_string($f[$col]) && !is_numeric(trim($f[$col]))) {
                    return trim($f[$col]);
                }
            }
            foreach ($f as $val) {
                if (is_string($val) && preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', trim($val))) {
                    return trim($val);
                }
            }
        }

        // Probar en tabla 'imagenes'
        $res = consulta_segura($conexion, "SELECT * FROM imagenes WHERE imagen_id = $foto_id OR id = $foto_id LIMIT 1");
        if ($res && $f = mysqli_fetch_assoc($res)) {
            foreach (['url', 'ruta', 'archivo', 'nombre', 'foto', 'imagen', 'path', 'filename'] as $col) {
                if (!empty($f[$col]) && is_string($f[$col]) && !is_numeric(trim($f[$col]))) {
                    return trim($f[$col]);
                }
            }
            foreach ($f as $val) {
                if (is_string($val) && preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', trim($val))) {
                    return trim($val);
                }
            }
        }

        // Probar en tabla 'archivos'
        $res = consulta_segura($conexion, "SELECT * FROM archivos WHERE id = $foto_id OR archivo_id = $foto_id LIMIT 1");
        if ($res && $f = mysqli_fetch_assoc($res)) {
            foreach ($f as $val) {
                if (is_string($val) && preg_match('/\.(jpg|jpeg|png|webp|gif|svg)$/i', trim($val))) {
                    return trim($val);
                }
            }
        }
    }

    return '';
}

// RESOLUCIÓN DE RUTAS CON DEPURACIÓN DE SERVIDOR (__DIR__)
function resolver_ruta_archivo($raw_path, $tipo = '', $default = './assets/reportaje-18-08-26.jpg') {
    if (empty($raw_path) || !is_string($raw_path) || is_numeric(trim($raw_path))) {
        return $default;
    }

    $raw_path = trim($raw_path);
    if (empty($raw_path)) {
        return $default;
    }

    if (es_url_externa($raw_path)) {
        return $raw_path;
    }

    // Normalizar separadores y eliminar slashes iniciales
    $clean_path = ltrim(str_replace('\\', '/', $raw_path), '/');
    $base_name  = basename($clean_path);

    // Posibles ubicaciones relativas a la raíz del proyecto (donde vive index.php)
    $candidatos = [
        $clean_path,
        "admin/" . $clean_path,
        "admin/images/fotos/" . $base_name,
        "admin/images/" . $base_name,
        "images/fotos/" . $base_name,
        "images/" . $base_name,
        "admin/uploads/" . $tipo . "/" . $base_name,
        "admin/uploads/reportajes/" . $base_name,
        "admin/uploads/fotos/" . $base_name,
        "admin/uploads/imagenes/" . $base_name,
        "admin/uploads/" . $base_name,
        "uploads/" . $tipo . "/" . $base_name,
        "uploads/reportajes/" . $base_name,
        "uploads/fotos/" . $base_name,
        "uploads/" . $base_name
    ];

    if (strpos($clean_path, 'admin/') === 0) {
        $candidatos[] = substr($clean_path, 6);
    }

    // Verificar existencia real en disco utilizando __DIR__
    foreach ($candidatos as $ruta) {
        if (!empty($ruta)) {
            $abs_path = __DIR__ . '/' . $ruta;
            if (@file_exists($abs_path) && !is_dir($abs_path)) {
                return $ruta;
            }
        }
    }

    // Si file_exists falla por permisos pero hay una ruta relativa plausible
    if (strpos($clean_path, 'admin/') === 0 || strpos($clean_path, 'images/') === 0 || strpos($clean_path, 'uploads/') === 0) {
        return $clean_path;
    }

    return "admin/images/fotos/" . $base_name;
}

// Formatear fechas a Español
function formatear_fecha_es($fecha_raw) {
    if (empty($fecha_raw)) return date("d/m/Y");
    $time = strtotime($fecha_raw);
    if (!$time) return $fecha_raw;

    $meses = [
        1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
    ];
    $mes = $meses[(int)date('n', $time)] ?? date('M', $time);
    return $mes . ' ' . date('d, Y', $time);
}

// ==========================================
// CONSULTAS A LA BASE DE DATOS
// ==========================================

// 1. REPORTAJES (Uniendo con la tabla fotos para obtener la portada directa)
$sql_rep = "SELECT r.*, f.url_foto
            FROM reportajes r
            LEFT JOIN fotos f ON r.foto_id = f.foto_id
            ORDER BY r.es_destacado DESC, r.reportaje_id DESC LIMIT 4";
$res_reportajes = consulta_segura($conexion, $sql_rep);

if (!$res_reportajes) {
    $res_reportajes = consulta_segura($conexion, "SELECT r.*, f.url_foto FROM reportajes r LEFT JOIN fotos f ON r.foto_id = f.foto_id ORDER BY r.reportaje_id DESC LIMIT 4");
}
if (!$res_reportajes) {
    $res_reportajes = consulta_segura($conexion, "SELECT * FROM reportajes ORDER BY 1 DESC LIMIT 4");
}

$reportajes_arr = [];
if ($res_reportajes && mysqli_num_rows($res_reportajes) > 0) {
    while ($r = mysqli_fetch_assoc($res_reportajes)) {
        $reportajes_arr[] = $r;
    }
}

$rep_principal = isset($reportajes_arr[0]) ? $reportajes_arr[0] : null;
$reportajes_secundarios = array_slice($reportajes_arr, 1);

// 2. NOTICIAS
$res_noticias = consulta_segura($conexion, "SELECT * FROM noticias ORDER BY noticia_id DESC LIMIT 3");
if (!$res_noticias) {
    $res_noticias = consulta_segura($conexion, "SELECT * FROM noticias ORDER BY 1 DESC LIMIT 3");
}

// 3. BOLETÍN
$res_boletin = consulta_segura($conexion, "SELECT * FROM boletines ORDER BY boletin_id DESC LIMIT 1");
if (!$res_boletin) {
    $res_boletin = consulta_segura($conexion, "SELECT * FROM boletines ORDER BY 1 DESC LIMIT 1");
}

// 4. PODCASTS
$res_podcast = consulta_segura($conexion, "SELECT * FROM podcasts ORDER BY 1 DESC LIMIT 4");

// 5. ESPECIALES / VIDEOS
$res_especiales = consulta_segura($conexion, "SELECT * FROM videos ORDER BY 1 DESC LIMIT 4");
if (!$res_especiales || mysqli_num_rows($res_especiales) == 0) {
    $res_especiales = consulta_segura($conexion, "SELECT * FROM especiales ORDER BY 1 DESC LIMIT 4");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DDP Noticias - Diálogo y Desarrollo Perú</title>

    <!-- Google fonts -->
    <link href="./assets/css" rel="stylesheet">

    <!-- Template CSS -->
    <link rel="stylesheet" href="./assets/style-starter.css">

    <style>
        html {
            scroll-behavior: smooth;
        }
        .audio-player-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 8px;
        }
        .embed-responsive-16by9 {
            position: relative;
            display: block;
            width: 100%;
            padding: 0;
            overflow: hidden;
            padding-top: 56.25%;
        }
        .embed-responsive-16by9 iframe,
        .embed-responsive-16by9 video {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }
        .titular-destacado {
            color: #d90429;
            font-weight: 700;
            line-height: 1.3;
            font-size: 1.7rem;
            transition: color 0.3s;
        }
        .titular-destacado:hover {
            color: #b00000;
            text-decoration: none;
        }
        .btn-ver-boletin {
            transition: transform 0.2s ease-in-out;
        }
        .btn-ver-boletin:hover {
            transform: translateY(-3px);
            text-decoration: none !important;
        }
    </style>
</head>
<body>

<!-- 1. HEADER / NAVBAR -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="index.php">
              <img src="./assets/logo.png" alt="Diálogo y Desarrollo Perú" title="Diálogo y Desarrollo Perú" class="img-fluid" style="max-height:75px;">
          </a>
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item active"><a class="nav-link" href="index.php">Inicio</a></li>
                  <li class="nav-item"><a class="nav-link" href="#noticias">Actualidad</a></li>
                  <li class="nav-item"><a class="nav-link" href="reportajes/reportajes.php">Reportajes</a></li>
                  <li class="nav-item"><a class="nav-link" href="podcast/podcast.php">Podcast</a></li>
                  <li class="nav-item"><a class="nav-link" href="boletines/boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="alianza/index.php">Alianzas</a></li>
                  <li class="nav-item"><a class="nav-link" href="sobre-dd/sobre-dd.php">Sobre D&amp;D</a></li>
                  <li class="ml-2">
                      <a href="contacto.php" class="btn btn-style btn-outline-secondary">Contacto</a>
                  </li>
              </ul>
          </div>
      </nav>
  </div>
</header>
<!-- //HEADER -->

<div class="main-content pt-5 mt-4">

    <!-- 2. SECCIÓN REPORTAJES -->
    <section class="w3l-homeblock1 py-5" id="reportajes">
        <div class="container py-lg-4">
            <h2 class="section-title-left mb-4 font-weight-bold" style="color:#2b3a4a;">Reportajes</h2>

            <?php
            if ($rep_principal) {
                $id_principal   = $rep_principal['reportaje_id'] ?? $rep_principal['id'] ?? 0;
                $link_principal = ($id_principal > 0) ? "reportajes/detalle.php?id=" . $id_principal : "reportajes/reportajes.php";
                $tit_principal  = $rep_principal['titulo'] ?? 'Reportaje Destacado';

                $raw_img_p     = obtener_imagen_reportaje($conexion, $rep_principal);
                $img_principal = resolver_ruta_archivo($raw_img_p, 'reportajes', './assets/reportaje-18-08-26.jpg');
                $fecha_p       = formatear_fecha_es($rep_principal['fecha_publicacion'] ?? $rep_principal['created_at'] ?? '');

                $txt_p = trim(strip_tags($rep_principal['resumen_corto'] ?? $rep_principal['desarrollo'] ?? ''));
                if (empty($txt_p)) {
                    $txt_p = 'Haz clic en el enlace para leer la información completa de este reportaje especial.';
                } else if (mb_strlen($txt_p) > 220) {
                    $txt_p = mb_substr($txt_p, 0, 220) . '...';
                }
            ?>

            <!-- REPORTAJE DESTACADO EN GRANDE -->
            <div class="row align-items-center mb-5">
                <div class="col-lg-6 col-md-12 mb-lg-0 mb-4">
                    <a href="<?php echo htmlspecialchars($link_principal); ?>" class="d-block">
                        <img src="<?php echo htmlspecialchars($img_principal); ?>"
                             onerror="this.onerror=null;this.src='./assets/reportaje-18-08-26.jpg';"
                             class="img-fluid radius-image w-100 shadow-sm"
                             alt="<?php echo htmlspecialchars($tit_principal); ?>"
                             style="max-height: 380px; object-fit: cover; border-radius: 10px;">
                    </a>
                </div>
                <div class="col-lg-6 col-md-12 pl-lg-4">
                    <p class="text-muted mb-2 font-weight-bold" style="font-size: 0.95rem;">
                        <?php echo $fecha_p; ?>
                    </p>
                    <h3 class="mb-3">
                        <a href="<?php echo htmlspecialchars($link_principal); ?>" class="titular-destacado">
                            <?php echo htmlspecialchars($tit_principal); ?>
                        </a>
                    </h3>
                    <p class="text-secondary mb-4" style="line-height: 1.6; font-size: 1rem; color: #495057;">
                        <?php echo htmlspecialchars($txt_p); ?>
                    </p>
                    <a href="<?php echo htmlspecialchars($link_principal); ?>" class="text-dark font-weight-bold d-inline-flex align-items-center" style="font-size: 1.05rem;">
                        Leer <i class="fa fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
            <?php } ?>

            <!-- SECUNDARIOS -->
            <div class="row">
                <?php if (!empty($reportajes_secundarios)): ?>
                    <?php foreach ($reportajes_secundarios as $rep):
                        $id_sec   = $rep['reportaje_id'] ?? $rep['id'] ?? 0;
                        $link_sec = ($id_sec > 0) ? "reportajes/detalle.php?id=" . $id_sec : "reportajes/reportajes.php";
                        $tit_sec  = $rep['titulo'] ?? '';

                        $raw_img_sec = obtener_imagen_reportaje($conexion, $rep);
                        $img_sec     = resolver_ruta_archivo($raw_img_sec, 'reportajes', './assets/reportaje-18-08-26.jpg');
                        $fecha_sec   = formatear_fecha_es($rep['fecha_publicacion'] ?? $rep['created_at'] ?? '');
                    ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card border-0 bg-light radius-image h-100 p-3 shadow-sm d-flex flex-column justify-content-between">
                                <div>
                                    <a href="<?php echo htmlspecialchars($link_sec); ?>">
                                        <img src="<?php echo htmlspecialchars($img_sec); ?>"
                                             onerror="this.onerror=null;this.src='./assets/reportaje-18-08-26.jpg';"
                                             class="card-img-top radius-image w-100"
                                             alt="<?php echo htmlspecialchars($tit_sec); ?>"
                                             style="height: 200px; object-fit: cover; border-radius: 8px;">
                                    </a>
                                    <div class="card-body px-0 pb-0 pt-3">
                                        <p class="text-muted mb-2"><small><?php echo $fecha_sec; ?></small></p>
                                        <h5 class="card-title font-weight-bold" style="font-size: 1.1rem; line-height: 1.4;">
                                            <a href="<?php echo htmlspecialchars($link_sec); ?>" class="text-dark">
                                                <?php echo htmlspecialchars($tit_sec); ?>
                                            </a>
                                        </h5>
                                    </div>
                                </div>
                                <div class="px-0 pt-2">
                                    <a href="<?php echo htmlspecialchars($link_sec); ?>" class="text-danger font-weight-bold d-inline-block">Leer <i class="fa fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="text-center mt-3">
                <a href="reportajes/reportajes.php" class="btn btn-light text-danger font-weight-bold px-4" style="background-color: #eaf4fc;">Ver todos los reportajes</a>
            </div>
        </div>
    </section>

    <!-- 3. SECCIÓN NOTICIAS RECIENTES (ACTUALIDAD) -->
    <section class="w3l-news py-5 bg-light" id="noticias">
        <div class="container py-lg-3">
            <h2 class="section-title-left mb-4 font-weight-bold" style="color:#2b3a4a;">Noticias Recientes</h2>
            <div class="row">
                <?php
                if ($res_noticias && mysqli_num_rows($res_noticias) > 0) {
                    while ($noticia = mysqli_fetch_assoc($res_noticias)) {
                        $id_n       = $noticia['noticia_id'] ?? $noticia['id'] ?? 0;
                        $tit_n      = $noticia['titulo'] ?? '';
                        $res_n      = $noticia['resumen'] ?? '';
                        $fec_n      = formatear_fecha_es($noticia['fecha_publicacion'] ?? $noticia['fecha'] ?? '');

                        $raw_img_n  = obtener_imagen_reportaje($conexion, $noticia);
                        $img_n      = resolver_ruta_archivo($raw_img_n, 'noticias', './assets/reportaje-18-08-26.jpg');

                        $link_ext_n = trim($noticia['link_externo'] ?? '');
                        if (!empty($link_ext_n)) {
                            $link_n   = $link_ext_n;
                            $target_n = 'target="_blank"';
                        } else {
                            $link_n   = "actualidad.php?id=" . $id_n;
                            $target_n = '';
                        }
                ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 bg-white radius-image h-100 p-3 shadow-sm d-flex flex-column justify-content-between">
                            <div>
                                <a href="<?php echo htmlspecialchars($link_n); ?>" <?php echo $target_n; ?>>
                                    <img src="<?php echo htmlspecialchars($img_n); ?>"
                                         onerror="this.onerror=null;this.src='./assets/reportaje-18-08-26.jpg';"
                                         class="card-img-top radius-image mb-2"
                                         alt="<?php echo htmlspecialchars($tit_n); ?>"
                                         style="height: 190px; object-fit: cover; border-radius: 8px;">
                                </a>
                                <div class="card-body px-0 pb-0 pt-2">
                                    <p class="text-muted mb-2"><small><?php echo $fec_n; ?></small></p>
                                    <h5 class="card-title font-weight-bold" style="font-size: 1.05rem; line-height: 1.4;">
                                        <a href="<?php echo htmlspecialchars($link_n); ?>" <?php echo $target_n; ?> class="text-dark">
                                            <?php echo htmlspecialchars($tit_n); ?>
                                        </a>
                                    </h5>
                                    <?php if(!empty($res_n)): ?>
                                        <p class="text-secondary small mt-2" style="line-height: 1.4; color: #6c757d;">
                                            <?php echo htmlspecialchars(mb_substr(strip_tags($res_n), 0, 110)) . '...'; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="px-0 pt-2">
                                <a href="<?php echo htmlspecialchars($link_n); ?>" <?php echo $target_n; ?> class="text-danger font-weight-bold d-inline-block">Leer <i class="fa fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card border-0 bg-white radius-image h-100 p-3 shadow-sm">
                            <img src="./assets/reportaje-18-08-26.jpg" class="card-img-top radius-image mb-2" alt="Noticia" style="height: 190px; object-fit: cover; border-radius: 8px;">
                            <div class="card-body px-0 pb-0">
                                <p class="text-muted mb-2"><small>Set 07, 2026</small></p>
                                <h5 class="card-title font-weight-bold"><a href="#" class="text-dark">Sin noticias recientes</a></h5>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="text-center mt-3">
                <a href="actualidad.php" class="btn btn-light text-danger font-weight-bold px-4" style="background-color: #eaf4fc;">Ver todas las noticias</a>
            </div>
        </div>
    </section>

    <!-- 4. SECCIÓN BOLETÍN NTEP -->
    <section class="w3l-boletin py-5" id="boletin" style="background-color: #ffffff;">
        <div class="container py-lg-4">
            <?php
            $bol = ($res_boletin && mysqli_num_rows($res_boletin) > 0) ? mysqli_fetch_assoc($res_boletin) : null;

            $num_boletin = $bol['numero_boletin'] ?? '45';
            $titulo_boletin = $bol['titulo'] ?? 'Boletin NTEP Año 2026';

            $resumen_boletin = $bol['resumen'] ?? "-Promueven megaproyectos turísticos por S/ 2,400 mllns.\n-Invertirán S/ 9 millones en zonas rurales de Cusco.\n-Producción láctea se duplica en Cajamarca.";

            $raw_pdf  = $bol['archivo_pdf_url'] ?? $bol['pdf'] ?? $bol['archivo'] ?? '';
            $ruta_pdf = resolver_ruta_archivo($raw_pdf, 'boletines', '');

            $raw_img_bol = obtener_imagen_reportaje($conexion, $bol);
            $img_bol     = resolver_ruta_archivo($raw_img_bol, 'boletines', './assets/reportaje-18-08-26.jpg');

            $fecha_raw = $bol['fecha_publicacion'] ?? '';
            if (!empty($fecha_raw)) {
                $time_b = strtotime($fecha_raw);
                $meses_es = [1=>'enero', 2=>'febrero', 3=>'marzo', 4=>'abril', 5=>'mayo', 6=>'junio', 7=>'julio', 8=>'agosto', 9=>'septiembre', 10=>'octubre', 11=>'noviembre', 12=>'diciembre'];
                $fecha_formateada = date('j', $time_b) . ' ' . ($meses_es[(int)date('n', $time_b)] ?? date('M', $time_b));
            } else {
                $fecha_formateada = '28 agosto';
            }
            ?>
            <div class="row align-items-center">
                <!-- Columna Izquierda: Datos y Botones -->
                <div class="col-lg-6 mb-lg-0 mb-4 pr-lg-5">
                    <h2 class="font-weight-bold mb-4" style="color: #2b2d42; font-size: 2.3rem; font-family: sans-serif;">
                        <?php echo htmlspecialchars($titulo_boletin); ?>
                    </h2>

                    <div class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.8; color: #6c757d; font-weight: 400;">
                        <?php echo nl2br(htmlspecialchars($resumen_boletin)); ?>
                    </div>

                    <div class="d-flex align-items-center mt-4 mb-4">
                        <!-- Número y Fecha -->
                        <div class="mr-5">
                            <div style="color: #d90429; font-size: 3.5rem; font-weight: 800; line-height: 1; font-family: sans-serif;">
                                N° <?php echo htmlspecialchars($num_boletin); ?>
                            </div>
                            <div class="mt-2" style="color: #2b2d42; font-size: 1.1rem; font-weight: 600;">
                                <?php echo htmlspecialchars($fecha_formateada); ?>
                            </div>
                        </div>

                        <!-- Botón Descarga Ver Boletin -->
                        <?php if (!empty($ruta_pdf)): ?>
                            <a href="<?php echo htmlspecialchars($ruta_pdf); ?>" target="_blank" class="btn-ver-boletin text-center text-decoration-none">
                                <div style="color: #d90429; font-size: 2.5rem; line-height: 1;">
                                    <i class="fa fa-download"></i>
                                </div>
                                <div class="mt-1" style="color: #2b2d42; font-size: 0.95rem; font-weight: 700;">
                                    Ver Boletin
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Botón Ver Todos -->
                    <div class="pt-2">
                        <a href="boletines/boletines.php" class="btn font-weight-bold text-white px-4 py-2" style="background-color: #d90429; border-color: #d90429; border-radius: 8px; font-size: 1rem; min-width: 140px;">
                            Ver Todos
                        </a>
                    </div>
                </div>

                <!-- Columna Derecha: Portada Oficial -->
                <div class="col-lg-6 text-center">
                    <div class="d-inline-block p-1 bg-white" style="max-width: 100%;">
                        <img src="<?php echo htmlspecialchars($img_bol); ?>"
                             onerror="this.onerror=null;this.src='./assets/reportaje-18-08-26.jpg';"
                             class="img-fluid"
                             alt="<?php echo htmlspecialchars($titulo_boletin); ?>"
                             style="max-height: 480px; width: auto; object-fit: contain; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.08);">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. SECCIÓN PODCAST -->
    <section class="w3l-podcast py-5 bg-light" id="podcast">
        <div class="container py-lg-4">
            <h2 class="text-center font-weight-bold mb-5" style="color:#2b3a4a;">Podcast &amp; Audios</h2>
            <div class="row">
                <?php
                if ($res_podcast && mysqli_num_rows($res_podcast) > 0) {
                    while ($pod = mysqli_fetch_assoc($res_podcast)) {
                        $audio_src = $pod['url_embed'] ?? $pod['archivo_audio'] ?? $pod['archivo'] ?? $pod['audio'] ?? '';

                        $raw_img_pod = obtener_imagen_reportaje($conexion, $pod);
                        $img_pod     = resolver_ruta_archivo($raw_img_pod, 'podcasts', './assets/logo-peru-red.png');

                        $es_spotify = (strpos($audio_src, 'spotify.com') !== false);
                        $es_youtube = (strpos($audio_src, 'youtube.com') !== false || strpos($audio_src, 'youtu.be') !== false);
                ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm radius-image p-3 h-100 text-center bg-white d-flex flex-column justify-content-between">
                            <div>
                                <img src="<?php echo htmlspecialchars($img_pod); ?>"
                                     onerror="this.onerror=null;this.src='./assets/logo-peru-red.png';"
                                     class="img-fluid mx-auto mb-3 radius-image"
                                     style="max-height: 100px; object-fit: cover;"
                                     alt="Podcast Cover">
                                <h6 class="font-weight-bold text-dark mb-2"><?php echo htmlspecialchars($pod['titulo']); ?></h6>
                            </div>

                            <div class="mt-3">
                                <?php if (!empty($audio_src)): ?>
                                    <?php if ($es_spotify): ?>
                                        <iframe src="<?php echo htmlspecialchars(obtener_embed_spotify($audio_src)); ?>" width="100%" height="80" frameborder="0" allowtransparency="true" allow="encrypted-media" style="border-radius: 8px;"></iframe>
                                    <?php elseif ($es_youtube): ?>
                                        <div class="embed-responsive-16by9">
                                            <iframe src="<?php echo htmlspecialchars(obtener_embed_youtube($audio_src)); ?>" allowfullscreen></iframe>
                                        </div>
                                    <?php else: ?>
                                        <div class="audio-player-box">
                                            <audio controls style="width: 100%; height: 35px;">
                                                <source src="<?php echo htmlspecialchars(resolver_ruta_archivo($audio_src, 'podcasts', $audio_src)); ?>">
                                                Tu navegador no soporta el audio.
                                            </audio>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p class="text-muted small">Audio no disponible</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm radius-image p-3 h-100 text-center bg-white">
                            <img src="./assets/logo-peru-red.png" class="img-fluid mx-auto mb-3" style="max-width: 60px;" alt="Podcast">
                            <p class="text-secondary small">Escucha los episodios más recientes en Diálogo y Desarrollo.</p>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="text-center mt-4">
                <a href="podcast/podcast.php" class="btn btn-danger font-weight-bold px-4 py-2" style="background-color: #d90429;">Ver Todos los Podcasts</a>
            </div>
        </div>
    </section>

    <!-- 6. SECCIÓN ESPECIALES / VIDEOS -->
    <section class="w3l-especiales py-5" id="especiales">
        <div class="container py-lg-4">
            <h2 class="text-center font-weight-bold mb-5" style="color:#2b3a4a;">Especiales y Reportajes en Video</h2>
            <div class="row">
                <?php
                if ($res_especiales && mysqli_num_rows($res_especiales) > 0) {
                    while ($esp = mysqli_fetch_assoc($res_especiales)) {
                        $url_vid  = $esp['url_video'] ?? $esp['url_embed'] ?? $esp['video_url'] ?? $esp['url'] ?? $esp['archivo_video'] ?? '';
                        $tit_esp  = $esp['titulo'] ?? $esp['nombre'] ?? 'Especial D&D';
                        $es_ext   = es_url_externa($url_vid);
                        $es_yt    = (strpos($url_vid, 'youtube.com') !== false || strpos($url_vid, 'youtu.be') !== false);
                ?>
                    <div class="col-lg-6 col-md-6 mb-4">
                        <div class="card border-0 shadow-sm radius-image overflow-hidden h-100 bg-dark text-white">
                            <?php if(!empty($url_vid)): ?>
                                <div class="embed-responsive-16by9">
                                    <?php if ($es_yt): ?>
                                        <iframe src="<?php echo htmlspecialchars(obtener_embed_youtube($url_vid)); ?>" title="<?php echo htmlspecialchars($tit_esp); ?>" allowfullscreen></iframe>
                                    <?php elseif ($es_ext): ?>
                                        <iframe src="<?php echo htmlspecialchars($url_vid); ?>" title="<?php echo htmlspecialchars($tit_esp); ?>" allowfullscreen></iframe>
                                    <?php else: ?>
                                        <video controls>
                                            <source src="<?php echo htmlspecialchars(resolver_ruta_archivo($url_vid, 'videos', $url_vid)); ?>" type="video/mp4">
                                            Tu navegador no soporta la reproducción de video.
                                        </video>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-5 text-center">
                                    <i class="fa fa-video-camera fa-3x text-muted mb-2"></i>
                                    <p class="text-muted">Video no disponible</p>
                                </div>
                            <?php endif; ?>

                            <div class="card-body text-center p-3">
                                <h6 class="font-weight-bold mb-0 text-white"><?php echo htmlspecialchars($tit_esp); ?></h6>
                            </div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-lg-6 col-md-6 mb-4 text-center mx-auto">
                        <div class="card border-0 bg-dark text-white radius-image overflow-hidden p-4 h-100 justify-content-center">
                            <p class="font-weight-bold mb-0">Por una <span class="bg-danger px-1">minería artesanal</span> segura para todos</p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <!-- 7. SECCIÓN SOBRE NOSOTROS -->
    <section class="w3l-nosotros py-5 bg-light" id="nosotros">
        <div class="container py-lg-4">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-lg-0 mb-4">
                    <h2 class="font-weight-bold mb-3" style="color:#2b3a4a; font-size: 2.5rem;">Diálogo y Desarrollo Perú</h2>
                    <p class="text-secondary mb-4" style="font-size: 1.1rem; line-height: 1.6;">Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
                    <a href="sobre-dd/sobre-dd.php" class="btn btn-danger font-weight-bold px-4 py-2" style="background-color: #d90429;">Nosotros</a>
                </div>
                <div class="col-lg-6 text-right">
                    <img src="./assets/nosotros-comunidad.png"
                         onerror="this.onerror=null;this.src='./assets/logo.png';"
                         class="img-fluid"
                         style="border-radius: 0 150px 150px 0;"
                         alt="Diálogo y Desarrollo Perú">
                </div>
            </div>
        </div>
    </section>

    <!-- 8. SECCIÓN REDES SOCIALES -->
    <section class="w3l-social-banner text-center py-5" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./assets/social-bg.jpg') center/cover no-repeat; color: #fff;">
        <div class="container py-4">
            <h3 class="font-weight-bold mb-3 text-white">Síguenos en nuestras Redes Sociales</h3>
            <div class="social-icons mt-3">
                <a href="https://www.facebook.com/DialogoyDesarrolloPeru" target="_blank" class="text-white mx-2" style="font-size: 1.8rem;"><i class="fa fa-facebook"></i></a>
                <a href="https://www.tiktok.com/@dialogo.y.desarrollo" target="_blank" class="text-white mx-2" style="font-size: 1.8rem;"><i class="fa fa-music"></i></a>
                <a href="https://www.instagram.com/dialogo.y.desarrollo/" target="_blank" class="text-white mx-2" style="font-size: 1.8rem;"><i class="fa fa-instagram"></i></a>
            </div>
        </div>
    </section>

</div>

<!-- FOOTER -->
<section class="w3l-footer-29-main py-5 bg-dark text-white" id="footer" style="background-color: #1e1e1e !important;">
    <div class="container py-lg-3">
        <div class="row footer-top-29">
            <!-- 1. Quiénes Somos -->
            <div class="col-lg-5 col-md-6 footer-list-29 mb-md-0 mb-4 pr-lg-5">
                <h5 class="footer-title-29 font-weight-bold text-white mb-3" style="font-size: 1.25rem;">Quiénes Somos</h5>
                <p class="text-secondary mb-3" style="color: #b0b0b0 !important; font-size: 0.95rem; line-height: 1.6;">
                    Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.
                </p>
                <div class="main-social-footer-29 mt-3">
                    <a href="https://www.facebook.com/DialogoyDesarrolloPeru" target="_blank" class="text-white mr-3" style="font-size: 1.1rem;"><i class="fa fa-facebook"></i></a>
                    <a href="https://www.tiktok.com/@dialogo.y.desarrollo" target="_blank" class="text-white mr-3" style="font-size: 1.1rem;"><i class="fa fa-music"></i></a>
                    <a href="https://www.instagram.com/dialogo.y.desarrollo/" target="_blank" class="text-white" style="font-size: 1.1rem;"><i class="fa fa-instagram"></i></a>
                </div>
            </div>

            <!-- 2. Contenido -->
            <div class="col-lg-3 col-md-6 footer-list-29 mb-md-0 mb-4">
                <h5 class="footer-title-29 font-weight-bold text-white mb-3" style="font-size: 1.25rem;">Contenido</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#noticias" class="text-secondary" style="color: #b0b0b0 !important;">Noticias</a></li>
                    <li class="mb-2"><a href="#especiales" class="text-secondary" style="color: #b0b0b0 !important;">Videos</a></li>
                    <li class="mb-2"><a href="podcast/podcast.php" class="text-secondary" style="color: #b0b0b0 !important;">Posdcast.</a></li>
                </ul>
            </div>

            <!-- 3. Contacto -->
            <div class="col-lg-4 col-md-6 footer-list-29">
                <h5 class="footer-title-29 font-weight-bold text-white mb-3" style="font-size: 1.25rem;">Contacto</h5>
                <p class="mb-0">
                    <a href="mailto:info@dialogoydesarrollo.com.pe" class="text-secondary" style="color: #b0b0b0 !important;">info@dialogoydesarrollo.com.pe</a>
                </p>
            </div>
        </div>

        <!-- Línea divisora -->
        <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">

        <!-- Copyright -->
        <div class="row align-items-center">
            <div class="col-12 text-center">
                <p class="copy-footer-29 mb-0" style="color: #b0b0b0; font-size: 0.9rem;">
                    © <?php echo date("Y"); ?> Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info/" class="text-white font-weight-bold">WebSolutions</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Botón Flotante Rojo "Ir arriba" -->
    <button onclick="topFunction()" id="movetop" title="Ir arriba" style="background-color: #d90429; color: #fff; border: none; border-radius: 3px; width: 36px; height: 36px; position: fixed; bottom: 20px; right: 20px; display: none; z-index: 99; cursor: pointer; text-align: center; line-height: 36px; padding: 0;">
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
<!-- //FOOTER -->

<!-- JS Scripts -->
<script src="./assets/jquery-3.3.1.min.js.descarga"></script>
<script src="./assets/theme-change.js.descarga"></script>
<script src="./assets/owl.carousel.js.descarga"></script>

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

<script src="./assets/bootstrap.min.js.descarga"></script>

</body>
</html>
