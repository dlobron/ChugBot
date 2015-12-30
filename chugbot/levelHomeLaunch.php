<?php
    session_start();
    include 'functions.php';
    bounceToLogin();

    $levelHomeUrl = urlIfy("levelHome.html");
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $edah = test_input($_POST["edah"]);
        $block = test_input($_POST["block"]);
        $levelHomeUrl .= "?edah=$edah&block=$block";
    }
    header("Location: $levelHomeUrl");
    exit;
    ?>
    
