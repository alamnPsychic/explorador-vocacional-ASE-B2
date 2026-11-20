<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';
requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/hilo.php';

$id = entero(ase_entrada($_GET, 'id'));
$conversacion = $id !== null ? ase_conversacion_obtener($id) : null;
if ($conversacion === null || !ase_puede_atender_conversacion(
    $conversacion['carrera_id'] === null ? null : (int)$conversacion['carrera_id']
)) {
    no_encontrado('La conversación no está disponible.');
}

$rutaActual = 'conversacion.php?id=' . $id;
$cuerpo = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ase_verificar_token();
    $accion = ase_entrada($_POST, 'accion');

    if ($accion === 'cerrar' || $accion === 'reabrir') {
        try {
            if (ase_conversacion_cambiar_estado($id, $accion === 'cerrar' ? 'cerrada' : 'abierta')) {
                mensaje('exito', $accion === 'cerrar' ? 'Consulta cerrada.' : 'Consulta reabierta.');
            } else {
                mensaje('alerta', 'La consulta cambió de estado. Revisa su estado actual.');
            }
        } catch (PDOException $e) {
            error_log('ASE: error al cambiar el estado de una consulta: ' . $e->getMessage());
            mensaje('error', 'No se pudo cambiar el estado de la consulta.');
        }
        redirigir($rutaActual);
    }

    if ($accion !== 'responder') {
        http_response_code(400);
        exit('Acción no válida.');
    }
    if ($conversacion['estado'] === 'cerrada') {
        mensaje('alerta', 'Reabre la consulta antes de enviar una respuesta.');
        redirigir($rutaActual);
    }

    $cuerpo = ase_entrada($_POST, 'cuerpo');
    if (!ase_texto_valido($cuerpo, ASE_MAX_CUERPO)) {
        $errores['cuerpo'] = 'Escribe una respuesta de hasta 16 000 caracteres.';
    }

    if (!$errores) {
        try {
            $archivo = ase_procesar_adjunto_subido($_FILES['adjunto'] ?? null);
        } catch (RuntimeException $e) {
            $errores['adjunto'] = $e->getMessage();
        }
    }

    if (!$errores) {
        try {
            ase_mensaje_agregar($id, (int)usuario()['id'], $cuerpo, true, $archivo);
            mensaje('exito', 'Respuesta enviada.');
            redirigir($rutaActual);
        } catch (DomainException $e) {
            mensaje('alerta', $e->getMessage());
            redirigir($rutaActual);
        } catch (PDOException $e) {
            error_log('ASE: error al responder una consulta: ' . $e->getMessage());
            $errores['envio'] = 'No se pudo guardar la respuesta. Intenta de nuevo.';
        }
    }
}

$mensajes = ase_mensajes_listar($id);
$estado = $conversacion['estado'];

cabecera('Consulta de asesoría #' . $id, 'salud', '../css/modulo.css');
?>

<section class="contenedor seccion ase-admin">
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= e(RUTA_BASE) ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Bandeja de orientación</a></li>
      <li aria-current="page">Consulta #<?= e((string)$id) ?></li>
    </ol>
  </nav>

  <?php pintar_mensajes(); ?>

  <header class="panel mb-6">
    <div class="fila fila--separada mb-4">
      <h1 class="sin-margen"><?= e($conversacion['asunto']) ?></h1>
      <span class="<?= e(ase_clase_estado($estado)) ?>"><?= e(ASE_ESTADOS[$estado]) ?></span>
    </div>
    <p class="sin-margen">
      <strong>Aspirante</strong> <?= e($conversacion['aspirante_nombre']) ?> · <?= e($conversacion['aspirante_correo']) ?>
    </p>
    <p class="leyenda sin-margen">
      <?= e($conversacion['categoria_nombre']) ?> · <?= e($conversacion['carrera_nombre'] ?? 'General UTBB') ?>
    </p>
    <?php if ($estado !== 'abierta'): ?>
      <form method="POST" action="<?= e($rutaActual) ?>" class="mt-4">
        <?= campo_token() ?>
        <?php if ($estado === 'respondida'): ?>
          <button class="boton boton--secundario" type="submit" name="accion" value="cerrar">Cerrar consulta</button>
        <?php else: ?>
          <button class="boton boton--primario" type="submit" name="accion" value="reabrir">Reabrir consulta</button>
        <?php endif; ?>
      </form>
    <?php endif; ?>
  </header>

  <?php ase_pintar_hilo($mensajes, '../descargar.php', rol() === 'coordinacion'); ?>

  <?php if ($estado !== 'cerrada'): ?>
    <form class="panel formulario" method="POST" action="<?= e($rutaActual) ?>" enctype="multipart/form-data">
      <h2>Responder consulta</h2>
      <?= campo_token() ?>
      <input type="hidden" name="accion" value="responder">

      <?php if ($errores): ?>
        <div class="alerta alerta--error" role="alert">
          <p><?= e($errores['envio'] ?? 'Revisa los campos señalados.') ?></p>
        </div>
      <?php endif; ?>

      <div class="campo <?= isset($errores['cuerpo']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="cuerpo">Respuesta</label>
        <textarea class="campo__control" id="cuerpo" name="cuerpo" rows="5" maxlength="<?= ASE_MAX_CUERPO ?>" required
                  <?= isset($errores['cuerpo']) ? 'aria-invalid="true" aria-describedby="error-cuerpo"' : '' ?>><?= e($cuerpo) ?></textarea>
        <?php if (isset($errores['cuerpo'])): ?>
          <small class="campo__error" id="error-cuerpo"><?= e($errores['cuerpo']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['adjunto']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="adjunto">Archivo adjunto (opcional)</label>
        <input class="campo__control" type="file" id="adjunto" name="adjunto"
               accept="application/pdf,image/jpeg,image/png,image/webp"
               <?= isset($errores['adjunto']) ? 'aria-invalid="true" aria-describedby="ayuda-adjunto error-adjunto"' : 'aria-describedby="ayuda-adjunto"' ?>>
        <small class="campo__ayuda" id="ayuda-adjunto">PDF, JPG, PNG o WebP. Máximo 5 MB.</small>
        <?php if (isset($errores['adjunto'])): ?>
          <small class="campo__error" id="error-adjunto"><?= e($errores['adjunto']) ?></small>
        <?php endif; ?>
      </div>

      <button class="boton boton--primario" type="submit">Enviar respuesta</button>
    </form>
  <?php endif; ?>
</section>

<?php pie(); ?>
