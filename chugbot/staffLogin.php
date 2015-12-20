<?php
    session_start();
    include 'functions.php';
    // If the user is already logged in, redirect.
    bouncePastIfLoggedIn("staffHome.php");
    
    // Check to see if there is an existing admin password.  If so, we'll
    // prompt the user for a password.  Otherwise, we will ask them to create
    // a new one, and to enter an email address.  Password changes will be
    // handled by a separate page - it's too complicated to squeeze all the logic
    // into this page.
    $resetUrl = urlBaseText() . "staffReset.php";
    $dbError = "";
    $existingPasswordHashed = "";
    $staffPasswordHashed = "";
    $mysqli = connect_db();
    $sql = "SELECT admin_password from admin_data";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbError = dbErrorString($sql, $mysqli->error);
    } else if ($result->num_rows > 1) {
        $dbError = dbErrorString($sql, "Bad row count for admin email and password");
    } else if ($result->num_rows == 1) {
        $row = mysqli_fetch_row($result);
        $existingPasswordHashed = $row[0];
    }
    mysqli_free_result($result);
    $mysqli->close();
    
    $staffEmailErr = $staffPasswordErr = $staffPasswordErr2 = "";
    
    // Note the redirect text and destination.  The default is the staff home page,
    // but if there is a "from" query string, we redirect back to that page.
    $redirUrl = staffBounceBackUrl();
    $redirText = "directed to the camp staff home page.";
    if (fromBounce()) {
        $redirText = "redirected.";
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        $staff_email = test_input($_POST["staff_email"]);
        $staff_password = test_input($_POST["staff_password"]);
        $staff_password2 = test_input($_POST["staff_password2"]);
        
        if (empty(test_input($_POST["staffInit"]))) {
            // If we have POST data, we validate it, and update as needed.
            if (empty($staff_password)) {
                $staffPasswordErr = errorString("Please enter a staff password");
            }
            if (empty($existingPasswordHashed)) {
                // If we don't have an existing password, then we want to insert
                // the incoming data, provided there are no errors.
                if (strlen($staff_password) < 5 ||
                    strlen($staff_password) > 20) {
                    $staffPasswordErr = errorString("Password must be between 5 and 20 characters");
                } else {
                    $staffPasswordHashed = password_hash($staff_password, PASSWORD_DEFAULT);
                }
                if ($staff_password2 != $staff_password) {
                    // The repeated password must match the first.
                    $staffPasswordErr2 = errorString("Passwords do not match");
                }
                if (empty($staff_email)) {
                    // A valid email address is required for new password setup.
                    $staffEmailErr = errorString("No email address was submitted.");
                } else if (! filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
                    $staffEmailErr = errorString("\"$staff_email\" is not a valid email address.");
                }
                if (empty($staffEmailErr) &&
                    empty($staffPasswordErr) &&
                    empty($staffPasswordErr2)) {
                    // No errors: insert the new password and email, and then redirect
                    // to the admin home page.
                    $mysqli = connect_db();
                    $sql = "";
                    if (empty($existingPasswordHashed)) {
                        $sql = "INSERT INTO admin_data (admin_email, admin_password) VALUES " .
                        "(\"$staff_email\", \"$staffPasswordHashed\")";
                    } else {
                        $sql = "UPDATE admin_data SET admin_email = \"$staff_email\", admin_password = \"$staffPasswordHashed\"";
                    }
                    $result = $mysqli->query($sql);
                    if ($result == FALSE) {
                        $dbError = dbErrorString($sql, $mysqli->error);
                    } else {
                        // New password entered OK: redirect.
                        $_SESSION['admin_logged_in'] = TRUE;
                        header("Location: $redirUrl");
                        exit();
                    }
                }
            } else if (empty($staffPasswordErr)) {
                // If we have an existing password, we just need to hash it
                // and match it against the database version, which is already hashed.
                // If they match, we set $_SESSION['admin_logged_in'] = TRUE;, and redirect
                // to the admin home page.
                if (! password_verify($staff_password, $existingPasswordHashed)) {
                    $staffPasswordErr = errorString("Password does not match - please try again.") .
                    "<p>If you forgot the password, please click <a href=\"$resetUrl\">here</a> to reset it.</p>";
                    usleep(500000); // Sleep for 0.5 sec, to slow a dictionary attack.
                } else {
                    // New password entered OK: redirect.
                    $_SESSION['admin_logged_in'] = TRUE;
                    header("Location: $redirUrl");
                    exit();
                }
            }
        }
    }
    
    ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Admin Staff Login</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<body id="main_body" >

<?php
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Staff Login</a></h1>

<form id="loginForm" class="appnitro"  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Admin Staff Login</h2>
<?php
    if (empty($existingPasswordHashed)) {
        echo ("<p>Please create a staff password, and enter an email in case you need to reset or change the password.<br>");
        echo ("The password should be between 5 and 20 characters.</p>");
    } else {
        echo ("<p>Please enter the staff admin password.  To change the existing password, or if you forgot the password, please click <a href=\"$resetUrl\">here</a>.</p>");
    }
    echo loginRequiredMessage();
    echo "After logging in, you will be $redirText";
    ?>
</div>
<ul>

<?php
    if (empty($existingPasswordHashed)) {
        $emailSection = <<<HERE
<li>
<div>
<input id="staff_email" name="staff_email" class="element text medium" maxlength=20 value="$staff_email" type="text">
<label for="staff_email">Staff email</label>
<span class="error">$staffEmailErr</span>
<p class="guidelines" id="email_guide"><small>Please enter an email for password change/retrieval.</small></p>
</div>
</li>
HERE;
        echo $emailSection;
    }
?>

<li>
<div>
<input id="staff_password" name="staff_password" class="element text medium" maxlength=50 type="password">
<label for="staff_password">Admin password</label>
<span class="error"><?php echo $staffPasswordErr; ?></span>
</div>
</li>

<?php
    if (empty($existingPasswordHashed)) {
        $existingPasswordSection = <<<HERE
<li>
<div>
<input id="staff_password2" name="staff_password2" class="element text medium" maxlength=50 type="password">
<label for="staff_password2">Retype admin password</label>
<span class="error">$staffPasswordErr2</span>
</div>
</li>
HERE;
        echo $existingPasswordSection;
    }
?>

<li class="buttons">
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
</li>
</ul>
<?php
    // Send the query string back, so that we can redirect back if we were sent
    // here by a login bounce.
    $qs = htmlspecialchars($_SERVER['QUERY_STRING']);
    ?>
<input type="hidden" name="query_string" id="query_string" value=\"<?php echo $qs; ?>\" />
</form>

<div id="footer">
<?php
    echo footerText();
?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
