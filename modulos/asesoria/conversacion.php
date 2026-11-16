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

$id = entero(get('id'));
if ($id === null) {
    no_encontrado('Conversación no especificada.');
}

$usuarioActual = usuario();
$esPersonal    = in_array(rol(), ['editor', 'coordinacion'], true);

// Si es aspirante, se fuerza a que solo pueda cargar SU propia conversación
$conversacion = ase_conversacion_obtener($id, $esPersonal ? null : (int)$usuarioActual['id']);

if ($conversacion === null) {
    no_encontrado('La conversación no existe o no tienes permiso para consultarla.');
}

$errores = [];
$cuerpoRespuesta = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_token();
    $accion = post('accion');

    if ($accion === 'cambiar_estado') {
        $nuevoEstado = post('nuevo_estado');
        if (in_array($nuevoEstado, ['abierta', 'cerrada'], true)) {
            ase_conversacion_cambiar_estado($id, $nuevoEstado);
            $textoMsj = $nuevoEstado === 'cerrada'
                ? 'Marcaste la conversación como cerrada.'
                : 'La conversación fue reabierta.';
            mensaje('exito', $textoMsj);
        }
        redirigir('conversacion.php?id=' . $id);
    }

if ($accion === 'responder') {
        $cuerpoRespuesta = trim(post('cuerpo'));

        if ($cuerpoRespuesta === '') {
            $errores['cuerpo'] = 'Escribe un mensaje antes de enviar.';
        }

        $archivoGuardado = null;
        if (!$errores && !empty($_FILES['adjunto']['name'])) {
            try {
                $archivoGuardado = ase_procesar_adjunto_subido($_FILES['adjunto']);
            } catch (RuntimeException $e) {
                $errores['adjunto'] = $e->getMessage();
            }
        }

        if (!$errores) {
            ase_mensaje_agregar(
                $id,
                (int)$usuarioActual['id'],
                $cuerpoRespuesta,
                $esPersonal,
                $archivoGuardado
            );
            mensaje('exito', 'Tu mensaje fue agregado a la conversación.');
            redirigir('conversacion.php?id=' . $id);
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
      <?php if ($conversacion['estado'] !== 'cerrada'): ?>
        <form method="POST" action="">
          <?= campo_token() ?>
          <input type="hidden" name="accion" value="cambiar_estado">
          <input type="hidden" name="nuevo_estado" value="cerrada">
          <button class="boton boton--secundario boton--pequeno" type="submit">
            Dar por resuelta y cerrar consulta
          </button>
        </form>
      <?php else: ?>
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
  <!-- Hilo de mensajes -->
  <h2>Historial de mensajes (<?= count($mensajes) ?>)</h2>

  <div class="ase-hilo mb-6">
    <?php foreach ($mensajes as $m): ?>
      <article class="panel mb-4 <?= (int)$m['es_orientador'] === 1 ? 'ase-mensaje--orientador' : 'ase-mensaje--aspirante' ?>">
        <div class="fila fila--separada mb-2">
          <strong>
            <?= e($m['autor_nombre']) ?>
            <?php if ((int)$m['es_orientador'] === 1): ?>
              <span class="insignia insignia--publicado">Orientador UTBB</span>
            <?php else: ?>
              <span class="insignia insignia--borrador">Aspirante</span>
            <?php endif; ?>
          </strong>
          <span class="leyenda sin-margen"><?= e(date('d/m/Y H:i', strtotime($m['creado_en']))) ?></span>
        </div>

        <p class="mb-2"><?= nl2br(e($m['cuerpo'])) ?></p>

        <?php if (!empty($m['adjuntos'])): ?>
          <div class="ase-adjuntos mt-2">
            <small class="leyenda">Archivos adjuntos:</small>
            <ul>
              <?php foreach ($m['adjuntos'] as $adj): ?>
                <li>
                  <a href="descargar.php?id=<?= (int)$adj['id'] ?>">
                    Descargar: <?= e($adj['nombre_original']) ?>
                  </a>
                  <small class="leyenda">(<?= round((int)$adj['tamano'] / 1024, 1) ?> KB)</small>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>