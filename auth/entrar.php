<?php
/**
 * NÚCLEO · Inicio de sesión
 * Explorador Vocacional UTBB
 *
 * Este archivo es parte del núcleo. NO SE MODIFICA.
 *
 * Es también el ejemplo de referencia del ciclo completo:
 * formulario → POST → validación en servidor → mensaje → redirección.
 */

declare(strict_types=1);

require_once __DIR__ . '/../nucleo/comun.php';

// Si ya hay sesión, no tiene caso volver a entrar.
if (hay_sesion()) {
    redirigir(RUTA_BASE . '/panel.php');
}

$correo  = '';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verificar_token();

    $correo     = post('correo');
    $contrasena = post('contrasena');

    // Validación en el SERVIDOR. La del navegador solo mejora la experiencia.
    if ($correo === '') {
        $errores['correo'] = 'Escribe tu correo.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = 'El correo no tiene un formato válido.';
    }

    if ($contrasena === '') {
        $errores['contrasena'] = 'Escribe tu contraseña.';
    }

    if (!$errores) {
        if (intentar_entrar($correo, $contrasena)) {
            $destino = $_SESSION['destino'] ?? RUTA_BASE . '/panel.php';
            unset($_SESSION['destino']);

            mensaje('exito', 'Bienvenido, ' . usuario()['nombre'] . '.');
            redirigir($destino);          // POST-Redirect-GET
        }

        // Un solo mensaje para los dos casos posibles.
        $errores['general'] = 'Correo o contraseña incorrectos.';
    }
}

cabecera('Iniciar sesión');
?>

<section class="contenedor seccion">

  <h1>Iniciar sesión</h1>
  <p class="entradilla">Acceso para editores de carrera y personal de coordinación.</p>

  <?php pintar_mensajes(); ?>

  <?php if (isset($errores['general'])): ?>
    <div class="alerta alerta--error" role="alert">
      <p><span class="alerta__titulo">No se pudo iniciar sesión.</span> <?= e($errores['general']) ?></p>
    </div>
  <?php endif; ?>

  <form class="formulario" method="POST" action="">
    <?= campo_token() ?>

    <div class="campo <?= isset($errores['correo']) ? 'campo--invalido' : '' ?>">
      <label class="campo__etiqueta" for="correo">
        Correo electrónico <span class="campo__requerido" aria-hidden="true">*</span>
      </label>
      <input class="campo__control" type="email" id="correo" name="correo"
             value="<?= e($correo) ?>" required autocomplete="username"
             <?= isset($errores['correo']) ? 'aria-describedby="correo-error"' : '' ?>>
      <?php if (isset($errores['correo'])): ?>
        <small class="campo__error" id="correo-error"><?= e($errores['correo']) ?></small>
      <?php endif; ?>
    </div>

    <div class="campo <?= isset($errores['contrasena']) ? 'campo--invalido' : '' ?>">
      <label class="campo__etiqueta" for="contrasena">
        Contraseña <span class="campo__requerido" aria-hidden="true">*</span>
      </label>
      <input class="campo__control" type="password" id="contrasena" name="contrasena"
             required autocomplete="current-password"
             <?= isset($errores['contrasena']) ? 'aria-describedby="contrasena-error"' : '' ?>>
      <?php if (isset($errores['contrasena'])): ?>
        <small class="campo__error" id="contrasena-error"><?= e($errores['contrasena']) ?></small>
      <?php endif; ?>
    </div>

    <button class="boton boton--primario boton--bloque" type="submit">Entrar</button>
  </form>

  <?php if (DESARROLLO): ?>
    <p class="leyenda mt-6">
      Ambiente de desarrollo. ¿No tienes cuenta?
      <a href="<?= RUTA_BASE ?>/herramientas/crear-usuarios.php">Crear los usuarios de prueba</a>.
    </p>
  <?php endif; ?>

</section>

<?php
pie();
