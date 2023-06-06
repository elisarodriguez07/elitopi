<?php

require_once "config.php";
require_once "session.php";
require_once "logs.php";

$error = '';
$disabled = '';
$isBlocked = false;
$error_message = '';


if (isset($_SESSION['block_time']) && (time() - $_SESSION['block_time']) < 60) {
    header("Location: block_page.php");
    exit;
} else {
    unset($_SESSION['block_time']);
}


if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'Token CSRF no válido.';
    }

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response'])) {
        $id_secret = "6LcFR_wlAAAAAFAr_pxgMvlTwfUljZyRry96mPxG";
        $response = $_POST['g-recaptcha-response'];
        $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$id_secret}&response={$response}");
        $captcha_success = json_decode($verify);
        if ($captcha_success->success == false) {
            $error .= '<p class="error">Captcha no válido</p>';
            write_login_log($email, true, "Captcha inválido");
        }
    } else {
        $error .= '<p class="error">Por favor, valide el captcha!</p>';
        write_login_log($email, true, "Captcha no validado");
    }

    if (empty($email)) {
        $error .= '<p class="error">Por favor, ingrese su correo electrónico!</p>';
        write_login_log($email, true, "Email vacío");
    }

    if (empty($password)) {
        $error .= '<p class="error">Por favor, ingrese su contraseña!</p>';
        write_login_log($email, true, "Contraseña vacía");
    }

    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");

        $stmt->bindParam(1, $email);
        $stmt->execute();
        $row = $stmt->fetch();

        if ($row) {
            $maxAttempts = 3;
            $waitTime = 60;

            if ($row['login_attempts'] >= $maxAttempts && (time() - strtotime($row['last_attempt'])) < $waitTime) {
                $error .= '<p class="error">Has excedido el número máximo de intentos de inicio de sesión. Por favor, espera un momento antes de intentarlo nuevamente.</p>';
                $isBlocked = true;
                $_SESSION['block_time'] = time();

                $disabled = 'disabled';

                // Registrar el bloqueo en los registros
                write_login_log($email, true, "Bloqueo de cuenta por múltiples intentos fallidos");
                

            } else {
                $isBlocked = false;

                if (password_verify($password, $row['password'])) {
                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, last_attempt = NULL WHERE id = ?");
                    $stmt->bindParam(1, $row['id']);
                    $stmt->execute();

                    $_SESSION['userid'] = $row['id'];
                    $_SESSION['user'] = $row;
                    write_login_log($email, false, "");


                    if ($row['rol'] === 'admin') {
                        header("Location: welcomeadmin.php");
                        
                    }elseif ($row['rol'] === 'usuario') {
                        header("Location: welcomeusuario.php");
                        
                    }else{
                        header("Location: welcomeusuario.php");
                    }
                    exit;

                } else {
                    $loginAttempts = $row['login_attempts'] + 1;
                    date_default_timezone_set('America/Mexico_City');
                    $lastAttempt = date('Y-m-d H:i:s');

                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = ?, last_attempt = ? WHERE id = ?");
                    $stmt->bindParam(1, $loginAttempts);
                    $stmt->bindParam(2, $lastAttempt);
                    $stmt->bindParam(3, $row['id']);
                    $stmt->execute();

                    if ($loginAttempts >= $maxAttempts) {
                        $error .= '<p class="error">Has excedido el número máximo de intentos de inicio de sesión. Por favor, intenta nuevamente después de un minuto.</p>';
                        $isBlocked = true;
                        $disabled = 'disabled';

                        // Registrar el bloqueo en los registros
                        write_login_log($email, true, "Bloqueo de cuenta por múltiples intentos fallidos");


                        date_default_timezone_set('America/Mexico_City');
                        $lastAttempt = date('Y-m-d H:i:s');

                        $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, last_attempt = ? WHERE id = ?");
                        $stmt->bindParam(1, $lastAttempt);
                        $stmt->bindParam(2, $row['id']);
                        $stmt->execute();

                        $_SESSION['block_time'] = time();

                    } else {

                        if ($row['name'] === "No registrado") {
                            // El usuario no existe
                            $error .= '<p class="error">Usuario no registrado!</p>';
                            write_login_log($email, true, "Usuario no registrado");
                        } else {
                            // Contraseña incorrecta
                                $error .= '<p class="error">Contraseña no válida!</p>';
                                write_login_log($email, true, "Contraseña no válida");
                        }

                        if ($loginAttempts == ($maxAttempts - 1)) {
                            $error .= '<p class="error">Este es tu último intento antes de que se bloquee tu cuenta.</p>';
                        }
                    }

                }
            }
        } else {
            $error .= '<p class="error">No se encontró un usuario asociado al correo!</p>';
            write_login_log($email, true, "Usuario no encontrado");

            $maxAttempts = 3;
            $waitTime = 60;

            // No se encontró un usuario asociado al correo
            $isRegistered = 0; // Usuario no registrado
            $loginAttempts = 1;
            date_default_timezone_set('America/Mexico_City');
            $lastAttempt = date('Y-m-d H:i:s');
            $name = "No registrado"; // Nombre del usuario no registrado
            $password = '';


            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, login_attempts, last_attempt, is_registered) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bindParam(1, $name);
            $stmt->bindParam(2, $email);
            $stmt->bindParam(3, $passwordHash);
            $stmt->bindParam(4, $loginAttempts);
            $stmt->bindParam(5, $lastAttempt);
            $stmt->bindParam(6, $isRegistered);
            $stmt->execute();

            if ($loginAttempts >= $maxAttempts) {
                $error .= '<p class="error">Has excedido el número máximo de intentos de inicio de sesión. Por favor, espera un momento antes de intentarlo nuevamente.</p>';
                $isBlocked = true;
                $disabled = 'disabled';
            } else {
                // Registrar el intento fallido en los registros
                write_login_log($email, true, "Usuario no registrado");

                // Almacenar el intento fallido en la tabla users
                $stmt = $pdo->prepare("UPDATE users SET login_attempts = ?, last_attempt = ? WHERE id = ?");
                $stmt->bindParam(1, $loginAttempts);
                $stmt->bindParam(2, $lastAttempt);
                $stmt->bindParam(3, $row['id']);
                $stmt->execute();
            }

        }
    }
}

$_SESSION['isBlocked'] = $isBlocked;
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Sing in</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="login">
        <h1 class="text-center">¡Hola otra vez!</h1>

        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <form class="needs-validation" method="post" action="">
            <div class="form-group was-validated">
                <label class="form-label" for="email">Correo Electrónico</label>
                <input class="form-control" type="email" id="email" required name="email" <?php echo $disabled; ?>>
                <div class="invalid-feedback">
                    Por favor ingresa tu correo electrónico
                </div>
            </div>
            <div class="form-group was-validated">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" required name="password" <?php echo $disabled; ?>>
                <div class="invalid-feedback">
                    Por favor ingresa tu contraseña
                    <?php echo $error; ?>
                </div>
            </div>
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group form-check">
                <input class="form-check-input" type="checkbox" id="check">
                <label class="form-check-label" for="check">Recuérdame</label>
                <div class="g-recaptcha" data-sitekey="6LcFR_wlAAAAANfPAHdvzv6VrJjizxFdEwb6APGI"></div>
            </div>
            <input class="btn btn-success w-100" type="submit" name="submit" value="SIGN IN"<?php echo $disabled; ?>>
            <a href="register.php">¿Aún no tienes una cuenta? Crea una aquí</a>
        </form>
    </div>
</body>

</html>

