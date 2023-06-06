<?php
session_start();
$isBlocked = true;

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Sesión bloqueada</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login">
        <h1 class="text-center">¡Ups! Tu sesión está bloqueada.</h1>
        <p class="text-center">Por favor, espera un momento antes de intentarlo nuevamente.</p>
    </div>

    <script>
        // Obtener el tiempo restante en segundos para desbloquear los campos
        var remainingTime = 60;

        // Esperar el tiempo restante y recargar la página para desbloquear los campos
        setTimeout(function() {
            location.reload();
        }, remainingTime * 1000); // Multiplicar por 1000 para convertir los segundos en milisegundos
    </script>
</body>
</html>
