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

    class FormItemSingleTextField extends FormItem {
        public function renderHtml() {
            if (! $this->staffOnlyOk()) {
                return;
            }
            $ph = ($this->placeHolder) ? $this->placeHolder : $this->inputName;
            $this->html .= "<div>\n";
            $this->html .= "<input id=\"$this->inputName\" name=\"$this->inputName\" placeholder=\"$ph\" " .
            "class=\"$this->inputClass\" type=\"$this->inputType\" $this->inputMaxLengthHtml " .
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
            "class=\"$this->inputClass\" $this->inputMaxLengthHtml >$this->inputValue</textarea>\n";
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
            $ph = ($this->placeHolder) ? $this->placeHolder : $this->inputName;
            $this->html .= "<div>\n";
            $this->html .= "<select class=\"$this->inputClass\" id=\"$this->inputName\" name=\"$this->inputName\"> placeholder=\"$ph\">";
            $this->html .= genPickList($this->id2Name, $this->colVal, $this->inputSingular); // $inputSingular = e.g., "group"
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
        
        public function fillDropDownId2Name(&$dbErr, $idCol, $table) {
            fillId2Name($this->id2Name, $dbErr,
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
        
        private $id2Name = array();
        private $inputSingular = "";
        private $colVal = "";
    }
    
    



    
            
    