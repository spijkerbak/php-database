<?php

require_once 'Record.php';
require_once 'Result.php';

class Database extends PDO {

    public function __construct() {
        try {
            $host = 'localhost';
            $dbname = 'beauty';
//            $user = 'Marcia';
//            $pass = 'xxx';
            $user = 'root';
            $pass = '';

            $dsn = "mysql:dbname=$dbname;host=$host;charset=utf8"; // no hyphen in utf8
            parent::__construct($dsn, $user, $pass, null);
            $this->dbname = $dbname;
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('connection failed: ' . $e->getMessage());
        }
    }

    function fetchAll($sql, $values = []) { // question marked parameters
        //echo "<p>$sql</p>";
        $stmt = $this->prepare($sql);
        $i = 1;
        foreach ($values as $value) {
            $stmt->bindValue($i++, $value);
        }
        if ($stmt->execute() !== false) {
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        }
        return [];
    }

    function execute($class, $sql, $values = [], $morevalues = []) { // question marked parameters
        if (!is_array($values)) {
            $values = [$values];
        }
        if (!empty($morevalues)) {
            if (is_array($morevalues)) {
                $values = array_merge($values, $morevalues);
            } else {
                $values[] = $morevalues;
            }
        }
        $stmt = $this->prepare($sql);
//        $i = 1;
//        foreach ($values as $value) {
//            $stmt->bindValue($i++, $value);
//        }
        if ($class !== '') {
            if ($stmt->execute($values) !== false) {
                return new Result($class, $stmt);
            }
        } else {
            return $stmt->execute($values);
        }
        return null;
    }

}

$db = new Database();


// enable exception handler with try-catch for database and other functions!
set_error_handler(function($errno, $errstr, $errfile, $errline ) {
    //throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    // IGNORE warning from mail() about logging
    if (stripos($errstr, 'phpmaillog') !== false) {
        return;
    }

    echo "Fout $errno:<br>$errstr";
    echo '<br>';

    if (stristr($errstr, 'SQL') !== false) {
        echo '<br>Statement:<br>';
        echo Database::getLastSQL();
        echo '<br>';
        $argv = Database::getLastArgv();
        if (!empty($argv) && count($argv) !== 0) {
            echo '<br>Parameters:<br>';
            print_r(Database::getLastArgv());
            echo '<br>';
        }
    }

    echo "<br>Back trace:<br>";
    foreach (debug_backtrace() as $trace) {
        if (!empty($trace['file'])) {
            echo substr($trace['file'], strlen($_SERVER['DOCUMENT_ROOT'])) . ' (' . $trace['line'] . ')';
            echo '<br>';
        }
    }

    echo '<br>';
    //echo '<a href="javascript:history.back()">Terug</a>';
    exit();
});

