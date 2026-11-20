<?php
/**
 * MÓDULO ASE · Seguimiento de conversación del aspirante
 * Explorador Vocacional UTBB
 *
 * Verifica en el servidor que la conversación pertenezca al usuario en sesión
 * (o sea personal de orientación) para impedir acceso por manipulación de ?id=.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';

requerir_sesion();

require_once __DIR__ . '/includes/consultas.php';
require_once __DIR__ . '/includes/entrada.php';
require_once __DIR__ . '/includes/hilo.php';

$id = entero(ase_entrada($_GET, 'id'));
if ($id === null) {
    no_encontrado('Conversación no especificada.');
}

$usuarioActual = usuario();
$esPersonal    = rol() === 'editor' || rol() === 'coordinacion';

// Si es aspirante, se fuerza a que solo pueda cargar SU propia conversación
$conversacion = ase_conversacion_obtener($id, $esPersonal ? null : (int)$usuarioActual['id']);

if ($conversacion === null || ($esPersonal && !ase_puede_atender_conversacion(
    $conversacion['carrera_id'] === null ? null : (int)$conversacion['carrera_id']
))) {
    no_encontrado('La conversación no existe o no tienes permiso para consultarla.');
}

$errores = [];
$cuerpoRespuesta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ase_verificar_token();
    $accion = ase_entrada($_POST, 'accion');

    if ($accion === 'cambiar_estado') {
        $nuevoEstado = ase_entrada($_POST, 'nuevo_estado');
        if (!in_array($nuevoEstado, ['abierta', 'cerrada'], true)) {
            http_response_code(400);
            exit('Estado no válido.');
        }
        try {
            if (ase_conversacion_cambiar_estado($id, $nuevoEstado)) {
                mensaje('exito', $nuevoEstado === 'cerrada'
                    ? 'Marcaste la conversación como cerrada.'
                    : 'La conversación fue reabierta.');
            } else {
                mensaje('alerta', 'La consulta cambió de estado. Revisa su estado actual.');
            }
        } catch (PDOException $e) {
            error_log('ASE: error al cambiar el estado de una consulta: ' . $e->getMessage());
            mensaje('error', 'No se pudo cambiar el estado de la consulta.');
        }
        redirigir('conversacion.php?id=' . $id);
    }

    if ($accion !== 'responder') {
        http_response_code(400);
        exit('Acción no válida.');
    }
    $cuerpoRespuesta = ase_entrada($_POST, 'cuerpo');
    if (!ase_texto_valido($cuerpoRespuesta, ASE_MAX_CUERPO)) {
        $errores['cuerpo'] = 'Escribe un mensaje de hasta 16 000 caracteres.';
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
            ase_mensaje_agregar(
                $id,
                (int)$usuarioActual['id'],
                $cuerpoRespuesta,
                $esPersonal,
                $archivoGuardado
            );
            mensaje('exito', 'Tu mensaje fue agregado a la conversación.');
            redirigir('conversacion.php?id=' . $id);
        } catch (DomainException $e) {
            mensaje('alerta', $e->getMessage());
            redirigir('conversacion.php?id=' . $id);
        } catch (PDOException $e) {
            error_log('ASE: error al enviar un mensaje: ' . $e->getMessage());
            $errores['envio'] = 'No se pudo guardar el mensaje. Intenta de nuevo.';
        }
    }
}

$mensajes = ase_mensajes_listar($id);

cabecera('Conversación #' . $id . ' · Asesoría', '', 'css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="index.php">Asesoría a aspirantes</a></li>
      <li>Conversación #<?= (int)$conversacion['id'] ?></li>
    </ol>
  </nav>

  <?php pintar_mensajes(); ?>

  <div class="panel mb-6">
    <div class="fila fila--separada">
      <div>
        <h1 class="sin-margen"><?= e($conversacion['asunto']) ?></h1>
        <p class="leyenda sin-margen mt-2">
          Categoría: <strong><?= e($conversacion['categoria_nombre']) ?></strong> ·
          Carrera: <strong><?= e($conversacion['carrera_nombre'] ?? 'General UTBB') ?></strong> ·
          Creada el <?= e(date('d/m/Y H:i', strtotime($conversacion['creado_en']))) ?>
        </p>
      </div>
      <div>
        <span class="<?= e(ase_clase_estado($conversacion['estado'])) ?>">
          <?= e(ASE_ESTADOS[$conversacion['estado']]) ?>
        </span>
      </div>
    </div>

    <div class="fila mt-4">
      <?php if ($conversacion['estado'] === 'respondida'): ?>
        <form method="POST" action="">
          <?= campo_token() ?>
          <input type="hidden" name="accion" value="cambiar_estado">
          <input type="hidden" name="nuevo_estado" value="cerrada">
          <button class="boton boton--secundario boton--pequeno" type="submit">
            Dar por resuelta y cerrar consulta
          </button>
        </form>
      <?php elseif ($conversacion['estado'] === 'cerrada'): ?>
        <form method="POST" action="">
          <?= campo_token() ?>
          <input type="hidden" name="accion" value="cambiar_estado">
          <input type="hidden" name="nuevo_estado" value="abierta">
          <button class="boton boton--primario boton--pequeno" type="submit">
            Reabrir conversación
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <?php ase_pintar_hilo($mensajes, 'descargar.php'); ?>

  <!-- Formulario para enviar seguimiento (o reabrir automáticamente al responder) -->
  <?php if (!$esPersonal || $conversacion['estado'] !== 'cerrada'): ?>
  <div class="panel">
    <h3><?= $conversacion['estado'] === 'cerrada' ? 'Enviar un nuevo mensaje y reabrir la consulta' : 'Agregar respuesta o seguimiento' ?></h3>

    <?php if ($errores): ?>
      <div class="alerta alerta--error" role="alert">
        <p><?= e($errores['envio'] ?? 'No se pudo enviar el mensaje. Corrige los campos señalados.') ?></p>
      </div>
    <?php endif; ?>

    <form class="formulario" method="POST" action="" enctype="multipart/form-data">
      <?= campo_token() ?>
      <input type="hidden" name="accion" value="responder">

      <div class="campo <?= isset($errores['cuerpo']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="cuerpo">
          Tu mensaje <span class="campo__requerido" aria-hidden="true">*</span>
        </label>
        <textarea class="campo__control" id="cuerpo" name="cuerpo" rows="4" maxlength="<?= ASE_MAX_CUERPO ?>" required
                  placeholder="Escribe tu respuesta o información adicional..."><?= e($cuerpoRespuesta) ?></textarea>
        <?php if (isset($errores['cuerpo'])): ?>
          <small class="campo__error"><?= e($errores['cuerpo']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['adjunto']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="adjunto">Adjuntar archivo (opcional)</label>
        <input class="campo__control" type="file" id="adjunto" name="adjunto"
               accept="application/pdf,image/jpeg,image/png,image/webp">
        <small class="campo__ayuda">PDF, JPG, PNG o WebP. Máximo 5 MB.</small>
        <?php if (isset($errores['adjunto'])): ?>
          <small class="campo__error"><?= e($errores['adjunto']) ?></small>
        <?php endif; ?>
      </div>

      <div class="fila">
        <button class="boton boton--primario" type="submit">Enviar mensaje</button>
        <a class="boton boton--secundario" href="index.php">Volver al inicio de asesoría</a>
      </div>
    </form>
  </div>
  <?php endif; ?>

</section>

<?php
pie();
