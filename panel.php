<?php
/**
 * Panel del usuario · punto de entrada a la administración
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Observa la PRIMERA instrucción ejecutable después de los require: la
 * verificación de sesión. Así empieza toda página de administración.
 */

declare(strict_types=1);

require_once __DIR__ . '/nucleo/comun.php';

requerir_sesion();

$u       = usuario();
$miCarrera = carrera_del_usuario() !== null ? carrera(carrera_del_usuario()) : null;

cabecera('Panel', $miCarrera['area'] ?? '');
?>

<section class="contenedor seccion">

  <?php pintar_mensajes(); ?>

  <h1>Hola, <?= e($u['nombre']) ?></h1>

  <div class="panel mb-6">
    <div class="fila fila--separada">
      <div>
        <p class="sin-margen"><strong>Rol:</strong> <?= e($u['rol']) ?></p>
        <?php if ($miCarrera): ?>
          <p class="sin-margen"><strong>Carrera a tu cargo:</strong> <?= e($miCarrera['nombre']) ?></p>
        <?php endif; ?>
      </div>
      <a class="boton boton--secundario" href="<?= RUTA_BASE ?>/auth/salir.php">Cerrar sesión</a>
    </div>
  </div>

  <h2>Administración</h2>
  <p>Cada módulo publica aquí el acceso a su panel. Los enlaces aparecen conforme los equipos entregan.</p>

  <div class="rejilla">
    <?php
    // Qué roles entran al panel de cada módulo.
    $paneles = [
        'carreras'    => ['editor', 'coordinacion'],
        'testimonios' => ['coordinacion'],
        'test'        => ['coordinacion'],
        'recorridos'  => ['editor', 'coordinacion'],
        'visitas'     => ['coordinacion'],
        'portafolio'  => ['coordinacion'],
        'becas'       => ['coordinacion'],
        'asesoria'    => ['coordinacion'],
        'egresados'   => ['coordinacion'],
        'noticias'    => ['editor', 'coordinacion'],
    ];

    $visibles = 0;

    foreach (MODULOS as $carpeta => $m):
        if (!in_array(rol(), $paneles[$carpeta] ?? [], true)) {
            continue;
        }
        if (!is_file(__DIR__ . '/modulos/' . $carpeta . '/admin/index.php')) {
            continue;
        }
        $visibles++;
    ?>
      <a class="tarjeta tarjeta--area" href="<?= RUTA_BASE ?>/modulos/<?= e($carpeta) ?>/admin/">
        <div class="tarjeta__cuerpo">
          <h3 class="tarjeta__titulo"><?= e($m['nombre']) ?></h3>
          <p class="leyenda">Panel de administración</p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($visibles === 0): ?>
    <div class="vacio">
      <h3>Todavía no hay paneles disponibles</h3>
      <p class="ancho-lectura">Aparecerán conforme los equipos entreguen sus módulos al repositorio.</p>
    </div>
  <?php endif; ?>

<?php /* ---------------------------------------------------------------
     Tablero de avance. Solo en desarrollo y solo para coordinación: es
     una herramienta del docente, no del usuario de la plataforma.
     --------------------------------------------------------------- */ ?>
<?php if (DESARROLLO && rol() === 'coordinacion'): ?>
  <h2 class="mt-6">Estado de los módulos</h2>
  <p class="leyenda">Este bloque solo aparece en el ambiente de desarrollo.</p>

  <div class="tabla-envoltura">
    <table class="tabla">
      <caption>Diez módulos del proyecto</caption>
      <thead>
        <tr>
          <th scope="col">Clave</th>
          <th scope="col">Módulo</th>
          <th scope="col">Equipo</th>
          <th scope="col">Estado</th>
          <th scope="col">Abrir</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach (MODULOS as $carpeta => $m): $existe = modulo_existe($carpeta); ?>
        <tr>
          <td><code><?= e($m['clave']) ?></code></td>
          <td><?= e($m['nombre']) ?></td>
          <td><?= e($m['equipo']) ?></td>
          <td>
            <?php if ($existe): ?>
              <span class="insignia insignia--publicado">En el repositorio</span>
            <?php else: ?>
              <span class="insignia insignia--borrador">Sin entregar</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($existe): ?>
              <a class="boton boton--secundario boton--pequeno"
                 href="<?= RUTA_BASE ?>/modulos/<?= e($carpeta) ?>/">Abrir</a>
            <?php else: ?>
              <span class="leyenda">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

  <div class="alerta alerta--info mt-6">
    <p><span class="alerta__titulo">Recuerda.</span>
       Este menú oculta lo que no te corresponde, pero ocultar no es proteger.
       Cada página del panel verifica el rol por su cuenta con
       <code>requerir_rol()</code>.</p>
  </div>

</section>

<?php
pie();
