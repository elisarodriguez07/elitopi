<?php
    //inicio de Secion
    session_start();

    //simel usuario ya inicio secion rtedireccionarlo a la pagina de bienvenida
    if(isset($_SESSION['userid']) && $_SESSION["userid"] == true){
        header("Location: welcome.php");
        exit;
    }
?>