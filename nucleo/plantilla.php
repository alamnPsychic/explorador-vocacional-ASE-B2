<?php
/**
 * NÚCLEO · Encabezado y pie comunes
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Uso dentro de una página del módulo:
 *
 *     <?php require_once __DIR__ . '/../../nucleo/comun.php';
 *     cabecera('Becas y apoyos', 'administrativa');
 *     ?>
 *     <section class="contenedor seccion">
 *         ... el contenido de la pantalla ...
 *     </section>
 *     <?php pie();
 */

declare(strict_types=1);

/**
 * Imprime el documento hasta la apertura de <main>.
 *
 * @param string $titulo     Título de la pestaña del navegador
 * @param string $area       Área de conocimiento: tiñe la página. Opcional.
 * @param string $hojaModulo Ruta de la hoja propia del módulo, relativa a la
 *                           página actual. Opcional.
 */
function cabecera(string $titulo, string $area = '', string $hojaModulo = ''): void
{
    $claseArea = clase_area($area);
    $u = usuario();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($titulo) ?> · Explorador Vocacional UTBB</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= RUTA_BASE ?>/css/ev-base.css">
<?php if ($hojaModulo !== ''): ?>
<link rel="stylesheet" href="<?= e($hojaModulo) ?>">
<?php endif; ?>
</head>
<body<?= $claseArea !== '' ? ' class="' . $claseArea . '"' : '' ?>>

<a class="salto-contenido" href="#contenido">Saltar al contenido</a>

<header class="encabezado">
  <div class="contenedor encabezado__barra">
    <a class="marca" href="<?= RUTA_BASE ?>/">
      <span class="marca__distintivo" aria-hidden="true">EV</span>
      Explorador Vocacional
    </a>

    <nav aria-label="Principal">
      <ul class="menu">
        <li><a href="<?= RUTA_BASE ?>/">Inicio</a></li>
        <li><a href="<?= RUTA_BASE ?>/modulos/carreras/">Carreras</a></li>

        <?php foreach (MENU_GRUPOS as $grupo => $enlaces): ?>
        <li>
          <details class="menu__grupo">
            <summary><?= e($grupo) ?></summary>
            <ul class="menu__lista">
              <?php foreach ($enlaces as $carpeta => $texto): ?>
              <li><a href="<?= RUTA_BASE ?>/modulos/<?= e($carpeta) ?>/"><?= e($texto) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </details>
        </li>
        <?php endforeach; ?>

        <li><a href="<?= RUTA_BASE ?>/modulos/visitas/">Visítanos</a></li>

        <?php if ($u === null): ?>
        <li><a href="<?= RUTA_BASE ?>/auth/entrar.php">Entrar</a></li>
        <?php else: ?>
        <li><a href="<?= RUTA_BASE ?>/panel.php"><?= e($u['nombre']) ?></a></li>
        <li><a href="<?= RUTA_BASE ?>/auth/salir.php">Salir</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>

<main id="contenido">
<?php
}

/** Cierra <main> e imprime el pie. */
function pie(): void
{
    ?>
</main>

<footer class="pie">
  <div class="contenedor">
    <p class="sin-margen">
      Universidad Tecnológica de Bahía de Banderas ·
      Proyecto integrador del cuarto cuatrimestre ·
      Plataforma de datos de prueba
    </p>
  </div>
</footer>

</body>
</html>
<?php
}
