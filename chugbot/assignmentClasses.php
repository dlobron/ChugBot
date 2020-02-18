<?php
class Chug
{
    public function __construct($name, $max_size, $min_size, $chug_id)
    {
        $this->name = $name;
        $this->max_size = intval($max_size);
        $this->min_size = intval($min_size);
        $this->chug_id = intval($chug_id);
        // A max of 0 means no max: set to our "infinity" value.
        if ($this->max_size == 0) {
            $this->max_size = MAX_SIZE_NUM;
        }
    }
    public function chugFree()
    {
        return ($this->max_size > $this->assigned_count);
    }
    public $name = "";
    public $max_size = 0;
    public $min_size = 0;
    public $chug_id = -1;
    public $assigned_count = 0;
    public $group_id = -1;
};

class Camper
{
    public function __construct($camper_id, $first, $last, $needs_first_choice)
    {
        $this->camper_id = intval($camper_id);
        $this->name = $first;
        $this->name .= " " . $last;
        $this->needs_first_choice = intval($needs_first_choice);
    }
    public $camper_id = -1;
    public $name = "";
    public $needs_first_choice = 0;
    public $choice_level = 0;
    public $prefs = array();
};
