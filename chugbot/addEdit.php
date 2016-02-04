<?php
    include_once 'formItem.php';
    
    abstract class ColumnType
    {
        const CT_STR =  0;
        const CT_INT =  1;
        const CR_DATE = 2;
    }
    
    class Column {
        function __construct($name, $type = ColumnType::CT_STR,
                             $required = TRUE, $defaultValue = NULL) {
            $this->name = $name;
            $this->type = $type;
            $this->required = $required;
            $this->defaultValue = $defaultValue; // May be NULL.
        }
        
        public $name;
        public $type;
        public $required;
        public $defaultValue;
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
        
        abstract protected function renderForm();
        
        public $title;
        public $dbErr = "";
        public $nameErr = "";
        protected $resultStr = "";
        protected $firstParagraph;
        protected $secondParagraph;
        protected $formItems = array();
        protected $mysqli;
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
        
        public function addColumn($name, $type = ColumnType::CT_STR,
                                  $required = TRUE) {
            $col = new Column($name, $type, $required);
            array_push($this->columns, $col);
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
            echo headerText($this->title);
            $errText = genFatalErrorReport(array($this->dbErr, $this->nameErr));
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
            
            $cancelText = staffHomeAnchor("Cancel");
            $footerText = footerText();
            $fromEditText = "";
            if ($this->editPage) {
                $val = $this->col2Val[$this->idCol];
                $fromEditText = "<input type=\"hidden\" name=\"submitData\" value=\"1\">";
                $fromEditText .= "<input type=\"hidden\" name=\"$this->idCol\" " .
                "id=\"$this->idCol\" value=\"$val\"/>";
            }
            $html .= <<<EOM
<li class="buttons">
<input type="hidden" name="form_id" value="$formId" />
<input id="saveForm" class="button_text" type="submit" name="submit" value="Submit" />
$fromEditText
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
            if (empty($this->instanceIdsIdentifier) ||
                empty($_POST[$this->instanceIdsIdentifier])) {
                return; // No instances.
            }
            foreach ($_POST[$this->instanceIdsIdentifier] as $instance_id) {
                $instanceId = test_input($instance_id);
                if (empty($instanceId)) {
                    continue;
                }
                $this->instanceActiveIdHash[$instanceId] = 1;
            }
        }
        
        protected function updateActiveInstances($idVal) {
            if (empty($this->instanceIdsIdentifier)) {
                return; // No instances.
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
        
        public $mainTable;
        protected $idCol; // The ID column name of $this->mainTable
        protected $col2Val = array(); // Column name -> value (filled by us)
        protected $columns = array(); // Column names (filled by the caller)
        protected $editPage = FALSE;
        // The next columns pertain to items with per-item instances.
        // We make them public so users can grab them directly.
        public $instanceTable = "";
        public $instanceId2Name = array();
        public $instanceActiveIdHash = array();
        public $instanceIdsIdentifier = "";
        public $instanceIdCol = "";
    }
    
    class EditPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
            $this->editPage = TRUE;
        }
        
        public function handlePost() {
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                return;
            }
            if (! empty($_POST["fromAddPage"])) {
                $this->fromAddPage = TRUE;
            }
            if (! empty($_POST["submitData"])) {
                $this->submitData = TRUE;
            }
            if (! empty($_POST["fromStaffHomePage"])) {
                // If we're coming from the staff home page, we need to get our
                // column values from the DB.
                $this->col2Val[$this->idCol] = test_input($_POST["itemId"]);
                $idVal = $this->col2Val[$this->idCol];
                $sql = "SELECT * FROM $this->mainTable WHERE $this->idCol = $idVal";
                $result = $this->mysqli->query($sql);
                if ($result == FALSE) {
                    $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                    return;
                }
                $this->col2Val = $result->fetch_array(MYSQLI_ASSOC);
                // If a DB column has a default, set it to the empty string
                // for display.
                foreach ($this->columns as $col) {
                    if ($col->defaultValue == NULL) {
                        continue;
                    }
                    if (! array_key_exists($col->name, $this->col2Val)) {
                        continue;
                    }
                    if ($this->col2Val[$col->name] == $col->defaultValue) {
                        $this->col2Val[$col->name] = "";
                    }
                }
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
                // be in the POST data.
                $this->col2Val[$this->idCol] = test_input($_POST[$this->idCol]);
                foreach ($this->columns as $col) {
                    $val = test_input($_POST[$col->name]);
                    if ($val == NULL || empty($val)) {
                        if ($col->required) {
                            $this->nameErr = errorString("Missing required column " . $col->name);
                            return;
                        }
                        continue;
                    }
                    $this->col2Val[$col->name] = $val;
                }
                // If we have active instance IDs, grab them.
                $this->populateInstanceActionIds();
            }
            if (! array_key_exists($this->idCol, $this->col2Val)) {
                $this->nameErr = errorString("ID is required");
                return;
            }
            
            $homeAnchor = staffHomeAnchor();
            $addPage = preg_replace('/^Edit /', "add", $this->title);
            $thingAdded = preg_replace('/^Edit /', "", $this->title);
            $addPage .= ".php";
            $addAnother = urlBaseText() . "/$addPage";
            $idVal = $this->col2Val[$this->idCol];
            $name = $this->col2Val["name"];
            if ($this->submitData) {
                $sql = "UPDATE $this->mainTable SET "; // Common start to SQL
                for ($i = 0; $i < count($this->columns); $i++) {
                    $col = $this->columns[$i];
                    if (array_key_exists($col->name, $this->col2Val)) {
                        $val = $this->col2Val[$col->name];
                        $sql .= "$col->name = \"$val\"";
                        if ($i < (count($this->columns) - 1)) {
                            $sql .= ", ";
                        }
                    }
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
                $this->resultStr =
                    "<h3>$name updated!  Please edit below if needed, or return $homeAnchor.  " .
                    "To add another $thingAdded, please click <a href=\"$addAnother\">here</a>.</h3>";
            } else if ($this->fromAddPage) {
                $this->resultStr =
                "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.  " .
                "To add another $thingAdded, please click <a href=\"$addAnother\">here</a>.</h3>";
            }
        }
        
        public $fromAddPage = FALSE;
        public $submitData = FALSE;
    }

    class AddPage extends AddEditBase {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
        }

        public function handlePost() {
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                return;
            }
            // Grab the column values.
            foreach ($this->columns as $col) {
                $val = test_input($_POST[$col->name]);
                if (empty($val)) {
                    if ($col->required) {
                        $this->nameErr = errorString("Missing value for required column " . $col->name);
                        return;
                    }
                    continue;
                }
                $this->col2Val[$col->name] = $val;
            }
            
            // If we have active instance IDs, grab them.
            $this->populateInstanceActionIds();
            
            $sql = "INSERT INTO $this->mainTable (";
            $insertOrderCols = array();
            foreach ($this->col2Val as $colName => $colVal) {
                $sql .= "$colName";
                array_push($insertOrderCols, $colName);
                if (count($insertOrderCols) < (count($this->col2Val))) {
                    $sql .= ", ";
                }
            }
            $sql .= ") VALUES (";
            for ($i = 0; $i < count($insertOrderCols); $i++) {
                $colName = $insertOrderCols[$i];
                $colVal = $this->col2Val[$colName];
                $sql .= "\"$colVal\"";
                if ($i < (count($insertOrderCols) - 1)) {
                    $sql .= ", ";
                }
            }
            $sql .= ")";
            $submitOk = $this->mysqli->query($sql);
            if ($submitOk == FALSE) {
                $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                return;
            }
            
            // If we have instances, update those.
            $mainTableInsertId = $this->mysqli->insert_id;
            $instanceUpdateOk = $this->updateActiveInstances($mainTableInsertId);
            if (! $instanceUpdateOk) {
                return;
            }
            
            $this->col2Val[$this->idCol] = $mainTableInsertId;
            $paramHash = array($this->idCol => $mainTableInsertId,
                               "name" => $this->col2Val["name"]);
            // Add instance info, if we have it.
            if (count($this->instanceActiveIdHash) > 0) {
                $key = $this->instanceIdsIdentifier . "[]";
                $paramHash[$key] = array_keys($this->instanceActiveIdHash);
            }
            
            $editPage = preg_replace('/^\w+ /', "edit", $this->title); // e.g., "Add Group" -> "editGroup"
            $editPage .= ".php";
            echo(genPassToEditPageForm($editPage, $paramHash));
        }
    }
    