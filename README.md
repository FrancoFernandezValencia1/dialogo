# 📰 Diálogo y Desarrollo — Sistema de Gestión de Contenidos (CMS)

[![PHP Version](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%2F%20MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-4.6%20%2F%205.x-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

Plataforma web integral de periodismo independiente diseñada para la difusión y administración de contenidos digitales (noticias, reportajes, boletines en PDF, podcasts e integraciones multimedia). Cuenta con un portal público optimizado para lectores y un **panel de administración protegido con control de acceso basado en roles (RBAC)**.

---

## 🌟 Características Principales

### 🌐 Portal Público
* **Lector de Boletines NTEP:** Visualización de publicaciones periódicas con soporte para descarga directa e integración de portadas dinámicas.
* **Paginación Dinámica:** Sistema automatizado para la navegación de archivos y noticias por páginas (`LIMIT` y `OFFSET`).
* **Integración Multimedia:** Reproducción nativa de podcasts (Spotify embed / audio HTML5) y catálogo de videos/fotos.
* **Diseño Responsive:** Interfaz adaptativa optimizada para dispositivos móviles, tablets y escritorio.

### 🔐 Panel de Administración (CMS)
* **Gestión de Usuarios:** Control de cuentas con roles diferenciados (*Administrador* y *Periodista*).
* **Control de Acceso (RBAC):** Restricción de permisos en menús y ejecuciones de código (ej. eliminación de registros o gestión de usuarios reservado únicamente para Administradores).
* **Autenticación Segura:** Manejo de sesiones persistentes con `auth.php` y encriptación de contraseñas.
* **CRUD Multisección:** Creación, edición y administración de Noticias, Reportajes, Boletines, Podcasts, Autores y Recursos PDF.

---

## 👥 Matriz de Permisos por Rol

| Módulo / Acción | Administrador | Periodista |
| :--- | :---: | :---: |
| Acceso al Dashboard (`home.php`) | ✅ | ✅ |
| Crear y Editar Contenido (Noticias, Reportajes, etc.) | ✅ | ✅ |
| Eliminar Registros / Publicaciones | ✅ | ❌ |
| Gestión de Usuarios (`usuarios.php`) | ✅ | ❌ |
| Configuración del Sistema | ✅ | ❌ |

---

## 🛠️ Tecnologías Utilizadas

* **Backend:** PHP 8.x (Programación modular, PDO / MySQLi con consultas preparadas)
* **Base de Datos:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript (ES6+), jQuery
* **Framework CSS & Iconos:** Bootstrap, Material Design Icons (MDI), FontAwesome
* **Servidor Web Recomendado:** Apache 2.4+ (XAMPP, WAMP, MAMP o Laragon)

---

## 📁 Estructura del Proyecto

```text
dialogo/
├── admin/                  # Core del Panel de Administración
│   ├── auth.php            # Sistema de autenticación y control de roles
│   ├── conexion.php        # Configuración de la base de datos
│   ├── home.php            # Dashboard principal / Métricas
│   ├── noticias.php        # Gestión de noticias
│   ├── reportajes.php      # Gestión de reportajes
│   ├── boletines.php       # Gestión de boletines
│   ├── usuarios.php        # Administración de cuentas (Exclusivo Admin)
│   └── uploads/            # Directorio de subida de archivos/PDFs
├── boletines/              # Almacenamiento local de archivos PDF
├── boletines_files/        # Estilos, scripts y assets del portal
├── index.php               # Página de inicio del portal público
├── boletines.php           # Vista pública de boletines
├── reportajes.php          # Vista pública de reportajes
└── README.md               # Documentación del proyecto
