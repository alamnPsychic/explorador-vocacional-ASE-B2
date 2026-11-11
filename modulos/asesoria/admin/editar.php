<?php
/**
 * MÓDULO PLANTILLA · Alta y modificación
 * Explorador Vocacional UTBB
 *
 * Una sola página resuelve alta y modificación: sin ?id= es alta, con ?id=
 * es modificación. Repetir el formulario en dos archivos duplica el trabajo
 * y garantiza que tarde o temprano se desincronicen.
 *
 * Ciclo completo: GET pinta el formulario → POST valida en el servidor →
 * si hay errores se vuelve a pintar con los datos escritos → si todo está
 * bien se guarda, se deja un mensaje y se REDIRIGE.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

const CARPETA_SUBIDAS = __DIR__ . '/../uploads';

$id       = entero(get('id'));
$registro = $id !== null ? plt_obtener($id) : null;

if ($id !== null && $registro === null) {
    no_encontrado('El registro no existe.');
}

// Un editor solo trabaja sobre su carrera.
if ($registro !== null) {
    requerir_carrera((int)$registro['carrera_id']);
}

// Valores que se pintan en el formulario.
$datos = [
    'carrera_id' => $registro['carrera_id'] ?? (carrera_del_usuario() ?? ''),
    'titulo'     => $registro['titulo']     ?? '',
    'resumen'    => $registro['resumen']    ?? '',
    'cuerpo'     => $registro['cuerpo']     ?? '',
    'imagen'     => $registro['imagen']     ?? null,
];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verificar_token();

    $datos['carrera_id'] = entero(post('carrera_id'));
    $datos['titulo']     = post('titulo');
    $datos['resumen']    = post('resumen');
    $datos['cuerpo']     = post('cuerpo');

    /* --- Validación en el servidor ------------------------------------ */

    if ($datos['carrera_id'] === null || carrera($datos['carrera_id']) === null) {
        $errores['carrera_id'] = 'Selecciona una carrera válida.';
    } else {
        // Un editor no puede asignar el registro a otra carrera.
        requerir_carrera($datos['carrera_id']);
    }

    if ($datos['titulo'] === '') {
        $errores['titulo'] = 'El título es obligatorio.';
    } elseif (mb_strlen($datos['titulo']) > 160) {
        $errores['titulo'] = 'El título no puede pasar de 160 caracteres.';
    }

    if (mb_strlen($datos['resumen']) > 400) {
        $errores['resumen'] = 'El resumen no puede pasar de 400 caracteres.';
    }

    /* --- Imagen (opcional) -------------------------------------------- */

    if (!empty($_FILES['imagen']['name'])) {
        try {
            $nuevo = guardar_archivo($_FILES['imagen'], CARPETA_SUBIDAS, SOLO_IMAGEN, 2);

            // Se borra la anterior solo cuando la nueva ya quedó guardada.
            if (!empty($registro['imagen'])) {
                @unlink(CARPETA_SUBIDAS . '/' . $registro['imagen']);
            }

            $datos['imagen'] = $nuevo;
        } catch (RuntimeException $e) {
            $errores['imagen'] = $e->getMessage();
        }
    }

    /* --- Guardar ------------------------------------------------------- */

    if (!$errores) {
        if ($registro === null) {
            $datos['estado'] = 'borrador';   // el estado inicial lo fija el servidor
            $nuevoId = plt_crear($datos, usuario()['id']);
            mensaje('exito', 'Registro creado como borrador.');
        } else {
            plt_actualizar((int)$registro['id'], $datos);
            mensaje('exito', 'Cambios guardados.');
        }

        redirigir('index.php');   // POST-Redirect-GET
    }
}

cabecera($registro ? 'Editar registro' : 'Nuevo registro', '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Módulo plantilla</a></li>
      <li><?= $registro ? 'Editar' : 'Nuevo' ?></li>
    </ol>
  </nav>

  <h1><?= $registro ? 'Editar registro' : 'Nuevo registro' ?></h1>

  <?php if ($errores): ?>
    <div class="alerta alerta--error" role="alert">
      <p><span class="alerta__titulo">Revisa los datos.</span>
         Hay <?= count($errores) ?> campo<?= count($errores) === 1 ? '' : 's' ?> con problemas.</p>
    </div>
  <?php endif; ?>

  <form class="formulario" method="POST" action="" enctype="multipart/form-data">
    <?= campo_token() ?>

    <fieldset class="grupo-campos">
      <legend class="grupo-campos__titulo">Datos del registro</legend>

      <div class="campo <?= isset($errores['carrera_id']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="carrera_id">
          Carrera <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <select class="campo__control" id="carrera_id" name="carrera_id" required
                <?= rol() === 'editor' ? 'disabled' : '' ?>>
          <option value="">Selecciona una opción</option>
          <?php foreach (carreras() as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$datos['carrera_id'] === (int)$c['id'] ? 'selected' : '' ?>>
              <?= e($c['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (rol() === 'editor'): ?>
          <input type="hidden" name="carrera_id" value="<?= (int)carrera_del_usuario() ?>">
          <small class="campo__ayuda">Como editor, trabajas sobre tu carrera.</small>
        <?php endif; ?>
        <?php if (isset($errores['carrera_id'])): ?>
          <small class="campo__error"><?= e($errores['carrera_id']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['titulo']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="titulo">
          Título <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <input class="campo__control" type="text" id="titulo" name="titulo"
               value="<?= e($datos['titulo']) ?>" maxlength="160" required>
        <?php if (isset($errores['titulo'])): ?>
          <small class="campo__error"><?= e($errores['titulo']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['resumen']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="resumen">Resumen</label>
        <textarea class="campo__control" id="resumen" name="resumen" rows="3"
                  maxlength="400"><?= e($datos['resumen']) ?></textarea>
        <small class="campo__ayuda">Se muestra en la tarjeta del listado. Máximo 400 caracteres.</small>
        <?php if (isset($errores['resumen'])): ?>
          <small class="campo__error"><?= e($errores['resumen']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo">
        <label class="campo__etiqueta" for="cuerpo">Contenido</label>
        <textarea class="campo__control" id="cuerpo" name="cuerpo" rows="8"><?= e($datos['cuerpo']) ?></textarea>
      </div>

      <div class="campo <?= isset($errores['imagen']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="imagen">Imagen</label>
        <?php if (!empty($datos['imagen'])): ?>
          <img src="../uploads/<?= e($datos['imagen']) ?>" alt="Imagen actual"
               style="max-width:260px;border-radius:6px;margin-bottom:8px">
        <?php endif; ?>
        <input class="campo__control" type="file" id="imagen" name="imagen"
               accept="image/jpeg,image/png,image/webp">
        <small class="campo__ayuda">JPG, PNG o WebP. Máximo 2 MB. El servidor comprueba el tipo real del archivo.</small>
        <?php if (isset($errores['imagen'])): ?>
          <small class="campo__error"><?= e($errores['imagen']) ?></small>
        <?php endif; ?>
      </div>
    </fieldset>

    <div class="fila">
      <button class="boton boton--primario" type="submit">
        <?= $registro ? 'Guardar cambios' : 'Crear registro' ?>
      </button>
      <a class="boton boton--secundario" href="index.php">Cancelar</a>
    </div>
  </form>

  <?php if ($registro): ?>
    <h2 class="mt-6">Historial</h2>
    <?php $historial = plt_historial((int)$registro['id']); ?>
    <?php if (!$historial): ?>
      <p class="leyenda">Sin cambios de estado registrados.</p>
    <?php else: ?>
      <div class="tabla-envoltura">
        <table class="tabla">
          <thead><tr><th scope="col">Fecha</th><th scope="col">Cambio</th><th scope="col">Quién</th><th scope="col">Motivo</th></tr></thead>
          <tbody>
          <?php foreach ($historial as $h): ?>
            <tr>
              <td><?= e(date('d/m/Y H:i', strtotime($h['creado_en']))) ?></td>
              <td><?= e($h['estado_previo'] ?? '—') ?> → <?= e($h['estado_nuevo']) ?></td>
              <td><?= e($h['usuario'] ?? 'Sistema') ?></td>
              <td><?= e($h['motivo'] ?? '') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  <?php endif; ?>

</section>

<?php
pie();
