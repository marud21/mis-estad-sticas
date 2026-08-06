# Mis Estadisticas - Corvepatios

Sistema de administracion para clubes/corporaciones deportivas: registro de socios y equipos, control de cargos y pagos, consulta publica de deuda, reportes contables en PDF/Excel, planilla de juego y mas.

Construido con **Laravel 11**, **Blade**, **MySQL** y **dompdf**.

## Funcionalidades principales

- **Socios**: registro completo (documento, contacto, entidad de salud, foto tomada con camara o subida desde galeria, posicion de juego, numero de camiseta, nivel), estado (activo/suspendido/retirado) y su equipo.
- **Equipos y torneos**: administracion de equipos y torneos.
- **Cargos y pagos**: tipos de cargo configurables (afiliacion, mensualidad, etc.), aplicacion masiva, historial de pagos con recibo imprimible.
- **Consulta publica de deuda**: cualquier socio puede consultar su deuda actual sin necesidad de cuenta, con datos de cuentas bancarias y WhatsApp de la corporacion para enviar comprobantes.
- **Reportes**: reporte contable (PDF/Excel) por rango de fechas, reporte individual de socio, planilla de pagos por equipo, y planilla de juego (formato de partido, con logo y datos de la corporacion).
- **Configuracion desde el panel de administracion**: nombre del sistema, logo, descripcion de la portada publica, numero de WhatsApp y cuentas bancarias, todo editable sin tocar codigo.
- **Landing page publica** con informacion del sistema y accesos rapidos a consulta de deuda / inicio de sesion.

## Requisitos

- PHP 8.2+ con extensiones: `mbstring`, `xml`, `bcmath`, `curl`, `zip`, **`gd`** (obligatoria para incrustar el logo en los PDFs generados con dompdf)
- Composer
- MySQL 8+ (o MariaDB equivalente)
- Servidor web (Nginx/Apache) o `php artisan serve` para desarrollo

## Instalacion local

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configura la base de datos en `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), y opcionalmente las credenciales del usuario administrador inicial:

```env
ADMIN_EMAIL=admin@tudominio.com
ADMIN_PASSWORD=una-contrasena-segura
```

Luego:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

El seeder crea un tipo de cargo base y un usuario administrador usando `ADMIN_EMAIL`/`ADMIN_PASSWORD` del `.env` (si no se definen, se genera una contrasena aleatoria que quedara solo en el log de la sesion de seed, asi que se recomienda definirlas explicitamente antes de sembrar).

## Notas de despliegue

- La extension **GD** de PHP es indispensable: sin ella, dompdf falla al generar cualquier PDF que incluya el logo de la corporacion.
- Ajusta `upload_max_filesize` y `post_max_size` en `php.ini` (y `client_max_body_size` en Nginx si aplica) para permitir la subida de fotos de socios tomadas con camara de celular (pueden pesar varios MB).
- Los archivos subidos por los usuarios (logo, fotos de socios) se guardan en `storage/app/public` via el disco `public` de Laravel — recuerda correr `php artisan storage:link`.

## Licencia

Proyecto privado, uso interno de la corporacion.
