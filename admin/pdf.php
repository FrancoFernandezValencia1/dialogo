<?php
require_once("auth.php");
requerir_login();
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$pdf_edit = [
    'pdf_id'      => '',
    'titulo'      => '',
    'descripcion' => '',
    'url_archivo' => ''
];

$dir_pdf = 'uploads/pdf/';

// Crear carpeta de almacenamiento de PDFs si no existe
if (!is_dir($dir_pdf)) {
    mkdir($dir_pdf, 0777, true);
}

// 1. CARGAR REGISTRO PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM pdfs WHERE pdf_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $pdf_edit = $res->fetch_assoc();
        $modo_edicion = true;
    }
}

// 2. ELIMINAR PDF Y SU ARCHIVO FÍSICO
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);

    $res = $conexion->query("SELECT url_archivo FROM pdfs WHERE pdf_id = $id");
    if ($res && $res->num_rows > 0) {
        $data = $res->fetch_assoc();
        if (!empty($data['url_archivo']) && file_exists($dir_pdf . $data['url_archivo'])) {
            unlink($dir_pdf . $data['url_archivo']);
        }
    }

    $conexion->query("DELETE FROM pdfs WHERE pdf_id = $id");
    header("Location: pdf.php");
    exit();
}

// 3. GUARDAR (CREAR O ACTUALIZAR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_pdf'])) {
    $pdf_id      = intval($_POST['pdf_id']);
    $titulo      = $conexion->real_escape_string(trim($_POST['titulo']));
    $descripcion = $conexion->real_escape_string(trim($_POST['descripcion']));
    $usuario_id  = isset($_SESSION['usuario_id']) ? intval($_SESSION['usuario_id']) : (isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 1);

    $archivo_sql_update = "";
    $nuevo_archivo_pdf  = "";

    // Procesar archivo PDF
    if (isset($_FILES['archivo_pdf']) && $_FILES['archivo_pdf']['error'] === UPLOAD_ERR_OK) {
        $ext_pdf = strtolower(pathinfo($_FILES['archivo_pdf']['name'], PATHINFO_EXTENSION));

        if ($ext_pdf === 'pdf') {
            $nuevo_archivo_pdf = "doc_" . time() . "_" . rand(100, 999) . ".pdf";

            // Si está en modo edición, borramos el PDF viejo del servidor
            if ($pdf_id > 0) {
                $res_old = $conexion->query("SELECT url_archivo FROM pdfs WHERE pdf_id = $pdf_id");
                if ($res_old && $res_old->num_rows > 0) {
                    $old_pdf = $res_old->fetch_assoc()['url_archivo'];
                    if (!empty($old_pdf) && file_exists($dir_pdf . $old_pdf)) {
                        unlink($dir_pdf . $old_pdf);
                    }
                }
            }

            move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $dir_pdf . $nuevo_archivo_pdf);
            $archivo_sql_update = ", url_archivo = '$nuevo_archivo_pdf'";
        } else {
            $mensaje = "<div class='alert alert-warning'>El archivo subido debe tener extensión .PDF.</div>";
        }
    }

    if (empty($mensaje)) {
        if ($pdf_id > 0) {
            // --- ACTUALIZAR REGISTRO ---
            $sql = "UPDATE pdfs SET
                        titulo = '$titulo',
                        descripcion = '$descripcion'
                        $archivo_sql_update
                    WHERE pdf_id = $pdf_id";

            if ($conexion->query($sql)) {
                $mensaje = "<div class='alert alert-success'>¡Documento PDF actualizado exitosamente!</div>";
                $modo_edicion = false;
                $pdf_edit = ['pdf_id' => '', 'titulo' => '', 'descripcion' => '', 'url_archivo' => ''];
            } else {
                $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
            }

        } else {
            // --- INSERTAR NUEVO REGISTRO ---
            if (!empty($nuevo_archivo_pdf)) {
                $sql = "INSERT INTO pdfs (titulo, descripcion, url_archivo, usuario_id, vistas)
                        VALUES ('$titulo', '$descripcion', '$nuevo_archivo_pdf', $usuario_id, 0)";

                if ($conexion->query($sql)) {
                    $mensaje = "<div class='alert alert-success'>¡Documento PDF publicado exitosamente!</div>";
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
                }
            } else {
                $mensaje = "<div class='alert alert-danger'>Debe seleccionar un archivo PDF válido.</div>";
            }
        }
    }
}

// CONSULTA DE REGISTROS
$lista_pdfs = $conexion->query("SELECT * FROM pdfs ORDER BY pdf_id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>PDFs - Panel Administrativo</title>
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

            <!-- Pestaña Activa: PDFs -->
            <li class="nav-item active"><a class="nav-link" href="pdf.php"><i class="mdi mdi-file-pdf menu-icon"></i><span class="menu-title">PDFs</span></a></li>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Documento PDF' : 'Publicar Nuevo PDF'; ?></h4>
              <form action="pdf.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="pdf_id" value="<?php echo $pdf_edit['pdf_id']; ?>">

                <div class="row">
                  <div class="col-md-8">
                    <div class="form-group">
                      <label>Título del Documento *</label>
                      <input type="text" name="titulo" class="form-control" value="<?php echo htmlspecialchars($pdf_edit['titulo']); ?>" required placeholder="Ej. Informe Anual de Sostenibilidad 2026">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Archivo PDF <?php echo $modo_edicion ? '(Opcional al editar)' : '*'; ?></label>
                      <input type="file" name="archivo_pdf" class="form-control-file d-block" accept="application/pdf" <?php echo $modo_edicion ? '' : 'required'; ?>>
                      <?php if($modo_edicion && !empty($pdf_edit['url_archivo'])): ?>
                        <div class="mt-2">
                          <small class="text-muted d-block">Archivo actual:</small>
                          <a href="uploads/pdf/<?php echo htmlspecialchars($pdf_edit['url_archivo']); ?>" target="_blank" class="btn btn-outline-dark btn-xs mt-1">
                            <i class="mdi mdi-file-pdf text-danger"></i> <?php echo htmlspecialchars($pdf_edit['url_archivo']); ?>
                          </a>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Descripción del Documento</label>
                  <textarea name="descripcion" class="form-control" rows="3" placeholder="Resumen o detalles sobre el documento..."><?php echo htmlspecialchars($pdf_edit['descripcion']); ?></textarea>
                </div>

                <button type="submit" name="guardar_pdf" class="btn btn-warning text-white">
                  <?php echo $modo_edicion ? 'Actualizar PDF' : 'Publicar PDF'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="pdf.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Documentos PDF Registrados</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Título</th>
                      <th>Fecha de Registro</th>
                      <th>Archivo PDF</th>
                      <th>Vistas</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($lista_pdfs && $lista_pdfs->num_rows > 0): ?>
                      <?php while($doc = $lista_pdfs->fetch_assoc()): ?>
                      <tr>
                        <td>
                          <strong><?php echo htmlspecialchars($doc['titulo']); ?></strong>
                          <?php if (!empty($doc['descripcion'])): ?>
                            <br><small class="text-muted"><?php echo htmlspecialchars(mb_strimwidth($doc['descripcion'], 0, 80, "...")); ?></small>
                          <?php endif; ?>
                        </td>
                        <td><?php echo isset($doc['created_at']) ? date('d/m/Y H:i', strtotime($doc['created_at'])) : '-'; ?></td>
                        <td>
                          <?php if($doc['url_archivo'] && file_exists('uploads/pdf/' . $doc['url_archivo'])): ?>
                            <a href="uploads/pdf/<?php echo htmlspecialchars($doc['url_archivo']); ?>" target="_blank" class="btn btn-outline-danger btn-sm">
                              <i class="mdi mdi-download"></i> Abrir / Ver PDF
                            </a>
                          <?php else: ?>
                            <span class="badge badge-secondary">Archivo no disponible</span>
                          <?php endif; ?>
                        </td>
                        <td><span class="badge badge-info"><?php echo intval($doc['vistas']); ?> vistas</span></td>
                        <td>
                          <a href="pdf.php?editar=<?php echo $doc['pdf_id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="pdf.php?eliminar=<?php echo $doc['pdf_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Desea eliminar este PDF?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No se han registrado documentos PDF todavía.</td>
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
