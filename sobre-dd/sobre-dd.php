<?php
@include("../admin/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sobre D&D - Diálogo y Desarrollo Perú</title>

    <!-- Google fonts -->
    <link href="../assets/css" rel="stylesheet">
    <!-- Template CSS -->
    <link rel="stylesheet" href="../assets/style-starter.css">

    <style>
        html { scroll-behavior: smooth; }
        .inner-banner {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('../assets/social-bg.jpg') center/cover no-repeat #1e1e1e;
            padding: 80px 0 50px 0;
            color: #fff;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <a class="navbar-brand" href="../index.php">
              <img src="../assets/logo.png" alt="Diálogo y Desarrollo Perú" class="img-fluid" style="max-height:75px;">
          </a>
          <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item"><a class="nav-link" href="../index.php">Inicio</a></li>
                  <li class="nav-item"><a class="nav-link" href="../index.php#noticias">Actualidad</a></li>
                  <li class="nav-item"><a class="nav-link" href="../reportajes/reportajes.php">Reportajes</a></li>
                  <li class="nav-item"><a class="nav-link" href="../podcast/podcast.php">Podcast</a></li>
                  <li class="nav-item"><a class="nav-link" href="../boletines/boletines.php">Boletín NTEP</a></li>
                  <li class="nav-item"><a class="nav-link" href="../alianza/index.php">Alianzas</a></li>
                  <li class="nav-item active"><a class="nav-link" href="sobre-dd.php">Sobre D&amp;D</a></li>
                  <li class="ml-2">
                      <a href="../contacto.php" class="btn btn-style btn-outline-secondary">Contacto</a>
                  </li>
              </ul>
          </div>
      </nav>
  </div>
</header>
<!-- //HEADER -->

<div class="main-content pt-5 mt-4">

    <!-- BREADCRUMB / TITLE BANNER -->
    <section class="inner-banner text-center">
        <div class="container">
            <h1 class="font-weight-bold text-white mb-2">Sobre D&amp;D</h1>
            <p class="text-white-50"><a href="../index.php" class="text-white">Inicio</a> / Sobre D&amp;D</p>
        </div>
    </section>

    <!-- CONTENIDO SOBRE D&D -->
    <section class="w3l-content py-5">
        <div class="container py-lg-4">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-lg-0 mb-4">
                    <h2 class="font-weight-bold mb-3" style="color:#2b3a4a; font-size: 2.2rem;">Diálogo y Desarrollo Perú</h2>
                    <p class="text-secondary mb-4" style="font-size: 1.1rem; line-height: 1.7;">
                        Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.
                    </p>
                </div>
                <div class="col-lg-6 text-center text-lg-right">
                    <img src="../assets/nosotros-comunidad.png"
                         onerror="this.onerror=null;this.src='../assets/logo.png';"
                         class="img-fluid"
                         style="border-radius: 0 150px 150px 0; max-height: 380px;"
                         alt="Diálogo y Desarrollo Perú">
                </div>
            </div>
        </div>
    </section>

</div>

<!-- FOOTER -->
<section class="w3l-footer-29-main py-5 bg-dark text-white" id="footer" style="background-color: #1e1e1e !important;">
    <div class="container py-lg-3">
        <div class="row footer-top-29">
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

            <div class="col-lg-3 col-md-6 footer-list-29 mb-md-0 mb-4">
                <h5 class="footer-title-29 font-weight-bold text-white mb-3" style="font-size: 1.25rem;">Contenido</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="../index.php#noticias" class="text-secondary" style="color: #b0b0b0 !important;">Noticias</a></li>
                    <li class="mb-2"><a href="../index.php#especiales" class="text-secondary" style="color: #b0b0b0 !important;">Videos</a></li>
                    <li class="mb-2"><a href="../podcast/podcast.php" class="text-secondary" style="color: #b0b0b0 !important;">Posdcast.</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6 footer-list-29">
                <h5 class="footer-title-29 font-weight-bold text-white mb-3" style="font-size: 1.25rem;">Contacto</h5>
                <p class="mb-0">
                    <a href="mailto:info@dialogoydesarrollo.com.pe" class="text-secondary" style="color: #b0b0b0 !important;">info@dialogoydesarrollo.com.pe</a>
                </p>
            </div>
        </div>

        <hr class="mt-4 mb-4" style="border-color: rgba(255,255,255,0.1);">

        <div class="row align-items-center">
            <div class="col-12 text-center">
                <p class="copy-footer-29 mb-0" style="color: #b0b0b0; font-size: 0.9rem;">
                    © <?php echo date("Y"); ?> Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info/" class="text-white font-weight-bold">WebSolutions</a>
                </p>
            </div>
        </div>
    </div>

    <button onclick="topFunction()" id="movetop" title="Ir arriba" style="background-color: #d90429; color: #fff; border: none; border-radius: 3px; width: 36px; height: 36px; position: fixed; bottom: 20px; right: 20px; display: none; z-index: 99; cursor: pointer; text-align: center; line-height: 36px;">
        <span class="fa fa-angle-up"></span>
    </button>
</section>

<!-- JS Scripts -->
<script src="../assets/jquery-3.3.1.min.js.descarga"></script>
<script>
  $(window).on("scroll", function () {
    if ($(window).scrollTop() >= 80) {
      $("#site-header").addClass("nav-fixed");
    } else {
      $("#site-header").removeClass("nav-fixed");
    }
  });

  $(".navbar-toggler").on("click", function () {
    $("header").toggleClass("active");
  });

  window.onscroll = function () {
    if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
      document.getElementById("movetop").style.display = "block";
    } else {
      document.getElementById("movetop").style.display = "none";
    }
  };

  function topFunction() {
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
  }
</script>
<script src="../assets/bootstrap.min.js.descarga"></script>
</body>
</html>