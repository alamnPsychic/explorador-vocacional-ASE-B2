<?php
/**
 * MÓDULO ASE · Bandeja de conversaciones del aspirante
 * Explorador Vocacional UTBB
 */

declare(strict_types=1);

require_once __DIR__ . '/../../nucleo/comun.php';
requerir_rol('aspirante');

require_once __DIR__ . '/includes/consultas.php';
require_once __DIR__ . '/includes/entrada.php';

$busqueda = ase_entrada($_GET, 'q');
$busqueda = ase_texto_valido($busqueda, 200) ? $busqueda : '';
$estado   = ase_entrada($_GET, 'estado');
if (!isset(ASE_ESTADOS[$estado])) {
    $estado = '';
}
$categoriaId = entero(ase_entrada($_GET, 'categoria'));
$pagina      = max(1, entero(ase_entrada($_GET, 'pagina')) ?? 1);

$categorias = ase_categorias_listar();
$resultado  = ase_conversaciones_listar(
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
      <a class="boton boton--primario" href="index.php#nueva-consulta">Nueva consulta</a>
    </div>
  </div>

  <?php pintar_mensajes(); ?>

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
        <a class="boton boton--primario" href="index.php#nueva-consulta">Enviar mi primera consulta</a>
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
</section>

<?php pie(); ?>