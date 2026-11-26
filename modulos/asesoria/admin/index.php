<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';
requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/admin.php';

$esEditor = rol() === 'editor';
if ($esEditor && carrera_del_usuario() === null) {
    http_response_code(403);
    exit('Tu cuenta de editor requiere una carrera asignada.');
}

$busqueda = ase_entrada($_GET, 'q');
$busqueda = ase_texto_valido($busqueda, 200) ? $busqueda : '';
$estado = ase_entrada($_GET, 'estado');
if (!isset(ASE_ESTADOS[$estado])) {
    $estado = '';
}
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$pagina = max(1, entero(ase_entrada($_GET, 'pagina')) ?? 1);
$categorias = ase_categorias_listar();

$resultado = ase_conversaciones_listar(
    $busqueda,
    $estado,
    $categoriaId,
    $esEditor ? carrera_del_usuario() : null,
    pagina: $pagina,
    incluirGenerales: $esEditor
);
$filtros = array_filter([
    'q'         => $busqueda,
    'estado'    => $estado,
    'categoria' => $categoriaId,
], static fn($v): bool => $v !== '' && $v !== null);

cabecera('Bandeja de orientación', 'salud', '../css/modulo.css');
?>

<section class="contenedor seccion ase-admin">
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= e(RUTA_BASE) ?>/panel.php">Panel</a></li>
      <li><a href="../index.php">Asesoría a aspirantes</a></li>
      <li aria-current="page">Bandeja</li>
    </ol>
  </nav>

  <h1>Bandeja de orientación</h1>
  <?php ase_admin_navegacion('bandeja'); ?>
  <?php pintar_mensajes(); ?>

  <form class="panel ase-panel-filtro mb-6" method="GET" action="index.php" aria-label="Filtrar consultas">
    <div class="ase-filtros">
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q" maxlength="200"
               placeholder="Asunto, nombre o correo" value="<?= e($busqueda) ?>">
      </div>
      <div class="campo sin-margen">
        <label class="campo__etiqueta" for="estado">Estado</label>
        <select class="campo__control" id="estado" name="estado">
          <option value="">Todas (abiertas y cerradas)</option>
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
          <a class="boton boton--fantasma boton--pequeno" href="index.php">Limpiar</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <?php if ($resultado['filas']): ?>
    <h2><?= e((string)$resultado['total']) ?> <?= $resultado['total'] === 1 ? 'consulta' : 'consultas' ?></h2>
    <div class="rejilla ase-tarjetas">
      <?php foreach ($resultado['filas'] as $consulta): ?>
        <article class="tarjeta">
          <div class="tarjeta__cuerpo">
            <div class="fila fila--separada mb-2">
              <span class="<?= e(ase_clase_estado($consulta['estado'])) ?>"><?= e(ASE_ESTADOS[$consulta['estado']]) ?></span>
              <time class="leyenda" datetime="<?= e(str_replace(' ', 'T', $consulta['actualizado_en'])) ?>">
                <?= e(date('d/m/Y H:i', strtotime($consulta['actualizado_en']))) ?>
              </time>
            </div>
            <h3 class="tarjeta__titulo">
              <a href="conversacion.php?id=<?= e((string)$consulta['id']) ?>"><?= e($consulta['asunto']) ?></a>
            </h3>
            <p class="sin-margen"><?= e($consulta['aspirante_nombre']) ?></p>
            <p class="leyenda sin-margen">
              <?= e($consulta['categoria_nombre']) ?> · <?= e($consulta['carrera_nombre'] ?? 'General UTBB') ?>
            </p>
            <p class="leyenda sin-margen">
              <?= e((string)$consulta['total_mensajes']) ?> <?= (int)$consulta['total_mensajes'] === 1 ? 'mensaje' : 'mensajes' ?>
              <?php if ($consulta['orientador_nombre'] !== null): ?>
                · <?= e($consulta['orientador_nombre']) ?>
              <?php endif; ?>
            </p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="vacio">
      <h2>No se encontraron consultas</h2>
      <p>Prueba con otro término de búsqueda o cambia los filtros.</p>
      <a class="boton boton--secundario" href="index.php">Ver todas las consultas</a>
    </div>
  <?php endif; ?>

  <?php if ($resultado['paginas'] > 1): ?>
    <?php $base = http_build_query($filtros); ?>
    <nav class="mt-6" aria-label="Paginación de consultas">
      <ul class="paginacion">
        <?php if ($resultado['pagina'] > 1): ?>
          <li>
            <a href="index.php?<?= e($base ? $base . '&' : '') ?>pagina=<?= (int)($resultado['pagina'] - 1) ?>">Anterior</a>
          </li>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $resultado['paginas']; $p++): ?>
          <li>
            <?php if ($p === $resultado['pagina']): ?>
              <span aria-current="page"><?= $p ?></span>
            <?php else: ?>
              <a href="index.php?<?= e($base ? $base . '&' : '') ?>pagina=<?= $p ?>"><?= $p ?></a>
            <?php endif; ?>
          </li>
        <?php endfor; ?>
        <?php if ($resultado['pagina'] < $resultado['paginas']): ?>
          <li>
            <a href="index.php?<?= e($base ? $base . '&' : '') ?>pagina=<?= (int)($resultado['pagina'] + 1) ?>">Siguiente</a>
          </li>
        <?php endif; ?>
      </ul>
    </nav>
  <?php endif; ?>
</section>

<?php pie(); ?>
