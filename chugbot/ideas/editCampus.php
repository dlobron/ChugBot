<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Add a Campus</title>
<link rel="stylesheet" type="text/css" href="meta/view.css" media="all">
<script type="text/javascript" src="meta/view.js"></script>

</head>

<?php include 'functions.php';?>

<?php
    
    // define variables and set to empty values
    $name = $address = $address2 = $city = $state = $zip = $country = $staff_email = $staff_password = $staff_password2 = "";
    $nameErr = $staffEmailErr = $staffPasswordErr = "";
    $submitData = FALSE;
    $fromAddPage = FALSE;
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (! empty($_POST["fromAddPage"])) {
            $fromAddPage = TRUE;
        }
        if (! empty($_POST["submitData"])) {
            $submitData = TRUE;
        }
        $address = test_input($_POST["address"]);
        $address2 = test_input($_POST["address2"]);
        $city = test_input($_POST["city"]);
        $state = test_input($_POST["state"]);
        $zip = test_input($_POST["zip"]);
        $country = test_input($_POST["country"]);
        $name = test_input($_POST["name"]);
        $staff_email = test_input($_POST["staff_email"]);
        $staff_password = test_input($_POST["staff_password"]);
        $staff_password2 = test_input($_POST["staff_password2"]);
        // Name is required for all POSTs.
        if (empty($name)) {
            $nameErr = errorString("Campus name is required");
        }
        if (empty($staff_email)) {
            $staffEmailErr = errorString("Please enter a staff email, for password reminder");
        }
        if (! filter_var($staff_email, FILTER_VALIDATE_EMAIL)) {
            $staffEmailErr = errorString("$staff_email is not a valid email address");
        }
        if (empty($staff_password)) {
            $staffPasswordErr = errorString("Please enter a staff password");
        }
        if (empty($staff_password2) ||
            $staff_password2 != $staff_password) {
            $staffPasswordErr = errorString("Passwords do not match");
        }
        
        if (empty($nameErr) && empty($staffEmailErr) && empty($staffPasswordErr)) {
            $mysqli = connect_db();
            
            $staff_password_hashed = password_hash($staff_password, PASSWORD_DEFAULT);
            $homeAnchor = homeAnchor();
            
            if ($submitData == TRUE) {
                // Insert edited data.
                $sql =
                "UPDATE campuses SET street1 = \"$address\", street2 = \"$address2\", " .
                "city = \"$city\", state = \"$state\", zip = \"$zip\", country = \"$country\", " .
                "staff_email = \"$staff_email\", staff_password = \"$staff_password_hashed\" " .
                "WHERE campus_name = \"$name\"";
                $submitOk = $mysqli->query($sql);
                if ($submitOk == FALSE) {
                    echo(dbErrorString($sql, $mysqli->error));
                } else {
                    // TODO: Add link back to admin home.
                    echo("<h3>$name updated!</h3>Please edit below if needed, or return $homeAnchor.</h3>");
                }
            } else {
                if ($fromAddPage) {
                    // TODO: Add link back to admin home.
                    echo "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.</h3>";
                }
                // Pull data for this campus from the database, and let the user make edits.
                $sql = "SELECT * FROM campuses WHERE campus_name = \"$name\";";
                $result = $mysqli->query($sql);
                if ($result->num_rows == 1) {
                    $row = $result->fetch_assoc();
                    if (! empty($row["street1"])) {
                        $address = test_input($row["street1"]);
                    }
                    if (! empty($row["street2"])) {
                        $address2 = test_input($row["street2"]);
                    }
                    if (! empty($row["city"])) {
                        $city = test_input($row["city"]);
                    }
                    if (! empty($row["state"])) {
                        $state = test_input($row["state"]);
                    }
                    if (! empty($row["zip"])) {
                        $zip = test_input($row["zip"]);
                    }
                    if (! empty($row["country"])) {
                        $country = test_input($row["country"]);
                    }
                } else {
                    echo(dbErrorString($sql, $mysqli->error));
                }
                mysqli_free_result($result);
            }
                
            $mysqli->close();
        }
    }
?>

<body id="main_body" >

<img id="top" src="meta/top.png" alt="">
<div id="form_container">

<h1><a>Edit Campus</a></h1>
<form id="form_1063605" class="appnitro" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Edit Campus</h2>
<p>Please edit fields as needed (<font color="red">*</font> = required field)</p>
</div>
<ul >

<li id="li_1" >
<label class="description" for="name">Campus Name</label>
<div>
<input id="name_disp" name="name_disp" class="element text medium" type="text" disabled="disabled" maxlength="255" value="<?php echo $name;?>"/>
<input id="name" name="name" class="element text medium" type="hidden" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><font color="red">*</font> <?php echo $nameErr;?></span>
</div>
</li>

<li id="li_2" >
<label class="description" for="address">Address </label>
<div>
<input id="address" name="address" class="element text large" value="<?php echo $address;?>" type="text">
<label for="address">Street Address</label>
</div>

<div>
<input id="address2" name="address2" class="element text large" value="<?php echo $address2;?>" type="text">
<label for="address2">Address Line 2</label>
</div>

<div class="left">
<input id="city" name="city" class="element text medium" value="<?php echo $city;?>" type="text">
<label for="city">City</label>
</div>

<div class="right">
<input id="state" name="state" class="element text medium" value="<?php echo $state;?>" type="text">
<label for="state">State / Province / Region</label>
</div>

<div class="left">
<input id="zip" name="zip" class="element text medium" maxlength=20 value="<?php echo $zip;?>" type="text">
<label for="zip">Postal / Zip Code</label>
</div>

<div class="right">
<input id="country" name="country" class="element text medium" maxlength=50 value="<?php echo $country;?>" type="text">
<label for="country">Country</label>
</div>

<div>
<input id="staff_email" name="staff_email" class="element text medium" maxlength=20 value="<?php echo $staff_email;?>" type="text">
<span class="error"><?php echo $staffEmailErr;?></span>
<label for="staff_email"><font color="red">*</font> Staff email (for password retrieval)</label>
</div>

<div>
<input id="staff_password" name="staff_password" class="element text medium" maxlength=50 value="<?php echo $staff_password;?>" type="password">
<label for="staff_password"><font color="red">*</font> Password</label>
</div>

<div>
<input id="staff_password2" name="staff_password2" class="element text medium" maxlength=50 value="<?php echo $staff_password2;?>" type="password">
<label for="staff_password2"><font color="red">*</font> Retype Password</label>
<span class="error"><?php echo $staffPasswordErr;?></span>
</div>

<li class="buttons">
<input type="hidden" name="form_id" value="1063605" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
</li>

</ul>
<input type="hidden" name="submitData" value="1">
</form>
<div id="footer">
<?php
    echo footerText();
    ?>
</div>
</div>
<img id="bottom" src="meta/bottom.png" alt="">
</body>
</html>
