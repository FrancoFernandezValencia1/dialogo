<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

// Subimos un nivel (../) para buscar la conexión en la raíz
@include("../admin/conexion.php");

function consulta_segura($conexion, $sql) {
    if (!isset($conexion) || !$conexion || @mysqli_connect_errno()) return false;
    $resultado = @mysqli_query($conexion, $sql);
    return ($resultado && $resultado instanceof mysqli_result) ? $resultado : false;
}

// Consulta uniendo reportajes con la tabla fotos
$sql = "SELECT r.*, f.url_foto
        FROM reportajes r
        LEFT JOIN fotos f ON r.foto_id = f.foto_id
        ORDER BY r.reportaje_id DESC";

$res_reportajes = consulta_segura($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DyD Perú | Reportajes</title>

    <link href="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/css" rel="stylesheet">
    <link rel="stylesheet" href="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/style-starter.css">

    <style>
        .blog-info h4 a, .grids5-info h4 {
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }
        .img-card-reportaje {
            height: 220px;
            width: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="../index.php">
              <img src="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/logo.png" alt="Logo DDP" style="height:75px;">
          </a>
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item"><a class="nav-link" href="../index.php">Inicio</a></li>
                  <li class="nav-item"><a class="nav-link" href="../index.php#noticias">Actualidad</a></li>
                  <li class="nav-item active"><a class="nav-link" href="reportajes.php">Reportajes</a></li>
                  <li class="nav-item"><a class="nav-link" href="../podcast/podcast.php">Podcast</a></li>
                  <li class="nav-item"><a class="nav-link" href="../boletines/boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="../alianza/index.php">Alianzas</a></li>
                  <li class="nav-item"><a class="nav-link" href="../sobre-dd/sobre-dd.php">Sobre D&amp;D</a></li>
                  <li class="ml-2"><a href="../contacto.php" class="btn btn-style btn-outline-secondary">Contacto</a></li>
              </ul>
          </div>
      </nav>
  </div>
</header>

<!-- BREADCRUMB -->
<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Reportajes</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li><a href="../index.php">Inicio</a></li>
                            <li class="active"><a href="reportajes.php">Reportajes</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- LISTA DE REPORTAJES -->
<div class="grids-block-5 py-5">
    <div class="container py-lg-3">
        <div class="row">
            <?php
            if ($res_reportajes && mysqli_num_rows($res_reportajes) > 0) {
                while ($row = mysqli_fetch_assoc($res_reportajes)) {
                    $id = $row['reportaje_id'];
                    $titulo = $row['titulo'];

                    $fecha_raw = !empty($row['fecha_publicacion']) ? $row['fecha_publicacion'] : $row['created_at'];
                    $fecha = !empty($fecha_raw) ? date("d/m/Y", strtotime($fecha_raw)) : 'Reciente';

                    // Búsqueda de imagen saliendo un nivel a la raíz (../)
                    $src_img = "";
                    if (!empty($row['url_foto'])) {
                        $clean = ltrim(trim($row['url_foto']), '/');
                        $nombre_archivo = basename($clean);

                        if (file_exists("../" . $clean)) {
                            $src_img = "../" . $clean;
                        } elseif (file_exists("../admin/" . $clean)) {
                            $src_img = "../admin/" . $clean;
                        } elseif (file_exists("../admin/images/fotos/" . $nombre_archivo)) {
                            $src_img = "../admin/images/fotos/" . $nombre_archivo;
                        } elseif (file_exists("../images/fotos/" . $nombre_archivo)) {
                            $src_img = "../images/fotos/" . $nombre_archivo;
                        } else {
                            $src_img = (strpos($clean, 'admin/') === 0) ? "../" . $clean : "../admin/" . $clean;
                        }
                    }
            ?>
                    <div class="col-lg-4 col-md-6 grids5-info mb-4">
                        <a href="detalle.php?id=<?php echo $id; ?>" class="d-block">
                            <?php if (!empty($src_img)): ?>
                                <img src="<?php echo htmlspecialchars($src_img); ?>" alt="<?php echo htmlspecialchars($titulo); ?>" class="img-fluid radius-image img-card-reportaje" onerror="this.style.display='none';" />
                            <?php else: ?>
                                <div class="bg-secondary text-white text-center py-5 radius-image mb-2" style="height:220px; display:flex; align-items:center; justify-content:center;">
                                    <span>Sin imagen</span>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="blog-info">
                            <h5><?php echo $fecha; ?></h5>
                            <h4><a href="detalle.php?id=<?php echo $id; ?>"><?php echo htmlspecialchars($titulo); ?></a></h4>
                            <a href="detalle.php?id=<?php echo $id; ?>" class="btn mt-3 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo '<div class="col-12"><p class="alert alert-info text-center">No hay reportajes publicados por el momento.</p></div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- FOOTER -->
<section class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="bottom-copies text-center">
        <p class="copy-footer-29">© <?php echo date("Y"); ?> Diálogo y Desarrollo Perú. Todos los derechos reservados.</p>
      </div>
    </div>
  </div>
</section>

<script src="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/jquery-3.3.1.min.js.descarga"></script>
<script src="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/bootstrap.min.js.descarga"></script>
</body>
</html>
