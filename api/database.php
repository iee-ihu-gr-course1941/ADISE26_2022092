<?php
    declare(strict_types=1);

    header("Content-Type: application/json; charset=UTF-8");

    class Database{

        public function __construct(
            private string $host,
            private string $dbname,
            private string $user,
            private string $password,
            private int $port
        ){}

        public function getConnection() : PDO{

            try{
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";

                return new PDO(
                    $dsn,
                    $this->user,
                    $this->password,
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_STRINGIFY_FETCHES => false,
                        ]
                );
            }catch(PDOException $e){
                http_response_code(500);
                echo json_encode(["message" => "Internal Server Database Error"]);
                exit;
            }
        }
    }
?>