<?php
    session_start();
    include 'functions.php';
    
    // Get the time blocks for a camper, and the chugim in each.
    if (isset($_POST["rank_page_camper_id"])) {
        $camper_id = test_input($_POST["rank_page_camper_id"]);
        
    
