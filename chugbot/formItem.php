<?php
    include_once 'functions.php';

    abstract class FormItem {
        abstract protected function renderHtml();
        
        function __construct($desc, $req, $inputName, $inputClass,
                             $inputType, $liNum) {
            $this->description = $desc;
            $this->required = $req;
            $this->inputName = $inputName;
            $this->inputClass = $inputClass;
            $this->inputType = $inputType;
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
            $this->inputMaxLength = $maxLen;
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
        protected $inputMaxLength = 255;
        protected $inputValue = "";
        protected $error = "";
        protected $guideText = "";
        protected $html = "";
    }

    class FormItemSingleTextField extends FormItem {
        public function renderHtml() {
            $this->html .= "<div>\n";
            $this->html .= "<input id=\"$this->inputName\" name=\"$this->inputName\" " .
            "class=\"$this->inputClass\" type=\"$this->inputType\" maxlength=\"$this->inputMaxLength\" " .
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
    
    



    
            
    