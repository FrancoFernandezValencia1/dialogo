<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

// Conexión subiendo un nivel a la raíz
@include("../admin/conexion.php");

function consulta_segura($conexion, $sql) {
    if (!isset($conexion) || !$conexion || @mysqli_connect_errno()) return false;
    $resultado = @mysqli_query($conexion, $sql);
    return ($resultado && $resultado instanceof mysqli_result) ? $resultado : false;
}

// Capturar ID desde la URL (?id=8)
$id_reportaje = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$reportaje = null;
if ($id_reportaje > 0) {
    $sql_main = "SELECT r.*, f.url_foto
                 FROM reportajes r
                 LEFT JOIN fotos f ON r.foto_id = f.foto_id
                 WHERE r.reportaje_id = $id_reportaje
                 LIMIT 1";

    $res = consulta_segura($conexion, $sql_main);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $reportaje = $row;
    }
}

if ($reportaje) {
    $titulo = $reportaje['titulo'];
    $resumen = $reportaje['resumen_corto'];
    $contenido = $reportaje['desarrollo'];

    $fecha_raw = !empty($reportaje['fecha_publicacion']) ? $reportaje['fecha_publicacion'] : $reportaje['created_at'];
    $fecha = !empty($fecha_raw) ? date("d/m/Y", strtotime($fecha_raw)) : 'Reciente';

    $autor_nombre = "";
    if (!empty($reportaje['autor_id'])) {
        $res_aut = consulta_segura($conexion, "SELECT * FROM autores WHERE autor_id = " . (int)$reportaje['autor_id'] . " LIMIT 1");
        if ($res_aut && $row_aut = mysqli_fetch_assoc($res_aut)) {
            $autor_nombre = $row_aut['nombre'] ?? $row_aut['nombre_autor'] ?? $row_aut['autor'] ?? '';
        }
    }

    // Ruta de la imagen saliendo a la raíz
    $src_img = "";
    if (!empty($reportaje['url_foto'])) {
        $clean = ltrim(trim($reportaje['url_foto']), '/');
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

} else {
    $titulo = "Reportaje no encontrado";
    $resumen = "";
    $contenido = "<p class='alert alert-warning'>El reportaje solicitado no existe o el ID ingresado es incorrecto.</p>";
    $fecha = "";
    $autor_nombre = "";
    $src_img = "";
}

$res_ultimos = consulta_segura($conexion, "SELECT * FROM reportajes ORDER BY reportaje_id DESC LIMIT 5");

$sql_rel = "SELECT r.*, f.url_foto
            FROM reportajes r
            LEFT JOIN fotos f ON r.foto_id = f.foto_id
            WHERE r.reportaje_id != $id_reportaje
            ORDER BY r.reportaje_id DESC LIMIT 3";
$res_relacionados = consulta_segura($conexion, $sql_rel);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DyD Perú | <?php echo htmlspecialchars($titulo); ?></title>

    <link href="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/css" rel="stylesheet">
    <link rel="stylesheet" href="../DyD Perú _ Universidades públicas administran casi S_900 millones de canon, regalías y otros recursos determinados_files/style-starter.css">

    <style>
        .single-post-content, .blockquote, .title-single, .blog-single-post, p, q {
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
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
                  <li class="nav-item"><a class="nav-link" href="../actualidad.php">Actualidad</a></li>
                  <li class="nav-item active"><a class="nav-link" href="reportajes.php">Reportajes</a></li>
                  <li class="nav-item"><a class="nav-link" href="../podcast.php">Podcast</a></li>
                  <li class="nav-item"><a class="nav-link" href="../boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="../alianzas.php">Alianzas</a></li>
                  <li class="nav-item"><a class="nav-link" href="../sobre-dd.php">Sobre D&amp;D</a></li>
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

<!-- REPORTAJE PRINCIPAL -->
<section class="w3l-blog mt-lg-5">
    <div class="text-element-9 py-5 mt-lg-5">
        <div class="container py-lg-3">
            <div class="row grid-text-9">
                <div class="col-lg-8">
                    <div class="blog-single-post">
                        <div class="post-content">
                            <h2 class="title-single mb-2"><?php echo htmlspecialchars($titulo); ?></h2>
                            <?php if (!empty($fecha)): ?>
                                <p class="text-muted mb-4"><span class="fa fa-calendar"></span> <?php echo $fecha; ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($src_img)): ?>
                            <div class="single-post-image mb-4 text-center">
                                <img src="<?php echo htmlspecialchars($src_img); ?>" class="img-fluid w-100 radius-image" alt="<?php echo htmlspecialchars($titulo); ?>" onerror="this.style.display='none';">
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($resumen)): ?>
                            <blockquote class="blockquote my-4">
                                <q class="mb-0 d-block"><?php echo htmlspecialchars($resumen); ?></q>
                            </blockquote>
                        <?php endif; ?>

                        <?php if (!empty($autor_nombre)): ?>
                            <p class="font-weight-bold mb-4" style="color: #555;">Por <?php echo htmlspecialchars($autor_nombre); ?></p>
                        <?php endif; ?>

                        <div class="single-post-content mb-5">
                            <?php
                            if (strpos($contenido, '<p>') === false) {
                                echo nl2br(htmlspecialchars($contenido));
                            } else {
                                echo $contenido;
                            }
                            ?>
                        </div>

                        <nav class="post-navigation row mb-5 py-4">
                            <div class="post-prev col-md-6 pr-sm-5">
                                <span class="nav-title"><span class="fa fa-arrow-left mr-2"></span> <a href="reportajes.php">Volver a Reportajes</a></span>
                            </div>
                        </nav>
                    </div>
                </div>

                <!-- BARRA LATERAL -->
                <div class="col-lg-4 left-text-9 mt-lg-0 mt-5 pl-lg-4">
                    <div class="left-top-9 mt-5 pt-sm-3">
                        <h6 class="heading-small-text-9 mb-3">Últimas noticias</h6>
                        <?php
                        if ($res_ultimos && mysqli_num_rows($res_ultimos) > 0) {
                            while ($u = mysqli_fetch_assoc($res_ultimos)) {
                                $t_u = $u['titulo'];
                                $f_u_raw = !empty($u['fecha_publicacion']) ? $u['fecha_publicacion'] : $u['created_at'];
                                $f_u = !empty($f_u_raw) ? date("M d, Y", strtotime($f_u_raw)) : '';
                                echo '<a href="reportaje.php?id='.$u['reportaje_id'].'" class="p-post d-block py-2">';
                                echo '<h6 class="text-left-inner-9">'.htmlspecialchars($t_u).'</h6>';
                                if ($f_u) echo '<span class="sub-inner-text-9">'.$f_u.'</span>';
                                echo '</a>';
                            }
                        }
                        ?>
                    </div>

                    <div class="categories mt-5 pt-sm-3">
                        <h6 class="heading-small-text-9">Archivos</h6>
                        <ul>
                            <li><a href="reportajes.php">Septiembre 2026</a></li>
                            <li><a href="reportajes.php">Agosto 2026</a></li>
                            <li><a href="reportajes.php">Julio 2026</a></li>
                            <li><a href="reportajes.php">Junio 2026</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- REPORTAJES RELACIONADOS -->
<?php if ($res_relacionados && mysqli_num_rows($res_relacionados) > 0): ?>
<div class="grids-block-5 py-5 bg-light">
    <section class="pb-lg-4 pb-md-3">
        <h3 class="title-big text-center mb-5">Otros Reportajes</h3>
        <div class="container">
            <div class="row">
                <?php
                while ($rel = mysqli_fetch_assoc($res_relacionados)) {
                    $t_rel = $rel['titulo'];
                    $f_rel_raw = !empty($rel['fecha_publicacion']) ? $rel['fecha_publicacion'] : $rel['created_at'];
                    $f_rel = !empty($f_rel_raw) ? date("d/m/Y", strtotime($f_rel_raw)) : 'Reciente';

                    $src_img_rel = "";
                    if (!empty($rel['url_foto'])) {
                        $clean = ltrim(trim($rel['url_foto']), '/');
                        $src_img_rel = (strpos($clean, 'admin/') === 0) ? "../" . $clean : "../admin/" . $clean;
                    }
                ?>
                    <div class="col-lg-4 col-md-6 grids5-info mb-4">
                        <a href="reportaje.php?id=<?php echo $rel['reportaje_id']; ?>" class="d-block">
                            <?php if (!empty($src_img_rel)): ?>
                                <img src="<?php echo htmlspecialchars($src_img_rel); ?>" alt="<?php echo htmlspecialchars($t_rel); ?>" class="img-fluid radius-image" style="height:220px; width:100%; object-fit:cover;" onerror="this.style.display='none';" />
                            <?php endif; ?>
                        </a>
                        <div class="blog-info">
                            <h5><?php echo $f_rel; ?></h5>
                            <h4><a href="reportaje.php?id=<?php echo $rel['reportaje_id']; ?>" class="d-block"><?php echo htmlspecialchars($t_rel); ?></a></h4>
                            <a href="reportaje.php?id=<?php echo $rel['reportaje_id']; ?>" class="btn mt-4 p-0">Leer más <span class="fa fa-arrow-right"></span> </a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
</div>
<?php endif; ?>

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
