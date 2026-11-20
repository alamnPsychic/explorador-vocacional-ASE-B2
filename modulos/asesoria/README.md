# Módulo ASE · Asesorías a aspirantes

**Equipo:** B-2
**Prefijo de tablas:** `ase_`
**Carrera cuyo contenido produce el equipo:** Médico Cirujano

## Qué resuelve

Canal de consulta asincrónica entre los aspirantes de la Universidad Tecnológica de Bahía de Banderas (UTBB) y el personal de orientación vocacional. Permite resolver dudas personalizadas con intercambio de archivos adjuntos y publicar las consultas recurrentes como preguntas frecuentes (FAQ) organizadas por categoría y carrera.

## Pantallas

| Pantalla | Archivo | Quién entra |
|---|---|---|
| Preguntas frecuentes y formulario de consulta | `index.php` | Consulta pública. Solo el aspirante puede enviar una consulta |
| Seguimiento de una conversación | `conversacion.php?id=ID` | Aspirante titular y personal de orientación con acceso |
| Descarga de un adjunto | `descargar.php?id=ID` | Aspirante titular y personal de orientación con acceso |
| Bandeja de orientación | `admin/index.php` | Editor y coordinación |
| Hilo, respuesta y cambio de estado | `admin/conversacion.php?id=ID` | Editor y coordinación, según la carrera |
| Listado, filtros y baja de preguntas frecuentes | `admin/faq.php` | Coordinación |
| Alta, edición y conversión de una respuesta | `admin/faq_form.php` (alta), `?id=ID` (edición), `?mensaje=ID` (conversión) | Coordinación |
| Listado, alta, edición y baja de categorías | `admin/categorias.php` (listado), `?nueva=1` (alta), `?id=ID` (edición y baja) | Coordinación |

Coordinación entra desde el panel general de la plataforma. El enlace de la página pública de Asesoría lleva a la bandeja tanto a coordinación como a editores.

## Tablas

| Tabla | Para qué | Relaciones |
|---|---|---|
| `ase_categorias` | Clasificación temática de las consultas y de las preguntas frecuentes | Ninguna externa (entidad catálogo propia) |
| `ase_faq` | Almacena las preguntas frecuentes publicadas y visibles al público | `categoria_id` → `ase_categorias(id)`, `carrera_id` → `carreras(id)`, `creado_por` → `usuarios(id)` |
| `ase_conversaciones` | Hilos o tickets de consulta abiertos por los aspirantes con control de estado | `aspirante_id` → `usuarios(id)`, `categoria_id` → `ase_categorias(id)`, `carrera_id` → `carreras(id)`, `orientador_id` → `usuarios(id)` |
| `ase_mensajes` | Mensajes cronológicos enviados por aspirantes y orientadores dentro de un hilo | `conversacion_id` → `ase_conversaciones(id)`, `usuario_id` → `usuarios(id)`, `publicado_faq_id` → `ase_faq(id)` |
| `ase_adjuntos` | Metadatos de los archivos subidos en los mensajes de ambas partes | `mensaje_id` → `ase_mensajes(id)` |

Referencias al núcleo: `usuarios`, `carreras`. Ninguna a otro módulo.

## Roles

| Rol | Qué puede hacer |
|---|---|
| `aspirante` | Buscar y filtrar preguntas frecuentes; abrir nuevas consultas con adjuntos; dar seguimiento a sus propias conversaciones; cerrar y reabrir sus hilos; descargar adjuntos de sus conversaciones. |
| `editor` (Orientador de carrera) | Atiende las consultas de su carrera y las generales. En esas consultas puede responder, adjuntar, descargar y cambiar el estado. |
| `coordinacion` (Orientador) | Atiende todas las consultas, administra las categorías y preguntas frecuentes, y convierte respuestas de orientación en preguntas frecuentes. |

Una pregunta creada desde una respuesta conserva el vínculo al mensaje y empieza oculta. Coordinación puede editar el asunto y el texto para retirar detalles personales antes de marcarla como visible. Solo se muestran públicamente las preguntas marcadas como visibles con una categoría activa. Las categorías que tienen consultas o preguntas vinculadas se pueden desactivar.

## Proceso de estados

- **Abierta.** Se crea al enviar una consulta o al reabrir una cerrada. Un seguimiento del aspirante también la devuelve a este estado.
- **Respondida.** Se alcanza cuando orientación responde.
- **Cerrada.** El titular u orientación pueden cerrar una consulta respondida. Ambos pueden reabrirla. Si el aspirante envía un seguimiento desde su hilo cerrado, el mensaje también reabre la consulta.

## Archivos que recibe

| Qué | Tipos | Tamaño máximo | Quién lo descarga |
|---|---|---|---|
| Adjuntos en mensajes de consulta y respuesta | `image/jpeg` (`.jpg`), `image/png` (`.png`), `image/webp` (`.webp`), `application/pdf` (`.pdf`) | 5 MB | Únicamente el aspirante titular de la conversación y el personal de orientación autorizado (`coordinacion` o `editor` de la carrera asociada). |

Los editores también pueden descargar adjuntos de consultas generales. Apache debe leer `uploads/.htaccess` para bloquear el acceso directo. Las descargas se sirven por `descargar.php` con verificación de permisos.

## Dependencias y datos de prueba

Se usa el núcleo compartido con PHP 8.x, PDO MySQL, `mbstring` y `fileinfo`. En una base local de prueba nueva, importa `sql/01-nucleo.sql`, ejecuta el generador del núcleo `herramientas/crear-usuarios.php` y luego importa `modulos/asesoria/esquema.sql`. Ese orden permite que los ejemplos de conversaciones usen los usuarios de prueba.

## Credenciales de prueba

| Correo | Rol |
|---|---|
| `aspirante@ejemplo.mx` | `aspirante` |
| `med@utbb.mx` | `editor` — Médico Cirujano |
| `coordinacion@utbb.mx` | `coordinacion` |

El generador del núcleo configura `Demo1234` para estas cuentas locales.

## Plan de pruebas ejecutado

| Caso | Verificación local |
|---|---|
| Bandeja | Búsqueda por texto y filtros por estado y categoría |
| Conversación | Respuesta con adjunto, cierre de una consulta respondida y reapertura |
| Archivos | Envío por ambas partes, descarga con permisos y rechazo por tipo real o tamaño inválido |
| Categorías | Alta, listado, edición, baja y bloqueo de la baja cuando existen referencias |
| Preguntas frecuentes | Alta, listado, filtros, edición, baja y visibilidad pública |
| Conversión | Creación desde una respuesta y reutilización del vínculo al intentar convertirla otra vez |
| Acceso | Redirección sin sesión, rechazo del aspirante en admin, alcance del editor por carrera y rechazo de CSRF inválido |
| Adaptación | Vistas de administración a 375 px en Chrome |

La prueba se ejecutó con PHP 8.2 y MariaDB en una instancia local aislada. La publicación HTTPS pertenece a la fase de integración.

## Asistencia de IA

Se usó Codex para diseñar e implementar la bandeja, el hilo administrativo, la administración de categorías y preguntas frecuentes, los ajustes de permisos y el plan de verificación de este cambio.
