<?php
/**
 * MÓDULO PLANTILLA · Panel de administración
 * Explorador Vocacional UTBB
 *
 * La verificación de rol es la PRIMERA instrucción ejecutable. Si se escribe
 * después de imprimir algo, la página ya se envió y la protección no sirve.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../nucleo/comun.php';

requerir_rol('editor', 'coordinacion');

require_once __DIR__ . '/../includes/consultas.php';

$busqueda = get('q');
$estado   = get('estado');

// Un editor solo ve lo de su carrera. La restricción se aplica en el
// servidor, no escondiendo el filtro en la interfaz.
$carreraId = (rol() === 'editor') ? carrera_del_usuario() : entero(get('carrera'));

$pagina    = entero(get('pagina')) ?? 1;
$resultado = plt_listar($busqueda, $estado, $carreraId, $pagina);

cabecera('Panel · Módulo plantilla', '', '../css/modulo.css');
?>

<section class="contenedor seccion">

  <nav aria-label="Ruta">
    <ol class="migas">
      <li><a href="<?= RUTA_BASE ?>/panel.php">Panel</a></li>
      <li>Módulo plantilla</li>
    </ol>
  </nav>

  <div class="fila fila--separada mb-6">
    <h1 class="sin-margen">Registros</h1>
    <a class="boton boton--primario" href="editar.php">Nuevo registro</a>
  </div>

  <?php pintar_mensajes(); ?>

  <form class="panel mb-6" method="GET" action="">
    <div class="fila">
      <div class="campo sin-margen" style="flex:1 1 220px">
        <label class="campo__etiqueta" for="q">Buscar</label>
        <input class="campo__control" type="search" id="q" name="q" value="<?= e($busqueda) ?>">
      </div>

      <div class="campo sin-margen" style="flex:0 1 200px">
        <label class="campo__etiqueta" for="estado">Estado</label>
        <select class="campo__control" id="estado" name="estado">
          <option value="">Todos</option>
          <?php foreach (PLT_ESTADOS as $clave => $texto): ?>
            <option value="<?= e($clave) ?>" <?= $estado === $clave ? 'selected' : '' ?>><?= e($texto) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="boton boton--secundario" type="submit">Filtrar</button>
    </div>
  </form>

  <?php if (!$resultado['filas']): ?>

    <div class="vacio">
      <h3>No hay registros</h3>
      <p class="ancho-lectura">Cuando captures el primero aparecerá aquí.</p>
      <a class="boton boton--primario" href="editar.php">Crear el primero</a>
    </div>

  <?php else: ?>

    <div class="tabla-envoltura">
      <table class="tabla">
        <caption><?= $resultado['total'] ?> registro<?= $resultado['total'] === 1 ? '' : 's' ?></caption>
        <thead>
          <tr>
            <th scope="col">Título</th>
            <th scope="col">Carrera</th>
            <th scope="col">Estado</th>
            <th scope="col">Actualizado</th>
            <th scope="col">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resultado['filas'] as $f): ?>
            <tr>
              <td><?= e($f['titulo']) ?></td>
              <td><?= e($f['carrera']) ?></td>
              <td><span class="<?= e(plt_clase_estado($f['estado'])) ?>"><?= e(PLT_ESTADOS[$f['estado']]) ?></span></td>
              <td><?= e(date('d/m/Y H:i', strtotime($f['actualizado_en']))) ?></td>
              <td>
                <div class="tabla__acciones">
                  <a class="boton boton--secundario boton--pequeno" href="editar.php?id=<?= (int)$f['id'] ?>">Editar</a>

                  <?php /* Eliminar es una acción que MODIFICA: va por POST, con
                          token, nunca por un enlace. Un enlace lo puede visitar
                          un robot o una imagen de otro sitio. */ ?>
                  <form method="POST" action="eliminar.php"
                        onsubmit="return confirm('¿Eliminar este registro? La acción no se puede deshacer.')">
                    <?= campo_token() ?>
                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                    <button class="boton boton--peligro boton--pequeno" type="submit">Eliminar</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  <?php endif; ?>

</section>

<?php
pie();
