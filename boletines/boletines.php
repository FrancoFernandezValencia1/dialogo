<?php
// Ocultar errores directos para evitar rupturas de maquetación HTML
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

// Conexión a BD desde la subcarpeta
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

// Configuración de Paginación Dinámica
$limite = 9;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $limite;

// Contar total de boletines
$total_registros = 0;
$res_total = consulta_segura($conexion, "SELECT COUNT(*) as total FROM boletines");
if ($res_total && $f_tot = mysqli_fetch_assoc($res_total)) {
    $total_registros = (int)$f_tot['total'];
}
$total_paginas = ($total_registros > 0) ? ceil($total_registros / $limite) : 1;

// Consulta de boletines paginados
$res_boletines = consulta_segura($conexion, "SELECT * FROM boletines ORDER BY boletin_id DESC LIMIT $limite OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Boletines NTEP - Diálogo y Desarrollo Perú</title>

    <!-- Google fonts -->
    <link href="../boletines_files/css" rel="stylesheet">

    <!-- Template CSS -->
    <link rel="stylesheet" href="../boletines_files/style-starter.css">
    <link type="text/css" rel="stylesheet" charset="UTF-8" href="../boletines_files/m=el_main_css">
</head>
<body>

<!-- HEADER / NAVBAR -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="../index.php">
              <img src="../boletines_files/logo.png" alt="Diálogo y Desarrollo Perú" title="Diálogo y Desarrollo Perú" style="height:75px;">
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
                  <li class="nav-item"><a class="nav-link" href="../podcast/podcast.php">Podcast</a></li>
                  <li class="nav-item active"><a class="nav-link" href="boletines.php">Boletín NTEP <span class="sr-only">(actual)</span></a></li>
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
                    <h2 class="title-big">Boletines NTEP</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="../index.php">Inicio</a></li>
                            <li class="active">Boletines</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MAIN CONTENT / GRIDS -->
<div class="grids-block-5 py-5">
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php
                $count = 0;
                if ($res_boletines && mysqli_num_rows($res_boletines) > 0) {
                    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");

                    while ($bol = mysqli_fetch_assoc($res_boletines)) {
                        $count++;

                        // 1. Título
                        $titulo_b = !empty($bol['titulo']) ? $bol['titulo'] : 'Boletín NTEP';

                        // 2. Formateo de fecha según columna fecha_publicacion
                        $fecha_raw = $bol['fecha_publicacion'] ?? $bol['created_at'] ?? '';
                        $fecha_b = 'Edición Reciente';
                        if (!empty($fecha_raw)) {
                            $time = strtotime($fecha_raw);
                            if ($time) {
                                $dia = date("d", $time);
                                $mes = $meses[date("n", $time) - 1];
                                $anio = date("Y", $time);
                                $fecha_b = "$dia de $mes de $anio";
                            }
                        }

                        // 3. Resolución de la ruta del PDF (Columna: archivo_pdf_url)
                        $raw_file = trim($bol['archivo_pdf_url'] ?? $bol['pdf'] ?? '');
                        $src_pdf = '#';
                        $target = '';

                        if (!empty($raw_file) && $raw_file !== '#') {
                            if (es_url_externa($raw_file)) {
                                $src_pdf = $raw_file;
                                $target = 'target="_blank"';
                            } else {
                                $clean_file = ltrim($raw_file, '/');
                                $nombre_pdf = basename($clean_file);

                                if (file_exists("../admin/uploads/boletines/" . $nombre_pdf)) {
                                    $src_pdf = "../admin/uploads/boletines/" . $nombre_pdf;
                                } elseif (file_exists("../admin/uploads/" . $nombre_pdf)) {
                                    $src_pdf = "../admin/uploads/" . $nombre_pdf;
                                } elseif (file_exists("../uploads/boletines/" . $nombre_pdf)) {
                                    $src_pdf = "../uploads/boletines/" . $nombre_pdf;
                                } elseif (file_exists("../uploads/" . $nombre_pdf)) {
                                    $src_pdf = "../uploads/" . $nombre_pdf;
                                } elseif (file_exists("../admin/" . $clean_file)) {
                                    $src_pdf = "../admin/" . $clean_file;
                                } elseif (file_exists("../" . $clean_file)) {
                                    $src_pdf = "../" . $clean_file;
                                } else {
                                    $src_pdf = (strpos($clean_file, 'admin/') === 0) ? "../" . $clean_file : "../admin/uploads/boletines/" . $nombre_pdf;
                                }
                                $target = 'target="_blank"';
                            }
                        }

                        // 4. Resolución de la imagen de portada (Columna: foto_portada_url)
                        $raw_img = trim($bol['foto_portada_url'] ?? $bol['imagen'] ?? '');
                        $src_img = "";

                        if (!empty($raw_img)) {
                            if (es_url_externa($raw_img)) {
                                $src_img = $raw_img;
                            } else {
                                $clean_img = ltrim($raw_img, '/');
                                $nombre_img = basename($clean_img);

                                if (file_exists("../admin/uploads/boletines/" . $nombre_img)) {
                                    $src_img = "../admin/uploads/boletines/" . $nombre_img;
                                } elseif (file_exists("../admin/uploads/" . $nombre_img)) {
                                    $src_img = "../admin/uploads/" . $nombre_img;
                                } elseif (file_exists("../admin/images/fotos/" . $nombre_img)) {
                                    $src_img = "../admin/images/fotos/" . $nombre_img;
                                } elseif (file_exists("../uploads/boletines/" . $nombre_img)) {
                                    $src_img = "../uploads/boletines/" . $nombre_img;
                                } elseif (file_exists("../uploads/" . $nombre_img)) {
                                    $src_img = "../uploads/" . $nombre_img;
                                } elseif (file_exists("../boletines_files/" . $nombre_img)) {
                                    $src_img = "../boletines_files/" . $nombre_img;
                                } elseif (file_exists("../admin/" . $clean_img)) {
                                    $src_img = "../admin/" . $clean_img;
                                } elseif (file_exists("../" . $clean_img)) {
                                    $src_img = "../" . $clean_img;
                                } else {
                                    $src_img = (strpos($clean_img, 'admin/') === 0) ? "../" . $clean_img : "../admin/uploads/boletines/" . $nombre_img;
                                }
                            }
                        } else {
                            $src_img = "../boletines_files/boletin-ntep-45.png";
                        }

                        $margin_class = ($count > 3) ? ' mt-5' : '';
                ?>
                    <div class="col-lg-4 col-md-6 grids5-info<?php echo $margin_class; ?> mb-4">
                        <a <?php echo $target; ?> href="<?php echo htmlspecialchars($src_pdf); ?>" class="d-block">
                            <img src="<?php echo htmlspecialchars($src_img); ?>" alt="<?php echo htmlspecialchars($titulo_b); ?>" class="img-fluid radius-image" style="width: 100%; height: auto; max-height: 420px; object-fit: cover;">
                        </a>
                        <div class="blog-info">
                            <h5><?php echo htmlspecialchars($fecha_b); ?></h5>
                            <a <?php echo $target; ?> href="<?php echo htmlspecialchars($src_pdf); ?>" class="btn mt-4 p-0">
                                <?php echo htmlspecialchars($titulo_b); ?> <span class="fa fa-arrow-right"></span>
                            </a>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div class="col-12 text-center py-5">
                        <p class="alert alert-info">No se encontraron boletines publicados.</p>
                    </div>
                <?php } ?>
            </div>

            <!-- PAGINACIÓN DINÁMICA -->
            <?php if ($total_paginas > 1): ?>
            <div class="pagination mt-5">
                <ul>
                    <?php if ($pagina_actual > 1): ?>
                        <li class="prev"><a href="boletines.php?pagina=<?php echo $pagina_actual - 1; ?>"><span class="fa fa-angle-double-left"></span> Anterior</a></li>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                        <li>
                            <a href="boletines.php?pagina=<?php echo $p; ?>" class="<?php echo ($p == $pagina_actual) ? 'active' : ''; ?>">
                                <?php echo $p; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <li class="next"><a href="boletines.php?pagina=<?php echo $pagina_actual + 1; ?>">Siguiente <span class="fa fa-angle-double-right"></span></a></li>
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
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="../boletines_files/tiktokp.png" alt="TikTok"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
          </div>
        </div>
        <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
          <ul>
            <h6 class="footer-title-29">Contenido</h6>
            <li><a href="../actualidad.php">Noticias</a></li>
            <li><a href="../reportajes/reportajes.php">Reportajes</a></li>
            <li><a href="../podcast.php">Podcast</a></li>
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

<!-- JS SCRIPTS -->
<script src="../boletines_files/jquery-3.3.1.min.js.descarga"></script>
<script src="../boletines_files/theme-change.js.descarga"></script>
<script src="../boletines_files/bootstrap.min.js.descarga"></script>

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
