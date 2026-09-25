<?php
require_once("auth.php");
requerir_login(); // Valida que el usuario esté autenticado (Admin o Periodista)
include("conexion.php");

// 1. Consultas para contadores totales
$total_reportajes = $conexion->query("SELECT COUNT(*) as total FROM reportajes")->fetch_assoc()['total'] ?? 0;
$total_noticias   = $conexion->query("SELECT COUNT(*) as total FROM noticias")->fetch_assoc()['total'] ?? 0;
$total_podcasts   = $conexion->query("SELECT COUNT(*) as total FROM podcasts")->fetch_assoc()['total'] ?? 0;
$total_videos     = $conexion->query("SELECT COUNT(*) as total FROM videos")->fetch_assoc()['total'] ?? 0;
$total_boletines  = $conexion->query("SELECT COUNT(*) as total FROM boletines")->fetch_assoc()['total'] ?? 0;
$total_usuarios   = $conexion->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'] ?? 0;

// 2. Consultas para suma de Vistas / Métricas acumuladas
$vistas_videos     = $conexion->query("SELECT SUM(vistas) as total FROM videos")->fetch_assoc()['total'] ?? 0;
$vistas_boletines  = $conexion->query("SELECT SUM(vistas) as total FROM boletines")->fetch_assoc()['total'] ?? 0;
$vistas_reportajes = $conexion->query("SELECT SUM(vistas) as total FROM reportajes")->fetch_assoc()['total'] ?? 0;
$vistas_noticias   = $conexion->query("SELECT SUM(vistas) as total FROM noticias")->fetch_assoc()['total'] ?? 0;

// 3. Contenidos más vistos (Métricas Top 5 Videos y Boletines)
$top_videos    = $conexion->query("SELECT titulo, vistas FROM videos ORDER BY vistas DESC LIMIT 5");
$top_boletines = $conexion->query("SELECT titulo, vistas FROM boletines ORDER BY vistas DESC LIMIT 5");

$nombre_usuario = $_SESSION['usuario_nombre'] ?? $_SESSION['nombre'] ?? $_SESSION['usuario'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Panel y Métricas - Diálogo y Desarrollo</title>
  <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="shortcut icon" href="images/logo.png" />
</head>
<body>
  <div class="container-scroller">

    <!-- Navbar Top -->
    <div class="horizontal-menu">
      <nav class="navbar top-navbar col-lg-12 col-12 p-0">
        <div class="container-fluid">
          <div class="navbar-menu-wrapper d-flex align-items-center justify-content-between">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
              <a class="navbar-brand brand-logo" href="home.php"><img src="images/logo.png" alt="logo"/></a>
            </div>
            <ul class="navbar-nav navbar-nav-right">
              <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown" id="profileDropdown">
                  <span class="nav-profile-name"><?php echo htmlspecialchars($nombre_usuario); ?></span>
                  <img src="images/faces/face28.png" alt="profile"/>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown">
                  <a class="dropdown-item" href="logout.php"><i class="mdi mdi-logout text-primary"></i> Cerrar Sesión</a>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </nav>

      <!-- Menú principal -->
      <nav class="bottom-navbar">
        <div class="container">
          <ul class="nav page-navigation">
            <li class="nav-item active"><a class="nav-link" href="home.php"><i class="mdi mdi-chart-bar menu-icon"></i><span class="menu-title">Métricas / Inicio</span></a></li>
            <li class="nav-item"><a class="nav-link" href="reportajes.php"><i class="mdi mdi-file-document menu-icon"></i><span class="menu-title">Reportajes</span></a></li>
            <li class="nav-item"><a class="nav-link" href="autores.php"><i class="mdi mdi-account-edit menu-icon"></i><span class="menu-title">Autores</span></a></li>
            <li class="nav-item"><a class="nav-link" href="noticias.php"><i class="mdi mdi-newspaper menu-icon"></i><span class="menu-title">Noticias</span></a></li>
            <li class="nav-item"><a class="nav-link" href="boletines.php"><i class="mdi mdi-book-open-page-variant menu-icon"></i><span class="menu-title">Boletines</span></a></li>
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

    <!-- Contenido Principal -->
    <div class="container-fluid page-body-wrapper">
      <div class="main-panel">
        <div class="content-wrapper">

          <h3 class="text-dark font-weight-bold mb-3">Métricas Generales y Registros</h3>

          <!-- Bloque de Totales por Tabla -->
          <div class="row">
            <div class="col-md-2 grid-margin stretch-card">
              <div class="card"><div class="card-body text-center"><h5>Reportajes</h5><h3><?php echo $total_reportajes; ?></h3></div></div>
            </div>
            <div class="col-md-2 grid-margin stretch-card">
              <div class="card"><div class="card-body text-center"><h5>Noticias</h5><h3><?php echo $total_noticias; ?></h3></div></div>
            </div>
            <div class="col-md-2 grid-margin stretch-card">
              <div class="card"><div class="card-body text-center"><h5>Boletines</h5><h3><?php echo $total_boletines; ?></h3></div></div>
            </div>
            <div class="col-md-2 grid-margin stretch-card">
              <div class="card"><div class="card-body text-center"><h5>Podcasts</h5><h3><?php echo $total_podcasts; ?></h3></div></div>
            </div>
            <div class="col-md-2 grid-margin stretch-card">
              <div class="card"><div class="card-body text-center"><h5>Videos</h5><h3><?php echo $total_videos; ?></h3></div></div>
            </div>

            <?php if (es_admin()): ?>
              <div class="col-md-2 grid-margin stretch-card">
                <div class="card"><div class="card-body text-center"><h5>Usuarios</h5><h3><?php echo $total_usuarios; ?></h3></div></div>
              </div>
            <?php endif; ?>
          </div>

          <!-- APARTADO DE MÉTRICAS DE VISUALIZACIONES -->
          <h4 class="text-dark font-weight-bold mt-4 mb-3">Métricas de Alcance (Visualizaciones)</h4>

          <div class="row">
            <!-- Tarjetas de Alcance acumulado -->
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card bg-primary text-white">
                <div class="card-body">
                  <h5 class="card-title text-white">Vistas en Videos</h5>
                  <h2><?php echo number_format($vistas_videos); ?></h2>
                  <p class="mb-0">Reproducciones acumuladas</p>
                </div>
              </div>
            </div>

            <div class="col-md-3 grid-margin stretch-card">
              <div class="card bg-success text-white">
                <div class="card-body">
                  <h5 class="card-title text-white">Vistas en Boletines</h5>
                  <h2><?php echo number_format($vistas_boletines); ?></h2>
                  <p class="mb-0">Lecturas/Descargas</p>
                </div>
              </div>
            </div>

            <div class="col-md-3 grid-margin stretch-card">
              <div class="card bg-info text-white">
                <div class="card-body">
                  <h5 class="card-title text-white">Vistas en Reportajes</h5>
                  <h2><?php echo number_format($vistas_reportajes); ?></h2>
                  <p class="mb-0">Lecturas de artículos</p>
                </div>
              </div>
            </div>

            <div class="col-md-3 grid-margin stretch-card">
              <div class="card bg-warning text-white">
                <div class="card-body">
                  <h5 class="card-title text-white">Vistas en Noticias</h5>
                  <h2><?php echo number_format($vistas_noticias); ?></h2>
                  <p class="mb-0">Ingresos a noticias</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tablas de Ranking: Videos y Boletines más vistos -->
          <div class="row mt-3">
            <div class="col-lg-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Top 5 Videos más Vistos</h4>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Título del Video</th>
                          <th>Visualizaciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while($row = $top_videos->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                          <td><label class="badge badge-primary"><?php echo $row['vistas']; ?> vistas</label></td>
                        </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-lg-6 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <h4 class="card-title">Top 5 Boletines más Leídos</h4>
                  <div class="table-responsive">
                    <table class="table table-striped">
                      <thead>
                        <tr>
                          <th>Título del Boletín</th>
                          <th>Lecturas</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php while($row = $top_boletines->fetch_assoc()): ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                          <td><label class="badge badge-success"><?php echo $row['vistas']; ?> vistas</label></td>
                        </tr>
                        <?php endwhile; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <footer class="footer">
          <div class="footer-wrap text-center">
            <span class="text-muted">Diálogo y Desarrollo - Sistema de Gestión de Contenidos</span>
          </div>
        </footer>
      </div>
    </div>
  </div>

  <script src="vendors/base/vendor.bundle.base.js"></script>
  <script src="js/template.js"></script>
</body>
</html>
