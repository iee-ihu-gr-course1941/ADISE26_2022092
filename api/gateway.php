<?php 
    class gateway{
        private mysqli $conn;

        public function __construct(database $database){
            $this->conn = $database->getConnection();
        }

        public function getTokenOAuthUser(string $uid) : string{

        }

        public function getUserByToken(string $apiToken) : array | false{

        }
    }
?>