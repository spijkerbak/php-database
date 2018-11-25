<?php
namespace database;

use PDO;

require_once 'Base.php';
require_once 'Result.php';
require_once 'Record.php';

set_error_handler(function($errno, $errstr, $errfile, $errline ) {
    //throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    // IGNORE warning from mail() about logging
    
    header('Content-type: text.html');
    echo "ERROR HANDLER:<br>\n";
    
    if (stripos($errstr, 'phpmaillog') !== false) {
        return;
    }

    echo "Fout $errno:<br>$errstr";
    echo '<br>';

    if (stristr($errstr, 'SQL') !== false) {
        echo '<br>Statement:<br>';
        echo '<br>';
    }

    echo "<br>Back trace:<br>";
    foreach (debug_backtrace() as $trace) {
        if (!empty($trace['file'])) {
            echo substr($trace['file'], strlen($_SERVER['DOCUMENT_ROOT'])) . ' (' . $trace['line'] . ')';
            echo "<br>\n";
        }
    }

    echo "<br>\n";
    exit();
});

class Database extends PDO {

    private $dbname;
    private $admin;
    private $prefix;
    private $_hostname = 'db.spijkerman.nl';
    private $_databasename = 'md136282db440445';
    private $_username = 'md136282db440445';
    private $_password = 'NoGetNoSet';

    public function hasTable($tableName) {
        try {
            $sql = "SELECT 1 FROM $tableName";
            $stmt = $this->prepare($this->xlatsql($sql));
            $stmt->execute();
        } catch (\PDOException $ex) {
            return false;
        }
        return true;
    }

    public function __construct() {
        $host = $this->_hostname;
        $dbname = $this->_databasename;
        $user = $this->_username;
        $pass = $this->_password;
        $this->admin = "https://phpmyadmin-mdh.mijndomein.nl/;$host;$user;$pass";
        $this->prefix = strtoupper(BASE) . '_';
        try {
            $dsn = "mysql:dbname=$dbname;host=$host;charset=utf8"; // no hyphen in utf8
            parent::__construct($dsn, $user, $pass, null);
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('database\Result', array($this)));
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $this->dbname = $dbname;
        } catch (PDOException $e) {
            die('connection failed: ' . $e->getMessage());
        }
    }

    public function getAdmin() {
        return $this->admin;
    }

    public function getDatabaseName() {
        return $this->dbname;
    }

    public function getPrefix() {
        return $this->prefix;
    }

    private function xlatSql($sql) {
        return preg_replace('/\[([^\]]*)\]/', '`' . $this->prefix . '\1`', $sql);
    }

    public function getRecord($sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        $object = $stmt->fetchObject();
        //$stmt = null;
        return $object === false ? null : $object;
    }

    public function getObject($class, $sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $stmt = $this->prepare($this->xlatsql($sql));
        if ($stmt->execute($args)) {
            return $stmt->fetchObject($class);
        } else {
            return null;
        }
    }

    public function getValue($sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        $record = $stmt->fetch();
        if ($record !== false) {
            return $record[0];
        }
        return null;
    }

    public function getList($class, $sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        $list = [];
        while ($record = $stmt->fetchObject($class)) {
            $list[] = $record;
        }
        return $list;
    }

    public function getIndexedList($class, $field, $sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        $list = [];
        while ($record = $stmt->fetchObject($class)) {
            $list[$record->$field] = $record;
        }
        return $list;
    }

    public function getColumn($sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $list = [];
        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        while ($record = $stmt->fetch(PDO::FETCH_BOTH)) {
            $list[] = $record[0];
        }
        //$stmt = null;
        return $list;
    }

    public function getIndexedColumn($sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $list = [];
        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        while ($record = $stmt->fetch(PDO::FETCH_BOTH)) {
            $list[$record[0]] = $record[1];
        }
        return $list;
    }

    public function getIndexedColumnX($sql, $args = null) {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $list = [];
        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args);
        while ($record = $stmt->fetch(PDO::FETCH_BOTH)) {
            $list[$record[0]] = [$record[1], $record[2]];
        }
        return $list;
    }

    public function execute($sql, $args = null, $errmsg = "Database error...") {
        // echo "sql = " . sql($this->xlatsql($sql), $args) . "<br>";

        $stmt = $this->prepare($this->xlatsql($sql));
        $stmt->execute($args, $errmsg);
        return $stmt;
    }

    /**
     * dump all tables for this application to a sql text file
     * this file can be useed to rebuild tables: structure, constraints and content
     * 
     * @return string web-relative path of output file
     */
    public function dump($destfolder) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        $errRep = error_reporting(E_ALL);
        
        $host = $this->_hostname;
        $dbname = $this->_databasename;
        $user = $this->_username;
        $pass = $this->_password;
        $filename = '/dump_' . today() . '_' . timetext('', '.') . '.sql';
        
        $sqlout = dirname(__FILE__) . $filename;
        $stderr = '';
        
        // get names of  tables for this applictation
        
        $tableNameList = $this->getColumn("SHOW TABLES LIKE '{$this->prefix}_%'");
        $tableNames = implode(' ', $tableNameList);
        
        exec("mysqldump --user={$user} --password={$pass} --host={$host} {$dbname} {$tableNames} --result-file={$sqlout} 2>&1", $stderr);
        if(!empty($stderr)) {
            var_dump($stderr);
        }
        $list = str_replace(' ', '<br>', $tableNames);
        echo "<h3>Dumped tables</h3>\n";
        echo "<p>$list</p>";
        error_reporting($errRep);
        
        return $destfolder . '/' . $filename;
    }
}
