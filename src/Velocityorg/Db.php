<?php
namespace Velocityorg;

use \Nette\Database\Connection;

class Db {
    protected $connection;

    public function __construct($dsn, $user = null, $password = null) {
        $this->connection = new Connection($dsn, $user, $password);
    }

    public function getConnection() {
        return $this->connection;
    }
}
