<?php
define('DBServer', 'localhost');
define('DBUsuario', 'root');
define('DBContraseña', 'elisa635');
define('DBNombre', 'dbinicio');

try {
    $pdo = new PDO("mysql:host=".DBServer.";dbname=".DBNombre, DBUsuario, DBContraseña);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Error de conexión a la base de datos: " . $e->getMessage();
    exit();
}
?>