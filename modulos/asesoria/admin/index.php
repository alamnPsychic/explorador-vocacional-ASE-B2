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
$estado = array_key_exists('estado', $_GET) ? ase_entrada($_GET, 'estado') : 'abierta';
if (!isset(ASE_ESTADOS[$estado])) {
    $estado = '';
}
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$pagina = entero(ase_entrada($_GET, 'pagina')) ?? 1;
$categorias = ase_categorias_listar();

$resultado = ase_conversaciones_listar(
    $busqueda,
    $estado,
    $categoriaId,
    $esEditor ? carrera_del_usuario() : null,
    pagina: $pagina,
    incluirGenerales: $esEditor
);
$filtros = ['q' => $busqueda, 'estado' => $estado, 'categoria' => $categoriaId];

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

  <form class="panel mb-6 ase-filtros" method="GET" action="index.php" aria-label="Filtrar consultas">
    <div class="campo sin-margen">
      <label class="campo__etiqueta" for="q">Buscar consultas</label>
      <input class="campo__control" type="search" id="q" name="q" maxlength="200"
             placeholder="Asunto, nombre o correo" value="<?= e($busqueda) ?>">
    </div>
    <div class="campo sin-margen">
      <label class="campo__etiqueta" for="estado">Estado</label>
      <select class="campo__control" id="estado" name="estado">
        <option value="">Todos</option>
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
    <button class="boton boton--secundario" type="submit">Filtrar</button>
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
      <a class="boton boton--secundario" href="index.php?estado=">Ver todas las consultas</a>
    </div>
  <?php endif; ?>

  <?php if ($resultado['paginas'] > 1): ?>
    <nav class="fila mt-6" aria-label="Paginación de consultas">
      <?php if ($resultado['pagina'] > 1): ?>
        <a class="boton boton--secundario"
           href="index.php?<?= e(http_build_query($filtros + ['pagina' => $resultado['pagina'] - 1])) ?>">Anterior</a>
      <?php endif; ?>
      <span>Página <?= e((string)$resultado['pagina']) ?> de <?= e((string)$resultado['paginas']) ?></span>
      <?php if ($resultado['pagina'] < $resultado['paginas']): ?>
        <a class="boton boton--secundario"
           href="index.php?<?= e(http_build_query($filtros + ['pagina' => $resultado['pagina'] + 1])) ?>">Siguiente</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</section>

<?php pie(); ?>
