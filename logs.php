<?php
require_once "config.php"; 
  function write_login_log($email, $error, $validation) {
    global $pdo;
    $resultado = $error ? "FALLO" : "ÉXITO";
    date_default_timezone_set(timezoneId: 'America/Mexico_City');
    $fecha_hora = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $navegador = $_SERVER['HTTP_USER_AGENT'];
    $SO = php_uname('s') . ' ' . php_uname('r');
    $log = "$resultado|$fecha_hora|$email|$ip|$navegador|$SO|$validation\n";
    
   
    $stmt = $pdo->prepare("INSERT INTO monitoreo (resultado, fecha_hora, email, ip, navegador, SO, validation) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$resultado, $fecha_hora, $email, $ip, $navegador, $SO, $validation]);
    
 
    file_put_contents('log.txt', $log, FILE_APPEND);
}

?>