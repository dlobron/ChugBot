<?php
    include_once 'formItem.php';
    
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
        public $name;
        public $dbErr = "";
        public $nameErr = "";
        protected $resultStr = "";
        protected $firstParagraph;
        protected $formItems = array();
        protected $mysqli;
    }
    
    abstract class SingletonPage extends FormPage {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph);
            $this->mainTable = $mainTable;
            $this->idCol = $idCol;
        }
        
        abstract protected function handlePost();
        
        public function renderForm($fromEdit = FALSE) {
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
            $html .= <<<EOM
<img id="top" src="images/top.png" alt="">
<div class="form_container">
            
<h1><a>$this->title</a></h1>
<form id="$formId" class="appnitro" method="post" action="$actionTarget">
<div class="form_description">
<h2>$this->title</h2>
<p>$this->firstParagraph (<font color="red">*</font> = required field)</p>
</div>
<ul>
            
EOM;
            foreach ($this->formItems as $formItem) {
                $html .= $formItem->renderHtml();
            }
            
            $cancelText = staffHomeAnchor("Cancel");
            $footerText = footerText();
            $fromEditText = "";
            if ($fromEdit) {
                $fromEditText = "<input type=\"hidden\" name=\"submitData\" value=\"1\">";
                $fromEditText .= "<input type=\"hidden\" name=\"$this->idCol\" " .
                "id=\"$this->idCol\" value=\"$this->idVal\"/>";
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
        
        public $mainTable;
        protected $idCol;
        protected $idVal;
    }
    
    class EditSingletonPage extends SingletonPage {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
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
                // If we're coming from the staff home page, we need to get the name from the
                // ID value.  Also, the ID will have a generic name.
                $this->idVal = test_input($_POST["itemId"]);
                $sql = "SELECT name FROM $this->mainTable WHERE $this->idCol = $this->idVal";
                $result = $this->mysqli->query($sql);
                if ($result == FALSE) {
                    $dbErr = dbErrorString($sql, $this->mysqli->error);
                } else {
                    $row =  $result->fetch_array(MYSQLI_NUM);
                    $this->name = $row[0];
                }
            } else {
                // From other sources (our add or edit page), we expect to have a name and ID column.
                $this->idVal = test_input($_POST[$this->idCol]);
                $this->name = test_input($_POST["name"]);
            }
            
            // At this point, we should have a name and ID.
            if (empty($this->name)) {
                $nameErr = errorString("Name is required");
            }
            if (empty($this->idVal)) {
                $nameErr = errorString("ID is required");
            }
            
            if (empty($nameErr)) {
                $homeAnchor = staffHomeAnchor();
                $addPage = preg_replace('/^Edit /', "add", $this->title);
                $thingAdded = preg_replace('/^Edit /', "", $this->title);
                $addPage .= ".php";
                $addAnother = urlBaseText() . "/$addPage";
                if ($this->submitData) {
                    $sql =
                    "UPDATE $this->mainTable SET name = \"$this->name\" " .
                    "WHERE $this->idCol = $this->idVal";
                    $submitOk = $this->mysqli->query($sql);
                    if ($submitOk == FALSE) {
                        $dbErr = dbErrorString($sql, $mysqli->error);
                    } else {
                        $this->resultStr =
                        "<h3>$this->name updated!  Please edit below if needed, or return $homeAnchor.  " .
                        "To add another $thingAdded, please click <a href=\"$addAnother\">here</a>.</h3>";
                    }
                } else if ($fromAddPage) {
                    $this->resultStr =
                    "<h3>$name added successfully!  Please edit below if needed, or return $homeAnchor.  " .
                    "To add another $thingAdded, please click <a href=\"$addAnother\">here</a>.</h3>";
                }
            }
        }
        
        public $fromAddPage = FALSE;
        public $submitData = FALSE;
    }

    class AddSingletonPage extends SingletonPage {
        function __construct($title, $firstParagraph, $mainTable, $idCol) {
            parent::__construct($title, $firstParagraph, $mainTable, $idCol);
        }

        public function handlePost() {
            if ($_SERVER["REQUEST_METHOD"] != "POST") {
                return;
            }
            $this->name = test_input($_POST["name"]);
            if (empty($this->name)) {
                $this->nameErr = errorString("Name is required");
            }
            if (empty($this->nameErr)) {
                $sql = "INSERT INTO $this->mainTable (name) VALUES (\"$this->name\");";
                $submitOk = $this->mysqli->query($sql);
                if ($submitOk == FALSE) {
                    $this->dbErr = dbErrorString($sql, $this->mysqli->error);
                }
                $this->idVal = $this->mysqli->insert_id;
                if ($submitOk == TRUE) {
                    $paramHash = array($this->idCol => $this->idVal,
                                       "name" => $this->name);
                    $editPage = preg_replace('/^\w+ /', "edit", $this->title); // e.g., "Add Group" -> "editGroup"
                    $editPage .= ".php";
                    echo(genPassToEditPageForm($editPage, $paramHash));
                }
            }
        }
    }
    