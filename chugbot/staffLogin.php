<?php
    session_start();
    include_once 'functions.php';
    include_once 'formItem.php';
    include_once 'dbConn.php';
    
    // If the user is already logged in, redirect.
    bouncePastIfLoggedIn("staffHome.php");
    
    // Check to see if there is an existing admin password.  If so, we'll
    // prompt the user for a password.  Otherwise, we will ask them to create
    // a new one, and to enter an email address.  Password changes will be
    // handled by a separate page - it's too complicated to squeeze all the logic
    // into this page.
    $forgotUrl = urlIfy("forgotPassword.php");
    $dbError = $staffPasswordErr = $staffPasswordErr2 = "";
    $existingPasswordHashed = "";
    $staffPasswordHashed = "";
    $db = new DbConn();
    $sql = "SELECT admin_password from admin_data";
    $result = $db->runQueryDirectly($sql, $dbError);
    if ($result == FALSE) {
        ;
    } else if ($result->num_rows > 1) {
        $dbError = dbErrorString($sql, "Bad row count for admin email and password");
    } else if ($result->num_rows == 1) {
        $row = mysqli_fetch_row($result);
        $existingPasswordHashed = $row[0];
    }
    
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
                    strlen($staff_password) > 255) {
                    $staffPasswordErr = errorString("Password must be between 5 and 255 characters");
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
                    $dbConn = new DbConn();
                    $dbConn->addColumn("admin_email", $staff_email, 's');
                    $dbConn->addColumn("admin_password", $staffPasswordHashed, 's');
                    if (empty($existingPasswordHashed)) {
                        $insertedOk = $dbConn->insertIntoTable("admin_data", $dbError);
                    } else {
                        $insertedOk = $dbConn->updateTable("admin_data", $dbError);
                    }
                    if ($insertedOk) {
                        // New password entered OK: log them in and redirect.  Note
                        // that staff privileges imply camper privileges.
                        $_SESSION['admin_logged_in'] = TRUE;
                        $_SESSION['camper_logged_in'] = TRUE;
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
                    "<p>If you forgot the password, please click <a href=\"$forgotUrl\">here</a>.</p>";
                    usleep(250000); // Sleep for 0.25 sec, to slow a dictionary attack.
                } else {
                    // New password entered OK: redirect.
                    $_SESSION['admin_logged_in'] = TRUE;
                    $_SESSION['camper_logged_in'] = TRUE;
                    header("Location: $redirUrl");
                    exit();
                }
            }
        }
    }
    
    ?>

<?php
    echo headerText("Staff Login");
    
    $errText = genFatalErrorReport(array($dbErr, $staffPasswordErr, $staffPasswordErr2));
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
        echo ("<p>Please enter the staff admin password.  If you forgot the password, please click <a href=\"$forgotUrl\">here</a>.</p>");
    }
    echo loginRequiredMessage();
    echo "After logging in, you will be $redirText";
    ?>
</div>
<ul>

<?php
    $liNum = 0;
    if (empty($existingPasswordHashed)) {
        $emailField = new FormItemSingleTextField("Staff Email Address", TRUE, "staff_email", $liNum++);
        $emailField->setInputValue($staff_email);
        $emailField->setInputType("text");
        $emailField->setInputClass("element text medium");
        $emailField->setInputMaxLength(20);
        $emailField->setPlaceHolder("leveling@campramahne.org");
        $emailField->setGuideText("Please enter an email for password change/retrieval. The person at this address should also be able to answer leveling questions from campers.");
        $emailField->setError($staffEmailErr);
        echo $emailField->renderHtml();
    }
    
    $staffPasswordField = new FormItemSingleTextField("Staff Password", TRUE, "staff_password", $liNum++);
    $staffPasswordField->setInputType("password");
    $staffPasswordField->setInputClass("element text medium");
    $staffPasswordField->setInputMaxLength(50);
    $staffPasswordField->setPlaceHolder(" ");
    $staffPasswordField->setGuideText("Enter a staff password.  This password protects the staff-only parts of the page, and should not be shared with campers.");
    echo $staffPasswordField->renderHtml();
    
    if (empty($existingPasswordHashed)) {
        $staffPasswordField2 = new FormItemSingleTextField("Retype Staff Password", TRUE, "staff_password2", $liNum++);
        $staffPasswordField2->setInputType("password");
        $staffPasswordField2->setInputClass("element text medium");
        $staffPasswordField2->setInputMaxLength(50);
        $staffPasswordField2->setPlaceHolder(" ");
        echo $staffPasswordField2->renderHtml();
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
</div>

<?php
    echo footerText();
?>

<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
