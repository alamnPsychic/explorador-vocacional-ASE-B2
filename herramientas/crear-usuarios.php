<?php
/**
 * HERRAMIENTA DE DESARROLLO · Usuarios de prueba
 * Explorador Vocacional UTBB
 *
 * Crea una cuenta de coordinación y una de editor por cada carrera.
 * Todas con la contraseña: Demo1234
 *
 *   http://localhost/explorador-vocacional/herramientas/crear-usuarios.php
 *
 * Este archivo NO se publica en internet. Se ejecuta solo en desarrollo.
 *
 * Observa por qué las contraseñas no se siembran desde el .sql: hay que
 * pasarlas por password_hash(), que es una función de PHP. Guardar la
 * contraseña tal cual en la base es falta grave en la rúbrica de la materia.
 */

declare(strict_types=1);

require_once __DIR__ . '/../nucleo/comun.php';

if (!DESARROLLO) {
    http_response_code(403);
    exit('Esta herramienta solo funciona en desarrollo.');
}

const CONTRASENA_DEMO = 'Demo1234';

$hash = password_hash(CONTRASENA_DEMO, PASSWORD_DEFAULT);

$sql = 'INSERT INTO usuarios (nombre, correo, contrasena, rol, carrera_id)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE contrasena = VALUES(contrasena), activo = 1';

$con = bd()->prepare($sql);

$creados = [];

// Coordinación
$con->execute(['Coordinación de la plataforma', 'coordinacion@utbb.mx', $hash, 'coordinacion', null]);
$creados[] = ['coordinacion@utbb.mx', 'coordinacion', '—'];

// Aspirante de prueba
$con->execute(['Aspirante de prueba', 'aspirante@ejemplo.mx', $hash, 'aspirante', null]);
$creados[] = ['aspirante@ejemplo.mx', 'aspirante', '—'];

// Un editor por carrera
foreach (carreras() as $c) {
    $correo = strtolower($c['clave']) . '@utbb.mx';
    $con->execute(['Editor de ' . $c['nombre'], $correo, $hash, 'editor', $c['id']]);
    $creados[] = [$correo, 'editor', $c['nombre']];
}

cabecera('Usuarios de prueba');
?>
<section class="contenedor seccion">
  <h1>Usuarios de prueba</h1>
  <div class="alerta alerta--exito" role="status">
    <p><span class="alerta__titulo">Listo.</span>
       Se crearon o actualizaron <?= count($creados) ?> cuentas.
       La contraseña de todas es <code><?= e(CONTRASENA_DEMO) ?></code>.</p>
  </div>

  <div class="tabla-envoltura">
    <table class="tabla">
      <caption>Cuentas disponibles</caption>
      <thead>
        <tr><th scope="col">Correo</th><th scope="col">Rol</th><th scope="col">Carrera</th></tr>
      </thead>
      <tbody>
      <?php foreach ($creados as [$correo, $rol, $carrera]): ?>
        <tr><td><?= e($correo) ?></td><td><?= e($rol) ?></td><td><?= e($carrera) ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="mt-6"><a class="boton boton--primario" href="<?= RUTA_BASE ?>/auth/entrar.php">Ir a iniciar sesión</a></p>
</section>
<?php
pie();
