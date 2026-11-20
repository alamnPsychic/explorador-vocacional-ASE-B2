<?php
declare(strict_types=1);

require_once __DIR__ . '/consultas.php';
require_once __DIR__ . '/entrada.php';

function ase_valor_orden(string $valor): ?int
{
    $orden = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 255]]);
    return $orden === false ? null : $orden;
}

function ase_admin_navegacion(string $actual): void
{
    $enlaces = ['bandeja' => ['index.php', 'Bandeja']];
    if (rol() === 'coordinacion') {
        $enlaces['faq'] = ['faq.php', 'Preguntas frecuentes'];
        $enlaces['categorias'] = ['categorias.php', 'Categorías'];
    }
    ?>
    <nav class="fila mb-6" aria-label="Administración de asesoría">
      <?php foreach ($enlaces as $clave => [$ruta, $texto]): ?>
        <a class="boton <?= $clave === $actual ? 'boton--primario' : 'boton--secundario' ?>"
           href="<?= e($ruta) ?>"<?= $clave === $actual ? ' aria-current="page"' : '' ?>><?= e($texto) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php
}
