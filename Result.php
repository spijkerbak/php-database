<?php

class Result {

    private $class;
    private $stmt;

    private function trace($i) {
        $t = debug_backtrace()[$i];
        echo $t['file'] . '(' . $t['line'] . ')<br>';
    }

    function __construct($class, $stmt) {
        $stclass = get_class($stmt);
        if ($stclass !== 'PDOStatement') {
            echo "\nTrying to create Result from {$stclass}<br>\n";
            $this->trace(1);
            $this->trace(2);
        }
        $this->class = $class;
        $this->stmt = $stmt;
    }

    function fetch() {
        if ($this->stmt == null) {
            return null;
        }
        $this->stmt->setFetchMode(PDO::FETCH_CLASS, $this->class);
        $result = $this->stmt->fetch();
        return $result !== false ? $result : null;
    }

}
