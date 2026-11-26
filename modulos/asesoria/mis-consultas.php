<?php
/**
 * MÓDULO ASE · Bandeja de conversaciones y creación de consultas del aspirante
 * Explorador Vocacional UTBB
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
requerir_rol('aspirante');

require_once __DIR__ . '/includes/consultas.php';
require_once __DIR__ . '/includes/entrada.php';

// --- Formulario de Nueva Consulta (POST) -----------------------------------
$datosConsulta = [
    'categoria_id' => '',
    'carrera_id'   => '',
    'asunto'       => '',
    'cuerpo'       => '',
];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

// --- Filtros y listado de conversaciones del aspirante (GET) ---------------
$busqueda = ase_entrada($_GET, 'q');
$busqueda = ase_texto_valido($busqueda, 200) ? $busqueda : '';
$estado   = ase_entrada($_GET, 'estado');
if (!isset(ASE_ESTADOS[$estado])) {
    $estado = '';
}
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$pagina      = max(1, entero(ase_entrada($_GET, 'pagina')) ?? 1);

$categorias        = ase_categorias_listar();
$categoriasActivas = ase_categorias_listar(true);
$resultado         = ase_conversaciones_listar(
    $busqueda,
    $estado,
    $categoriaId,
    null,
    (int)usuario()['id'],
    $pagina
);
$misConversaciones = $resultado['filas'];

$filtros = array_filter([
    'q'         => $busqueda,
    'estado'    => $estado,
    'categoria' => $categoriaId,
], static fn($v): bool => $v !== '' && $v !== null);

cabecera('Mis conversaciones · Asesoría', '', 'css/modulo.css');
?>

<section class="contenedor seccion ase-publica">
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= e(RUTA_BASE) ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Asesoría a aspirantes</a></li>
      <li aria-current="page">Mis conversaciones</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-4">
    <h1 class="sin-margen">Mis conversaciones de asesoría</h1>
    <div class="fila">
      <a class="boton boton--secundario" href="index.php">Preguntas frecuentes</a>
      <a class="boton boton--primario" href="#nueva-consulta">Nueva consulta</a>
    </div>
  </div>

  <?php pintar_mensajes(); ?>

  <!-- =====================================================================
       1. BANDEJA DE CONVERSACIONES DEL ASPIRANTE
       ===================================================================== -->
  <form class="panel ase-panel-filtro mb-6" method="GET" action="mis-consultas.php" aria-label="Filtrar mis conversaciones">
    <div class="ase-filtros">
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="q">Buscar por asunto</label>
        <input class="campo__control" type="search" id="q" name="q" maxlength="200"
               placeholder="Ej. Ficha, beca..." value="<?= e($busqueda) ?>">
      </div>
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="estado">Estado</label>
        <select class="campo__control" id="estado" name="estado">
          <option value="">Todas</option>
          <?php foreach (ASE_ESTADOS as $clave => $texto): ?>
            <option value="<?= e($clave) ?>"<?= $estado === $clave ? ' selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="categoria">Categoría</label>
        <select class="campo__control" id="categoria" name="categoria">
          <option value="">Todas</option>
          <?php foreach ($categorias as $cat): ?>
            <option value="<?= e((string)$cat['id']) ?>"<?= $categoriaId === (int)$cat['id'] ? ' selected' : '' ?>>
              <?= e($cat['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="ase-filtros__acciones">
        <button class="boton boton--primario boton--pequeno" type="submit">Filtrar</button>
        <?php if ($filtros): ?>
          <a class="boton boton--fantasma boton--pequeno" href="mis-consultas.php">Limpiar</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <?php if (!$misConversaciones): ?>
    <div class="vacio">
      <h2>No hay conversaciones para mostrar</h2>
      <p class="ancho-lectura">
        <?= $filtros ? 'No encontramos consultas con los filtros aplicados.' : 'Aún no has enviado consultas al equipo de orientación.' ?>
      </p>
      <?php if ($filtros): ?>
        <a class="boton boton--secundario" href="mis-consultas.php">Ver todas</a>
      <?php else: ?>
        <a class="boton boton--primario" href="#nueva-consulta">Enviar mi primera consulta</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <p class="leyenda">
      <?= (int)$resultado['total'] ?> conversación<?= $resultado['total'] === 1 ? '' : 'es' ?>
    </p>

    <div class="tabla-envoltura">
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

    <?php if ($resultado['paginas'] > 1): ?>
      <?php $base = http_build_query($filtros); ?>
      <nav class="mt-6" aria-label="Paginación de mis conversaciones">
        <ul class="paginacion">
          <?php if ($resultado['pagina'] > 1): ?>
            <li>
              <a href="mis-consultas.php?<?= e($base ? $base . '&' : '') ?>pagina=<?= (int)($resultado['pagina'] - 1) ?>">Anterior</a>
            </li>
          <?php endif; ?>
          <?php for ($p = 1; $p <= $resultado['paginas']; $p++): ?>
            <li>
              <?php if ($p === $resultado['pagina']): ?>
                <span aria-current="page"><?= $p ?></span>
              <?php else: ?>
                <a href="mis-consultas.php?<?= e($base ? $base . '&' : '') ?>pagina=<?= $p ?>"><?= $p ?></a>
              <?php endif; ?>
            </li>
          <?php endfor; ?>
          <?php if ($resultado['pagina'] < $resultado['paginas']): ?>
            <li>
              <a href="mis-consultas.php?<?= e($base ? $base . '&' : '') ?>pagina=<?= (int)($resultado['pagina'] + 1) ?>">Siguiente</a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
  <?php endif; ?>

  <hr class="mt-6 mb-6">

  <!-- =====================================================================
       2. FORMULARIO DE NUEVA CONSULTA A ORIENTACIÓN
       ===================================================================== -->
  <div id="nueva-consulta" class="mt-6">
    <h2>Nueva consulta a orientación</h2>

    <?php if ($errores): ?>
      <div class="alerta alerta--error" role="alert">
        <p><?= e($errores['envio'] ?? 'Revisa los campos señalados en tu consulta.') ?></p>
      </div>
    <?php endif; ?>

    <form class="formulario" method="POST" action="mis-consultas.php#nueva-consulta" enctype="multipart/form-data">
      <?= campo_token() ?>

      <fieldset class="grupo-campos">
        <legend class="grupo-campos__titulo">Escribe tu duda</legend>

        <div class="campo <?= isset($errores['categoria_id']) ? 'campo--invalido' : '' ?>">
          <label class="campo__etiqueta" for="categoria_id">
            Categoría del tema <span class="campo__requerido" aria-hidden="true">*</span>
          </label>
          <select class="campo__control" id="categoria_id" name="categoria_id" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach ($categoriasActivas as $cat): ?>
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
  </div>
</section>

<?php pie(); ?>