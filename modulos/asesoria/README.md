# Módulo ASE · Asesorías a aspirantes

**Equipo:** B-2
**Prefijo de tablas:** `ase_`
**Carrera cuyo contenido produce el equipo:** Médico Cirujano

## Qué resuelve

Canal de consulta asincrónica entre los aspirantes de la Universidad Tecnológica de Bahía de Banderas (UTBB) y el personal de orientación vocacional. Permite resolver dudas personalizadas con intercambio de archivos adjuntos y publicar las consultas recurrentes como preguntas frecuentes (FAQ) organizadas por categoría y carrera.

## Pantallas

| Pantalla | Archivo | Quién entra |
|---|---|---|
| Listado público | `index.php` | Cualquiera |
| Detalle | `ficha.php?id=` | Cualquiera |
| Panel | `admin/index.php` | <rol> |
| Alta y edición | `admin/editar.php` | <rol> |
| Baja | `admin/eliminar.php` | <rol> |

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
| `editor` / `coordinacion` (Orientador) | Responder en los hilos de conversación, adjuntar archivos y descargar adjuntos permitidos *(gestión desde el panel administrativo pendiente de implementar)*. |

## Proceso de estados

```
borrador → revision → publicado
                   ↘ rechazado
```

Quién puede provocar cada cambio y con qué condiciones.

## Archivos que recibe

| Qué | Tipos | Tamaño máximo | Quién lo descarga |
|---|---|---|---|
| Adjuntos en mensajes de consulta y respuesta | `image/jpeg` (`.jpg`), `image/png` (`.png`), `image/webp` (`.webp`), `application/pdf` (`.pdf`) | 5 MB | Únicamente el aspirante titular de la conversación y el personal de orientación autorizado (`coordinacion` o `editor` de la carrera asociada). |

## Credenciales de prueba

| Correo | Rol |
|---|---|
| `aspirante@ejemplo.mx` | `aspirante` |
| `med@utbb.mx` | `editor` — Médico Cirujano |
| `coordinacion@utbb.mx` | `coordinacion` |

## Plan de pruebas ejecutado

| # | Qué se probó | Resultado |
|---|---|---|
| 1 | | |

## Pendientes conocidos

Lo que no quedó y por qué. Es preferible declararlo a que aparezca en la
prueba cruzada.
