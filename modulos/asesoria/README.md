# Módulo <CLAVE> · <Nombre del módulo>

**Equipo:** <A-1>
**Prefijo de tablas:** `<xxx_>`
**Carrera cuyo contenido produce el equipo:** <Carrera>

## Qué resuelve

Dos o tres frases. Qué problema atiende el módulo y para quién.

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
| `<xxx>_...` | | |

Referencias al núcleo: `usuarios`, `carreras`. Ninguna a otro módulo.

## Roles

| Rol | Qué puede hacer |
|---|---|
| | |

## Proceso de estados

```
borrador → revision → publicado
                   ↘ rechazado
```

Quién puede provocar cada cambio y con qué condiciones.

## Archivos que recibe

| Qué | Tipos | Tamaño máximo | Quién lo descarga |
|---|---|---|---|
| | | | |

## Credenciales de prueba

| Correo | Rol |
|---|---|
| | |

## Plan de pruebas ejecutado

| # | Qué se probó | Resultado |
|---|---|---|
| 1 | | |

## Pendientes conocidos

Lo que no quedó y por qué. Es preferible declararlo a que aparezca en la
prueba cruzada.
