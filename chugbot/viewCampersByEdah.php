<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    $name = "";
    $dbErr = $nameErr = "";
    $camperId2Name = array();
    $mysqli = connect_db();
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = test_input($_POST["name"]);
        if (empty($name)) {
            $nameErr = errorString("Edah name is required in order to find campers in edah.");
        }
        // Grab the camper IDs in this edah.
        $sql = "SELECT c.camper_id camper_id, c.first first, c.last last FROM campers c, edot e " .
        "WHERE c.edah_id = e.edah_id and e.name=\"$name\";
        $result = $mysqli->query($sql);
        if ($result == FALSE) {
            $dbErr = dbErrorString($sql, $mysqli->error);
        } else {
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $camperId2Name[$row[0]] = "$row[2], $row[1]";
            }
        }
    }

    $mysqli->close();
    ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Campers for $name</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php
    $errText = genFatalErrorReport(array($dbErr, $nameErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<body id="main_body" >

<div id="centered_container">
<h1>View Campers</a></h1>
<h2>Campers for $name</h2>
<p>This page lists the campers in edah $name who have entered preferences in this system.  To update
information or settings for a camper, click the camper's name.  To return to the staff admin
page, please click <?php echo staffHomeAnchor(); ?>.</p>
</div>

<div id="text_container">
<?php
    if (count($camperId2Name) == 0) {
        echo "<h3>No campers were found in the system for edah $name</h3>";
    }
    arsort($camperId2Name);
    foreach ($camperId2Name as $camperId => $camperName) {
        $editUrl = urlIfy("editCamper.php?cid=$camperId");
        echo "<a href=\"$editUrl\">$camperName</a><br>";
    }
    ?>
</div>

<div id="footer">
<?php
    echo footerText();
    ?>
</div>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>





