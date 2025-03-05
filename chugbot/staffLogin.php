<?php
session_start();
include_once 'functions.php';
include_once 'formItem.php';
include_once 'dbConn.php';

// If the user is already logged in, redirect.
bouncePastIfLoggedIn("staffHome.php", "admin");
bouncePastIfLoggedIn("attendance/roshHome.php", "rosh");
bouncePastIfLoggedIn("attendance/chugLeaderHome.php", "chugLeader");


// Check to see if there is an existing admin password.  If so, we'll
// prompt the user for a password.  Otherwise, we will ask them to create
// a new one, and to enter an email address.  Password changes will be
// handled by a separate page - it's too complicated to squeeze all the logic
// into this page.
$dbError = $staffPasswordErr = $staffPasswordErr2 = "";
$existingAdminPasswordHashed = $existingChugLeaderPasswordHashed = $existingRoshPasswordHashed = "";
$staffPasswordHashed = "";
$db = new DbConn();
$sql = "SELECT admin_password, chug_leader_password, rosh_yoetzet_password from admin_data";
$result = $db->runQueryDirectly($sql, $dbError);
if ($result == false) {
    $dbError = dbErrorString($sql, "Failed to query database: $dbError");
} else if ($result->num_rows > 1) {
    $dbError = dbErrorString($sql, "Bad row count for admin email and password");
} else if ($result->num_rows == 1) {
    $row = mysqli_fetch_row($result);
    $existingAdminPasswordHashed = $row[0];
    $existingChugLeaderPasswordHashed = $row[1];
    $existingRoshPasswordHashed = $row[2];
}

$staffEmailErr = $staffPasswordErr = $staffPasswordErr2 = "";

// Include a CAPTCHA to ensure a person is the only being trying to reset a password
// Simple CAPTCHA - user must sum 2 digits before progressing to forgotPassword.php, which checks to see
// if the POST value sent matches the SESSION value stored.
$forgotUrl = urlIfy("forgotPassword.php");
$num1 = rand(1, 10);
$num2 = rand(1, 10);
$_SESSION['password_captcha_answer'] = $num1 + $num2;
$forgotPasswordButton = <<<EOM
<p class="mt-4"><b>ADMIN:</b> <a href data-bs-toggle="modal" data-bs-target="#resetPasswordCAPTCHAModal">Forgot your password?</a></p>
EOM;
$forgotPasswordModal = <<<EOM
<div class="modal fade" id="resetPasswordCAPTCHAModal" tabindex="-1" aria-labelledby="resetPasswordCAPTCHAModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="resetPasswordCAPTCHAModalLabel">Wait!</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
    <form id="reset-pwd" action="$forgotUrl" method="POST">
      <div class="modal-body">
            <p>Proceeding will email a password reset link to the admin email.</p>
            <label for="sum" class="form-label"><span style="color: red;">*</span> To make sure you're human, what is $num1 + $num2 (required)?</label>
            <input type="number" class="form-control form-control-sm" id="sum" placeholder="0" name="captcha" style="width: 40%;" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Reset Admin Password</button>
      </div>
    </form>
    </div>
  </div>
</div>
EOM;

// Note the redirect text and destination.  The default is the staff home page,
// but if there is a "from" query string, we redirect back to that page.
$redirUrl = staffBounceBackUrl();
$redirText = "directed to your camp staff home page.";
if (fromBounce()) {
    $redirText = "redirected.";
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $staff_email = test_post_input("staff_email");
    $staff_password = test_post_input("staff_password");
    $staff_password2 = test_post_input("staff_password2");
    $role = test_post_input("role");

    if (empty(test_post_input("staffInit"))) {
        // If we have POST data, we validate it, and update as needed.
        if (empty($staff_password)) {
            $staffPasswordErr = errorString("Please enter a staff password");
        }
        if (empty($existingAdminPasswordHashed)) {
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
            } else if (!filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
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
                if (empty($existingAdminPasswordHashed)) {
                    $insertedOk = $dbConn->insertIntoTable("admin_data", $dbError);
                } else {
                    $insertedOk = $dbConn->updateTable("admin_data", $dbError);
                }
                if ($insertedOk) {
                    // New password entered OK: log them in and redirect.  Note
                    // that staff privileges imply camper privileges.
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['camper_logged_in'] = true;
                    header("Location: $redirUrl");
                    exit();
                }
            }
        } else if (empty($staffPasswordErr) && !empty($role)) {
            // If we have an existing password, we just need to hash it
            // and match it against the database version, which is already hashed.
            // If they match, we set $_SESSION['admin_logged_in'] = TRUE;, and redirect
            // to the admin home page.
            if($role == "admin") {
                if (!password_verify($staff_password, $existingAdminPasswordHashed)) {
                    $staffPasswordErr = errorString("Password does not match - please try again.") .
                        "<p>If you forgot the password, please click <a href data-bs-toggle=\"modal\" data-bs-target=\"#resetPasswordCAPTCHAModal\">here</a>.</p>";
                    echo $forgotPasswordModal;
                    usleep(250000); // Sleep for 0.25 sec, to slow a dictionary attack.
                } else {
                    // New password entered OK: redirect.
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['rosh_logged_in'] = true;
                    $_SESSION['chug_leader_logged_in'] = true;
                    $_SESSION['camper_logged_in'] = true;
                    header("Location: $redirUrl");
                    exit();
                }
            }
            else if ($role == "chugleader") {
                if (!password_verify($staff_password, $existingChugLeaderPasswordHashed)) {
                    $staffPasswordErr = errorString("Password does not match - please try again.") .
                        "<p>If you forgot the password, please contact an administrator.</p>";
                    usleep(250000); // Sleep for 0.25 sec, to slow a dictionary attack.
                } else {
                    // New password entered OK: redirect.
                    $_SESSION['chug_leader_logged_in'] = true;
                    $_SESSION['camper_logged_in'] = true;
                    if(!fromBounce()) {
                        $redirUrl = "../attendance/chugLeaderHome.php";
                    }
                    header("Location: $redirUrl");
                    exit();
                }
            }
            else if ($role == "rosh") {
                if (!password_verify($staff_password, $existingRoshPasswordHashed)) {
                    $staffPasswordErr = errorString("Password does not match - please try again.") .
                        "<p>If you forgot the password, please contact an administrator.</p>";
                    usleep(250000); // Sleep for 0.25 sec, to slow a dictionary attack.
                } else {
                    // New password entered OK: redirect.
                    $_SESSION['rosh_logged_in'] = true;
                    $_SESSION['chug_leader_logged_in'] = true;
                    $_SESSION['camper_logged_in'] = true;
                    if(!fromBounce()) {
                        $redirUrl = "../attendance/roshHome.php";
                    }
                    header("Location: $redirUrl");
                    exit();
                }
            }
        }
    }
}

?>

<?php
echo headerText("Staff Login");
$staffUrl = urlIfy("staffLogin.php");
$errText = genFatalErrorReport(array($dbError, $staffPasswordErr, $staffPasswordErr2),
    false);
if (!is_null($errText)) {
    echo $errText;
    exit();
}
?>

<div class="card card body mt-3 p-3 container">
<h1><a>Staff Login</a></h1>
<form id="loginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
<div class="page-header">
<h2>Staff Login</h2>
<?php
if (empty($existingAdminPasswordHashed)) {
    echo ("<p>Please create a staff password, and enter an email in case you need to reset or change the password.<br>");
    echo ("The password should be between 5 and 20 characters.</p>");
}
echo loginRequiredMessage();
echo "After logging in, you will be $redirText";
?>
</div>
<br>
<ul>
<?php
$liNum = 0;

// if admin + other password(s) are set, show buttons to select correct one
if(!empty($existingAdminPasswordHashed)) {
    $roleSelectField = "<li id=\"li_0\">" . 
        "<label class=\"description\" for=\"role\"><font color=\"red\">*</font>Select User Role</label>" .
        "<div class=\"btn-group mb-2\" role=\"group\" aria-label=\"Basic example\">" . 
        "<input type=\"radio\" class=\"btn-check\" name=\"role\" id=\"admin\" autocomplete=\"off\" value=\"admin\" required>" . 
        "<label class=\"btn btn-outline-primary\" for=\"admin\">Admin</label>";
    if(!empty($existingChugLeaderPasswordHashed) && !empty($existingRoshPasswordHashed)) {
        $roleSelectField .= "<input type=\"radio\" class=\"btn-check\" name=\"role\" id=\"chugleader\" autocomplete=\"off\" value=\"chugleader\">" . 
        "<label class=\"btn btn-outline-primary\" for=\"chugleader\">Chug Leader</label>" . 
        "<input type=\"radio\" class=\"btn-check\" name=\"role\" id=\"rosh\" autocomplete=\"off\" value=\"rosh\">" . 
        "<label class=\"btn btn-outline-primary\" for=\"rosh\">Rosh/Yoetzet</label>";
    }
    else if (!empty($existingChugLeaderPasswordHashed)) {
        $roleSelectField .= "<input type=\"radio\" class=\"btn-check\" name=\"role\" id=\"chugleader\" autocomplete=\"off\" value=\"chugleader\">" . 
        "<label class=\"btn btn-outline-primary\" for=\"chugleader\">Chug Leader</label>";
    }
    else if (!empty($existingRoshPasswordHashed)) {
        $roleSelectField .= "<input type=\"radio\" class=\"btn-check\" name=\"role\" id=\"rosh\" autocomplete=\"off\" value=\"rosh\">" . 
        "<label class=\"btn btn-outline-primary\" for=\"rosh\">Rosh/Yoetzet</label>";
    }
    $roleSelectField .= "</li>";
    echo $roleSelectField;
    $liNum++;
}

if (empty($existingAdminPasswordHashed)) {
    $emailField = new FormItemSingleTextField("Staff Email Address", true, "staff_email", $liNum++);
    $emailField->setInputValue($staff_email);
    $emailField->setInputType("text");
    $emailField->setInputClass("element text medium");
    $emailField->setInputMaxLength(50);
    $emailField->setPlaceHolder("leveling@campramahne.org");
    $emailField->setGuideText("Please enter an email for password change/retrieval. The person at this address should also be able to answer leveling questions from campers.");
    $emailField->setError($staffEmailErr);
    echo $emailField->renderHtml();
}

$staffPasswordField = new FormItemSingleTextField("Staff Password", true, "staff_password", $liNum++);
$staffPasswordField->setInputType("password");
$staffPasswordField->setInputClass("element text medium");
$staffPasswordField->setInputMaxLength(50);
$staffPasswordField->setPlaceHolder(" ");
$staffPasswordField->setGuideText("Enter a staff password.  This password protects the staff-only parts of the page, and should not be shared with campers.");
echo $staffPasswordField->renderHtml();

if (empty($existingAdminPasswordHashed)) {
    $staffPasswordField2 = new FormItemSingleTextField("Retype Staff Password", true, "staff_password2", $liNum++);
    $staffPasswordField2->setInputType("password");
    $staffPasswordField2->setInputClass("element text medium");
    $staffPasswordField2->setInputMaxLength(50);
    $staffPasswordField2->setPlaceHolder(" ");
    echo $staffPasswordField2->renderHtml();
}
?>

<li class="buttons">
<input id="saveForm" class="btn btn-primary" type="submit" name="submit" value="Log In" />
</li>
    <?php
    if(!empty($existingAdminPasswordHashed)) {
        echo $forgotPasswordButton;
    }?>
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
    // include forgot password popup if password exists
    if(!empty($existingAdminPasswordHashed)) {
        echo $forgotPasswordModal;
    }?>
<?php
echo footerText();
?>

</body>
</html>
