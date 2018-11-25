<?php
namespace database;

class Field {

    // non numbers
    const STRING = 1;
    const DATE = 2;
    
    // numbers
    const NUMERIC = 128; // flag
    const INT = 128;
    const FLOAT = 129;
    const DOUBLE = 130;
    
    // flags
    const PK = 2 ** 8;
    const HIDE = 2 ** 9;
    const LOOKUP = 2 ** 10;
    const COMPUTED = 2 ** 11;

    public $name;
    public $type;
    public $width;
    public $label;

    function __construct($type, $param = null, $class = '') {
        $this->type = $type;
        $this->class = $class;
        if ($this->type & Field::LOOKUP) {
            $this->width = 0;
            $this->lookup = $param;
        } else {
            $this->width = $param;
            $this->lookup = '';
        }
    }

    function isPK() {
        return ($this->type & Field::PK) != 0;
    }

    function isNumeric() {
        return ($this->type & Field::NUMERIC) != 0;
    }

    function isHidden() {
        return ($this->type & Field::HIDE) != 0;
    }

    function isComputed() {
        return ($this->type & Field::COMPUTED) != 0;
    }

}

