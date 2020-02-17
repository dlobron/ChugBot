<?php
    include_once 'formItem.php';
    include_once 'dbConn.php';

    class Column {
        function __construct($name, $required = TRUE, $defaultValue = NULL) {
            $this->name = $name;
            $this->required = $required;
            $this->defaultValue = $defaultValue;
        }

        public function setNumeric($n) {
            $this->numeric = $n;
        }

        public $name;
        public $required;
        public $defaultValue;
        public $numeric;
    }

    abstract class FormPage {
        function __construct($title, $firstParagraph) {
            $this->title = $title;
            $this->firstParagraph = $firstParagraph;
        }

        public function addFormItem($fi) {
            array_push($this->formItems, $fi);
        }

        public function errForColName($colName) {
            if (! array_key_exists($colName, $this->colName2Error)) {
                return "";
            }
            return $this->colName2Error[$colName];
        }

        public function setSubmitAndContinueTarget($sact, $text) {
            $this->submitAndContinueTarget = $sact;
            $this->submitAndContinueLabel = $text;
        }

        public function setSaveAndReturnLabel($text) {
            $this->saveAndReturnLabel = $text;
        }

        abstract protected function renderForm();

        public $title;
        public $dbErr = "";
        protected $colName2Error = array();
        protected $resultStr = "";
        protected $firstParagraph;
        protected $secondParagraph;
        protected $formItems = array();
        protected $submitAndContinueTarget = NULL;
        protected $submitAndContinueLabel = "";
        protected $saveAndReturnLabel = NULL;
    }

    // This class handles most of the work for the add and edit pages.  The
    // subclasses each implement a custom handleSubmit function, since those are
    // substantially different for add and edit actions.
    abstract class AddEditBase extends FormPage {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph);
            $this->mainTable = $mainTable;
            $this->idCol = $idCol;
        }

        abstract protected function handleSubmit();

        public function addSecondParagraph($p) {
            $this->secondParagraph = $p;
        }

        public function addInstanceTable($it) {
            $this->instanceTable = $it;
        }

        public function addColumn($name, $required = TRUE,
                                  $numeric = FALSE, $defVal = NULL) {
            $col = new Column($name, $required, $defVal);
            $col->setNumeric($numeric);
            array_push($this->columns, $col);
            $this->col2Type[$name] = $numeric ? 'i' : 's';
        }

        public function columnValue($column) {
            if (! array_key_exists($column, $this->col2Val)) {
                return NULL;
            }
            return $this->col2Val[$column];
        }

        public function fillInstanceId2Name($instanceIdCol, $instanceTable) {
            fillId2Name(NULL, $this->instanceId2Name, $this->dbErr,
                        $instanceIdCol, $instanceTable);
            $this->instanceIdCol = $instanceIdCol;
            $this->instanceIdsIdentifier = $instanceIdCol . "s";
        }

        public function setActiveEdotFilterBy($filterBy) {
            fillId2Name(NULL, $this->activeEdotFilterId2Name, $this->dbErr,
                        "edah_id", "edot");
            $this->activeEdotFilterBy = $filterBy;
            $this->activeEdotFilterTable = "edot_for_" . $filterBy;
        }

        public function renderForm() {
            camperBounceToLogin(); // Forms require at least camper-level access.
            echo headerText($this->title);

            // If the current submitted data is a duplicate, display an overlay
            // that offers options.
            if ($this->duplicateCamperDesc !== NULL) {
                $html = "";
                $html .= "<div id=\"dupMsgBox\" class=\"modal show\">";
                $html .= "<a href=\"javascript:void(0)\" class=\"closebtn\" onclick=\"closeNav()\">&times;</a>";
                $html .= "<div class=\"modal-dialog\">";
                $html .= "<div class=\"modal-content\">";
                // Display a message that we detected a duplicate, and offer
                // two buttons.  One directs to the edit page, with the currently
                // selected camper activated.  The other redirects to the add-camper
                // page, with the fields cleared.
                $html .= "<div class=\"modal-header\">";
                $html .= "<h2><i>Oops!</i></h2>";
                $html .= "</div>";
                $html .= "<div class=\"modal-body\">";
                $html .= "<h3>" . $this->duplicateCamperDesc . " is already in the database for Kayitz " . yearOfCurrentSummer() . "!</h3>";
                $html .= "<ul><li><b>If you are " . $this->col2Val["first"] . "</b>, click <a href=";
                $link = urlIfy("preEditCamper.php");
                $link .= "?first=" . $this->col2Val["first"];
                $link .= "&last=" . $this->col2Val["last"];
                $link .= "&edah_id=" . $this->col2Val["edah_id"];
                $html .= $link;
                $html .= ">here</a> to add or update your personal info or chugim.</li>";
                $html .= "<li><b>Otherwise</b>, <a href=\"javascript:void(0)\" onclick=\"closeNav()\">close this window</a> and try registering again with a different name (you can use a nickname).</li></ul>";
                $html .= "</div>";
                $html .= "<div class=\"modal-footer\">";
                $html .= "<button class=\"btn btn-secondary\" onclick=\"closeNav()\">Close</button>";
                $html .= "</div></div></div>";
                $html .= "<script>";
                $html .= "function closeNav() { document.getElementById(\"dupMsgBox\").style.width = \"0%\"; document.getElementById(\"dupMsgBoxBackdrop\").style.width = \"0%\"; }";
                $html .= "</script>";
                $html .= "</div>";
                $html .= "<div id=\"dupMsgBoxBackdrop\" class=\"modal-backdrop in\"></div>";
                echo $html;
            }

            $allErrors = array();
            foreach (array_merge(array($this->dbErr), array_values($this->colName2Error)) as $err) {
                if (! $err) {
                    continue;
                }
                array_push($allErrors, $err);
            }
            $errText = genFatalErrorReport($allErrors, TRUE, NULL, TRUE);
            if (! is_null($errText)) {
                echo $errText;
                //exit(); // Keep rendering, so the user can fix the data.
            }
            $formId = "main_form";
            $actionTarget = htmlspecialchars($_SERVER["PHP_SELF"]);
            $html = "";
            if ($this->resultStr) {
                $html .= "<div class=\"container well\">$this->resultStr</div>";
            }
            $secondParagraphHtml = "";
            if ($this->secondParagraph) {
                $secondParagraphHtml = "<p>$this->secondParagraph</p>";
            }
            $html .= <<<EOM
<div class="well well-white container">
<h1><a>$this->title</a></h1>
<form id="$formId" method="post" action="$actionTarget">
<div class="page-header">
<h2>$this->title</h2>
<p>$this->firstParagraph (<font color="red">*</font> = required field)</p>
$secondParagraphHtml
</div>
<ul>

EOM;
            foreach ($this->formItems as $formItem) {
                $html .= $formItem->renderHtml();
            }

            $cancelUrl = "";
            if (isset($_SESSION['admin_logged_in'])) {
                $cancelUrl = urlIfy("staffHome.php");
            } else {
                $cancelUrl = urlIfy("index.php");
            }
            $cancelText = "<a class=\"btn btn-link\" href=\"$cancelUrl\">Cancel</a>";
            $footerText = footerText();
            $fromText = "";
            $submitAndContinueText = "";
            $saveAndReturnText = "";
            $submitText = "";
            $homeUrl = homeUrl();
            if (! is_null($this->submitAndContinueTarget)) {
                // If we have a submitAndContinueTarget, display a bold
                // continue link.
                // Set the ID column in the session status so we
                // can pick it up if needed.
                $label = $this->submitAndContinueLabel;
                $submitAndContinueText = "<input id=\"submitAndContinue\" class=\"btn btn-primary\" type=\"submit\" name=\"submitAndContinue\" value=\"$label\" />";
                $idCol = $this->idCol;
                $val = $this->col2Val[$this->idCol];
                $cancelText = "";
            } else {
                // If we don't have submitAndContinueTarget, display a submit
                // in regular typeface.
                $submitText = "<input id=\"saveForm\" class=\"btn btn-primary\" type=\"submit\" name=\"submit\" value=\"Submit\" />";
            }
            if (! is_null($this->saveAndReturnLabel)) {
                // If we have a saveAndReturnLabel, create a button to save and return to the
                // home page.
                $label = $this->saveAndReturnLabel;
                $saveAndReturnText = "<input id=\"saveAndReturn\" class=\"btn btn-primary\" type=\"submit\" name=\"saveAndReturn\" value=\"$label\" />";
            }
            if ($this->editPage) {
                $val = $this->col2Val[$this->idCol];
                if ((! $val) &&
                    (! is_null($this->constantIdValue))) {
                    $val = $this->constantIdValue;
                }
                $fromText = "<input type=\"hidden\" name=\"submitData\" value=\"1\">";
                $fromText .= "<input type=\"hidden\" name=\"$this->idCol\" " .
                "id=\"$this->idCol\" value=\"$val\"/>";
            } else {
                $fromText = "<input type=\"hidden\" name=\"fromAddPage\" value=\"1\">";
            }
            $html .= <<<EOM
<li class="buttons">
<input type="hidden" name="form_id" value="$formId" />
$submitText
$saveAndReturnText
$submitAndContinueText
$fromText
$cancelText
</li>
</ul>
</form>
</div>
$footerText
</body>
</html>
EOM;
            echo $html;
        }

        protected function populateInstanceActionIds() {
            // If we have active instance IDs, grab them.
            populateActiveIds($this->instanceActiveIdHash, $this->instanceIdsIdentifier);

            // Same, for per-edah filter.
            if ($this->activeEdotFilterTable) {
                $multiColName = $this->activeEdotFilterTable;
                populateActiveIds($this->activeEdotHash, $multiColName);
            }
        }

        protected function updateInstances($idVal, &$activeHash, $edahFilter = FALSE) {
            $instanceIdCol = "";
            $idCol = "";
            $instanceTable = "";
            $activeHash = NULL;
            if ($edahFilter) {
                if ($this->activeEdotFilterBy === NULL) {
                    return;
                }
                $instanceIdCol = "edah_id";
                $instanceTable = $this->activeEdotFilterTable;
                $idCol = $this->activeEdotFilterBy . "_id"; // e.g., chug_id or block_id
            } else {
                if (empty($this->instanceIdsIdentifier)) {
                    return;
                }
                $instanceIdCol = $this->instanceIdCol;
                $instanceTable = $this->instanceTable;
                $idCol = $this->idCol;
            }

            $db = new DbConn();
            $db->addSelectColumn($instanceIdCol);
            $db->addWhereColumn($idCol, $idVal, 'i');
            $result = $db->simpleSelectFromTable($instanceTable, $this->dbErr);
            if ($result == FALSE) {
                error_log("Instance update select failed: $this->dbErr");
                return;
            }
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $activeHash[$row[0]] = 1;
            }
        }

        protected function updateActiveInstances($idVal, $edahFilter = FALSE) {
            $instanceIdCol = "";
            $idCol = "";
            $instanceTable = "";
            $activeHash = NULL;
            if ($edahFilter) {
                if ($this->activeEdotFilterTable === NULL) {
                    return TRUE; // No edah filter: not an error.
                }
                $instanceIdCol = "edah_id";
                $idCol = $this->activeEdotFilterBy . "_id"; // e.g., chug_id or block_id
                $instanceTable = $this->activeEdotFilterTable;
                $activeHash = $this->activeEdotHash;
            } else {
                if (empty($this->instanceIdsIdentifier)) {
                    return TRUE; // No instances: not an error.
                }
                $instanceIdCol = $this->instanceIdCol;
                $idCol = $this->idCol;
                $instanceTable = $this->instanceTable;
                $activeHash = $this->instanceActiveIdHash;
            }

            // First, grab existing IDs from the instance table.
            $db = new DbConn();
            $db->addSelectColumn($instanceIdCol);
            $db->addWhereColumn($idCol, $idVal, 'i');
            $result = $db->simpleSelectFromTable($instanceTable, $this->dbErr);
            if ($result == FALSE) {
                error_log("Existing ID query failed: $this->dbErr");
                return FALSE;
            }
            $existingInstanceKeys = array();
            while ($row = $result->fetch_array(MYSQLI_NUM)) {
                $instanceId = $row[0];
                if (! array_key_exists($idVal, $existingInstanceKeys)) {
                    $existingInstanceKeys[$idVal] = array();
                }
                $existingInstanceKeys[$idVal][$instanceId] = 1;
            }
            // Next, step through the active instance hash, and update as follows:
            // - If an entry exists in $existingInstanceKeys, note that.
            // - If the entry does not exist, insert it.
            foreach ($activeHash as $instanceId => $active) {
                if (array_key_exists($idVal, $existingInstanceKeys) &&
                    array_key_exists($instanceId, $existingInstanceKeys[$idVal])) {
                    // This entry exists in the DB: delete it from $existingInstanceKeys.
                    unset($existingInstanceKeys[$idVal][$instanceId]);
                    continue;
                }
                // New entry: insert it.
                $dbc = new DbConn();
                $dbc->addColumn($idCol, $idVal, 'i');
                $dbc->addColumn($instanceIdCol, $instanceId, 'i');
                $queryOk = $dbc->insertIntoTable($instanceTable, $this->dbErr);
                if (! $queryOk) {
                    error_log("Instance insert failed: $this->dbErr");
                    return FALSE;
                }
            }
            // At this point, $existingInstanceKeys contains entries that exist in the DB but
            // not in the new set.  Delete these entries from the DB.
            foreach ($existingInstanceKeys as $idValKey => $existingInstanceIds) {
                foreach ($existingInstanceIds as $existingInstanceId => $active) {
                    $db = new DbConn();
                    $db->addWhereColumn($instanceIdCol, $existingInstanceId, 'i');
                    $db->addWhereColumn($idCol, $idVal, 'i');
                    $delOk = $db->deleteFromTable($instanceTable, $this->dbErr);
                    if ($delOk == FALSE) {
                        error_log("Failed to delete instance: $this->dbErr");
                        return FALSE;
                    }
                }
            }

            return TRUE;
        }

        public function setConstantIdValue($civ) {
            $this->constantIdValue = $civ;
        }

        public function setAlternateResultString($ars) {
            $this->alternateResultString = $ars;
        }

        public function setAddEditChugPage() {
            $this->addEditChugPage = 1;
        }

        public $mainTable;

        protected $idCol; // The ID column name of $this->mainTable
        protected $col2Val = array(); // Column name -> value (filled by us)
        protected $columns = array(); // Column names in order (filled by the caller)
        protected $col2Type = array(); // Like above, but hashed by name.
        protected $editPage = FALSE;
        protected $constantIdValue = NULL;

        // The next members pertain to items with per-item instances.
        // We make them public so users can grab them directly.
        public $instanceTable = "";
        public $instanceId2Name = array();
        public $instanceActiveIdHash = array();
        public $instanceIdsIdentifier = "";
        public $instanceIdCol = "";

        // These members pertain to items with per-edah filters.  These have
        // names like edot_for_block or edot_for_chug;
        public $activeEdotHash = array();
        public $activeEdotFilterBy = NULL; // e.g., "chug" or "block"
        public $activeEdotFilterTable = NULL;
        public $activeEdotFilterId2Name = array();

        public $fromAddPage = FALSE;
        public $submitData = FALSE;
        public $fromHomePage = FALSE;

        protected $infoMessage = "";
        protected $alternateResultString = NULL;
        protected $duplicateCamperDesc = NULL;

        protected $addEditChugPage = 0;
    }

    class EditPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
            $this->editPage = TRUE;
        }

        public function handleSubmit() {
            $submitAndContinue = FALSE;
            $saveAndReturn = FALSE;
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                // If the page was not POSTed, we might have arrived here via
                // a link.  In this case, we expect the ID value to be in the query
                // string, as eid=foo.
                // For security, we only do this if the user is logged in as an
                // administrator (otherwise, a camper could put eid=SomeOtherCamperId, and
                // edit that other camper's data).
                $parts =  explode("&", $_SERVER['QUERY_STRING']);
                foreach ($parts as $part) {
                    $cparts = explode("=", $part);
                    if (count($cparts) != 2) {
                        continue;
                    }
                    if ($cparts[0] == "eid") {
                        // Set idVal and mark as coming from a home page.
                        $idVal = $cparts[1];
                        $this->fromHomePage = TRUE;
                    }
                }
                if (! $this->fromHomePage) {
                    // If we did not get an item ID from the query string, return
                    // here.
                    return;
                }
            } else {
                // We have POST data: extract expected values.
                if (! empty($_POST["fromAddPage"])) {
                    $this->fromAddPage = TRUE;
                }
                if (! empty($_POST["submitData"])) {
                    $this->submitData = TRUE;
                }
                if ((! empty($_POST["fromHome"])) ||
                    (! empty($_POST["fromStaffHomePage"]))) {
                    $this->fromHomePage = TRUE;
                }
                if (! empty($_POST["submitAndContinue"])) {
                    $submitAndContinue = TRUE;
                }
                if (! empty($_POST["saveAndReturn"])) {
                    $saveAndReturn = TRUE;
                }
                // Get the ID of the item to be edited: this is required to either
                // exist in the POST or be set as a constant.
                $idVal = test_input($_POST[$this->idCol]);
                if (! $idVal) {
                    if (! is_null($this->constantIdValue)) {
                        $idVal = $this->constantIdValue;
                    } else {
                        $this->colName2Error[$this->idCol] = errorString("No $this->idCol was chosen to edit: please select one");
                        return;
                    }
                }
            }
            $this->col2Val[$this->idCol] = $idVal;
            // Certain edit pages, such as for campers, need the ID to be
            // stored as a session variable.
            $_SESSION[$this->idCol] = $idVal;

            // For the add-edit page, get de-dup data.  If it's in the POST
            // data, use that.  Otherwise, pull from the DB.
            if ($this->addEditChugPage) {
                if (array_key_exists("dedup_chugim", $_POST)) {
                    $this->displayListSelectedIds = $_POST["dedup_chugim"];
                } else {
                    $seen = array();
                    $db = new DbConn();
                    $db->addSelectColumn("left_chug_id");
                    $db->addWhereColumn("right_chug_id", $idVal, 'i');
                    $result = $db->simpleSelectFromTable("chug_dedup_instances_v2", $this->dbErr);
                    if ($result == FALSE) {
                        error_log("Failed to get column values from DB: $this->dbErr");
                        return;
                    }
                    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                        if (! array_key_exists($row[0], $seen)) {
                            array_push($this->displayListSelectedIds, $row[0]);
                            $seen[$row[0]] = 1;
                        }
                    }
                    $db = new DbConn();
                    $db->addSelectColumn("right_chug_id");
                    $db->addWhereColumn("left_chug_id", $idVal, 'i');
                    $result = $db->simpleSelectFromTable("chug_dedup_instances_v2", $this->dbErr);
                    if ($result == FALSE) {
                        error_log("Failed to get column values from DB: $this->dbErr");
                        return;
                    }
                    while ($row = mysqli_fetch_array($result, MYSQLI_NUM)) {
                        if (! array_key_exists($row[0], $seen)) {
                            array_push($this->displayListSelectedIds, $row[0]);
                            $seen[$row[0]] = 1;
                        }
                    }
                }
            }
            if ($this->fromHomePage) {
                // If we're coming from a home page, we need to get our
                // column values from the DB.
                $db = new DbConn();
                $db->addSelectColumn("*");
                $db->addWhereColumn($this->idCol, $idVal, 'i');
                $result = $db->simpleSelectFromTable($this->mainTable, $this->dbErr);
                if ($result == FALSE) {
                    error_log("Failed to get column values from DB: $this->dbErr");
                    return;
                }
                $this->col2Val = $result->fetch_array(MYSQLI_ASSOC);

                // Populate active instance IDs and edah filter, if configured.
                $this->updateInstances($idVal, $this->instanceActiveIdHash);
                $this->updateInstances($idVal, $this->activeEdotHash, TRUE);
            } else {
                // From other sources (our add page or this page), column values should
                // be in the form data.
                foreach ($this->columns as $col) {
                    $val = test_input($_POST[$col->name]);
                    // Translate numeric values as needed, but keep NULL as-is.
                    if ($col->numeric &&
                        $val !== NULL) {
                        if ($val == "on") {
                            $val = 1;
                        } else if ($val == "off") {
                            $val = 0;
                        } else if (empty($val)) {
                            $val = 0;
                        } else {
                            $val = intval($val);
                        }
                    }
                    if ($val === NULL) {
                        if ($col->required && (! $this->fromHomePage)) {
                            $this->colName2Error[$col->name] = errorString("Missing required field " . $col->name);
                            return;
                        }
                        // This is tricky: if a value is NULL, and we have a default,
                        // should we leave it NULL, or use the default?  For now, let's
                        // use the default.  This means that columns with default values
                        // will revert to those values if they are not set.  I think this
                        // is OK, but we might eventually want to change this.
                        if (! is_null($col->defaultValue)) {
                            error_log("Field $col->name is unset: using default value $col->defaultValue");
                            $val = $col->defaultValue;
                        }
                    }
                    $this->col2Val[$col->name] = $val;
                }
                // If we have active instance IDs, grab them.
                $this->populateInstanceActionIds();
            }
            if (! array_key_exists($this->idCol, $this->col2Val)) {
                $this->colName2Error[$this->idCol] = errorString("ID is required");
                return;
            }

            $homeAnchor = homeAnchor();
            $thisPage = basename($_SERVER['PHP_SELF']);
            $addPage = preg_replace('/^edit/', "add", $thisPage);
            $name = preg_replace('/^edit/', "", $thisPage);
            $name = preg_replace('/.php$/', "", $name);
            $idVal = $this->col2Val[$this->idCol];
            $additionalText = "Please edit below if needed, or return $homeAnchor.";
            if ($this->alternateResultString) {
                $additionalText = $this->alternateResultString;
            }
            if (is_null($this->constantIdValue)) {
                // Only display an "add another" link for tables that allow multiple
                // rows.
                $addAnother = urlBaseText() . "/$addPage";
                $additionalText .= " To add another $name, click <a href=\"$addAnother\">here</a>.";
            }
            if ($this->submitData) {
                $db = new DbConn();
                foreach ($this->col2Val as $colName => $colVal) {
                    $type = $this->col2Type[$colName];
                    if ($type === NULL || empty($type)) {
                        $type = 'i'; // Assume unknown columns are always numeric IDs.
                    }
                    $db->addColumn($colName, $colVal, $type);
                }
                $db->addWhereColumn($this->idCol, $idVal, 'i');
                $submitOk = $db->updateTable($this->mainTable, $this->dbErr);
                if ($submitOk == FALSE) {
                    error_log("Update failed: $this->dbErr");
                    return;
                }
                // Update instances, if we have them.
                $instanceUpdateOk = $this->updateActiveInstances($idVal);
                if (! $instanceUpdateOk) {
                    return;
                }
                // Same, for edah filter, if any.
                $instanceUpdateOk = $this->updateActiveInstances($idVal, TRUE);
                if (! $instanceUpdateOk) {
                    return;
                }

                if ($this->addEditChugPage &&
                    $this->displayListSelectedIds) {
                    // If we're editing a chug, update the exclusion table.
                    // First, we delete dedup rows where this chug is in the left
                    // column.  Then, we add rows from our active list.
                    $db = new DbConn();
                    $db->addWhereColumn("left_chug_id", $idVal, 'i');
                    $db->deleteFromTable("chug_dedup_instances_v2", $this->dbErr);
                    // Add the current set of names.
                    foreach ($this->displayListSelectedIds as $dedupId) {
                        $db = new DbConn();
                        $db->addIgnore();
                        $db->addColumn("left_chug_id", $idVal, 'i');
                        $db->addColumn("right_chug_id", $dedupId, 'i');
                        $db->insertIntoTable("chug_dedup_instances_v2", $this->dbErr);
                    }
                    debugLog("Added " . count($this->displayListSelectedIds) . " dedup instances");
                }

                $this->resultStr =
                    "<h3><font color=\"green\">$name updated!</font> $additionalText</h3>";
            } else if ($this->fromAddPage) {
                $this->resultStr =
                "<h3><font color=\"green\">$name added successfully!</font> $additionalText</h3>";
            }

            // If we've been asked to continue, do so here.
            if ($submitAndContinue) {
                $submitAndContinueUrl = urlIfy($this->submitAndContinueTarget);
                echo ("<script type=\"text/javascript\">window.location.replace(\"$submitAndContinueUrl\");</script>");
                exit();
            }
            // For save and return, go back to the home page.
            if ($saveAndReturn) {
                $homeUrl = homeUrl();
                echo ("<script type=\"text/javascript\">window.location.replace(\"$homeUrl\");</script>");
                exit();
            }

            // If a column is set to its default, set it to the empty string
            // for display.
            foreach ($this->columns as $col) {
                if (is_null($col->defaultValue)) {
                    continue;
                }
                if (! array_key_exists($col->name, $this->col2Val)) {
                    continue;
                }
                if ($this->col2Val[$col->name] == $col->defaultValue) {
                    $this->col2Val[$col->name] = "";
                }
            }
        }

        public function displayListSelectedIds() {
            return $this->displayListSelectedIds;
        }

        // Display list for dedup IDs, or other drop-down IDs.
        private $displayListSelectedIds = array();
    }

    class AddPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
        }

        public function handleSubmit() {
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                return;
            }
            if ((! empty($_POST["fromHome"])) ||
                (! empty($_POST["fromStaffHomePage"]))) {
                $this->fromHomePage = TRUE;
            }

            // If we have active instance IDs, grab them.
            $this->populateInstanceActionIds();

            // If we're coming from the home page, there's nothing further to
            // process: just display the form.
            if ($this->fromHomePage) {
                return;
            }

            // Check for submitted values.  Fire an error if required inputs
            // are missing, and grab defaults if applicable.
            foreach ($this->columns as $col) {
                $val = test_input($_POST[$col->name]);
                if ($val === NULL) {
                    if ($col->required) {
                        $this->colName2Error[$col->name] = errorString("Missing value for required field");
                        continue;
                    }
                    if (! is_null($col->defaultValue)) {
                        $val = $col->defaultValue;
                    } else {
                        continue;
                    }
                }
                $this->col2Val[$col->name] = $val;
            }
            if (! empty($this->colName2Error)) {
                return;
            }

            // Insert the values we collected above.
            $dbc = new DbConn();
            foreach ($this->col2Val as $colName => $colVal) {
                $type = $this->col2Type[$colName];
                if ($type === NULL || empty($type)) {
                    $type = 'i'; // If we don't know of this column, assume it's a numeric ID.
                }
                $dbc->addColumn($colName, $colVal, $type);
            }
            $queryOk = $dbc->insertIntoTable($this->mainTable, $this->dbErr);
            if (! $queryOk) {
                error_log("Insert failed: $this->dbErr");
                return;
            }

            // If we have instances, update them.
            $mainTableInsertId = $dbc->insertId();
            $instanceUpdateOk = $this->updateActiveInstances($mainTableInsertId);
            if (! $instanceUpdateOk) {
                return;
            }
            // Same, for edah filter, if any.
            $instanceUpdateOk = $this->updateActiveInstances($mainTableInsertId, TRUE);
            if (! $instanceUpdateOk) {
                return;
            }

            // Add all parameters with values to the hash we'll pass to the edit
            // page.
            $this->col2Val[$this->idCol] = $mainTableInsertId;
            $paramHash = array($this->idCol => $mainTableInsertId);
            foreach ($this->col2Val as $colName => $colVal) {
                $paramHash[$colName] = $colVal;
            }

            // Add instance info, if we have it.
            if (count($this->instanceActiveIdHash) > 0) {
                $key = $this->instanceIdsIdentifier . "[]";
                $paramHash[$key] = array_keys($this->instanceActiveIdHash);
            }

            // Same for edah filter.
            if (count($this->activeEdotHash) > 0) {
                $key = $this->activeEdotFilterTable . "[]";
                $paramHash[$key] = array_keys($this->activeEdotHash);
            }

            // If we are adding a new chug, update the exclusion table.
            if ($this->addEditChugPage) {
                // First, dedup this chug with itself.
                $dedupIds = array($mainTableInsertId);
                // Dedup other chugim as requested.
                if (array_key_exists("dedup_chugim", $_POST)) {
                    array_push($dedupIds, $_POST["dedup_chugim"]);
                }
                foreach ($dedupIds as $dedupChugId) {
                    $db = new DbConn();
                    $db->addIgnore();
                    $db->addColumn("left_chug_id", $mainTableInsertId, 'i');
                    $db->addColumn("right_chug_id", $dedupChugId, 'i');
                    $insertOk = $db->insertIntoTable("chug_dedup_instances_v2", $this->dbErr);
                    if (! $insertOk) {
                        error_log("Insert into chug_dedup_instances_v2 failed: $this->dbErr");
                        return;
                    }
                }
            }

            $thisPage = basename($_SERVER['PHP_SELF']);
            $editPage = preg_replace('/^add/', "edit", $thisPage); // e.g., "addGroup.php" -> "editGroup.php"
            echo(genPassToEditPageForm($editPage, $paramHash));
        }
    }

    // Adding a camper is very different from other add actions, so we define
    // a separate class for this.  The main difference is that when the data is
    // submitted, we store it in session state, to be committed only after
    // the user has chosen chugim.
    class AddCamperPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
        }

        public function handleSubmit() {

            // If we're coming from the home page, there's nothing further to
            // process: just display the form.
            if ((! empty($_POST["fromHome"])) ||
                (! empty($_POST["fromStaffHomePage"]))) {
                $this->fromHomePage = TRUE;
            }
            if ($this->fromHomePage) {
                return;
            }

            // Clear camper ID from the session, in case multiple campers are
            // being added in the same browser.
            unset($_SESSION["camper_id"]);

            // Check for submitted values.  Fire an error if required inputs
            // are missing, and grab defaults if applicable.
            foreach ($this->columns as $col) {
                $val = test_input($_POST[$col->name]);
                if ($val === NULL) {
                    if ($col->required) {
                        $this->colName2Error[$col->name] = errorString("Missing value for required field");
                        continue;
                    }
                    if (! is_null($col->defaultValue)) {
                        $val = $col->defaultValue;
                    } else {
                        continue;
                    }
                }
                $this->col2Val[$col->name] = $val;
            }
            if (! empty($this->colName2Error)) {
                // If we hit any errors, stop here and display, so the user
                // can correct them.
                return;
            }

            // Check to see if this camper registration is a duplicate, which
            // we define as having the same first and last name, and edah.  If
            // a dup is detected, display an overlay that lets the user either
            // continue to the edit-camper page, with the ID set to the existing
            // camper ID, or else lets them start the add form again, with the
            // fields cleared.
            $db = new DbConn();
            $db->isSelect = TRUE;
            $db->addColVal($this->col2Val["first"], 's');
            $db->addColVal($this->col2Val["last"], 's');
            $db->addColVal($this->col2Val["edah_id"], 'i');
            $sql = "SELECT c.camper_id camper_id, e.name edah_name FROM campers c, edot e WHERE c.edah_id = e.edah_id AND " .
            "c.first = ? AND c.last = ? AND c.edah_id = ?";
            $result = $db->doQuery($sql, $err);
            if (($result !== FALSE) && ($result->num_rows > 0)) {
                // If the query succeeded and returned one or more rows, then
                // we've found a duplicate.
                $row = mysqli_fetch_assoc($result);
                $this->duplicateCamperDesc = $this->col2Val["first"] . " " . $this->col2Val["last"] . " in " . $row["edah_name"];
                return;
            }

            // At this point, we have valid new-camper data.  Set all our
            // column data in the SESSION hash, so that ajax methods can pick it
            // up.
            foreach ($this->col2Val as $colName => $colVal) {
                $_SESSION[$colName] = $colVal;
            }

            // Go to the choice-ranking page.
            $rankChoicesUrl = urlIfy("rankCamperChoices.html");
            echo ("<script type=\"text/javascript\">window.location.replace(\"$rankChoicesUrl\");</script>");
            exit();
        }
    }

?>

