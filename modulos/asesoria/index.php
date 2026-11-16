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

iniciar_sesion();

// --- Filtros de FAQ (GET) --------------------------------------------------
$busqueda    = get('q');
$categoriaId = entero(get('categoria'));
$carreraId   = entero(get('carrera'));

// --- Formulario de Nueva Consulta (POST) -----------------------------------
$datosConsulta = [
    'categoria_id' => '',
    'carrera_id'   => '',
    'asunto'       => '',
    'cuerpo'       => '',
];
$errores = [];

$categorias = ase_categorias_listar(true);
$faqs       = ase_faq_listar($busqueda, $categoriaId, $carreraId, true);

// Si hay sesión iniciada, cargamos las conversaciones propias del usuario
$misConversaciones = [];
if (hay_sesion()) {
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

<section class="contenedor seccion">

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
    <a class="boton boton--primario" href="#nueva-consulta">Hacer una consulta personal</a>
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