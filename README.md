# MisterPlan — Gestión de Reservas de Alojamiento

Aplicación de gestión de reservas de alojamiento desarrollada para la prueba técnica de **MisterPlan**.
Construida en **PHP nativo** (sin frameworks como Laravel o Symfony), con renderizado de vistas en servidor mediante **Twig** y frontend interactivo en **JavaScript vanilla** que consume la **API REST**.

---

## Enlace al despliegue

> **URL en producción:** `https://jeangreservas.alwaysdata.net/` _(ver sección Despliegue)_

---

## 1. Requisitos previos

- **PHP 8.2** o superior con las extensiones:
    - `pdo` y `pdo_mysql`
    - `mbstring`
    - `json`
    - `xml`
    - `curl`
- **MariaDB 10.4+** o **MySQL 8.0+**
- **Composer** (para descargar Twig y PHPUnit)

---

## 2. Cómo levantarlo en local (paso a paso)

### Paso 1: Clonar el repositorio y entrar al directorio

```bash
git clone https://github.com/jeangalvisc/prueba-tecnica-php.git
cd prueba-tecnica-php
```

### Paso 2: Instalar dependencias de Composer

```bash
composer install
```

_(Solo descarga `twig/twig` para las vistas y `phpunit/phpunit` para los tests de desarrollo)._

### Paso 3: Crear la base de datos e importar el esquema

En Ubuntu / Debian con MariaDB o MySQL:

```bash
# Crear la base de datos y usuario (si no existen)
sudo mariadb -e "CREATE DATABASE IF NOT EXISTS reservas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'reservas_app'@'localhost' IDENTIFIED BY 'reservas_local_pwd';
GRANT ALL PRIVILEGES ON reservas.* TO 'reservas_app'@'localhost';
FLUSH PRIVILEGES;"

# Importar las tablas y los 20 registros de ejemplo
mariadb -u reservas_app -preservas_local_pwd reservas < database/schema.sql
```

_(En entornos como **MAMP** o **WAMP**, abre phpMyAdmin, crea la base de datos `reservas`, selecciónala y usa la pestaña **Importar** seleccionando el fichero `database/schema.sql`)._

### Paso 4: Configurar el entorno

Copia la plantilla de variables de entorno:

```bash
cp .env.example .env
```

Verifica que las credenciales en `.env` coinciden con tu motor de base de datos local:

```ini
APP_ENV=dev
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=reservas
DB_USER=reservas_app
DB_PASS=reservas_local_pwd
```

### Paso 5: Arrancar el servidor de desarrollo

Puedes arrancarlo con el script de Composer:

```bash
composer run serve
```

O directamente con el servidor integrado de PHP:

```bash
php -S localhost:8000 -t public
```

Abre en tu navegador: **[http://localhost:8000](http://localhost:8000)**

---

## 3. Ejecución de tests

Se incluyen tests unitarios con **PHPUnit** que cubren la validación de entrada, las transiciones de estados del dominio y la lógica del servicio con repositorios en memoria:

```bash
composer test
# O directamente:
./vendor/bin/phpunit
```

---

## 4. Estructura del proyecto

El proyecto sigue una arquitectura limpia inspirada en DDD/Hexagonal, manteniendo la sencillez al no usar contenedores pesados:

```
├── database/
│   └── schema.sql                  # DDL + 20 INSERTs de ejemplo con historial
├── public/                         # Raíz pública del servidor web
│   ├── .htaccess                   # Rewrite rules para servidores Apache
│   ├── index.php                   # Front Controller único
│   └── assets/
│       ├── css/app.css             # Estilos responsive modernos
│       └── js/
│           ├── api.js              # Cliente fetch para la API REST
│           ├── ui.js               # Feedback, formateadores y gestión de errores
│           ├── listado.js          # Filtrado dinámico, modal de alta y cancelación
│           └── detalle.js          # Confirmar/cancelar y actualización de timeline
├── src/
│   ├── Controller/
│   │   ├── ReservaApiController.php   # Endpoints JSON (REST)
│   │   └── ReservaPageController.php  # Vistas HTML (Twig)
│   ├── Domain/
│   │   ├── EstadoReserva.php       # Enum con máquina de estados y transiciones válidas
│   │   ├── FiltroReservas.php      # Objeto de valor para criterios de búsqueda
│   │   ├── NuevaReserva.php        # DTO inmutable de entrada
│   │   ├── Reserva.php             # Entidad del dominio
│   │   ├── ReservaEvento.php       # Entidad del timeline/historial
│   │   └── ResultadoPaginado.php   # Contenedor de paginación
│   ├── Exception/
│   │   ├── ConflictException.php   # Error de transición de estado (HTTP 409)
│   │   ├── NotFoundException.php   # Recurso no encontrado (HTTP 404)
│   │   └── ValidationException.php # Errores de validación por campo (HTTP 400)
│   ├── Http/
│   │   ├── ErrorHandler.php        # Captura centralizada de excepciones y logs
│   │   ├── HttpException.php       # Excepciones de nivel HTTP (400, 405, 413, 415)
│   │   ├── Request.php             # Envoltorio inmutable de superglobales y JSON
│   │   ├── Response.php            # Respuestas tipadas JSON / HTML
│   │   ├── Router.php              # Enrutador regex con soporte para métodos HTTP
│   │   └── SecurityHeaders.php     # CSP estricto, no-sniff y Same-Origin check
│   ├── Infrastructure/
│   │   ├── Clock.php / SystemClock.php  # Abstracción de reloj para testing
│   │   ├── Config.php              # Lector ligero de .env
│   │   ├── Database.php            # Conexión PDO con prepared statements nativos
│   │   └── TwigFactory.php         # Configuración segura de Twig (autoescape)
│   ├── Repository/
│   │   ├── ReservaRepository.php        # Contrato de acceso a datos
│   │   ├── ReservaEventoRepository.php  # Contrato de eventos de historial
│   │   ├── TransactionManager.php       # Abstracción de transacciones ACID
│   │   └── Pdo/                         # Implementaciones con PDO MariaDB/MySQL
│   ├── Service/
│   │   └── ReservaService.php      # Casos de uso de negocio (orquestación atómica)
│   ├── Validation/
│   │   ├── FiltroValidator.php     # Validación de filtros de consulta
│   │   ├── Input.php               # Lector tipado y saneamiento de cadenas
│   │   └── ReservaValidator.php    # Validación de reglas de negocio para reservas
│   └── Kernel.php                  # Composición e inyección manual de dependencias
├── templates/                      # Plantillas Twig con herencia
│   ├── base.html.twig              # Layout principal
│   ├── error.html.twig             # Vista de errores HTTP amigable
│   ├── _partials/
│   │   └── badge_estado.html.twig  # Badge reutilizable según estado
│   └── reservas/
│       ├── listado.html.twig       # Listado, filtros y modales
│       └── detalle.html.twig       # Detalle y timeline de eventos
├── tests/                          # Tests unitarios con PHPUnit
├── composer.json
└── phpunit.xml.dist
```

---

## 5. Decisiones técnicas de diseño

### 1. ¿Por qué esta organización en capas?

- **Separación de responsabilidades:** La lógica del negocio (`ReservaService`, `Reserva`, `EstadoReserva`) no tiene conocimiento de SQL ni de HTTP. Si el día de mañana se cambiara la base de datos a MongoDB o la API se consumiera por CLI, el núcleo permanece intacto.
- **Inversión de dependencias:** El servicio depende de interfaces (`ReservaRepository`, `ReservaEventoRepository`, `TransactionManager`), lo que permite realizar tests unitarios ultrarrápidos con implementaciones en memoria sin tocar una base de datos real.
- **Inyección de dependencias manual en `Kernel.php`:** Al no usar un framework con contenedores mágicos de reflexión, cada servicio se construye bajo demanda de forma explícita y transparente.

### 2. Máquina de estados estricta y concurrencia optimista

- Los estados (`pendiente`, `confirmada`, `cancelada`) y sus transiciones están modelados mediante el enum `EstadoReserva`.
    - `pendiente` &rarr; `confirmada` o `cancelada`.
    - `confirmada` &rarr; `cancelada`.
    - `cancelada` es un estado final e inmutable.
- Si dos usuarios intentan cambiar el estado simultáneamente, la consulta SQL incluye control de concurrencia optimista:
    ```sql
    UPDATE reserva SET estado = :nuevo WHERE id = :id AND estado = :actual
    ```
    Si `rowCount() === 0`, el sistema devuelve un error `HTTP 409 Conflict` avisando al usuario de que la reserva ya fue modificada por otro proceso.

### 3. Atomicidad garantizada

Cada creación de reserva o cambio de estado genera obligatoriamente una fila en `reserva_evento`. Ambas operaciones se ejecutan dentro del mismo bloque transaccional (`TransactionManager::transactional`). Si falla el registro del evento, la modificación de la reserva hace rollback automático.

### 4. Seguridad en profundidad (Defense in Depth)

- **Prepared Statements nativos:** Se desactiva la emulación (`PDO::ATTR_EMULATE_PREPARES = false`). Los parámetros se envían al motor de base de datos de forma separada del árbol de análisis sintáctico de la consulta, anulando cualquier riesgo de SQL Injection.
- **Validación y saneamiento estricto:** El validador rechaza caracteres de control ASCII invisibles, valida emails con `FILTER_VALIDATE_EMAIL`, comprueba fechas existentes en calendario (`Y-m-d`), asegura que `fecha_salida > fecha_entrada` y que la estancia no supere un año.
- **Manejo monetario sin pérdidas de precisión:** El importe se valida y gestiona como decimal estricto (`DECIMAL(10,2)` en SQL y comparaciones de cadena normalizada en PHP), evitando los conocidos errores de redondeo de los tipos flotantes IEEE 754.
- **Protección XSS y CSRF:**
    - En backend: Cabeceras `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, y verificación de cabeceras de origen en peticiones de mutación (`Origin` / `Sec-Fetch-Site`).
    - En vistas: Twig con autoescape HTML activado por defecto. **No se utiliza el filtro `|raw` en ninguna parte de la aplicación.**
    - En frontend: El DOM se construye mediante `textContent` y creación dinámica de elementos, nunca interpolando cadenas de usuario con `innerHTML`.
- **Manejo de errores centralizado:** En modo producción (`APP_ENV=prod`), los errores 500 no muestran trazas ni detalles de base de datos al usuario; se registran en `var/log/app.log` y devuelven un mensaje genérico.

## 6. Qué haría diferente con más tiempo

1. **Autenticación y Autorización (RBAC):**
    - Incorporar autenticación mediante tokens JWT o sesiones HTTP seguras con cookies `HttpOnly`, `SameSite=Strict`.
    - Diferenciar permisos: recepción (crear y ver), administración (confirmar importes y cancelaciones extraordinarias).
2. **Sistema de migraciones automatizadas:**
    - En lugar de un archivo `.sql` monolítico, utilizar un gestor de migraciones liviano (como Phinx) para versionar cambios de esquema en equipo.
3. **Eventos de dominio y colas asíncronas:**
    - Desacoplar el envío de correos electrónicos de confirmación mediante una cola ligera (Redis / RabbitMQ) para que la respuesta HTTP tarde pocos milisegundos y no dependa del servidor SMTP.
4. **Tests de integración / End-to-End:**
    - Añadir tests funcionales con una base de datos SQLite en memoria o contenedor de pruebas levantando el servidor integrado de PHP.
5. **Auditoría completa:**
    - Registrar el usuario o IP que realizó cada evento en la tabla `reserva_evento`.

---

## 7. Escalabilidad (1.000 reservas / hora)

Una carga de 1.000 reservas por hora equivale a un promedio de ~0,3 escrituras por segundo, pero en el sector hotelero el tráfico presenta **picos pronunciados** (campañas promocionales, apertura de temporadas) donde las lecturas de disponibilidad y listados superan las escrituras en una proporción de 20:1 o superior.
Para soportar este volumen y picos sin degradación, aplicaría los siguientes cambios:

### 1. Base de datos

- **Arquitectura de réplica de lectura (Read Replicas):** Separar las conexiones de lectura y escritura. El listado de reservas y las consultas de disponibilidad se dirigen a nodos réplica de MySQL, mientras que las creaciones y cambios de estado van al nodo primario.
- **Indexación y optimización de rangos:** Los índices compuestos `(fecha_entrada, fecha_salida)` y `(estado, fecha_entrada)` optimizan el escaneo de índices para consultas de solapamiento de fechas sin recurrir a escaneos de tabla completa.
- **Particionamiento de tablas:** Si el historial acumula millones de reservas tras varios años, particionar la tabla `reserva` por rango anual de `fecha_entrada` o archivar reservas canceladas/pasadas en un esquema histórico.

### 2. Capa de aplicación y caché

- **Caché en Redis:**
    - Almacenar en Redis la disponibilidad y los datos maestros de alojamientos con invalidación por eventos.
    - Implementar **Rate Limiting** en Redis mediante algoritmo de Token Bucket por IP o cliente API para mitigar scraping abusivo o ataques de denegación de servicio.
- **Procesamiento asíncrono:** Mover notificaciones por email, generación de facturas PDF y webhooks de sincronización con Channel Managers / OTAs a trabajadores en segundo plano (workers CLI en PHP supervisados por Supervisor).

### 3. Infraestructura y servidores

- **Arquitectura Stateless en contenedores:** Empaquetar la aplicación en una imagen Docker ligera con PHP 8.3-FPM + Nginx.
- **Escalado horizontal:** Desplegar detrás de un balanceador de carga (AWS ALB o Cloudflare) con autoescalado basado en consumo de CPU / memoria.
- **Ajuste de PHP-FPM:** Configurar `pm = dynamic` con valores adecuados de `pm.max_children` calculados en base a la RAM disponible del servidor para evitar saturación de procesos.
- **Almacenamiento y observabilidad:** Centralizar logs estructurados (JSON) en herramientas como Datadog, Grafana Loki o CloudWatch para alertas en tiempo real de picos de latencia o errores 5xx.

## 8. se agrego un postman_collection.json en la raiz del proyecto para facilitar la interacción con la API.

El json contiene las siguientes peticiones:
1. Listar reservas (con filtros opcionales)
2. Ver detalle de una reserva
3. Crear nueva reserva (201 Created)
4. Confirmar reserva (PATCH)
5. Cancelar reserva (PATCH)
6. Error 400 — Validación (fechas o email incorrectos)
7. Error 404 — Reserva no existente
8. Error 409 — Conflicto de estado (cambio no permitido)
