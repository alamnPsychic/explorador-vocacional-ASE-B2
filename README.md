# Explorador Vocacional UTBB

Plataforma web para aspirantes a la Universidad Tecnológica de Bahía de Banderas:
información de las diez carreras, test vocacional, recorridos virtuales, testimonios
y agenda de visitas al campus.

Proyecto integrador del cuarto cuatrimestre de la Licenciatura en Ingeniería en
Tecnologías de la Información e Innovación Digital. Asignatura **Aplicaciones Web**,
clave E-APW-2.

Diez equipos desarrollan diez módulos distintos sobre un núcleo común. Este
repositorio contiene ese núcleo y la plantilla desde la que cada equipo construye
el suyo.

**Stack:** PHP 8.1+ · MariaDB / MySQL · Apache · PDO con sentencias preparadas · CSS propio

---

## 1. Puesta en marcha

### 1.1 Requisitos

XAMPP con Apache, PHP 8.1 o superior y MariaDB o MySQL. Git.

### 1.2 Pasos

1. **Clonar el repositorio dentro de `htdocs`.**

```
   cd C:\xampp\htdocs
   git clone https://github.com/GuillermoQV/4toApWeb-Proyecto-Final-Base.git explorador-vocacional
```

2. **Crear tu archivo de configuración.** No se sube al repositorio: cada
   quien tiene el suyo.

```
   cd explorador-vocacional
   copy config\config.ejemplo.php config\config.php
```

   Ajusta `BD_CONTRASENA` y `RUTA_BASE` si tu instalación lo requiere.

3. **Crear la base de datos.** En phpMyAdmin, pestaña Importar, selecciona
   `sql/01-nucleo.sql`. Crea la base, las dos tablas comunes y siembra las
   diez carreras.

4. **Crear los usuarios de prueba.** Abre en el navegador:

```
   http://localhost/explorador-vocacional/herramientas/crear-usuarios.php
```

   Crea una cuenta de coordinación, una de aspirante y una de editor por
   carrera. La contraseña de todas es `Demo1234`.

5. **Comprobar.** Abre `http://localhost/explorador-vocacional/`. Debes ver
   las diez carreras y la tabla de estado de los módulos.

---

## 2. Estructura

```
explorador-vocacional/
    index.php              Portada pública
    panel.php              Entrada a la administración
    README.md

    config/
        config.ejemplo.php Plantilla de configuración
        config.php         LA TUYA. No se sube al repositorio.

    nucleo/                NO SE MODIFICA
        comun.php          Punto de entrada: lo incluye todo
        conexion.php       Conexión PDO única
        seguridad.php      e(), redirigir(), mensajes, token CSRF
        sesion.php         Sesión, roles, requerir_rol()
        archivos.php       Carga y entrega segura de archivos
        consultas.php      Consultas de usuarios y carreras
        plantilla.php      cabecera() y pie()

    css/
        ev-base.css        Sistema de diseño común. NO SE MODIFICA.

    docs/
        guia-visual.html   Todos los componentes con su marcado

    auth/
        entrar.php  salir.php

    sql/
        01-nucleo.sql      Esquema común y semilla de carreras

    herramientas/
        crear-usuarios.php Solo desarrollo

    modulos/
        _plantilla/        Copia esta carpeta para empezar
        carreras/  testimonios/  test/  recorridos/  visitas/
        portafolio/  becas/  asesoria/  egresados/  noticias/
```

---

## 3. Cómo empieza cada equipo

1. Copia `modulos/_plantilla` sobre la carpeta de tu módulo. Si te tocó BEC,
   el destino es `modulos/becas`.
2. En `esquema.sql`, sustituye el prefijo `plt_` por el tuyo y modela tus
   tablas. Ejecútalo después de `01-nucleo.sql`.
3. En `includes/consultas.php`, sustituye el prefijo `plt_` de los nombres de
   función y ajusta las consultas a tu modelo.
4. Escribe tus pantallas tomando como base las de la plantilla.
5. Llena el `README.md` de tu módulo.

**Trabajas solo dentro de tu carpeta.** No se modifica la carpeta de otro
equipo, ni `nucleo/`, ni `css/ev-base.css`.

---

## 4. Lo que el núcleo ya resuelve

No lo vuelvas a escribir.

| Necesidad | Qué usar |
|---|---|
| Conectarse a la base | `bd()` |
| Imprimir un dato sin riesgo de XSS | `e($valor)` |
| Exigir sesión | `requerir_sesion()` |
| Exigir un rol | `requerir_rol('coordinacion')` |
| Exigir que el editor trabaje su carrera | `requerir_carrera($id)` |
| Redirigir tras un POST | `redirigir('index.php')` |
| Mensaje para la siguiente página | `mensaje('exito', '...')` y `pintar_mensajes()` |
| Token anti-CSRF | `campo_token()` y `verificar_token()` |
| Leer un dato del formulario | `post('titulo')`, `get('q')`, `entero(...)` |
| Subir un archivo con validación real | `guardar_archivo(...)` |
| Descargar un archivo restringido | `entregar_archivo(...)` |
| Lista de carreras | `carreras()`, `carrera($id)` |
| Encabezado y pie | `cabecera($titulo, $area, $hoja)` y `pie()` |

---

## 5. Reglas técnicas

1. El núcleo y el sistema de diseño no se modifican.
2. Todas tus tablas llevan tu prefijo.
3. No se referencian tablas de otro módulo. Solo `usuarios` y `carreras`.
4. Ninguna consulta concatena datos variables. Siempre marcadores `?`.
5. Toda salida de datos pasa por `e()`.
6. Toda validación se repite en el servidor.
7. Toda página de administración llama a `requerir_rol()` en su primera
   instrucción ejecutable.
8. Las contraseñas se guardan con `password_hash()`.
9. Tras un POST que modifica datos, siempre se redirige.
10. Las acciones que modifican datos van por POST y con token.

---

## 6. Trabajo con Git

```
git pull                    antes de empezar
git status                  qué cambiaste
git add .
git commit -m "BEC: formulario de solicitud con validación"
git push
```

Escribe el mensaje empezando con la clave de tu módulo. El historial es
evidencia de participación individual y se revisa.

**Nunca subas `config/config.php` ni el contenido de `uploads/`.** Están en
`.gitignore`.

---

## 7. Cuentas de prueba

| Correo | Rol | Contraseña |
|---|---|---|
| `coordinacion@utbb.mx` | coordinacion | `Demo1234` |
| `aspirante@ejemplo.mx` | aspirante | `Demo1234` |
| `tic@utbb.mx`, `med@utbb.mx`, `gas@utbb.mx`… | editor de esa carrera | `Demo1234` |

El correo del editor es la clave de la carrera en minúsculas. Las claves son
TIC, MAN, MED, TER, CON, MER, TUR, GAS, AGR y ENE.

---

## 8. Problemas frecuentes

**Apache no inicia.** El puerto 80 está ocupado. Libéralo o configura Apache
en el 8080; en ese caso `http://localhost:8080/...`.

**«No fue posible conectar con la base de datos».** Revisa que MySQL esté
iniciado y que `config/config.php` tenga el usuario y la contraseña correctos.

**La página se ve sin estilos.** `RUTA_BASE` no coincide con la carpeta real
dentro de `htdocs`.

**«Table doesn't exist».** Falta ejecutar `sql/01-nucleo.sql` o el
`esquema.sql` de tu módulo.

**El navegador muestra el código PHP.** Abriste el archivo con doble clic en
lugar de pedirlo por `http://localhost/...`.

---

## 9. Documentación del proyecto

| Documento | Para quién |
|---|---|
| Guía del proyecto integrador | Estudiantes de las cinco asignaturas |
| Especificación de Aplicaciones Web | Los diez equipos |
| Documento de módulo | Cada equipo, el suyo |
| Guía visual del sistema de diseño | Los diez equipos (`docs/guia-visual.html`) |
