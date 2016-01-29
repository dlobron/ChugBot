<?php
    session_start();
    include 'functions.php';
    
    // Grab the existing admin email from the database.  This is presumed to exist, since
    // this is a reset page.
    $gotPostData = FALSE;
    $existingEmail = "";
    $dbError = "";
    $mysqli = connect_db();
    $sql = "SELECT admin_email from admin_data";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbError = dbErrorString($sql, $mysqli->error);
    } else if ($result->num_rows != 1) {
        $dbError = dbErrorString($sql, "Bad row count for admin email");
    } else {
        $row = mysqli_fetch_row($result);
        $existingEmail = $row[0];
    }
    mysqli_free_result($result);
    $mysqli->close();
    
    $staffEmailErr = $staffPasswordErr = $staffPasswordErr2 = $existingEmailErr = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $gotPostData = TRUE;
        $staff_email_verify = test_input($_POST["staff_email_verify"]);
        $staff_email = test_input($_POST["staff_email"]);
        $staff_password = test_input($_POST["staff_password"]);
        $staff_password2 = test_input($_POST["staff_password2"]);
        $redirUrl = urlBaseText() . "staffHome.php"; // Redir for successful email/pw change.

        if (! empty($staff_email_verify)) {
            // If we got an email to verify, we just need to verify that it matches.  If it does,
            // we will display fields to enter a new password (twice), and optionally a new
            // email (default to the current email value, filled in but editable).  If the email
            // doesn't match, display a mismatch error so they can try again, but don't display
            // the change fields.
            if ($existingEmail == $staff_email_verify) {
                $_SESSION['admin_email_verified'] = "yes";
            } else {
                $staffEmailErr = errorString("$staff_email_verify does not match the admin email address.");
            }
        } else {
            // If there is no verify email set, it means the user is submitting a new password and
            // possibly a new email.  We need to validate the email, and make sure that the new
            // password matches the retyped version.  If the checks pass, we hash the new password
            // and insert the email and hashed password into the DB.  Once that is done, we redirect
            // to the staff home page.  If we have an error, we set the appropriate error and
            // let the user try again.
            if (strlen($staff_password) < 5 ||
                strlen($staff_password) > 20) {
                $staffPasswordErr = errorString("Password must be between 5 and 20 characters");
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
                empty($staffPasswordErr2) &&
                empty($dbError)) {
                // No errors: insert the new password and email, and then redirect
                // to the admin home page.
                $staffPasswordHashed = password_hash($staff_password, PASSWORD_DEFAULT);
                $mysqli = connect_db();
                $sql = "UPDATE admin_data SET admin_email = \"$staff_email\", admin_password = \"$staffPasswordHashed\"";
                $result = $mysqli->query($sql);
                if ($result == FALSE) {
                    $dbError = dbErrorString($sql, $mysqli->error);
                } else {
                    // New password entered OK: go to the home page.
                    $_SESSION['admin_logged_in'] = TRUE;
                    header("Location: $redirUrl");
                    exit();
                }
            }
        }
    }
    
    ?>

<?php
    echo headerText("Change Admin Password and Email");
    
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Change Admin Password and Email</a></h1>

<form id="loginForm" class="appnitro"  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Change Admin Password and Email</h2>
<?php
    $onlyDisplayEmail = FALSE;
    if (isset($_SESSION['admin_email_verified'])) {
        echo ("<p>Please enter and confirm your new password.  You may also change the admin email address.<br>");
        echo "After updating successfully, you will be directed to the camp staff home page";
    } else {
        echo ("<p>Please enter the staff email address to begin.</p>");
        $onlyDisplayEmail = TRUE;
    }
    ?>
</div>
<ul>

<?php
    // Display the email field.  Set the field ID accoding to whether we've verified the
    // email yet.
    $fieldId = "";
    if ($onlyDisplayEmail) {
        $fieldId = "staff_email_verify";
    } else {
        $fieldId = "staff_email";
    }
    $emailSection = <<<HERE
<li>
<div>
<input id="$fieldId" name="$fieldId" class="element text medium" maxlength=20 value="$staff_email" type="text">
<label for="staff_email">Staff email</label>
<span class="error">$staffEmailErr</span>
<p class="guidelines" id="email_guide"><small>Please enter or update the admin email address.</small></p>
</div>
</li>
HERE;
    echo $emailSection;
?>

<?php
    $newPasswordSection = <<<HERE
<li>
<div>
<input id="staff_password" name="staff_password" class="element text medium" maxlength=50 type="password">
<label for="staff_password">New admin password</label>
<span class="error">$staffPasswordErr</span>
</div>
</li>
HERE;
    if (isset($_SESSION['admin_email_verified'])) {
        echo $newPasswordSection;
    }

    $existingPasswordSection = <<<HERE
<li>
<div>
<input id="staff_password2" name="staff_password2" class="element text medium" maxlength=50 type="password">
<label for="staff_password2">Retype new password</label>
<span class="error">$staffPasswordErr2</span>
</div>
</li>
HERE;
    if (isset($_SESSION['admin_email_verified'])) {
        echo $existingPasswordSection;
    }
?>

<li class="buttons">
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
</li>
</ul>
</form>

<div id="footer">
<?php
    echo footerText();
?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
