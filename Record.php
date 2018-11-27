<?php

// '`', '`', ' AND ', ['aap', 'noot']  ==>  `aap` = ? AND `noot` = ? 
function glue($pre, $post, $delim, $elems) {
    $r = '';
    foreach ($elems as $elem) {
        if ($r !== '') {
            $r .= $delim;
        }
        $r .= $pre . $elem . $post;
    }
    return $r;
}

function dup($text, $delim, $count) {
    $r = '';
    for ($i = 0; $i < $count; $i++) {
        if ($r !== '') {
            $r .= $delim;
        }
        $r .= $text;
    }
    return $r;
}

abstract class Record {

    private $originalKeyValues;
    
    abstract static function getTableName();
    abstract static function getFields();
    abstract static function getKeyNames();
   
    function __construct($record = null) {

        if ($record !== null) {
            if (is_array($record)) {
                foreach ($this->getFieldNames() as $field) {
                    $this->$field = $record[$field];
                }
            } else if ($record == '') {
                foreach ($this->getFieldNames() as $field) {
                    $this->$field = '';
                }
            } else {
                foreach ($this->getFieldNames() as $field) {
                    $this->$field = $record->$field;
                }
            }
        }
        foreach ($this->getFieldNames() as $field) {
            if(!isset($this->$field)) {
                $this->$field = '';
            }
        }
        foreach ($this->getKeyNames() as $field) {
            $this->originalKeyValues[] = $this->$field;
        }
    }
    
    function getOriginalKeyValues() {
        return $this->originalKeyValues;
    }

    static function getObject($key, $class) {
        global $db;
        if (!is_array($key)) {
            if(substr($key, 0, 1) === '{') {
                return self::getObjectByExpression($key, $class);
            }
            $key = [$key];
        }
        $table = $class::getTableName();
        $sql = "SELECT * FROM `$table` WHERE " . glue('`', '` = ?', ' AND ', $class::getKeyNames());
        $result = $db->execute($class, $sql, $key);
        return $result->fetch();
    }
    private static function getObjectByExpression($keystring, $class) {
        global $db;
        $keypairs = get_object_vars(json_decode(urldecode($keystring)));
        $keys = array_keys($keypairs);
        $values = array_values($keypairs);
        $table = $class::getTableName();
        $sql = "SELECT * FROM `$table` WHERE " . glue('`', '` = ?', ' AND ', $keys);
        $result = $db->execute($class, $sql, $values);
        return $result->fetch();
    }


    function getValues() {
        $values = [];
        foreach ($this->getFieldNames() as $field) {
            $values[] = $this->$field;
        }
        return $values;
    }

    /**
     * get an array of key values
     * @return array
     */
    function getKeyValues() {
        $key = [];
        foreach ($this->getKeyNames() as $field) {
            $key[$field] = $this->$field;
        }
        return $key;
    }

    /*
     * get key values as one string
     * @return string
     */

    function getKeyString() {
        return urlencode(json_encode($this->getKeyValues()));
    }
    
    function update() {
        global $db;
        $sql = 'UPDATE `' . $this->getTableName();
        $sql .= '` SET ' . glue('`', '` = ?', ', ', $this->getFieldNames());
        $sql .= ' WHERE ' . glue('`', '` = ?', ' AND ', $this->getKeyNames());
        $db->execute('', $sql, $this->getValues(), $this->getOriginalKeyValues());
    }
    
    function insert() {
        global $db;
        $cols = $this->getFieldNames();
        $sql = 'INSERT INTO `' . $this->getTableName();
        $sql .= '` (' . glue('`', '`', ', ', $cols) . ') ';
        $sql .= ' VALUES (' . dup('?', ', ', count($cols)) . ') ';
        $db->execute('', $sql, $this->getValues());
    }

}
