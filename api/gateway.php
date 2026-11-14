<?php 
    class gateway{
        private mysqli $conn;

        public function __construct(database $database){
            $this->conn = $database->getConnection();
        }
    }
?>