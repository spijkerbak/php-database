<?php

namespace database;

require_once 'Field.php';

abstract class Record {
    private $key; // keep original primary key, so primary key may be changed

    // static fields don't seem to be inherited, so we use static::class as index
    
    protected static $tableName = [];
    protected static $allFields = [];
    protected static $listFields = [];
    protected static $editFields = [];
    protected static $order = [];
    protected static $lookup = [];

    static function setAccess($canList, $canEdit) {
        
    }
    static function setTableName($tableName) {
        static::$tableName[static::class] = $tableName;
    }

    static function setFields($list) {
        static::$allFields[static::class] = $list;
    }

    static function setListFields($list) {
        static::$listFields[static::class] = self::selectFields($list);
    }

    static function setEditFields($list) {
        static::$editFields[static::class] = self::selectFields($list);
    }

    static function setOrder($order) {
        static::$order[static::class] = $order;
    }

    static function setLookup($lookup) {
        static::$lookup[static::class] = $lookup;
    }

    static function getTableName() {
        return static::$tableName[static::class];
    }

    static function getFields() {
        return static::$allFields[static::class];
    }

    static function getListFields() {
        return static::$listFields[static::class];
    }

    static function getEditFields() {
        return static::$editFields[static::class];
    }

    static function getOrder() {
        return static::$order[static::class];
    }

    static function getLookup() {
        $table = static::$tableName[static::class];
        $lookup = static::$lookup[static::class];
        $left =  $lookup[0];
        $right = $lookup[1];
        if(array_key_exists($right, static::$allFields)) {
            $right = "`{$right}`";
        }
        $sql = "SELECT `{$left}`, {$right} FROM [{$table}] ORDER BY `{$left}`";
        return db()->getIndexedColumn($sql);
    }

    function __construct() {
    // add fields not set by database 'pre-constructor'
        foreach (static::getFields() as $name => $field) {
            if (!isset($this->$name)) {
                $this->$name = null;
            }
        }
        $this->afterSet();
        $this->getKey();
    }

    protected function afterSet() {
        
    }

    public function getKey() {
        if ($this->key == null) {
            $this->key = [];
            foreach ($this->getFields() as $name => $field) {
                if ($field->type & Field::PK) {
                    $this->key[$name] = $this->$name;
                }
            }
        }
        return $this->key;
    }

    public function getKeyString($glue = '&') {
        $val = [];
        foreach ($this->getFields() as $name => $field) {
            if ($field->type & Field::PK) {
                $val[] = "{$name}={$this->$name}";
            }
        }
        return implode($glue, $val);
    }

    static function makeWhere($key, &$args) {
        $sql = '';
        $glue = ' WHERE ';
        $fields = static::getFields();
        foreach ($key as $name => $value) {
            if (array_key_exists($name, $fields)) {
                $sql .= "{$glue} `{$name}` = ? ";
                $glue = ' AND ';
                $args[] = $value;
            }
        }
        return $sql;
    }

    static function get($key) {
        $table = static::getTableName();
        if (!is_array($key)) {
            $key = ['ID' => $key];
        }
        $sql = 'SELECT * FROM [' . $table . ']';
        $args = [];
        $sql .= self::makeWhere($key, $args);

        if (count($args) == 0) { // empty key, would select all
            return null;
        } else {
            return db()->getObject(static::class, $sql, $args);
        }
    }

    public static function getAll($where = '', $args = []) {
        $table = static::getTableName();
        $sql = "SELECT * FROM [{$table}] " . $where;
        $order = static::getOrder();
        if (!empty($order)) {
            $sql .= " ORDER BY '" . implode("', '", $order) . "'";
        }
        return db()->getList(get_called_class(), $sql, $args);
    }

    protected static function selectFields($names) {
        $fields = static::getFields();
        $r = [];
        foreach ($names as $name) {
            $f = $fields[$name];
            if ($f->lookup == '') {
                $f->label = $name;
            } else {
                $f->label = $f->lookup;
            }
            $r[$name] = $f;
        }
        return $r;
    }

    public function set($set) {
        foreach ($this->getFields() as $name => $field) {
            if (!$field->isComputed()) {
                if (isset($set[$name])) {
                    $this->$name = $set[$name];
                }
            }
        }
        $this->afterSet();
    }

    public function setFromPost() {
        $this->set(Post::all());
    }

    public function update() {
        $table = static::getTableName();
        $sql = "UPDATE [{$table}] SET ";
        $c = '';
        $args = [];
        foreach ($this->getFields() as $name => $field) {
            if (isset($this->$name) && !$field->isComputed()) {
                if ($field->isNumeric() && $this->$name == '') {
                    $args[] = null;
                } else {
                    $args[] = $this->$name;
                }
                $sql .= $c . "`$name` = ?";
                $c = ', ';
            }
        }

        $sql .= self::makeWhere($this->getKey(), $args);
        db()->execute($sql, $args);
    }

    public function delete() {
        $table = static::getTableName();
        $sql = "DELETE FROM [{$table}] WHERE `ID` = ?";
        db()->execute($sql, [$this->ID], TXT("Can not delete, there are relations"));
    }

    public function insert() {
        $table = static::getTableName();
        $sql = "INSERT INTO [{$table}] (";
        $c = '';
        $args = [];
        $qms = '';
        foreach ($this->getFields() as $name => $field) {
            if (isset($this->$name) && !$field->isComputed()) {
                if ($field->isNumeric() && $this->$name == '') {
                    $args[] = null;
                } else {
                    $args[] = $this->$name;
                }
                $sql .= $c . "`$name`";
                $qms .= $c . '?';
                $c = ', ';
            }
        }
        $sql .= ") VALUES ({$qms})";
        db()->execute($sql, $args);
    }

}
