<?php
/** Módulo ASE · Alta y edición de preguntas frecuentes. */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('coordinacion');

require_once __DIR__ . '/../includes/admin.php';

$esPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($esPost) {
    ase_verificar_token();
}

$id = entero(ase_entrada($_GET, 'id'));
$mensajeId = entero(ase_entrada($_GET, 'mensaje'));
if ((isset($_GET['id']) && $id === null) || (isset($_GET['mensaje']) && $mensajeId === null)
    || ($id !== null && $mensajeId !== null)) {
    no_encontrado('La dirección de la pregunta frecuente no es válida.');
}

$registro = $id !== null ? ase_faq_obtener($id) : null;
if ($id !== null && $registro === null) {
    no_encontrado('La pregunta frecuente no existe.');
}

$origen = $mensajeId !== null ? ase_mensaje_obtener($mensajeId) : null;
if ($mensajeId !== null && ($origen === null || !(bool)$origen['es_orientador'])) {
    no_encontrado('La respuesta del orientador no existe.');
}
if ($origen !== null && $origen['publicado_faq_id'] !== null) {
    mensaje('info', 'Esta respuesta ya está vinculada a una pregunta frecuente.');
    redirigir('faq_form.php?id=' . (int)$origen['publicado_faq_id']);
}

$categorias = array_column(ase_categorias_listar(), null, 'id');
$carrerasDisponibles = array_column(carreras(), null, 'id');
$carreraGuardada = $registro['carrera_id'] ?? null;
if ($carreraGuardada !== null && !isset($carrerasDisponibles[$carreraGuardada])) {
    $carrerasDisponibles[$carreraGuardada] = [
        'id' => $carreraGuardada,
        'nombre' => $registro['carrera_nombre'] . ' (inactiva)',
    ];
}

$carreraInicial = $carreraGuardada ?? $origen['carrera_id'] ?? null;
$datos = [
    'categoria_id' => (string)($registro['categoria_id'] ?? $origen['categoria_id'] ?? ''),
    'carrera_id' => isset($carrerasDisponibles[$carreraInicial]) ? (string)$carreraInicial : '',
    'pregunta' => $registro['pregunta'] ?? $origen['asunto'] ?? '',
    'respuesta' => $registro['respuesta'] ?? $origen['cuerpo'] ?? '',
    'orden' => (string)($registro['orden'] ?? 1),
    'visible' => (string)($registro['visible'] ?? 0),
];
$errores = [];

if ($esPost) {
    foreach (array_keys($datos) as $campo) {
        $datos[$campo] = ase_entrada($_POST, $campo);
    }

    $categoriaId = entero($datos['categoria_id']);
    $carreraId = entero($datos['carrera_id']);
    $orden = ase_valor_orden($datos['orden']);

    if ($categoriaId === null || !isset($categorias[$categoriaId])) {
        $errores['categoria_id'] = 'Selecciona una categoría válida.';
    }
    if (!is_string($_POST['carrera_id'] ?? '')
        || ($datos['carrera_id'] !== '' && ($carreraId === null || !isset($carrerasDisponibles[$carreraId])))) {
        $errores['carrera_id'] = 'Selecciona una carrera válida o la opción General.';
    }
    if (!ase_texto_valido($datos['pregunta'], 255)) {
        $errores['pregunta'] = 'Escribe una pregunta de hasta 255 caracteres.';
    }
    if ($datos['respuesta'] === '' || strlen($datos['respuesta']) > 65535 || !mb_check_encoding($datos['respuesta'], 'UTF-8')) {
        $errores['respuesta'] = 'Escribe una respuesta válida de hasta 65 535 bytes.';
    }
    if ($orden === null) {
        $errores['orden'] = 'Ingresa un número entero entre 0 y 255.';
    }
    if (!in_array($datos['visible'], ['0', '1'], true)) {
        $errores['visible'] = 'Selecciona una opción de visibilidad válida.';
    }

    if (!$errores) {
        $datosGuardar = [
            'categoria_id' => $categoriaId,
            'carrera_id' => $carreraId,
            'pregunta' => $datos['pregunta'],
            'respuesta' => $datos['respuesta'],
            'orden' => $orden,
            'visible' => (int)$datos['visible'],
        ];
        $usuarioId = (int)usuario()['id'];

        try {
            if ($id !== null) {
                ase_faq_actualizar($id, $datosGuardar);
                mensaje('exito', 'Se guardaron los cambios en la pregunta frecuente.');
            } elseif ($mensajeId !== null) {
                ase_publicar_mensaje_como_faq($mensajeId, $datosGuardar, $usuarioId);
                mensaje('exito', 'Se creó la pregunta frecuente a partir de la respuesta.');
            } else {
                ase_faq_crear($datosGuardar, $usuarioId);
                mensaje('exito', 'Se creó la pregunta frecuente.');
            }
            redirigir('faq.php');
        } catch (DomainException) {
            $errores['general'] = 'La respuesta ya no está disponible para crear una pregunta frecuente.';
        } catch (PDOException $excepcion) {
            error_log('ASE · No se pudo guardar la pregunta frecuente: ' . $excepcion->getMessage());
            $errores['general'] = 'No se pudo guardar la pregunta frecuente. Intenta de nuevo.';
        }
    }
}

$titulo = match (true) {
    $registro !== null => 'Editar pregunta frecuente',
    $origen !== null => 'Crear pregunta frecuente a partir de una respuesta',
    default => 'Nueva pregunta frecuente',
};
$rutaFormulario = 'faq_form.php';
if ($id !== null) {
    $rutaFormulario .= '?id=' . $id;
} elseif ($mensajeId !== null) {
    $rutaFormulario .= '?mensaje=' . $mensajeId;
}

cabecera($titulo . ' · Asesoría', 'salud', '../css/modulo.css');
?>

<section class="contenedor seccion ase-admin">
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= e(RUTA_BASE) ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Asesoría a aspirantes</a></li>
      <li><a href="faq.php">Preguntas frecuentes</a></li>
      <li aria-current="page"><?= e($titulo) ?></li>
    </ol>
  </nav>

  <h1><?= e($titulo) ?></h1>
  <?php ase_admin_navegacion('faq'); ?>
  <?php pintar_mensajes(); ?>

  <?php if ($origen !== null): ?>
    <div class="alerta alerta--info mb-6">
      <p>Adapta la pregunta y la respuesta para que sirvan a cualquier aspirante. Quita nombres, datos de contacto y detalles personales antes de hacerlas públicas.</p>
    </div>
  <?php endif; ?>

  <?php if ($errores): ?>
    <div class="alerta alerta--error" role="alert">
      <p><?= e($errores['general'] ?? 'Revisa los campos señalados.') ?></p>
    </div>
  <?php endif; ?>

  <?php if (!$categorias): ?>
    <div class="vacio">
      <h2>Falta una categoría</h2>
      <p>Crea una categoría para poder guardar preguntas frecuentes.</p>
      <a class="boton boton--primario" href="categorias.php">Administrar categorías</a>
    </div>
  <?php else: ?>
    <form class="panel formulario" method="POST" action="<?= e($rutaFormulario) ?>">
      <?= campo_token() ?>

      <div class="campo <?= isset($errores['pregunta']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="pregunta">Pregunta</label>
        <input class="campo__control" type="text" id="pregunta" name="pregunta" maxlength="255" required
               value="<?= e($datos['pregunta']) ?>" <?php if (isset($errores['pregunta'])): ?>aria-invalid="true" aria-describedby="pregunta-error"<?php endif; ?>>
        <?php if (isset($errores['pregunta'])): ?>
          <small class="campo__error" id="pregunta-error"><?= e($errores['pregunta']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['respuesta']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="respuesta">Respuesta</label>
        <textarea class="campo__control" id="respuesta" name="respuesta" rows="8" maxlength="65535" required
                  <?php if (isset($errores['respuesta'])): ?>aria-invalid="true" aria-describedby="respuesta-error"<?php endif; ?>><?= e($datos['respuesta']) ?></textarea>
        <?php if (isset($errores['respuesta'])): ?>
          <small class="campo__error" id="respuesta-error"><?= e($errores['respuesta']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['categoria_id']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="categoria_id">Categoría</label>
        <select class="campo__control" id="categoria_id" name="categoria_id" required
                <?php if (isset($errores['categoria_id'])): ?>aria-invalid="true" aria-describedby="categoria-error"<?php endif; ?>>
          <option value="">Selecciona una categoría</option>
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?= e((string)$categoria['id']) ?>" <?= (int)$datos['categoria_id'] === (int)$categoria['id'] ? 'selected' : '' ?>>
              <?= e($categoria['nombre']) ?><?= $categoria['activa'] ? '' : ' (inactiva)' ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['categoria_id'])): ?>
          <small class="campo__error" id="categoria-error"><?= e($errores['categoria_id']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['carrera_id']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="carrera_id">Carrera</label>
        <select class="campo__control" id="carrera_id" name="carrera_id"
                <?php if (isset($errores['carrera_id'])): ?>aria-invalid="true" aria-describedby="carrera-error"<?php endif; ?>>
          <option value="">General · Todas las carreras</option>
          <?php foreach ($carrerasDisponibles as $carrera): ?>
            <option value="<?= e((string)$carrera['id']) ?>" <?= (int)$datos['carrera_id'] === (int)$carrera['id'] ? 'selected' : '' ?>>
              <?= e($carrera['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errores['carrera_id'])): ?>
          <small class="campo__error" id="carrera-error"><?= e($errores['carrera_id']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['orden']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="orden">Orden en la categoría</label>
        <input class="campo__control" type="number" id="orden" name="orden" min="0" max="255" step="1" required
               value="<?= e($datos['orden']) ?>" <?php if (isset($errores['orden'])): ?>aria-invalid="true" aria-describedby="orden-error"<?php endif; ?>>
        <?php if (isset($errores['orden'])): ?>
          <small class="campo__error" id="orden-error"><?= e($errores['orden']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['visible']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="visible">Visibilidad</label>
        <select class="campo__control" id="visible" name="visible" required
                <?php if (isset($errores['visible'])): ?>aria-invalid="true" aria-describedby="visible-error visible-ayuda"<?php else: ?>aria-describedby="visible-ayuda"<?php endif; ?>>
          <option value="0" <?= $datos['visible'] === '0' ? 'selected' : '' ?>>Oculta al público</option>
          <option value="1" <?= $datos['visible'] === '1' ? 'selected' : '' ?>>Visible al público</option>
        </select>
        <small class="campo__ayuda" id="visible-ayuda">Solo aparecen en la vista pública las preguntas visibles de categorías activas.</small>
        <?php if (isset($errores['visible'])): ?>
          <small class="campo__error" id="visible-error"><?= e($errores['visible']) ?></small>
        <?php endif; ?>
      </div>

      <div class="fila">
        <button class="boton boton--primario" type="submit"><?= $registro === null ? 'Guardar pregunta' : 'Guardar cambios' ?></button>
        <a class="boton boton--secundario" href="faq.php">Cancelar</a>
      </div>
    </form>
  <?php endif; ?>
</section>

<?php pie(); ?>
