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
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        
        if (empty($name)) {
            $nameErr = errorString("Name is required");
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
            
            $sql =
            "INSERT INTO campuses (campus_name, street1, street2, city, state, zip, country, staff_email, staff_password) " .
            "VALUES (\"$name\", \"$address\", \"$address2\", \"$city\", \"$state\", \"$zip\", \"$country\", \"$staff_email\", \"$staff_password_hashed\");";
            
            $submitOk = $mysqli->query($sql);
            if ($submitOk == FALSE) {
                if (preg_match("/duplicate entry/i",
                               $mysqli->error)) {
                    echo(dbErrorString($sql, "Campus $name already exists."));
                } else {
                    echo(dbErrorString($sql, $mysqli->error));
                }
            }
    
            $mysqli->close();
            
            if ($submitOk == TRUE) {
                $paramHash = array("name" => $name);
                echo(genPassToEditPageForm("editCampus.php", $paramHash));
            }
        }
    }
?>

<body id="main_body" >

<img id="top" src="meta/top.png" alt="">
<div id="form_container">

<h1><a>Add a Campus</a></h1>
<form id="form_1063605" class="appnitro"  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
<div class="form_description">
<h2>Add A Campus</h2>
<p>Please enter a description of your campus (<font color="red">*</font> = required field)</p>
</div>
<ul >

<li id="li_1" >
<label class="description" for="name"><font color="red">*</font> Campus Name (for example, "Palmer")</label>
<div>
<input id="name" name="name" class="element text medium" type="text" maxlength="255" value="<?php echo $name;?>"/>
<span class="error"><?php echo $nameErr;?></span>
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
<input type="hidden" name="fromAddPage" value="1">
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
