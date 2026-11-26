<?php
/**
 * MÓDULO ASE · Asesoría a aspirantes (Equipo B-2) · Pantalla pública principal
 * Explorador Vocacional UTBB
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';
require_once __DIR__ . '/includes/entrada.php';

iniciar_sesion();

// --- Filtros y paginación de FAQ (GET) -------------------------------------
$busqueda    = ase_entrada($_GET, 'q');
$busqueda    = ase_texto_valido($busqueda, 255) ? $busqueda : '';
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$carreraId   = entero(ase_entrada($_GET, 'carrera'));
$pagina      = max(1, entero(ase_entrada($_GET, 'pagina')) ?? 1);

// --- Formulario de Nueva Consulta (POST) -----------------------------------
$datosConsulta = [
    'categoria_id' => '',
    'carrera_id'   => '',
    'asunto'       => '',
    'cuerpo'       => '',
];
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requerir_rol('aspirante');
    ase_verificar_token();

    $carreraEntrada = ase_entrada($_POST, 'carrera_id');
    $datosConsulta['categoria_id'] = entero(ase_entrada($_POST, 'categoria_id'));
    $datosConsulta['carrera_id']   = entero($carreraEntrada);
    $datosConsulta['asunto']       = ase_entrada($_POST, 'asunto');
    $datosConsulta['cuerpo']       = ase_entrada($_POST, 'cuerpo');

    $categoria = $datosConsulta['categoria_id'] !== null ? ase_categoria_obtener($datosConsulta['categoria_id']) : null;
    if (!(bool)($categoria['activa'] ?? false)) {
        $errores['categoria_id'] = 'Selecciona una categoría válida.';
    }

    if ((isset($_POST['carrera_id']) && !is_string($_POST['carrera_id']))
        || ($carreraEntrada !== '' && ($datosConsulta['carrera_id'] === null || carrera($datosConsulta['carrera_id']) === null))) {
        $errores['carrera_id'] = 'La carrera seleccionada no es válida.';
    }

    if (!ase_texto_valido($datosConsulta['asunto'], 200)) {
        $errores['asunto'] = 'Escribe un asunto de hasta 200 caracteres.';
    }

    if (!ase_texto_valido($datosConsulta['cuerpo'], ASE_MAX_CUERPO)) {
        $errores['cuerpo'] = 'Describe tu duda en un mensaje de hasta 16 000 caracteres.';
    }

    $archivoGuardado = null;
    if (!$errores) {
        try {
            $archivoGuardado = ase_procesar_adjunto_subido($_FILES['adjunto'] ?? null);
        } catch (RuntimeException $e) {
            $errores['adjunto'] = $e->getMessage();
        }
    }

    if (!$errores) {
        try {
            $nuevaId = ase_conversacion_crear($datosConsulta, (int)usuario()['id'], $archivoGuardado);
            mensaje('exito', 'Tu consulta fue enviada. Un orientador te responderá pronto.');
            redirigir('conversacion.php?id=' . $nuevaId);
        } catch (PDOException $e) {
            error_log('ASE: error al crear una consulta: ' . $e->getMessage());
            $errores['envio'] = 'No se pudo guardar tu consulta. Intenta de nuevo.';
        }
    }
}

$categorias   = ase_categorias_listar(true);
$resultadoFaq = ase_faq_paginar($busqueda, $categoriaId, $carreraId, $pagina);
$faqs         = $resultadoFaq['filas'];

$totalMisConsultas = 0;
if (rol() === 'aspirante') {
    $conteoPropio      = ase_conversaciones_listar('', '', null, null, (int)usuario()['id'], 1);
    $totalMisConsultas = $conteoPropio['total'];
}

$filtrosFaq = array_filter([
    'q'         => $busqueda,
    'categoria' => $categoriaId,
    'carrera'   => $carreraId,
], static fn($v): bool => $v !== '' && $v !== null);

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
       1. PREGUNTAS FRECUENTES CON BUSCADOR, FILTROS AND Y PAGINACIÓN
       ===================================================================== -->
  <div class="fila fila--separada mb-4">
    <h2 class="sin-margen">Preguntas frecuentes (FAQ)</h2>
    <?php if (!hay_sesion() || rol() === 'aspirante'): ?>
      <div class="fila">
        <a class="boton boton--secundario" href="mis-consultas.php">
          Mis conversaciones<?= rol() === 'aspirante' ? ' (' . (int)$totalMisConsultas . ')' : '' ?>
        </a>
        <a class="boton boton--primario" href="#nueva-consulta">Hacer una consulta personal</a>
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
          <?php foreach ($categorias as $cat): ?>
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
        <?= (int)$resultadoFaq['total'] ?> pregunta<?= $resultadoFaq['total'] === 1 ? '' : 's' ?> frecuente<?= $resultadoFaq['total'] === 1 ? '' : 's' ?>
      </p>

      <?php if (!$faqs): ?>
        <div class="vacio">
          <h3>No encontramos preguntas frecuentes con esos filtros</h3>
          <p class="ancho-lectura">Prueba con otros términos o envíanos directamente tu pregunta en el formulario inferior.</p>
          <a class="boton boton--secundario" href="index.php">Quitar filtros</a>
        </div>
      <?php else: ?>
        <div class="ase-lista-faq">
          <?php foreach ($faqs as $f): ?>
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
              <?php for ($p = 1; $p <= $resultadoFaq['paginas']; $p++): ?>
                <li>
                  <?php if ($p === $resultadoFaq['pagina']): ?>
                    <span aria-current="page"><?= $p ?></span>
                  <?php else: ?>
                    <a href="index.php?<?= e($baseFaq ? $baseFaq . '&' : '') ?>pagina=<?= $p ?>"><?= $p ?></a>
                  <?php endif; ?>
                </li>
              <?php endfor; ?>
              <?php if ($resultadoFaq['pagina'] < $resultadoFaq['paginas']): ?>
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

  <?php if (!hay_sesion() || rol() === 'aspirante'): ?>
  <hr class="mt-6 mb-6">

  <!-- =====================================================================
       2. FORMULARIO DE NUEVA CONSULTA
       ===================================================================== -->
  <div id="nueva-consulta" class="mt-6">
    <h2>Nueva consulta a orientación</h2>

    <?php if (!hay_sesion()): ?>
      <div class="alerta alerta--info">
        <p class="sin-margen">
          Debes iniciar sesión para abrir una consulta.
          <a href="<?= RUTA_BASE ?>/auth/entrar.php">Haz clic aquí para entrar</a> (cuenta de prueba: <code>aspirante@ejemplo.mx</code> / <code>Demo1234</code>).
        </p>
      </div>
    <?php else: ?>

      <?php if ($errores): ?>
        <div class="alerta alerta--error" role="alert">
          <p><?= e($errores['envio'] ?? 'Revisa los campos señalados en tu consulta.') ?></p>
        </div>
      <?php endif; ?>

      <form class="formulario" method="POST" action="#nueva-consulta" enctype="multipart/form-data">
        <?= campo_token() ?>

        <fieldset class="grupo-campos">
          <legend class="grupo-campos__titulo">Escribe tu duda</legend>

          <div class="campo <?= isset($errores['categoria_id']) ? 'campo--invalido' : '' ?>">
            <label class="campo__etiqueta" for="categoria_id">
              Categoría del tema <span class="campo__requerido" aria-hidden="true">*</span>
            </label>
            <select class="campo__control" id="categoria_id" name="categoria_id" required>
              <option value="">Selecciona una categoría</option>
              <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= (int)$datosConsulta['categoria_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errores['categoria_id'])): ?>
              <small class="campo__error"><?= e($errores['categoria_id']) ?></small>
            <?php endif; ?>
          </div>

          <div class="campo <?= isset($errores['carrera_id']) ? 'campo--invalido' : '' ?>">
            <label class="campo__etiqueta" for="carrera_id">Carrera de interés (opcional)</label>
            <select class="campo__control" id="carrera_id" name="carrera_id">
              <option value="">Duda general de la universidad</option>
              <?php foreach (carreras() as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$datosConsulta['carrera_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                  <?= e($c['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errores['carrera_id'])): ?>
              <small class="campo__error"><?= e($errores['carrera_id']) ?></small>
            <?php endif; ?>
          </div>

          <div class="campo <?= isset($errores['asunto']) ? 'campo--invalido' : '' ?>">
            <label class="campo__etiqueta" for="asunto">
              Asunto <span class="campo__requerido" aria-hidden="true">*</span>
            </label>
            <input class="campo__control" type="text" id="asunto" name="asunto"
                   value="<?= e($datosConsulta['asunto']) ?>" maxlength="200"
                   placeholder="Ej. Requisito de certificado para Médico Cirujano" required>
            <?php if (isset($errores['asunto'])): ?>
              <small class="campo__error"><?= e($errores['asunto']) ?></small>
            <?php endif; ?>
          </div>

          <div class="campo <?= isset($errores['cuerpo']) ? 'campo--invalido' : '' ?>">
            <label class="campo__etiqueta" for="cuerpo">
              Mensaje detallado <span class="campo__requerido" aria-hidden="true">*</span>
            </label>
            <textarea class="campo__control" id="cuerpo" name="cuerpo" rows="5" maxlength="<?= ASE_MAX_CUERPO ?>" required
                      placeholder="Explica tu duda con detalle para que el orientador pueda ayudarte..."><?= e($datosConsulta['cuerpo']) ?></textarea>
            <?php if (isset($errores['cuerpo'])): ?>
              <small class="campo__error"><?= e($errores['cuerpo']) ?></small>
            <?php endif; ?>
          </div>

          <div class="campo <?= isset($errores['adjunto']) ? 'campo--invalido' : '' ?>">
            <label class="campo__etiqueta" for="adjunto">Archivo adjunto (opcional)</label>
            <input class="campo__control" type="file" id="adjunto" name="adjunto"
                   accept="application/pdf,image/jpeg,image/png,image/webp">
            <small class="campo__ayuda">Formatos aceptados: PDF, JPG, PNG o WebP. Máximo 5 MB.</small>
            <?php if (isset($errores['adjunto'])): ?>
              <small class="campo__error"><?= e($errores['adjunto']) ?></small>
            <?php endif; ?>
          </div>
        </fieldset>

        <button class="boton boton--primario" type="submit">Enviar consulta</button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</section>

<?php
pie();