<?php

    declare(strict_types=1);
    class database{

        public function __construct(
            private string $host,
            private string $user,
            private string $pass,
            private string $db,
            private ?int $port = null,
            private ?string $socket = null
        ){}

        public function getConnection() : mysqli{
            try{
                $conn = mysqli_connect(
                    $this->host, 
                    $this->user, 
                    $this->pass, 
                    $this->db, 
                    $this->port, 
                    $this->socket
                );
                mysqli_set_charset($conn, "utf8mb4");

                return $conn;
            }catch(mysqli_sql_exception $e){
                http_response_code(500);
                echo json_encode([
                    "message" => "Connection trough ssh failed",
                    "error" => $e->getMessage()
                ]);
                exit;
            }
        }
    }
?>