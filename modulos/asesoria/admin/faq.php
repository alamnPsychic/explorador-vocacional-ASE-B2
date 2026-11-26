<?php
/** Módulo ASE · Administración de preguntas frecuentes. */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('coordinacion');

require_once __DIR__ . '/../includes/admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ase_verificar_token();

    $id = entero(ase_entrada($_POST, 'id'));
    if (ase_entrada($_POST, 'accion') !== 'eliminar' || $id === null) {
        mensaje('error', 'La solicitud de eliminación no es válida.');
        redirigir('faq.php');
    }

    try {
        if (ase_faq_eliminar($id)) {
            mensaje('exito', 'Se eliminó la pregunta frecuente.');
        } else {
            mensaje('error', 'La pregunta frecuente ya no existe.');
        }
    } catch (PDOException $excepcion) {
        error_log('ASE · No se pudo eliminar la pregunta frecuente: ' . $excepcion->getMessage());
        mensaje('error', 'No se pudo eliminar la pregunta frecuente. Intenta de nuevo.');
    }

    redirigir('faq.php');
}

$busqueda = ase_entrada($_GET, 'q');
if (!ase_texto_valido($busqueda, 255)) {
    $busqueda = '';
}
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$visibilidad = ase_entrada($_GET, 'visible');
if (!in_array($visibilidad, ['0', '1'], true)) {
    $visibilidad = '';
}

$categorias = ase_categorias_listar();
$categoriasPorId = array_column($categorias, null, 'id');
$preguntas = ase_faq_listar($busqueda, $categoriaId, null, false);

if ($visibilidad !== '') {
    $preguntas = array_filter($preguntas, static function (array $pregunta) use ($categoriasPorId, $visibilidad): bool {
        $esPublica = (bool)$pregunta['visible'] && (bool)($categoriasPorId[$pregunta['categoria_id']]['activa'] ?? false);
        return $esPublica === ($visibilidad === '1');
    });
}

cabecera('Preguntas frecuentes · Asesoría', 'salud', '../css/modulo.css');
?>

<section class="contenedor seccion ase-admin">
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= e(RUTA_BASE) ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Asesoría a aspirantes</a></li>
      <li aria-current="page">Preguntas frecuentes</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <h1 class="sin-margen">Preguntas frecuentes</h1>
    <a class="boton boton--primario" href="faq_form.php">Nueva pregunta frecuente</a>
  </div>

  <?php ase_admin_navegacion('faq'); ?>
  <?php pintar_mensajes(); ?>

 <form class="panel ase-panel-filtro mb-6" method="GET" action="faq.php" aria-label="Filtrar preguntas frecuentes">
    <div class="ase-filtros">
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q"
               placeholder="Pregunta o respuesta" value="<?= e($busqueda) ?>" maxlength="255">
      </div>
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="categoria">Categoría</label>
        <select class="campo__control" id="categoria" name="categoria">
          <option value="">Todas las categorías</option>
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?= e((string)$categoria['id']) ?>" <?= $categoriaId === (int)$categoria['id'] ? 'selected' : '' ?>>
              <?= e($categoria['nombre']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="visible">Visibilidad</label>
        <select class="campo__control" id="visible" name="visible">
          <option value="">Todas</option>
          <option value="1" <?= $visibilidad === '1' ? 'selected' : '' ?>>Visibles al público</option>
          <option value="0" <?= $visibilidad === '0' ? 'selected' : '' ?>>Ocultas al público</option>
        </select>
      </div>
      <div class="ase-filtros__acciones">
        <button class="boton boton--primario boton--pequeno" type="submit">Filtrar</button>
        <?php if ($busqueda !== '' || $categoriaId !== null || $visibilidad !== ''): ?>
          <a class="boton boton--fantasma boton--pequeno" href="faq.php">Limpiar</a>
        <?php endif; ?>
      </div>
    </div>
  </form>
  
  <?php if (!$preguntas): ?>
    <div class="vacio">
      <h2>No hay preguntas frecuentes</h2>
      <?php if ($busqueda !== '' || $categoriaId !== null || $visibilidad !== ''): ?>
        <p>No encontramos preguntas que coincidan con los filtros.</p>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <h2><?= e((string)count($preguntas)) ?> <?= count($preguntas) === 1 ? 'pregunta frecuente' : 'preguntas frecuentes' ?></h2>
    <div class="rejilla ase-tarjetas">
      <?php foreach ($preguntas as $pregunta): ?>
        <?php $categoriaActiva = (bool)($categoriasPorId[$pregunta['categoria_id']]['activa'] ?? false); ?>
        <article class="tarjeta">
          <div class="tarjeta__cuerpo">
            <div class="fila mb-2">
              <?php if (!$pregunta['visible']): ?>
                <span class="insignia insignia--archivado">Oculta</span>
              <?php elseif ($categoriaActiva): ?>
                <span class="insignia insignia--publicado">Pública</span>
              <?php else: ?>
                <span class="insignia insignia--revision">Categoría inactiva</span>
              <?php endif; ?>
              <span class="leyenda">Orden <?= e((string)$pregunta['orden']) ?></span>
            </div>
            <h3 class="tarjeta__titulo"><?= e($pregunta['pregunta']) ?></h3>
            <p class="leyenda"><?= e($pregunta['categoria_nombre']) ?> · <?= e($pregunta['carrera_nombre'] ?? 'General UTBB') ?></p>
            <div class="fila">
              <a class="boton boton--secundario boton--pequeno" href="faq_form.php?id=<?= e((string)$pregunta['id']) ?>">Editar</a>
              <form method="POST" action="faq.php" onsubmit="return confirm('¿Eliminar esta pregunta frecuente?')">
                <?= campo_token() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= e((string)$pregunta['id']) ?>">
                <button class="boton boton--peligro boton--pequeno" type="submit">Eliminar</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php pie(); ?>
