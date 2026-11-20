<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('coordinacion');

require_once __DIR__ . '/../includes/admin.php';

$esPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($esPost) {
    ase_verificar_token();
}

$id = entero(ase_entrada($_GET, 'id'));
$categoria = $id !== null ? ase_categoria_obtener($id) : null;
if (array_key_exists('id', $_GET) && $categoria === null) {
    no_encontrado('La categoría solicitada no existe.');
}

$mostrarFormulario = $categoria !== null || ase_entrada($_GET, 'nueva') === '1';
$destinoFormulario = $id !== null ? 'categorias.php?id=' . $id : 'categorias.php?nueva=1';
$datos = [
    'nombre' => $categoria['nombre'] ?? '',
    'descripcion' => $categoria['descripcion'] ?? '',
    'orden' => (string)($categoria['orden'] ?? 1),
    'activa' => (bool)($categoria['activa'] ?? true),
];
$errores = [];

if ($esPost) {
    $accion = ase_entrada($_POST, 'accion');

    if ($accion === 'eliminar' && $id !== null) {
        try {
            if (ase_categoria_eliminar($id)) {
                mensaje('exito', 'Categoría eliminada.');
            } else {
                mensaje('error', 'No se pudo eliminar la categoría. Si tiene consultas o preguntas vinculadas, puedes desactivarla.');
            }
        } catch (PDOException $e) {
            error_log('ASE: error al eliminar una categoría: ' . $e->getMessage());
            mensaje('error', 'No se pudo eliminar la categoría. Intenta de nuevo.');
        }
        redirigir('categorias.php');
    }

    if ($accion !== 'guardar' || !$mostrarFormulario) {
        http_response_code(400);
        exit('Acción no válida.');
    }

    $datos = [
        'nombre' => ase_entrada($_POST, 'nombre'),
        'descripcion' => ase_entrada($_POST, 'descripcion'),
        'orden' => ase_entrada($_POST, 'orden'),
        'activa' => ($_POST['activa'] ?? null) === '1',
    ];
    $orden = ase_valor_orden($datos['orden']);

    if (!ase_texto_valido($datos['nombre'], 100)) {
        $errores['nombre'] = 'Escribe un nombre de hasta 100 caracteres.';
    }
    if ((isset($_POST['descripcion']) && !is_string($_POST['descripcion']))
        || ($datos['descripcion'] !== '' && !ase_texto_valido($datos['descripcion'], 255))) {
        $errores['descripcion'] = 'Escribe una descripción de hasta 255 caracteres.';
    }
    if ($orden === null) {
        $errores['orden'] = 'Ingresa un número entero del 0 al 255.';
    }
    if (array_key_exists('activa', $_POST) && !$datos['activa']) {
        $errores['activa'] = 'Selecciona una opción válida.';
    }

    if (!$errores) {
        try {
            $datosGuardar = $datos;
            $datosGuardar['orden'] = $orden;
            if ($id === null) {
                ase_categoria_crear($datosGuardar);
                mensaje('exito', 'Categoría creada.');
            } else {
                ase_categoria_actualizar($id, $datosGuardar);
                mensaje('exito', 'Categoría actualizada.');
            }
            redirigir('categorias.php');
        } catch (PDOException $e) {
            error_log('ASE: error al guardar una categoría: ' . $e->getMessage());
            $errores['guardar'] = 'No se pudo guardar la categoría. Intenta de nuevo.';
        }
    }
}

$titulo = 'Categorías';
if ($mostrarFormulario) {
    $titulo = $id !== null ? 'Editar categoría' : 'Nueva categoría';
}
$categorias = $mostrarFormulario ? [] : ase_categorias_listar();

cabecera($titulo . ' · Asesoría', 'salud', '../css/modulo.css');
?>

<section class="contenedor seccion ase-admin">
  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= e(RUTA_BASE) ?>/panel.php">Panel</a></li>
      <li><a href="index.php">Asesoría</a></li>
      <?php if ($mostrarFormulario): ?>
        <li><a href="categorias.php">Categorías</a></li>
      <?php endif; ?>
      <li aria-current="page"><?= e($titulo) ?></li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-4">
    <h1 class="sin-margen"><?= e($titulo) ?></h1>
    <?php if (!$mostrarFormulario): ?>
      <a class="boton boton--primario" href="categorias.php?nueva=1">Nueva categoría</a>
    <?php endif; ?>
  </div>

  <?php ase_admin_navegacion('categorias'); ?>
  <?php pintar_mensajes(); ?>

  <?php if ($mostrarFormulario): ?>
    <?php if ($errores): ?>
      <div class="alerta alerta--error" role="alert">
        <p><?= e($errores['guardar'] ?? 'Revisa los campos señalados.') ?></p>
      </div>
    <?php endif; ?>

    <form class="panel formulario" method="POST" action="<?= e($destinoFormulario) ?>">
      <?= campo_token() ?>
      <input type="hidden" name="accion" value="guardar">

      <div class="campo <?= isset($errores['nombre']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="nombre">Nombre <span class="campo__requerido" aria-hidden="true">*</span></label>
        <input class="campo__control" id="nombre" name="nombre" value="<?= e($datos['nombre']) ?>"
               maxlength="100" required <?= isset($errores['nombre']) ? 'aria-invalid="true" aria-describedby="error-nombre"' : '' ?>>
        <?php if (isset($errores['nombre'])): ?>
          <small class="campo__error" id="error-nombre"><?= e($errores['nombre']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['descripcion']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="descripcion">Descripción (opcional)</label>
        <textarea class="campo__control" id="descripcion" name="descripcion" rows="3" maxlength="255"
                  <?= isset($errores['descripcion']) ? 'aria-invalid="true" aria-describedby="error-descripcion"' : '' ?>><?= e($datos['descripcion']) ?></textarea>
        <?php if (isset($errores['descripcion'])): ?>
          <small class="campo__error" id="error-descripcion"><?= e($errores['descripcion']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo <?= isset($errores['orden']) ? 'campo--invalido' : '' ?>">
        <label class="campo__etiqueta" for="orden">Orden <span class="campo__requerido" aria-hidden="true">*</span></label>
        <input class="campo__control" type="number" id="orden" name="orden" value="<?= e($datos['orden']) ?>"
               min="0" max="255" step="1" required <?= isset($errores['orden']) ? 'aria-invalid="true" aria-describedby="error-orden"' : 'aria-describedby="ayuda-orden"' ?>>
        <small class="campo__ayuda" id="ayuda-orden">Las categorías con un orden menor aparecen primero.</small>
        <?php if (isset($errores['orden'])): ?>
          <small class="campo__error" id="error-orden"><?= e($errores['orden']) ?></small>
        <?php endif; ?>
      </div>

      <div class="campo">
        <label class="opcion">
          <input type="checkbox" name="activa" value="1" <?= $datos['activa'] ? 'checked' : '' ?>
                 <?= isset($errores['activa']) ? 'aria-invalid="true" aria-describedby="error-activa"' : 'aria-describedby="ayuda-activa"' ?>>
          Categoría activa
        </label>
        <small class="campo__ayuda" id="ayuda-activa">Las categorías activas se muestran en el formulario de consulta y en las preguntas frecuentes públicas.</small>
        <?php if (isset($errores['activa'])): ?>
          <small class="campo__error" id="error-activa"><?= e($errores['activa']) ?></small>
        <?php endif; ?>
      </div>

      <div class="fila">
        <button type="submit" class="boton boton--primario">Guardar categoría</button>
        <a class="boton boton--secundario" href="categorias.php">Cancelar</a>
      </div>
    </form>
  <?php elseif (!$categorias): ?>
    <div class="vacio">
      <h2>No hay categorías</h2>
      <p>Crea una categoría para clasificar consultas y preguntas frecuentes.</p>
    </div>
  <?php else: ?>
    <div class="rejilla">
      <?php foreach ($categorias as $item): ?>
        <?php
        $itemId = (int)$item['id'];
        $activa = (bool)$item['activa'];
        $totalConsultas = (int)$item['total_conversaciones'];
        $totalPreguntas = (int)$item['total_faq'];
        ?>
        <article class="tarjeta tarjeta--area">
          <div class="tarjeta__cuerpo">
            <div class="fila fila--separada mb-4">
              <h2 class="tarjeta__titulo sin-margen"><?= e($item['nombre']) ?></h2>
              <span class="insignia <?= $activa ? 'insignia--publicado' : 'insignia--archivado' ?>">
                <?= $activa ? 'Activa' : 'Inactiva' ?>
              </span>
            </div>
            <?php if ($item['descripcion'] !== ''): ?>
              <p><?= e($item['descripcion']) ?></p>
            <?php endif; ?>
            <p class="leyenda">Orden <?= e((string)$item['orden']) ?> · <?= e((string)$totalConsultas) ?> consulta<?= $totalConsultas === 1 ? '' : 's' ?> · <?= e((string)$totalPreguntas) ?> pregunta<?= $totalPreguntas === 1 ? '' : 's' ?></p>
          </div>
          <div class="tarjeta__pie fila">
            <a class="boton boton--secundario boton--pequeno" href="categorias.php?id=<?= e((string)$itemId) ?>"
               aria-label="Editar <?= e($item['nombre']) ?>">Editar</a>
            <?php if ($totalConsultas === 0 && $totalPreguntas === 0): ?>
              <form method="POST" action="categorias.php?id=<?= e((string)$itemId) ?>"
                    onsubmit="return confirm('¿Eliminar esta categoría?')">
                <?= campo_token() ?>
                <input type="hidden" name="accion" value="eliminar">
                <button class="boton boton--peligro boton--pequeno" type="submit"
                        aria-label="Eliminar <?= e($item['nombre']) ?>">Eliminar</button>
              </form>
            <?php else: ?>
              <span class="leyenda">La categoría está en uso.</span>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php pie();
