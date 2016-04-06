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
        
        abstract protected function renderForm();
        
        public $title;
        public $dbErr = "";
        public $colName2Error = array();
        protected $resultStr = "";
        protected $firstParagraph;
        protected $secondParagraph;
        protected $formItems = array();
        protected $submitAndContinueTarget = NULL;
        protected $submitAndContinueLabel = "";
    }
    
    // This class handles most of the work for the add and edit pages.  The
    // subclasses each implement a custom handlePost function, since those are
    // substantially different for add and edit actions.
    abstract class AddEditBase extends FormPage {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph);
            $this->mainTable = $mainTable;
            $this->idCol = $idCol;
        }
        
        abstract protected function handlePost();
        
        public function addSecondParagraph($p) {
            $this->secondParagraph = $p;
        }
        
        public function addInstanceTable($it) {
            $this->instanceTable = $it;
        }
        
        public function addColumn($name, $required = TRUE,
                                  $defVal = NULL, $numeric = FALSE) {
            $col = new Column($name, $required, $defVal);
            $col->setNumeric($numeric);
            array_push($this->columns, $col);
            $this->colName2Error[$name] = "";
            $this->col2Type[$name] = $numeric ? 'i' : 's';
        }
        
        public function columnValue($column) {
            if (! array_key_exists($column, $this->col2Val)) {
                return NULL;
            }
            return $this->col2Val[$column];
        }
        
        public function fillInstanceId2Name($instanceIdCol, $instanceTable) {
            fillId2Name($this->instanceId2Name, $this->dbErr,
                        $instanceIdCol, $instanceTable);
            $this->instanceIdCol = $instanceIdCol;
            $this->instanceIdsIdentifier = $instanceIdCol . "s";
        }
        
        public function setActiveEdotFilterBy($filterBy) {
            fillId2Name($this->activeEdotFilterId2Name, $this->dbErr,
                        "edah_id", "edot");
            $this->activeEdotFilterBy = $filterBy;
            $this->activeEdotFilterTable = "edot_for_" . $filterBy;
        }
        
        public function renderForm() {
            camperBounceToLogin(); // Forms require at least camper-level access.
            
            echo headerText($this->title);
            $allErrors = array_merge(array($this->dbErr), array_values($this->colName2Error));
            $errText = genFatalErrorReport($allErrors);
            if (! is_null($errText)) {
                echo $errText;
                exit();
            }
            $formId = "main_form";
            $actionTarget = htmlspecialchars($_SERVER["PHP_SELF"]);
            $html = "";
            if ($this->resultStr) {
                $html .= "<div class=\"centered_container\">$this->resultStr</div>";
            }
            $secondParagraphHtml = "";
            if ($this->secondParagraph) {
                $secondParagraphHtml = "<p>$this->secondParagraph</p>";
            }
            $html .= <<<EOM
<img id="top" src="images/top.png" alt="">
<div class="form_container">
            
<h1><a>$this->title</a></h1>
<form id="$formId" class="appnitro" method="post" action="$actionTarget">
<div class="form_description">
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
            $cancelText = "<a href=\"$cancelUrl\">Cancel</a>";
            $footerText = footerText();
            $fromText = "";
            $submitAndContinueText = "";
            $submitText = "";
            $backText = "";
            $homeUrl = homeUrl();
            $homeText = "<input type=\"button\" onclick=\"location.href='$homeUrl';\" value=\"Home\" />";
            if (! is_null($this->submitAndContinueTarget)) {
                // If we have a submitAndContinueTarget, display a bold
                // continue link, and do not display the home or cancel links.
                // Set the ID column in the session status so we
                // can pick it up if needed.
                $label = $this->submitAndContinueLabel;
                $submitAndContinueText = "<input id=\"submitAndContinue\" class=\"control_button\" type=\"submit\" name=\"submitAndContinue\" value=\"$label\" />";
                $idCol = $this->idCol;
                $_SESSION["$idCol"] = $this->col2Val[$this->idCol];
                $val = $this->col2Val[$this->idCol];
                $homeText = "";
                $cancelText = "";
            } else {
                // If we don't have submitAndContinueTarget, display a submit
                // and back button, in regular typeface.
                $submitText = "<input id=\"saveForm\" class=\"button_text\" type=\"submit\" name=\"submit\" value=\"Submit\" />";
                $backText = "<button onclick=\"history.go(-1);\">Back </button>";
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
                $fromText = "<input type=\"hidden\" name=\"fromAddPage value=\"1\">";
            }
            $html .= <<<EOM
<li class="buttons">
<input type="hidden" name="form_id" value="$formId" />
$submitText
$backText
$homeText
$submitAndContinueText
$fromText
$cancelText
</li>
</ul>
</form>
</div>
$footerText
<img id="bottom" src="images/bottom.png" alt="">
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
    }
    
    class EditPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
            $this->editPage = TRUE;
        }
        
        public function handlePost() {
            $submitAndContinue = FALSE;
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                // If the page was not POSTed, we might have arrived here via
                // a link.  In this case, we expect the ID value to be in the query
                // string, as eid=foo.
                // For security, we only do this if the user is logged in as an
                // administrator (otherwise, a camper could put eid=SomeOtherCamperId, and
                // edit that other camper's data).
                $parts = array();
                if (adminLoggedIn()) {
                    $parts = explode("&", $_SERVER['QUERY_STRING']);
                }
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
                // be in the POST data, unless we are coming from the home page.
                foreach ($this->columns as $col) {
                    $val = test_input($_POST[$col->name]);
                    if ($col->numeric) {
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
                    if ($val == NULL) {
                        if ($col->required && (! $this->fromHomePage)) {
                            $this->colName2Error[$col->name] = errorString("Missing required column " . $col->name);
                            return;
                        }
                        // Use default, if present.  Otherwise, leave NULL, so users can
                        // erase optional columns.
                        if (! is_null($col->defaultValue)) {
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
                    error_log("Updated failed: $this->dbErr");
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

                $this->resultStr =
                    "<h3><font color=\"green\">$name updated!</font> $additionalText</h3>";
            } else if ($this->fromAddPage) {
                $this->resultStr =
                "<h3><font color=\"green\">$name added successfully!</font> $additionalText</h3>";
            }
            
            // Edits to certain tables and columns might render other items invalid.  For
            // example, if the user changes the edot allowed for a chug, then we need to remove
            // matches to that chug for campers in that edah.
            // We construct a query that returns the primary key and table name for
            // invalid rows, and delete those rows.  We log an info message to the user if
            // any invalid rows are found.
	    // Note that certain other items are auto-deleted via cascade.  For example, if a chug is deleted, then instances are
	    // also deleted, and that cascades to matches.  However, the allowed-edot for chugim and blocks are not cascaded.
            // So far, we only handle invalid matches here, but we can add additional queries with a UNION ALL if needed.  For each
            // category, simply select the ID value from the target table, and do a left outer join against a subquery that returns only
            // valid instances in that table.  Then, iterate through the result, and delete any rows that have a NULL value in the right-hand
            // table from the join.
            // This query has no parameters, so we can run it directly.
            $sql = "SELECT m.match_id pk_value, legal_instances.chug_instance_id instance_id, 'match_id' pk_column, 'matches' table_name FROM " .
            "matches m LEFT OUTER JOIN " .
            "(SELECT i.chug_instance_id chug_instance_id, m.match_id match_id FROM " .
            "matches m, chug_instances i, edot_for_block e, campers c, block_instances bi, edot_for_chug ec WHERE " .
            "m.chug_instance_id = i.chug_instance_id AND i.block_id = bi.block_id AND bi.session_id = c.session_id AND " .
            "m.camper_id = c.camper_id AND e.block_id = i.block_id AND e.edah_id = c.edah_id AND ec.chug_id = i.chug_id	AND " .
            "ec.edah_id = c.edah_id) legal_instances " .
            "ON m.chug_instance_id = legal_instances.chug_instance_id AND m.match_id = legal_instances.match_id ORDER BY pk_value";
            $db = new DbConn();
            $result = $db->runQueryDirectly($sql, $this->dbErr);
            if ($result == FALSE) {
                return FALSE;
            }
            $deleteHash = array();
            while ($row = $result->fetch_assoc()) {
                $pk_value = $row["pk_value"];
                $instance_id = $row["instance_id"];
                $pk_column = $row["pk_column"];
                $table_name = $row["table_name"];
                if ($instance_id === NULL) {
                    error_log("pk value $pk_value in col $pk_column in table $table_name is now invalid: marking for delete");
                    if (! array_key_exists($table_name, $deleteHash)) {
                        $deleteHash[$table_name] = array();
                    }
                    $deleteHash[$table_name][$pk_value] = $pk_column;
                }
            }
            foreach ($deleteHash as $table => $pkVal2Col) {
                foreach ($pkVal2Col as $val => $pkCol) {
                    $db = new DbConn();
                    $err = "";
                    $db->addWhereColumn($pkCol, $val, 'i');
                    if ($db->deleteFromTable($table, $err)) {
                        error_log("Deleted OK");
                    } else {
                        error_log("Failed to delete obsolete instance: $err");
                    }
                }
            }
            // Our preferences table has a somewhat odd structure, so we delete items in a custom way.  This suggests that the design
            // of the table is not optimal, but for the moment I don't have time to redesign and debug, so I'm resorting to this hack.
            // CHOICECOL = "first_choice_id".
            // TODO: Improve the preferences table structure.
            // This query has no input parameters, so we can run it directly.
            $template = "SELECT p.preference_id pref_id, p.CHOICECOL choice_id, legal_choice_n.preference_id legal_pref_id, 'CHOICECOL' col FROM " .
            "preferences p LEFT OUTER JOIN " .
            "(SELECT p.preference_id preference_id, p.CHOICECOL CHOICECOL, p.camper_id camper_id, p.group_id group_id, p.block_id block_id FROM " .
            "preferences p, campers c, edot_for_block eb, edot_for_chug ec, chugim ch WHERE " .
            "p.camper_id = c.camper_id AND c.edah_id = ec.edah_id AND ec.chug_id = p.CHOICECOL AND " .
            "p.block_id = eb.block_id AND eb.edah_id = c.edah_id AND eb.edah_id = c.edah_id AND " .
            "ch.group_id = p.group_id) legal_choice_n " .
            "ON p.preference_id = legal_choice_n.preference_id AND " .
            "p.CHOICECOL = legal_choice_n.CHOICECOL AND " .
            "p.camper_id = legal_choice_n.camper_id AND " .
            "p.group_id = legal_choice_n.group_id AND " .
            "p.block_id = legal_choice_n.block_id " .
            "GROUP BY pref_id";
            $choices = array("first_choice_id", "second_choice_id", "third_choice_id", "fourth_choice_id", "fifth_choice_id", "sixth_choice_id");
            $deleteHash = array();
            foreach ($choices as $choice) {
                $sql = preg_replace('/CHOICECOL/', $choice, $template);
                $db = new DbConn();
                $result = $db->runQueryDirectly($sql, $this->dbErr);
                if ($result == FALSE) {
                    return FALSE;
                }
                while ($row = $result->fetch_assoc()) {
                    $pref_id = $row["pref_id"];
                    $choice_id = $row["choice_id"];
                    $legal_pref_id = $row["legal_pref_id"];
                    $col = $row["col"];
                    // Look for rows where choice_id is not NULL, but legal_pref_id is NULL.
                    if ($choice_id !== NULL &&
                        $legal_pref_id === NULL) {
                        if (! array_key_exists($pref_id, $deleteHash)) {
                            $deleteHash[$pref_id] = array();
                        }
                        array_push($deleteHash[$pref_id], $col);
                        error_log("pref ID $pref_id has choice $choice_id for column $col, which is now illegal: will remove");
                    }
                }
            }
            foreach ($deleteHash as $pref_id => $cols_to_null) {
                foreach ($cols_to_null as $col) {
                    $db = new DbConn();
                    $db->addColumn($col, NULL, 's');
                    $db->addWhereColumn("preference_id", $pref_id, 'i');
                    $err = "";
                    if ($db->updateTable("preferences", $err)) {
                        error_log("Removed preference OK");
                    } else {
                        error_log("Failed to remove preference: $err");
                    }
                }
            }            
            
            // If we've been asked to continue, do so here.  Set the ID
            // field in the _SESSION hash, so JQuery can grab it via an Ajax call.
            if ($submitAndContinue) {
                $_SESSION["$this->idCol"] = $idVal;
                $submitAndContinueUrl = urlIfy($this->submitAndContinueTarget);
                header("Location: $submitAndContinueUrl");
                exit;
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
    }

    class AddPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
        }

        public function handlePost() {
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                return;
            }
            
            // If we have active instance IDs, grab them.
            $this->populateInstanceActionIds();
            
            // If we're coming from the home page, there's nothing further to
            // process.
            if (! empty($_POST["fromHome"])) {
                return;
            }
            
            // Check for POST values.  Fire an error if required inputs
            // are missing, and grab defaults if applicable.
            foreach ($this->columns as $col) {
                $val = test_input($_POST[$col->name]);
                if ($val === NULL) {
                    if ($col->required && (! $this->fromHomePage)) {
                        $this->colName2Error[$col->name] = errorString("Missing value for required column " . $col->name);
                        return;
                    }
                    if (! is_null($col->defaultValue)) {
                        $val = $col->defaultValue;
                    } else {
                        continue;
                    }
                }
                $this->col2Val[$col->name] = $val;
            }
            
            // Insert the POST values we collected above.
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
            
            // If we added a new chug, update the exclusion table.
            if ($this->addChugPage) {
                $myChugName = $this->col2Val["name"];
                $db = new DbConn();
                $db->addIgnore();
                $db->addColumn("left_chug_name", $myChugName, 's');
                $db->addColumn("right_chug_name", $myChugName, 's');
                $insertOk = $db->insertIntoTable("chug_dedup_instances", $this->dbErr);
                if (! $insertOk) {
                    error_log("Insert into chug_dedup_instances failed: $this->dbErr");
                    return;
                }
            }
            
            $thisPage = basename($_SERVER['PHP_SELF']);
            $editPage = preg_replace('/^add/', "edit", $thisPage); // e.g., "addGroup.php" -> "editGroup.php"
            echo(genPassToEditPageForm($editPage, $paramHash));
        }
        
        public function setAddChugPage() {
            $this->addChugPage = 1;
        }
        
        private $addChugPage;
    }
    
