<?php
declare(strict_types=1);

function ase_pintar_hilo(array $mensajes, string $rutaDescarga, bool $gestionarFaq = false): void
{
    ?>
    <section class="ase-hilo mb-6" aria-labelledby="titulo-hilo">
      <h2 id="titulo-hilo">Historial de mensajes (<?= e((string)count($mensajes)) ?>)</h2>

      <?php foreach ($mensajes as $m): ?>
        <?php $esOrientador = (int)$m['es_orientador'] === 1; ?>
        <article class="panel mb-4 <?= $esOrientador ? 'ase-mensaje--orientador' : 'ase-mensaje--aspirante' ?>">
          <div class="fila fila--separada mb-2">
            <div class="fila">
              <strong><?= e($m['autor_nombre']) ?></strong>
              <span class="insignia <?= $esOrientador ? 'insignia--publicado' : 'insignia--borrador' ?>">
                <?= $esOrientador ? 'Orientador UTBB' : 'Aspirante' ?>
              </span>
            </div>
            <time class="leyenda" datetime="<?= e(str_replace(' ', 'T', $m['creado_en'])) ?>">
              <?= e(date('d/m/Y H:i', strtotime($m['creado_en']))) ?>
            </time>
          </div>

          <p class="sin-margen"><?= nl2br(e($m['cuerpo'])) ?></p>

          <?php if ($m['adjuntos']): ?>
            <div class="ase-adjuntos mt-2">
              <small class="leyenda">Archivos adjuntos</small>
              <ul>
                <?php foreach ($m['adjuntos'] as $adjunto): ?>
                  <li>
                    <a href="<?= e($rutaDescarga . '?id=' . $adjunto['id']) ?>">Descargar <?= e($adjunto['nombre_original']) ?></a>
                    <small class="leyenda">(<?= e((string)round((int)$adjunto['tamano'] / 1024, 1)) ?> KB)</small>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <?php if ($gestionarFaq && $esOrientador): ?>
            <div class="mt-4">
              <?php if ($m['publicado_faq_id'] === null): ?>
                <a class="boton boton--secundario boton--pequeno"
                   href="faq_form.php?mensaje=<?= e((string)$m['id']) ?>">Convertir en pregunta frecuente</a>
              <?php else: ?>
                <a class="boton boton--secundario boton--pequeno"
                   href="faq_form.php?id=<?= e((string)$m['publicado_faq_id']) ?>">Ver pregunta frecuente vinculada</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
    <?php
}
