<?php
session_start();
include_once 'functions.php';
include_once 'formItem.php';
include_once 'dbConn.php';

function fatalError($err)
{
    echo headerText("Password Reset Error");
    $tryAgainUrl = urlIfy("staffLogin.php");
    $errText = genFatalErrorReport(array($err), false,
        $tryAgainUrl);
    echo $errText;
    exit();
}

// Three cases:
// - If we have a query string, it should contain a UUID to validate.
// - If we have POST data, it should contain either a new and retyped new password
// or an email-status flag.
// - If we have neither of the above, we got here from a link click: we generate
// an email with a reset link, and display the email send-status.

// Check the query string for a UUID.  If we find one, and the user is not validated
// yet, validate the code against the database.
$emailSent = false;
$emailError = null;
$uuid = null;
$uuidError = $dbErr = "";
$parts = explode("&", $_SERVER['QUERY_STRING']);
$adminEmail = "";
foreach ($parts as $part) {
    if (empty($part)) {
        continue;
    }
    $cparts = explode("=", $part);
    if (count($cparts) != 2) {
        fatalError("Malformed URL: please try pasting the link directly into your browser.");
    }
    if ($cparts[0] == "rid") {
        $uuid = $cparts[1];
        break;
    }
}
if ($uuid) {
    // Select all codes, and see if the incoming code matches one.
    // First, delete all expired codes.
    $db = new DbConn();
    $sql = "DELETE FROM password_reset_codes WHERE expires <= NOW()";
    $db->runQueryDirectly($sql, $dbErr);
    if ($dbErr) {
        fatalError($dbErr);
    }

    // Select all remaining codes, and compare against the one we received.
    $sql = "SELECT code FROM password_reset_codes";
    $result = $db->runQueryDirectly($sql, $dbErr);
    if ($dbErr) {
        fatalError($dbErr);
    }
    $matchedCodeId = null;
    $matchedUuid = false;
    while ($row = $result->fetch_assoc()) {
        $code = $row["code"];
        if ($code == $uuid) {
            $matchedUuid = true;
            $_SESSION['reset_password_ok'] = true; // Allow reset.
            break;
        }
    }
    if ($matchedUuid == false) {
        fatalError("The reset code you supplied does not match any valid reset code.  Please try the reset link again.");
    }
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$_SESSION['reset_password_ok']) {
        fatalError("You must present a valid reset code before attempting a password reset");
    }
    // Check for a new and re-typed password, and make sure they match.
    $staff_password = test_input($_POST["staff_password"]);
    $staff_password2 = test_input($_POST["staff_password2"]);
    if (strlen($staff_password) < 5 ||
        strlen($staff_password) > 255) {
        fatalError("Password must be between 5 and 255 characters");
    }
    if ($staff_password2 != $staff_password) {
        // The repeated password must match the first.
        fatalError("Passwords do not match.  Please hit \"Back\" and try again.");
    }
    // At this point, we have a valid password.  Update the admin database, log
    // the user in, and redirect to the staff home page with a success message.
    $err = "";
    $staffPasswordHashed = password_hash($staff_password, PASSWORD_DEFAULT);
    $db = new DbConn();
    $db->addColumn("admin_password", $staffPasswordHashed, 's');
    if (!$db->updateTable("admin_data", $err)) {
        fatalError("Can't update admin password: $err");
    }

    $_SESSION['admin_logged_in'] = true;
    $redirUrl = urlIfy("staffHome.php?update=pw");
    header("Location: $redirUrl");
    exit();
} else {
    // If there's no POST data or UUID string, we need to generate a new code and
    // send an email.
    // Generate and send the email.  Display a message indicating the email status.
    // First, generate a code, and insert it into the database, with a generous
    // expiration date.
    $db = new DbConn();
    $code = bin2hex(openssl_random_pseudo_bytes(32));
    $sql = "INSERT INTO password_reset_codes (code, expires) VALUES " .
        "(\"$code\", DATE_ADD(NOW(), INTERVAL 24 HOUR))";
    $db->runQueryDirectly($sql, $dbErr);
    if ($dbErr) {
        fatalError($dbErr);
    }
    $sql = "SELECT * FROM admin_data";
    $result = $db->runQueryDirectly($sql, $dbErr);
    if ($dbErr) {
        fatalError($dbErr);
    }
    $row = $result->fetch_assoc();
    $campName = $row["camp_name"];
    $resetUrl = urlIfy("forgotPassword.php");
    $resetUrl .= "?rid=$code";
    $mailBody = <<<EOM
<html>
<body>
To reset the administrator password for $campName, please click on the following link:

<a href="$resetUrl">$resetUrl</a>

If the link does not work, simply paste it directly into your browser window.

</body>
</html>
EOM;
    $adminEmail = $row["admin_email"];
    $emailSent = sendMail($adminEmail,
        "$campName admin password reset",
        $mailBody,
        $row,
        $emailError);
}

if ($emailError) {
    fatalError("Failed to send reset email to $adminEmail: $emailError.  Please try again, or escalate to a database administrator to reset the administrative password manually.");
}

// Page display for non-error cases starts here.
echo headerText("Admin Password Reset Page");

if ($emailSent) {
    echo "<div class=\"container well well-white\">";
    echo "<h2>Mail Sent</h2>";
    echo "<p>An email has been sent to $adminEmail.  Please check your Inbox and follow the instructions in the message to reset the administrative password.</p>";
    echo "</div>";
} else if ($_SESSION['reset_password_ok']) {
    echo "<div class=\"container well well-white\">";
    echo "<h2>Enter New Password</h2>";
    echo "<p>Please enter a new administrative password.  Passwords must be at least 5 characters.</p>";
    echo "</div>";
    $selfTarget = htmlspecialchars($_SERVER["PHP_SELF"]);
    echo "<div class=\"container well well-white\">";
    echo "<form method=\"post\" action=\"$selfTarget\">";
    echo "<div class=\"page-header\">";
    echo "<h2>Enter New Admin Password</h2>";
    echo "<p>Please enter a new administrative password.  Passwords must be at least 5 characters.</p>";
    echo "</div>";
    echo "<ul>";

    // Present input for password and retyped password.
    $staffPasswordField = new FormItemSingleTextField("Staff Password", true, "staff_password", 0);
    $staffPasswordField->setInputType("password");
    $staffPasswordField->setInputClass("element text medium");
    $staffPasswordField->setInputMaxLength(50);
    $staffPasswordField->setPlaceHolder(" ");
    $staffPasswordField->setGuideText("Enter new password here.  The password must be at least 5 characters");
    echo $staffPasswordField->renderHtml();

    $staffPasswordField2 = new FormItemSingleTextField("Retype Staff Password", true, "staff_password2", 1);
    $staffPasswordField2->setInputType("password");
    $staffPasswordField2->setInputClass("element text medium");
    $staffPasswordField2->setInputMaxLength(50);
    $staffPasswordField2->setPlaceHolder(" ");
    echo $staffPasswordField2->renderHtml();

    echo "<li class=\"buttons\">";
    echo "<input class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\"Submit\" />";
    $cancelUrl = homeUrl();
    echo "<a class=\"btn btn-link\" href=\"$cancelUrl\">Cancel</a>";
    echo "</li></ul></div></form>";
} else {
    // We shouldn't hit this case.
    fatalError("Password reset failed: please try again, or contact a database administrator to reset manually.");
}
?>

<?php
echo footerText();
?>

</body>
</html>




