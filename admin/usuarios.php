<?php
require_once("auth.php");
requerir_rol(['admin']);
include("conexion.php");

$mensaje = "";
$modo_edicion = false;
$usuario_edit = [
    'user_id'         => '',
    'nombre_completo' => '',
    'email'           => '',
    'telefono'        => '',
    'rol'             => 'periodista',
    'avatar_url'      => ''
];

$dir_avatars = 'uploads/avatars/';
if (!is_dir($dir_avatars)) {
    mkdir($dir_avatars, 0777, true);
}

// 1. ELIMINAR USUARIO
if (isset($_GET['eliminar'])) {
    $id_del = intval($_GET['eliminar']);
    $session_user_id = intval($_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0);

    if ($session_user_id > 0 && $session_user_id === $id_del) {
        $mensaje = "<div class='alert alert-warning'>No puedes eliminar tu propio usuario en uso.</div>";
    } else {
        // Borrar avatar físico si existe
        $res = $conexion->query("SELECT avatar_url FROM usuarios WHERE user_id = $id_del");
        if ($res && $res->num_rows > 0) {
            $f = $res->fetch_assoc();
            if (!empty($f['avatar_url'])) {
                if (file_exists($dir_avatars . $f['avatar_url'])) unlink($dir_avatars . $f['avatar_url']);
                elseif (file_exists('uploads/' . $f['avatar_url'])) unlink('uploads/' . $f['avatar_url']);
            }
        }

        $conexion->query("DELETE FROM usuarios WHERE user_id = $id_del");
        header("Location: usuarios.php");
        exit();
    }
}

// 2. CARGAR DATOS PARA EDICIÓN
if (isset($_GET['editar'])) {
    $id_edit = intval($_GET['editar']);
    $res = $conexion->query("SELECT * FROM usuarios WHERE user_id = $id_edit");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $usuario_edit = [
            'user_id'         => $row['user_id'] ?? '',
            'nombre_completo' => $row['nombre_completo'] ?? '',
            'email'           => $row['email'] ?? '',
            'telefono'        => $row['telefono'] ?? '',
            'rol'             => strtolower($row['rol'] ?? 'periodista'),
            'avatar_url'      => $row['avatar_url'] ?? ''
        ];
        $modo_edicion = true;
    }
}

// 3. GUARDAR O ACTUALIZAR USUARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_usuario'])) {
    $id_val          = intval($_POST['user_id']);
    $nombre_completo = $conexion->real_escape_string(trim($_POST['nombre_completo']));
    $email           = $conexion->real_escape_string(trim($_POST['email']));
    $telefono        = $conexion->real_escape_string(trim($_POST['telefono']));
    $password        = trim($_POST['password']);
    $rol             = strtolower($conexion->real_escape_string(trim($_POST['rol'])));

    $avatar_sql = "";
    $nuevo_avatar = "";

    // Procesar archivo de avatar si fue cargado
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $extensiones_permitidas)) {
            $nuevo_avatar = time() . "_" . uniqid() . "." . $ext;

            if ($id_val > 0) {
                $res_old = $conexion->query("SELECT avatar_url FROM usuarios WHERE user_id = $id_val");
                if ($res_old && $res_old->num_rows > 0) {
                    $old_img = $res_old->fetch_assoc()['avatar_url'];
                    if (!empty($old_img)) {
                        if (file_exists($dir_avatars . $old_img)) unlink($dir_avatars . $old_img);
                        elseif (file_exists('uploads/' . $old_img)) unlink('uploads/' . $old_img);
                    }
                }
            }

            move_uploaded_file($_FILES['avatar']['tmp_name'], $dir_avatars . $nuevo_avatar);
            $avatar_sql = ", avatar_url = '$nuevo_avatar'";
        } else {
            $mensaje = "<div class='alert alert-warning'>Formato de avatar inválido. Permitiendo JPG, PNG o WEBP.</div>";
        }
    }

    if (empty($mensaje)) {
        if (!empty($nombre_completo) && !empty($email)) {
            if ($id_val > 0) {
                // --- ACTUALIZAR REGISTRO ---
                $pass_sql = "";
                if (!empty($password)) {
                    $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                    $pass_sql = ", password_hash = '$pass_hash'";
                }

                $sql = "UPDATE usuarios SET
                            nombre_completo = '$nombre_completo',
                            email = '$email',
                            telefono = '$telefono',
                            rol = '$rol',
                            updated_at = NOW()
                            $pass_sql
                            $avatar_sql
                        WHERE user_id = $id_val";

                if ($conexion->query($sql)) {
                    $mensaje = "<div class='alert alert-success'>Usuario actualizado correctamente.</div>";
                    $modo_edicion = false;
                    $usuario_edit = ['user_id' => '', 'nombre_completo' => '', 'email' => '', 'telefono' => '', 'rol' => 'periodista', 'avatar_url' => ''];
                } else {
                    $mensaje = "<div class='alert alert-danger'>Error al actualizar: " . $conexion->error . "</div>";
                }
            } else {
                // --- CREAR NUEVO REGISTRO ---
                if (!empty($password)) {
                    $pass_hash = password_hash($password, PASSWORD_BCRYPT);
                    $sql = "INSERT INTO usuarios (nombre_completo, email, password_hash, telefono, avatar_url, rol, activo, created_at, updated_at)
                            VALUES ('$nombre_completo', '$email', '$pass_hash', '$telefono', '$nuevo_avatar', '$rol', 1, NOW(), NOW())";

                    if ($conexion->query($sql)) {
                        $mensaje = "<div class='alert alert-success'>Usuario creado correctamente.</div>";
                    } else {
                        $mensaje = "<div class='alert alert-danger'>Error al guardar: " . $conexion->error . "</div>";
                    }
                } else {
                    $mensaje = "<div class='alert alert-warning'>Debe ingresar una contraseña para el nuevo usuario.</div>";
                }
            }
        } else {
            $mensaje = "<div class='alert alert-warning'>Por favor complete todos los campos obligatorios.</div>";
        }
    }
}

// 4. LISTAR USUARIOS
$lista_usuarios = $conexion->query("SELECT * FROM usuarios ORDER BY user_id DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Gestión de Usuarios - Panel Administrativo</title>
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
            <li class="nav-item"><a class="nav-link" href="pdf.php"><i class="mdi mdi-file-pdf menu-icon"></i><span class="menu-title">PDFs</span></a></li>
            <li class="nav-item"><a class="nav-link" href="fotos.php"><i class="mdi mdi-image menu-icon"></i><span class="menu-title">Fotos</span></a></li>

            <?php if (es_admin()): ?>
              <li class="nav-item active"><a class="nav-link" href="usuarios.php"><i class="mdi mdi-account-group menu-icon"></i><span class="menu-title">Usuarios</span></a></li>
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
              <h4 class="card-title"><?php echo $modo_edicion ? 'Editar Usuario' : 'Crear Nuevo Usuario'; ?></h4>
              <form action="usuarios.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="user_id" value="<?php echo $usuario_edit['user_id']; ?>">

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Nombre Completo *</label>
                      <input type="text" name="nombre_completo" class="form-control" value="<?php echo htmlspecialchars($usuario_edit['nombre_completo']); ?>" required placeholder="Ej: Franco Morales">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Correo Electrónico *</label>
                      <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($usuario_edit['email']); ?>" required placeholder="correo@ejemplo.com">
                    </div>
                  </div>
                </div>

                <div class="row">
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Teléfono</label>
                      <input type="text" name="telefono" class="form-control" value="<?php echo htmlspecialchars($usuario_edit['telefono']); ?>" placeholder="Ej: 977873734">
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Contraseña <?php echo $modo_edicion ? '(Opcional al editar)' : '*'; ?></label>
                      <input type="password" name="password" class="form-control" <?php echo $modo_edicion ? '' : 'required'; ?>>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="form-group">
                      <label>Rol de Acceso *</label>
                      <select name="rol" class="form-control" required>
                        <option value="periodista" <?php echo ($usuario_edit['rol'] === 'periodista') ? 'selected' : ''; ?>>Periodista</option>
                        <option value="admin" <?php echo ($usuario_edit['rol'] === 'admin') ? 'selected' : ''; ?>>Administrador</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label>Avatar / Foto de Perfil (Opcional)</label>
                  <input type="file" name="avatar" class="form-control-file d-block" accept="image/*">
                </div>

                <button type="submit" name="guardar_usuario" class="btn btn-primary text-white">
                  <?php echo $modo_edicion ? 'Actualizar Usuario' : 'Crear Usuario'; ?>
                </button>
                <?php if ($modo_edicion): ?>
                  <a href="usuarios.php" class="btn btn-light">Cancelar</a>
                <?php endif; ?>
              </form>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h4 class="card-title">Usuarios del Sistema</h4>
              <div class="table-responsive">
                <table class="table table-striped align-middle">
                  <thead>
                    <tr>
                      <th>Avatar</th>
                      <th>ID</th>
                      <th>Nombre Completo</th>
                      <th>Correo Electrónico</th>
                      <th>Teléfono</th>
                      <th>Rol</th>
                      <th>Acción</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($lista_usuarios && $lista_usuarios->num_rows > 0): ?>
                      <?php while($u = $lista_usuarios->fetch_assoc()): ?>
                      <?php
                        $ruta_avatar = '';
                        if (!empty($u['avatar_url'])) {
                            if (file_exists('uploads/avatars/' . $u['avatar_url'])) {
                                $ruta_avatar = 'uploads/avatars/' . $u['avatar_url'];
                            } elseif (file_exists('uploads/' . $u['avatar_url'])) {
                                $ruta_avatar = 'uploads/' . $u['avatar_url'];
                            }
                        }
                      ?>
                      <tr>
                        <td>
                          <?php if (!empty($ruta_avatar)): ?>
                            <img src="<?php echo htmlspecialchars($ruta_avatar); ?>" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                          <?php else: ?>
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-weight: bold;">
                              <?php echo strtoupper(substr($u['nombre_completo'] ?? 'U', 0, 1)); ?>
                            </div>
                          <?php endif; ?>
                        </td>
                        <td><?php echo $u['user_id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($u['nombre_completo']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo htmlspecialchars($u['telefono'] ?? '-'); ?></td>
                        <td>
                          <span class="badge <?php echo (strtolower($u['rol']) === 'admin') ? 'badge-danger' : 'badge-info'; ?>">
                            <?php echo ucfirst(htmlspecialchars($u['rol'])); ?>
                          </span>
                        </td>
                        <td>
                          <a href="usuarios.php?editar=<?php echo $u['user_id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                          <a href="usuarios.php?eliminar=<?php echo $u['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de eliminar este usuario?');">Eliminar</a>
                        </td>
                      </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="7" class="text-center py-3 text-muted">No hay usuarios registrados.</td>
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
