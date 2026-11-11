<?php
/**
 * MÓDULO PLANTILLA · Pantalla pública
 * Explorador Vocacional UTBB
 *
 * Pantalla de listado: búsqueda, filtro, rejilla de tarjetas, estado vacío
 * y paginación. Es el esqueleto de casi todas las pantallas públicas de los
 * diez módulos.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';

// --- Entrada ---------------------------------------------------------------
$busqueda  = get('q');
$carreraId = entero(get('carrera'));
$pagina    = entero(get('pagina')) ?? 1;

// Al público solo se le muestra lo publicado. El estado NO se toma de la
// dirección: lo fija el servidor.
$resultado = plt_listar($busqueda, 'publicado', $carreraId, $pagina);

cabecera('Módulo plantilla', '', 'css/modulo.css');
?>

<section class="portada">
  <div class="contenedor">
    <h1>Módulo plantilla</h1>
    <p class="entradilla">Sustituye este texto por la descripción de tu módulo.</p>
  </div>
</section>

<section class="contenedor seccion">

  <?php pintar_mensajes(); ?>

  <div class="disposicion-filtros">

    <!-- Filtros -->
    <form class="panel" method="GET" action="">
      <h2>Filtrar</h2>

      <div class="campo">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q"
               value="<?= e($busqueda) ?>" placeholder="Palabra clave">
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="carrera">Carrera</label>
        <select class="campo__control" id="carrera" name="carrera">
          <option value="">Todas</option>
          <?php foreach (carreras() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $carreraId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--primario boton--bloque" type="submit">Aplicar</button>
      <?php if ($busqueda !== '' || $carreraId !== null): ?>
        <p class="centrado mt-4 sin-margen"><a href="?">Quitar filtros</a></p>
      <?php endif; ?>
    </form>

    <!-- Resultados -->
    <div>
      <p class="leyenda">
        <?= $resultado['total'] ?> resultado<?= $resultado['total'] === 1 ? '' : 's' ?>
      </p>

      <?php if (!$resultado['filas']): ?>

        <div class="vacio">
          <h3>No hay resultados con esos filtros</h3>
          <p class="ancho-lectura">Prueba con otra palabra o quita los filtros para ver todo el contenido publicado.</p>
          <a class="boton boton--secundario" href="?">Quitar filtros</a>
        </div>

      <?php else: ?>

        <div class="rejilla">
          <?php foreach ($resultado['filas'] as $f): ?>
            <a class="tarjeta tarjeta--area <?= e(clase_area($f['area'])) ?>"
               href="ficha.php?id=<?= (int)$f['id'] ?>">

              <?php if ($f['imagen']): ?>
                <img class="tarjeta__medio"
                     src="uploads/<?= e($f['imagen']) ?>"
                     alt="Imagen de <?= e($f['titulo']) ?>">
              <?php endif; ?>

              <div class="tarjeta__cuerpo">
                <h3 class="tarjeta__titulo"><?= e($f['titulo']) ?></h3>
                <p class="leyenda"><?= e($f['carrera']) ?></p>
                <p><?= e($f['resumen']) ?></p>
              </div>

              <div class="tarjeta__pie">
                <span class="<?= e(plt_clase_estado($f['estado'])) ?>">
                  <?= e(PLT_ESTADOS[$f['estado']]) ?>
                </span>
                <span>Ver detalle</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>

        <?php if ($resultado['paginas'] > 1): ?>
          <nav aria-label="Paginación">
            <ul class="paginacion">
              <?php
              // Se conservan los filtros al cambiar de página.
              $base = http_build_query(array_filter([
                  'q'       => $busqueda,
                  'carrera' => $carreraId,
              ]));
              for ($p = 1; $p <= $resultado['paginas']; $p++):
              ?>
                <li>
                  <?php if ($p === $resultado['pagina']): ?>
                    <span aria-current="page"><?= $p ?></span>
                  <?php else: ?>
                    <a href="?<?= $base ? $base . '&amp;' : '' ?>pagina=<?= $p ?>"><?= $p ?></a>
                  <?php endif; ?>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>

      <?php endif; ?>
    </div>

  </div>
</section>

<?php
pie();
