<?php
    session_start();
    include 'functions.php';
    bounceToLogin();

    $resetUrl = urlIfy("staffReset.php");
    $levelingUrl = urlIfy("levelHomeLaunch.php");    
    $dbErr = "";
    $sessionId2Name = array();
    $blockId2Name = array();
    $groupId2Name = array();
    $edahId2Name = array();
    $chugId2Name = array();
    $bunkId2Name = array();
    
    $mysqli = connect_db();
    fillId2Name($mysqli, $chugId2Name, $dbErr,
                "chug_id", "chugim", "group_id",
                "groups");
    fillId2Name($mysqli, $sessionId2Name, $dbErr,
                "session_id", "sessions");
    fillId2Name($mysqli, $blockId2Name, $dbErr,
                "block_id", "blocks");
    fillId2Name($mysqli, $groupId2Name, $dbErr,
                "group_id", "groups");
    fillId2Name($mysqli, $edahId2Name, $dbErr,
                "edah_id", "edot");
    fillId2Name($mysqli, $bunkId2Name, $dbErr,
                "bunk_id", "bunks");
    ?>

<?php
    echo headerText("Staff Home");
    
    $errText = genFatalErrorReport(array($dbErr));
    if (! is_null($errText)) {
        echo $errText;
        exit();
    }
    ?>

<div class="centered_container">
<!-- This empty div makes the display cleaner. -->
</div>

<div class="centered_container">
<h2>Camp Staff Control Panel</h2>
<p>From the left menus, you may add and edit Edot, Sessions, Blocks, Groups, and Chugim.  You may also view and edit campers according to edah.</p>
<p>The right menu launches the leveling bot for a specific Edah/Block/Group combination.</p>
<p>Please hover your mouse over a menu for further help.<p>
<p>To edit your administrative settings, please click <a href="<?php echo $resetUrl; ?>">here</a>. </div>

<div class="right_container">
<h3>Leveling</h3>
<p>To level, choose a time block and edah from the drop-down lists, and click "Go."</p>
<p>If you have an existing assignment, you will be able to edit it.  Nothing will be changed until you click
the Save button.</p>
<form id="leveling_choice_form" class="appnitro" method="post" action="<?php echo $levelingUrl;?>">
<ul>
<li>
<label class="description" for="edah">Edah</label>
<div>
<select class="element select medium" id="edah" name="edah">
<?php
    echo genPickList($edahId2Name, "", "edah");
    ?>
</select>
</div><p class="guidelines" id="guide_1"><small>Choose an Edah.</small></p>
</li>
<li>
<label class="description" for="block">Block</label>
<div>
<select class="element select medium" id="block" name="block">
<?php
    echo genPickList($blockId2Name, "", "block");
    ?>
</select>
</div><p class="guidelines" id="guide_2"><small>Choose a Block.</small></p>
</li>
<li>
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
</li>
</ul>
</form>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($edahId2Name, "edah", "edot"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($sessionId2Name, "session", "sessions"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($blockId2Name, "block", "blocks"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($groupId2Name, "group", "groups"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($chugId2Name, "chug", "chugim"); ?>
</div>

<div class="multi_form_container">
<?php echo genPickListForm($bunkId2Name, "bunk", "bunks"); ?>
</div>

<div id="footer">
<?php
    echo footerText();
?>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
