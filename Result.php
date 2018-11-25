<?php

namespace database;

// ----- IMPORTANT ------
// look in https://secure.php.net/manual/en/book.pdo.php for extending PDOStatement
class Result extends \PDOStatement {

    public $dbh;

    protected function __construct($dbh) {
        $this->dbh = $dbh;
    }

    /*
     * 
     * throws \PDOException
     */

    public function execute($args = null, $errmsg = "Database error...") {
        if ($args === null) {
            return parent::execute();
        } else {
            if (!is_array($args)) {
                $args = [$args];
            }
            return parent::execute($args);
        }
    }

}
