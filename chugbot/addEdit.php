<?php
    include_once 'formItem.php';
    
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
            $this->mysqli = connect_db();
        }
        
        function __destruct() {
            $this->mysqli->close();
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
        
        public function setSubmitAndContinueTarget($sact) {
            $this->submitAndContinueTarget = $sact;
        }
        
        abstract protected function renderForm();
        
        public $title;
        public $dbErr = "";
        public $colName2Error = array();
        public $mysqli;
        protected $resultStr = "";
        protected $firstParagraph;
        protected $secondParagraph;
        protected $formItems = array();
        protected $submitAndContinueTarget = NULL;
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
        }
        
        public function columnValue($column) {
            if (! array_key_exists($column, $this->col2Val)) {
                return NULL;
            }
            return $this->col2Val[$column];
        }
        
        public function fillInstanceId2Name($instanceIdCol, $instanceTable) {
            fillId2Name($this->mysqli, $this->instanceId2Name, $this->dbErr,
                        $instanceIdCol, $instanceTable);
            $this->instanceIdCol = $instanceIdCol;
            $this->instanceIdsIdentifier = $instanceIdCol . "s";
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
            $html = "<img id=\"top\" src=\"images/top.png\" alt=\"\">";
            if ($this->resultStr) {
                $html .= "<div id=\"centered_container\">$this->resultStr</div>";
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
            if (! is_null($this->submitAndContinueTarget)) {
                $submitAndContinueText = "<input id=\"submitAndContinue\" class=\"button_text\" type=\"submit\" name=\"submitAndContinue\" value=\"Continue\" />";
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
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
<button onclick="history.go(-1);">Back </button>
$submitAndContinueText
$fromText
$cancelText
</li>
</ul>
</form>
<div id="footer">
$footerText
</div>
</div>
<img id="bottom" src="images/bottom.png" alt="">
</body>
</html>
EOM;
            echo $html;
        }
        
        protected function populateInstanceActionIds() {
            // If we have active instance IDs, grab them.
            populateActiveIds($this->instanceActiveIdHash, $this->instanceIdsIdentifier);
        }
        
        protected function updateActiveInstances($idVal) {
            if (empty($this->instanceIdsIdentifier)) {
                return TRUE; // No instances: not an error.
            }
            $sql = "DELETE FROM $this->instanceTable WHERE $this->idCol = $idVal";
            $submitOk = $this->mysqli->query($sql);
            if ($submitOk == FALSE) {
                $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                return FALSE;
            }
            foreach ($this->instanceActiveIdHash as $instanceId => $active) {
                $sql = "INSERT INTO $this->instanceTable ($this->idCol, $this->instanceIdCol) VALUES ($idVal, $instanceId)";
                $submitOk = $this->mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                    return FALSE;
                }
            }
            
            return TRUE;
        }
        
        public function setConstantIdValue($civ) {
            $this->constantIdValue = $civ;
        }
        
        public $mainTable;
        
        protected $idCol; // The ID column name of $this->mainTable
        protected $col2Val = array(); // Column name -> value (filled by us)
        protected $columns = array(); // Column names (filled by the caller)
        protected $editPage = FALSE;
        protected $constantIdValue = NULL;
        
        // The next columns pertain to items with per-item instances.
        // We make them public so users can grab them directly.
        public $instanceTable = "";
        public $instanceId2Name = array();
        public $instanceActiveIdHash = array();
        public $instanceIdsIdentifier = "";
        public $instanceIdCol = "";
        
        public $fromAddPage = FALSE;
        public $submitData = FALSE;
        public $fromHomePage = FALSE;
    }
    
    class EditPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
            $this->editPage = TRUE;
        }
        
        public function handlePost() {
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
                $submitAndContinue = FALSE;
                if (! empty($_POST["submitAndContinue"])) {
                    $submitAndContinue = TRUE;
                }
                // Get the ID of the item to be edited: this is required to either
                // exist in the POST or be set as a constant.
                $idVal = test_input($_POST[$this->idCol]);
                if (! $idVal) {
                    if (! is_null($self->constantIdValue)) {
                        $idVal = $self->constantIdValue;
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
                $sql = "SELECT * FROM $this->mainTable WHERE $this->idCol = $idVal";
                $result = $this->mysqli->query($sql);
                if ($result == FALSE) {
                    $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                    return;
                }
                $this->col2Val = $result->fetch_array(MYSQLI_ASSOC);
                // Populate active instance IDs, if configured.
                if (! empty($this->instanceIdsIdentifier)) {
                    $sql = "SELECT $this->instanceIdCol from $this->instanceTable where $this->idCol = $idVal";
                    $result = $this->mysqli->query($sql);
                    if ($result == FALSE) {
                        $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                        return;
                    }
                    while ($row = $result->fetch_array(MYSQLI_NUM)) {
                        $this->instanceActiveIdHash[$row[0]] = 1;
                    }
                    mysqli_free_result($result);
                }
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
            $addAnotherText = "";
            if (is_null($this->constantIdValue)) {
                // Only display an "add another" link for tables that allow multiple
                // rows.
                $addAnother = urlBaseText() . "/$addPage";
                $addAnotherText = "To add another $name, please click <a href=\"$addAnother\">here</a>.";
            }
            if ($this->submitData) {
                $i = 0;
                $sql = "UPDATE $this->mainTable SET "; // Common start to SQL
                foreach ($this->col2Val as $colName => $colVal) {
                    if ($colVal == NULL || empty($colVal)) {
                        $sql .= "$colName = NULL";
                    } else {
                        $sql .= "$colName = \"$colVal\"";
                    }
                    if ($i < (count($this->col2Val) - 1)) {
                        $sql .= ", ";
                    }
                    $i++;
                }
                $sql .=" WHERE $this->idCol = $idVal";
                $submitOk = $this->mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                    return;
                }
                // Update instances, if we have them.
                $instanceUpdateOk = $this->updateActiveInstances($idVal);
                if (! $instanceUpdateOk) {
                    return;
                }
                // If we've been asked to continue, do so here.  Set the ID
                // field in the _SESSION hash, so JQuery can grab it via an Ajax call.
                if ($submitAndContinue) {
                    $_SESSION["$this->idCol"] = $idVal;
                    $submitAndContinueUrl = urlIfy($this->submitAndContinueTarget);
                    header("Location: $submitAndContinueUrl");
                    exit;
                }
                $this->resultStr =
                    "<h3>$name updated!  Please edit below if needed, or return $homeAnchor. $addAnotherText</h3>";
            } else if ($this->fromAddPage) {
                $this->resultStr =
                "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor. $addAnotherText</h3>";
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
                if ($val == NULL) {
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
            
            // Build the insert SQL from the POST values we collected above.
            // It's critical that columns and their values be listed in the same order,
            // so we build the lists at the same time.
            $i = 0;
            $sqlStart = "INSERT INTO $this->mainTable (";
            $valueList = " VALUES (";
            foreach ($this->col2Val as $colName => $colVal) {
                $sqlStart .= "$colName";
                $valueList .= "\"$colVal\"";
                if ($i++ < (count($this->col2Val) - 1)) {
                    $sqlStart .= ", ";
                    $valueList .= ", ";
                }
            }
            $sqlStart .= ")";
            $valueList .= ")";
            $sql = $sqlStart . $valueList;
            $submitOk = $this->mysqli->query($sql);
            if ($submitOk == FALSE) {
                $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                return;
            }
            
            // If we have instances, update them.
            $mainTableInsertId = $this->mysqli->insert_id;
            $instanceUpdateOk = $this->updateActiveInstances($mainTableInsertId);
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
            
            $thisPage = basename($_SERVER['PHP_SELF']);
            $editPage = preg_replace('/^add/', "edit", $thisPage); // e.g., "addGroup.php" -> "editGroup.php"
            echo(genPassToEditPageForm($editPage, $paramHash));
        }
    }
    
