<?php
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
    }

    class FormItemSingleTextField extends FormItem {
        public function renderHtml() {
            $this->html .= "<div>\n";
            $this->html .= "<input id=\"$this->inputName\" name=\"$this->inputName\" " .
            "class=\"$this->inputClass\" type=\"$this->inputType\" $this->inputMaxLengthHtml " .
            "value=\"$this->inputValue\"/>\n";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</div></li>\n";
            
            return $this->html;
        }
    }
    
    class FormItemTextArea extends FormItem {
        public function renderHtml() {
            $this->html .= "<div>\n";
            $this->html .= "<textarea id=\"$this->inputName\" name=\"$this->inputName\" " .
            "class=\"$this->inputClass\" $this->inputMaxLengthHtml >$this->inputValue</textarea>\n";
            if ($this->error) {
                $this->html .= "<span class=\"error\">$this->error</span>\n";
            }
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</div></li>\n";
            
            return $this->html;
        }
    }
    
    class FormItemInstanceChooser extends FormItem {
        public function renderHtml() {
            $this->html .= "<div>\n";
            $this->html .= genCheckBox($this->id2Name, $this->activeIdHash, $this->inputName);
            if ($this->guideText) {
                $guideId = "guide_" . $this->liNum;
                $this->html .= "<p class=\"guidelines\" id=\"$guideId\"><small>$this->guideText</small></p>\n";
            }
            $this->html .= "</div></li>\n";
            
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
    
        
        
    
    



    
            
    