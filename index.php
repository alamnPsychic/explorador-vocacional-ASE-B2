<?php
/**
 * Portada pública de la plataforma
 * Explorador Vocacional UTBB
 *
 * Esta página pertenece al núcleo. Es la puerta de entrada del aspirante y
 * debe llevar a los diez módulos.
 *
 * La fotografía de la portada se coloca en img/portada.jpg. Mientras no
 * exista, la variable --portada-imagen queda sin valor y se ve el degradado:
 * la página funciona igual, sin imágenes rotas.
 */

declare(strict_types=1);

require_once __DIR__ . '/nucleo/comun.php';

$lista = carreras();

$imagen = is_file(__DIR__ . '/img/portada.jpg')
    ? "--portada-imagen:url('" . RUTA_BASE . "/img/portada.jpg')"
    : '';

cabecera('Encuentra tu carrera');
?>

<section class="portada portada--imagen" style="<?= e($imagen) ?>">
  <div class="contenedor portada__contenido">
    <h1>Hay una carrera que se parece a ti</h1>
    <p class="entradilla">
      Conoce las diez carreras de la Universidad Tecnológica de Bahía de Banderas,
      recorre sus laboratorios, escucha a quienes ya estudian aquí y agenda tu visita al campus.
    </p>

    <div class="portada__acciones">
      <a class="boton boton--acento" href="<?= RUTA_BASE ?>/modulos/test/">Haz el test vocacional</a>
      <a class="boton boton--secundario" href="#carreras">Ver las carreras</a>
    </div>

    <div class="cifras">
      <div class="cifra">
        <span class="cifra__numero"><?= count($lista) ?></span>
        <span class="cifra__texto">carreras</span>
      </div>
      <div class="cifra">
        <span class="cifra__numero">5</span>
        <span class="cifra__texto">áreas de conocimiento</span>
      </div>
      <div class="cifra">
        <span class="cifra__numero">100%</span>
        <span class="cifra__texto">de las visitas, gratuitas</span>
      </div>
    </div>
  </div>
</section>

<!-- ======================= Carreras ======================= -->
<section class="contenedor seccion" id="carreras">

  <?php pintar_mensajes(); ?>

  <h2>Las diez carreras</h2>
  <p class="entradilla ancho-lectura">
    Cada carrera tiene su ficha con el plan de estudios, el perfil de egreso y los
    espacios donde se trabaja.
  </p>

  <?php if (!$lista): ?>
    <div class="vacio">
      <h3>Todavía no hay carreras cargadas</h3>
      <p class="ancho-lectura">
        Ejecuta <code>sql/01-nucleo.sql</code> para crear el esquema y sembrar las diez carreras.
      </p>
    </div>
  <?php else: ?>
    <div class="rejilla">
      <?php foreach ($lista as $c): ?>
        <a class="tarjeta tarjeta--area <?= e(clase_area($c['area'])) ?>"
           href="<?= RUTA_BASE ?>/modulos/carreras/ficha.php?id=<?= (int)$c['id'] ?>">
          <div class="tarjeta__cuerpo">
            <h3 class="tarjeta__titulo"><?= e($c['nombre']) ?></h3>
            <p class="leyenda"><?= e($c['duracion']) ?> · <?= e($c['modalidad']) ?></p>
          </div>
          <div class="tarjeta__pie">
            <span class="insignia insignia--info"><?= e($c['clave']) ?></span>
            <span>Ver ficha</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</section>

<!-- ======================= Accesos a los diez módulos ======================= -->
<section class="contenedor seccion" id="explora">

  <h2>Qué puedes hacer aquí</h2>
  <p class="entradilla ancho-lectura">
    Diez secciones para conocer la universidad antes de inscribirte.
  </p>

  <div class="accesos">
    <?php foreach (MODULOS as $carpeta => $m):
        $activo = modulo_existe($carpeta);
    ?>
      <a class="acceso <?= e(clase_area($m['area'])) ?> <?= $activo ? '' : 'acceso--inactivo' ?>"
         href="<?= $activo ? RUTA_BASE . '/modulos/' . e($carpeta) . '/' : '#explora' ?>"
         <?= $activo ? '' : 'aria-disabled="true" tabindex="-1"' ?>>
        <span class="acceso__distintivo" aria-hidden="true"><?= e($m['clave']) ?></span>
        <span>
          <span class="acceso__titulo"><?= e($m['nombre']) ?></span>
          <span class="acceso__texto">
            <?= $activo ? e($m['texto']) : 'Disponible próximamente.' ?>
          </span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

</section>

<!-- ======================= Visita ======================= -->
<section class="contenedor seccion">
  <div class="panel">
    <div class="fila fila--separada">
      <div class="ancho-lectura">
        <h2 class="sin-margen">¿Prefieres verlo en persona?</h2>
        <p class="sin-margen">
          Las visitas guiadas recorren laboratorios y talleres con estudiantes de cada
          carrera. El cupo es limitado y el registro, gratuito.
        </p>
      </div>
      <a class="boton boton--primario" href="<?= RUTA_BASE ?>/modulos/visitas/">Agendar visita</a>
    </div>
  </div>
</section>

<?php
pie();
