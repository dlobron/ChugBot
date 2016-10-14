<?php
    include_once 'dbConn.php';
    include_once 'functions.php';

    abstract class FormItem {
        abstract protected function renderHtml();
        
        function __construct($desc, $req, $inputName, $liNum) {
            $this->description = $desc;
            $this->required = $req;
            $this->inputName = $inputName;
            $this->liNum = $liNum;
            
            // Initialize HTML with text that is common to all subclasses.
            $this->html = "<li id=\"li_$this->liNum\">\n";
            $this->html .= "<label class=\"description\" for=\"$this->inputName\">";
            if ($this->required) {
                $this->html .= "<font color=\"red\">*</font>";
            }
            $this->html .= " $this->description</label>";
        }
        
        public function setInputMaxLength($maxLen) {
            $this->inputMaxLengthHtml = "maxlength=$maxLen";
        }
        
        public function setInputType($it) {
            $this->inputType = $it;
        }
        
        public function setInputClass($ic) {
            $this->inputClass = $ic;
        }
        
        public function setInputValue($val) {
            if ($val == NULL) {
                return;
            }
            $this->inputValue = $val;
        }
        
        public function setError($err) {
            $this->error = $err;
        }
        
        public function setGuideText($gt) {
            $this->guideText = $gt;
        }
        
        public function setPlaceHolder($ph) {
            $this->placeHolder = $ph;
        }
        
        public function setStaffOnly($so) {
            $this->staffOnly = $so;
        }
        
        public function staffOnlyOk() {
            if (! $this->staffOnly) {
                return TRUE;
            }
            return isset($_SESSION['admin_logged_in']);
        }
        
        protected $description;
        protected $required;
        protected $inputName;
        protected $inputClass;
        protected $inputType;
        protected $formItemType;
        protected $liNum;
        protected $inputMaxLengthHtml = "";
        protected $inputValue = "";
        protected $error = "";
        protected $guideText = "";
        protected $html = "";
        protected $placeHolder = "";
        protected $staffOnly = FALSE;
    }
    
    class FormItemCheckBox extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $this->html .= "<div>\n";
            $this->html .= "<input id=\"$this->inputName\" name=\"$this->inputName\" type=\"checkbox\"";
            if ($this->inputValue) {
                $this->html .= " checked=\"checked\"";
            }
            $this->html .= ">";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            $this->html .= "</div>";
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</li>\n";
            
            return $this->html;
        }
    }
    
    class FormItemRadio extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $this->html .= "<input name=\"$this->inputName\" value=\"$this->inputValue\" type=\"radio\"";
            if ($this->checked) {
                $this->html .= " checked";
            }
            $this->html .= ">";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</li>\n";
            
            return $this->html;
        }
        
        public function radioSetChecked() {
            $this->checked = TRUE;
        }
        
        protected $checked = FALSE;
    }

    class FormItemSingleTextField extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $ph = ($this->placeHolder) ? $this->placeHolder : $this->inputName;
            $this->html .= "<div>\n";
            $this->html .= "<input id=\"$this->inputName\" name=\"$this->inputName\" placeholder=\"$ph\" " .
            "class=\"form-control $this->inputClass\" type=\"$this->inputType\" $this->inputMaxLengthHtml " .
            "value=\"$this->inputValue\"/>\n";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            $this->html .= "</div>";
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</li>\n";
            
            return $this->html;
        }
    }
    
    class FormItemTextArea extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $ph = ($this->placeHolder) ? $this->placeHolder : $this->inputName;
            $this->html .= "<div>\n";
            $this->html .= "<textarea id=\"$this->inputName\" name=\"$this->inputName\" \"$this->inputName\" placeholder=\"$ph\" " .
            "class=\"form-control $this->inputClass\" $this->inputMaxLengthHtml >$this->inputValue</textarea>\n";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            $this->html .= "</div>";
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</li>\n";
            
            return $this->html;
        }
    }
    
    class FormItemInstanceChooser extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $this->html .= "<div>\n";
            $this->html .= genCheckBox($this->id2Name, $this->activeIdHash, $this->inputName);
            $this->html .= "</div>";
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</li>\n";
            
            return $this->html;
        }
        
        public function setId2Name($id2Name) {
            $this->id2Name = $id2Name;
        }
        
        public function setActiveIdHash($activeIdHash) {
            $this->activeIdHash = $activeIdHash;
        }
        
        private $id2Name = array();
        private $activeIdHash = array();
    }
    
    class FormItemDropDown extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            if ($this->displayListName) {
                $javascript = $this->selectIngredientText();
                $this->html .= "\n$javascript\n";
            }
            $ph = ($this->placeHolder) ? $this->placeHolder : $this->inputName;
            $this->html .= "<div>\n";
            $selectedMap = array();
            $ocStr = "";
            if ($this->displayListName) {
                $this->html .= "<ul class=\"options_toggle\">";
                foreach ($this->displayListSelectedIds as $selectedId) {
                    $name = $this->id2Name[$selectedId];
                    $this->html .= "<li onclick=\"this.parentNode.removeChild(this);\">";
                    $this->html .= "<input type=\"hidden\" name=\"$this->displayListName[]\" value=\"$selectedId\" />";
                    $this->html .= "$name</li>";
                    $selectedMap[$selectedId] = 1;
                }
                $this->html .= "</ul>";
                $ocStr = "onchange=\"selectIngredient(this);\"";
            } else {
                $selectedMap[$this->colVal] = 1;
            }
            $this->html .= "<select class=\"form-control $this->inputClass\" id=\"$this->inputName\" name=\"$this->inputName\" placeholder=\"$ph\" $ocStr>";
            $this->html .= genPickList($this->id2Name, $selectedMap,
                                       $this->inputSingular, $this->defaultMsg); // $inputSingular = e.g., "group"
            $this->html .= "</select>";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>";
            }
            $this->html .= "</div>";
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>";
            }
            $this->html .= "</li>";
            
            return $this->html;
        }
        
        // Emit JS for the chug-selection drop-down.  We return if the index
        // is zero, because the first item in the drop-down is always the
        // default prompt "Choose One" text.  We also return if the user
        // chooses a duplicate.
        public function selectIngredientText() {
            $name = $this->displayListName;
            $javascript = <<<JS
            <script>
            function selectIngredient(select)
            {
                if (select.selectedIndex == 0)
                    return;
                var option = select.options[select.selectedIndex];
                var ul = select.parentNode.getElementsByTagName('ul')[0];
                
                var choices = ul.getElementsByTagName('input');
                for (var i = 0; i < choices.length; i++)
                    if (choices[i].value == option.value)
                        return;
                
                var li = document.createElement('li');
                var input = document.createElement('input');
                var text = document.createTextNode(option.firstChild.data);
                
                input.type = 'hidden';
                input.name = "${name}[]";
                input.value = option.value;
                
                li.appendChild(input);
                li.appendChild(text);
                li.setAttribute('onclick', 'this.parentNode.removeChild(this);');     
                
                ul.appendChild(li);
            }
            </script>
JS;
            return $javascript;
        }
        
        public function fillDropDownId2Name(&$dbErr, $idCol, $table) {
            fillId2Name(NULL, $this->id2Name, $dbErr,
                        $idCol, $table);
        }
        
        public function setId2Name($id2Name) {
            $this->id2Name = $id2Name;
        }
        
        public function setInputSingular($is) {
            $this->inputSingular = $is;
        }
        
        public function setColVal($cv) {
            if ($cv != NULL) {
                $this->colVal = $cv;
            }
        }
        
        public function setDefaultMsg($msg) {
            $this->defaultMsg = $msg;
        }
        
        public function setDisplayListSelectedIds($displayListSelectedIds) {
            $this->displayListSelectedIds = $displayListSelectedIds;
        }
        
        public function setDisplayListName($displayListName) {
            $this->displayListName = $displayListName;
        }
        
        private $id2Name = array();
        private $inputSingular = "";
        private $colVal = "";
        private $defaultMsg = NULL;
        
        // These variables are only used if there is a display list.
        private $displayListSelectedIds = array();
        private $displayListName = NULL;
    }
    
    // This class generates a drop down that depends on the choices made in
    // a parent drop down.
    class FormItemConstrainedDropDown extends FormItem {
        function __construct($desc, $req, $inputName, $liNum, $sql) {
            parent::__construct($desc, $req, $inputName, $liNum);
            $this->sql = $sql;
        }
        
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $parentId = $this->parentId;
            $parentName = $this->parentName;
            $ourId = $this->inputName;
            $ourCurrentValue = $this->colVal; // Might be empty.
            $sql = $this->sql;
            $javascript = <<<JS
<script src="jquery/jquery-1.11.3.min.js"></script>
<script src="jquery/ui/1.11.4/jquery-ui.js"></script>
<script>
function fillConstraints() {
    var parent = $("#${parentId}");
    var parentName = "$parentName";
    var ourDropDown = $("#${ourId}");
    var selected = "$ourCurrentValue";
    var values = {};
    values["get_legal_id_to_name"] = 1;
    var curSelectedEdahId = $(parent).find(':selected').val(); // Selected value in parent drop-down.
    var curSelectedEdahName = $(parent).find(':selected').text();
    values["instance_id"] = curSelectedEdahId;
    values["sql"] = "$sql";
    $.ajax({
         url: 'ajax.php',
         type: 'post',
         data: values,
         success: function(data) {
           ourDropDown.empty();
           var html = "";
           var hadSel = 0;
           $.each(data, function(itemId, itemName) {
                  var optionText = "<option value=\"" + itemId + "\"";
                  if (itemId == selected) {
                      optionText += " selected";
                      hadSel = 1;
                  }
                  optionText += " >" + itemName + "</option>";
                  html += optionText;
           });
           // Prepend a -- option for no choice.  Make this the selected
           // option if we didn't have one above.
           var noBunkStr = "<option value=\"\" ";
           if (hadSel == 0) {
              noBunkStr += "selected";
           }
           noBunkStr += ">--</option>";
           html = noBunkStr + html;
           // Display special text if no options were found.
           if (html.length == 0) {
                if (curSelectedEdahId) {
                    html = "<option value=\"\" disabled>--None Available for " + curSelectedEdahName + "--</option>";
                } else if (parentName.length > 0) {
                    html = "<option value=\"\" disabled>--Select " + parentName + " First--</option>";
                } else {
                    html = "<option value=\"\" disabled>---</option>";
                }
           }
           $(ourDropDown).append(html);
        },
        error: function(xhr, desc, err) {
            console.log(xhr);
            console.log("Details: " + desc + " Error:" + err);
        }
    });
}
$(function() {
  $("select#${parentId}").load(fillConstraints());
  $("select#${parentId}").bind('change',fillConstraints);
});
            
</script>
JS;
            $ph = ($this->placeHolder) ? $this->placeHolder : $this->inputName;
            $this->html .= "<div>\n";
            $this->html .= "<select class=\"$this->inputClass\" id=\"$this->inputName\" name=\"$this->inputName\" placeholder=\"$ph\">\n";
            $this->html .= "</select>\n";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            $this->html .= "</div>\n";
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</li>\n";
            
            $this->html .= "\n$javascript\n";
            
            return $this->html;
        }
        
        public function setParentIdAndName($pid, $pn) {
            $this->parentId = $pid;
            $this->parentName = $pn;
        }
        
        public function setColVal($cv) {
            if ($cv != NULL) {
                $this->colVal = $cv;
            }
        }
        
        private $sql = NULL; // e.g., "SELECT b.bunk_id id_val, b.name name_val FROM bunks b, bunk_instances i WHERE b.bunk_id = i.bunk_id AND i.edah_id = ?";
        private $parentId = NULL;
        private $colVal = "";
        private $parentName = "";
    }
            
    