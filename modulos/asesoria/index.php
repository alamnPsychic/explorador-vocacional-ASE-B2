<?php
/**
 * MÓDULO ASE · Asesoría a aspirantes (Equipo B-2) · Pantalla pública principal
 * Explorador Vocacional UTBB
 *
 * Incluye:
 *  1. Preguntas frecuentes (FAQ) por categoría y carrera con buscador.
 *  2. Formulario para enviar una nueva consulta (con adjunto opcional).
 *  3. Listado de conversaciones propias del aspirante en sesión.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
require_once __DIR__ . '/includes/consultas.php';
require_once __DIR__ . '/includes/entrada.php';

iniciar_sesion();

// --- Filtros de FAQ (GET) --------------------------------------------------
$busqueda    = ase_entrada($_GET, 'q');
$busqueda    = ase_texto_valido($busqueda, 255) ? $busqueda : '';
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$carreraId   = entero(ase_entrada($_GET, 'carrera'));

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

    // Validación en el servidor
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

$categorias = ase_categorias_listar(true);
$faqs       = ase_faq_listar($busqueda, $categoriaId, $carreraId, true);

// Si hay sesión iniciada, cargamos las conversaciones propias del usuario
$misConversaciones = [];
if (rol() === 'aspirante') {
    $listadoPropio     = ase_conversaciones_listar('', '', null, null, (int)usuario()['id'], 1);
    $misConversaciones = $listadoPropio['filas'];
}

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
       1. PREGUNTAS FRECUENTES CON BUSCADOR Y FILTROS
       ===================================================================== -->
  <div class="fila fila--separada mb-4">
    <h2 class="sin-margen">Preguntas frecuentes (FAQ)</h2>
    <?php if (!hay_sesion() || rol() === 'aspirante'): ?>
      <a class="boton boton--primario" href="#nueva-consulta">Hacer una consulta personal</a>
    <?php endif; ?>
  </div>

  <div class="disposicion-filtros">

    <!-- Panel lateral de filtros -->
    <form class="panel" method="GET" action="">
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
      <?php if ($busqueda !== '' || $categoriaId !== null || $carreraId !== null): ?>
        <p class="centrado mt-4 sin-margen"><a href="?">Ver todas las preguntas</a></p>
      <?php endif; ?>
    </form>

    <!-- Listado de preguntas frecuentes -->
    <div>
      <p class="leyenda">
        <?= count($faqs) ?> pregunta<?= count($faqs) === 1 ? '' : 's' ?> frecuente<?= count($faqs) === 1 ? '' : 's' ?>
      </p>

      <?php if (!$faqs): ?>
        <div class="vacio">
          <h3>No encontramos preguntas frecuentes con esos filtros</h3>
          <p class="ancho-lectura">Prueba con otros términos o envíanos directamente tu pregunta en el formulario inferior.</p>
          <a class="boton boton--secundario" href="?">Quitar filtros</a>
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
      <?php endif; ?>
    </div>

  </div>

  <?php if (!hay_sesion() || rol() === 'aspirante'): ?>
  <hr class="mt-6 mb-6">
  <!-- =====================================================================
       2. SEGUIMIENTO DE MIS CONVERSACIONES
       ===================================================================== -->
  <h2 id="mis-consultas">Mis conversaciones de asesoría</h2>

  <?php if (!hay_sesion()): ?>
    <div class="panel mb-6">
      <p class="sin-margen">
        Para dar seguimiento a tus consultas o enviar una duda personalizada a un orientador,
        <a href="<?= RUTA_BASE ?>/auth/entrar.php"><strong>inicia sesión con tu cuenta de aspirante</strong></a>.
      </p>
    </div>
  <?php else: ?>
    <?php if (!$misConversaciones): ?>
      <div class="vacio mb-6">
        <h3>Aún no tienes conversaciones registradas</h3>
        <p class="ancho-lectura">Usa el formulario de abajo para enviar tu primera consulta al equipo de orientación.</p>
      </div>
    <?php else: ?>
      <div class="tabla-envoltura mb-6">
        <table class="tabla">
          <caption>Historial de tus consultas</caption>
          <thead>
            <tr>
              <th scope="col">Asunto</th>
              <th scope="col">Categoría</th>
              <th scope="col">Carrera</th>
              <th scope="col">Estado</th>
              <th scope="col">Última actividad</th>
              <th scope="col">Acción</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($misConversaciones as $cv): ?>
              <tr>
                <td><strong><?= e($cv['asunto']) ?></strong></td>
                <td><?= e($cv['categoria_nombre']) ?></td>
                <td><?= e($cv['carrera_nombre'] ?? 'General') ?></td>
                <td>
                  <span class="<?= e(ase_clase_estado($cv['estado'])) ?>">
                    <?= e(ASE_ESTADOS[$cv['estado']]) ?>
                  </span>
                </td>
                <td><?= e(date('d/m/Y H:i', strtotime($cv['actualizado_en']))) ?></td>
                <td>
                  <a class="boton boton--secundario boton--pequeno"
                     href="conversacion.php?id=<?= (int)$cv['id'] ?>">
                    Ver seguimiento (<?= (int)$cv['total_mensajes'] ?>)
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>

  <!-- =====================================================================
       3. FORMULARIO DE NUEVA CONSULTA
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
