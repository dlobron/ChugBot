<?php
    session_start();
    include 'functions.php';
    bounceToLogin();
    
    // Grab the existing admin email from the database.  This is presumed to exist, since
    // this is a reset page.
    $existingAdminEmail = $existingAdminEmailUserName = $existingAdminEmailPassword = $admin_email = $existingRegularUserToken = "";
    $dbErr = $staffPasswordErr = $staffPasswordErr2 = "";
    $mysqli = connect_db();
    $sql = "SELECT * from admin_data";
    $result = $mysqli->query($sql);
    if ($result == FALSE) {
        $dbError = dbErrorString($sql, $mysqli->error);
    } else if ($result->num_rows != 1) {
        $dbError = dbErrorString($sql, "Bad row count for admin data");
    } else {
        $row = mysqli_fetch_assoc($result);
        $existingAdminEmail = $row["admin_email"];
        $existingAdminEmailUserName = $row["admin_email_username"];
        $existingAdminEmailPassword = $row["admin_email_password"];
        $existingRegularUserToken = $row["regular_user_token"];
        
        // Set the admin email and password to current values.  These will be
        // clobbered if we have incoming POST data - otherwise, we'll display them
        // on the initial page.
        $admin_email = $existingAdminEmail;
        $admin_email_username = $existingAdminEmailUserName;
        $admin_email_password = $existingAdminEmailPassword;
        $regular_user_token = $existingRegularUserToken;
    }
    mysqli_free_result($result);
    $mysqli->close();
    
    $staffEmailErr = $staffPasswordErr = $staffPasswordErr2 = $existingEmailErr = "";
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $admin_email = test_input($_POST["admin_email"]);
        $staff_password = test_input($_POST["staff_password"]);
        $staff_password2 = test_input($_POST["staff_password2"]);
        $redirUrl = urlBaseText() . "staffHome.php"; // Redir for successful email/pw change.
        $admin_email_username = test_input($_POST["admin_email_username"]);
        $admin_email_password = test_input($_POST["admin_email_password"]);
        $regular_user_token = test_input($_POST["regular_user_token"]);
        // For email values and token, fill in existing values unless new ones were specified.
        if (is_null($admin_email_username)) {
            $admin_email_username = $existingAdminEmailUserName;
        } 
        if (is_null($admin_email_password)) {
            $admin_email_password = $existingAdminEmailPassword;
        }
        if (is_null($regular_user_token)) {
            $regular_user_token = $existingRegularUserToken;
        }
        
        // Grab the data to update.  We only update the staff email and password
        // if they were explictly set in the form.
        $sql = "UPDATE admin_data SET admin_email_username = \"$admin_email_username\", " .
        "admin_email_password=\"$admin_email_password\", regular_user_token=\"$regular_user_token\"";
        if ($staff_password) {
            if (strlen($staff_password) < 5 ||
                strlen($staff_password) > 20) {
                $staffPasswordErr = errorString("Password must be between 5 and 20 characters");
            }
            if ($staff_password2 != $staff_password) {
                // The repeated password must match the first.
                $staffPasswordErr2 = errorString("Passwords do not match");
            }
            $staffPasswordHashed = password_hash($staff_password, PASSWORD_DEFAULT);
            $sql .= ", admin_password = \"$staffPasswordHashed\"";
        }
        if ($admin_email) {
            if (! filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $staffEmailErr = errorString("\"$admin_email\" is not a valid email address.");
            }
            $sql .= ", admin_email = \"$admin_email\"";
        }
        if (empty($staffEmailErr) &&
            empty($staffPasswordErr) &&
            empty($staffPasswordErr2) &&
            empty($dbError)) {
            // No errors: insert the new/updated data, and then redirect
            // to the admin home page.
            $mysqli = connect_db();
            $result = $mysqli->query($sql);
            if ($result == FALSE) {
                $dbError = dbErrorString($sql, $mysqli->error);
            } else {
                // New data entered OK: go to the home page.  If a password was
                // validated, log the user in.
                if ($staff_password) {
                    $_SESSION['admin_logged_in'] = TRUE;
                }
                header("Location: $redirUrl");
                exit();
            }
        }
    }
    
    ?>

<?php
    echo headerText("Change Admin Password and Email");
    
    $errText = genFatalErrorReport(array($dbErr, $staffPasswordErr, $staffPasswordErr2, $staffEmailErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<img id="top" src="images/top.png" alt="">
<div class="form_container">

<h1><a>Edit Admin Data</a></h1>

<form id="loginForm" class="appnitro"  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Admin Data</h2>
<p>Please update the staff admin data as needed. For more information about a field, hover over that field.<br>
After updating successfully, you will be directed to the camp staff home page.
</p>
</div>
<ul>

<?php
    $updateFieldSection = <<<HERE
<li>
<div>
<input id="admin_email" name="admin_email" class="element text medium" maxlength=20 value="$admin_email" type="text">
<label for="admin_email">Staff email</label>
<span class="error">$staffEmailErr</span>
<p class="guidelines" id="email_guide"><small>Please enter or update the admin email address.</small></p>
</div>
</li>

<li>
<div>
<input id="admin_email_username" name="admin_email_username" class="element text medium" maxlength=50 value="$admin_email_username" type="text">
<label for="admin_email_username">Admin Email Account User Name</label>
<p class="guidelines" id="admin_email_guide"><small>Enter the username for the staff email account (this is often the same as the address).</small></p>
</div>
</li>

<li>
<div>
<input id="admin_email_password" name="admin_email_password" class="element text medium" maxlength=20 value="$admin_email_password" type="text">
<label for="admin_email_password">Admin Email Account Password</label>
<p class="guidelines" id="admin_email_pw_guide"><small>Enter the password of the staff email account (this is <b>not</b> the same as the admin password for this site).</small></p>
</div>
</li>
    
<li>
<div>
<input id="regular_user_token" name="regular_user_token" class="element text medium" maxlength=50 value="$regular_user_token" type="text">
<label for="regular_user_token">Camper Access Token</label>
<p class="guidelines" id="regular_user_token_guide"><small>The camper access token is used by non-admin users to confirm their login.  It can be any easy-to-remember string.  This value is not a password, just a token, so it should be something simple, e.g., "RamahKayitz".</small></p>
</div>
</li>
    
<li>
<div>
<input id="staff_password" name="staff_password" class="element text medium" maxlength=50 type="password">
<label for="staff_password">New admin password (you may <b>leave this field blank to keep it the same</b>.)</label>
<span class="error">$staffPasswordErr</span>
<p class="guidelines" id="staff_pw_guide">Leave this field and the next one blank if you do not wish to change the admin password.<small>
</div>
</li>

<li>
<div>
<input id="staff_password2" name="staff_password2" class="element text medium" maxlength=50 type="password">
<label for="staff_password2">Retype new admin password</label>
<span class="error">$staffPasswordErr2</span>
</div>
</li>
HERE;
    echo $updateFieldSection;
?>

<li class="buttons">
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<?php
    $cancelUrl = urlIfy("staffHome.php");
    echo "<a href=\"$cancelUrl\">Cancel</a>";
    ?>
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
