<?php
/**
 * MÓDULO ASE · Asesoría a aspirantes (Equipo B-2) · Pantalla pública principal (FAQ)
 * Explorador Vocacional UTBB
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';
require_once __DIR__ . '/includes/entrada.php';

iniciar_sesion();

// --- Filtros y paginación de FAQ (GET) -------------------------------------
$busqueda    = ase_entrada($_GET, 'q');$busqueda    = ase_texto_valido($busqueda, 255) ?$busqueda : '';
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$carreraId   = entero(ase_entrada($_GET, 'carrera'));
$pagina      = max(1, entero(ase_entrada($_GET, 'pagina')) ?? 1);

$categorias   = ase_categorias_listar(true);$resultadoFaq = ase_faq_paginar($busqueda,$categoriaId, $carreraId,$pagina);
$faqs         =$resultadoFaq['filas'];

$totalMisConsultas = 0;
if (rol() === 'aspirante') {
    $conteoPropio      = ase_conversaciones_listar('', '', null, null, (int)usuario()['id'], 1);
    $totalMisConsultas =$conteoPropio['total'];
}

$filtrosFaq = array_filter([
    'q'         => $busqueda,
    'categoria' => $categoriaId,
    'carrera'   => $carreraId,
], static fn($v): bool => $v !== '' &&$v !== null);

cabecera('Asesoría a aspirantes', '', 'css/modulo.css');
?>

<section class="portada">
  <div class="contenedor">
    <h1>Asesoría a aspirantes</h1>
    <p class="entradilla">
      Resuelve tus dudas sobre admisión, becas, planes de estudio y carreras como Médico Cirujano.
      Consulta las preguntas frecuentes o inicia una conversación con orientación vocacional.
    </p>
  </div>
</section>

<section class="contenedor seccion ase-publica">

  <?php pintar_mensajes(); ?>

  <?php if (rol() === 'editor' || rol() === 'coordinacion'): ?>
    <div class="alerta alerta--info mb-6">
      <div class="fila fila--separada">
        <span>Estás conectado como personal de orientación (<?= e(rol()) ?>).</span>
        <a class="boton boton--primario boton--pequeno" href="admin/index.php">Ir a la bandeja del orientador</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- =====================================================================
       PREGUNTAS FRECUENTES CON BUSCADOR, FILTROS AND Y PAGINACIÓN
       ===================================================================== -->
  <div class="fila fila--separada mb-4">
    <h2 class="sin-margen">Preguntas frecuentes (FAQ)</h2>
    <?php if (!hay_sesion() || rol() === 'aspirante'): ?>
      <div class="fila">
        <a class="boton boton--secundario" href="mis-consultas.php">
          Mis conversaciones<?= rol() === 'aspirante' ? ' (' . (int)$totalMisConsultas . ')' : '' ?>
        </a>
        <a class="boton boton--primario" href="mis-consultas.php#nueva-consulta">Hacer una consulta personal</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="disposicion-filtros">

    <!-- Panel lateral de filtros -->
    <form class="panel" method="GET" action="index.php">
      <h3>Filtrar dudas</h3>

      <div class="campo">
        <label class="campo__etiqueta" for="q">Buscar palabra clave</label>
        <input class="campo__control" type="search" id="q" name="q"
               value="<?= e($busqueda) ?>" placeholder="Ej. ficha, internado, beca...">
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="categoria">Categoría</label>
        <select class="campo__control" id="categoria" name="categoria">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as$cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= $categoriaId === (int)$cat['id'] ? 'selected' : '' ?>>
              <?= e($cat['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="carrera">Carrera</label>
        <select class="campo__control" id="carrera" name="carrera">
          <option value="">Todas las carreras</option>
          <?php foreach (carreras() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= $carreraId === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--primario boton--bloque" type="submit">Buscar</button>
      <?php if ($filtrosFaq): ?>
        <p class="centrado mt-4 sin-margen"><a href="index.php">Ver todas las preguntas</a></p>
      <?php endif; ?>
    </form>

    <!-- Listado paginado de preguntas frecuentes -->
    <div>
      <p class="leyenda">
        <?= (int)$resultadoFaq['total'] ?> pregunta<?= $resultadoFaq['total'] === 1 ? '' : 's' ?> frecuente<?=$resultadoFaq['total'] === 1 ? '' : 's' ?>
      </p>

      <?php if (!$faqs): ?>
        <div class="vacio">
          <h3>No encontramos preguntas frecuentes con esos filtros</h3>
          <p class="ancho-lectura">Prueba con otros términos o envía tu pregunta directamente a un orientador.</p>
          <div class="fila">
            <a class="boton boton--secundario" href="index.php">Quitar filtros</a>
            <?php if (!hay_sesion() || rol() === 'aspirante'): ?>
              <a class="boton boton--primario" href="mis-consultas.php#nueva-consulta">Nueva consulta</a>
            <?php endif; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="ase-lista-faq">
          <?php foreach ($faqs as$f): ?>
            <article class="panel mb-4">
              <div class="fila fila--separada mb-2">
                <span class="insignia insignia--publicado"><?= e($f['categoria_nombre']) ?></span>
                <span class="leyenda sin-margen">
                  <?= $f['carrera_nombre'] ? e($f['carrera_nombre']) : 'General UTBB' ?>
                </span>
              </div>
              <h3 class="mt-2 mb-2"><?= e($f['pregunta']) ?></h3>
              <p class="sin-margen"><?= nl2br(e($f['respuesta'])) ?></p>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if ($resultadoFaq['paginas'] > 1): ?>
          <?php $baseFaq = http_build_query($filtrosFaq); ?>
          <nav class="mt-4" aria-label="Paginación de preguntas frecuentes">
            <ul class="paginacion">
              <?php if ($resultadoFaq['pagina'] > 1): ?>
                <li>
                  <a href="index.php?<?= e($baseFaq ? $baseFaq . '&' : '') ?>pagina=<?= (int)($resultadoFaq['pagina'] - 1) ?>">Anterior</a>
                </li>
              <?php endif; ?>
              <?php for ($p = 1; $p <= $resultadoFaq['paginas'];$p++): ?>
                <li>
                  <?php if ($p ===$resultadoFaq['pagina']): ?>
                    <span aria-current="page"><?= $p ?></span>
                  <?php else: ?>
                    <a href="index.php?<?= e($baseFaq ? $baseFaq . '&' : '') ?>pagina=<?= $p ?>"><?= $p ?></a>
                  <?php endif; ?>
                </li>
              <?php endfor; ?>
              <?php if ($resultadoFaq['pagina'] <$resultadoFaq['paginas']): ?>
                <li>
                  <a href="index.php?<?= e($baseFaq ? $baseFaq . '&' : '') ?>pagina=<?= (int)($resultadoFaq['pagina'] + 1) ?>">Siguiente</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>

  </div>

</section>

<?php
pie();