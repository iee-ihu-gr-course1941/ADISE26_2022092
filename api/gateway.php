<?php 
    class gateway{
        private mysqli $conn;

        public function __construct(database $database){
            $this->conn = $database->getConnection();
        }

        public function getTokenOAuthUser(string $uid) : string{

            $token = bin2hex(random_bytes(16));

            $sql = "INSERT INTO users(uid, token) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE token = VALUES(token)"
            ;

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("ss", $uid, $token);
            $stmt->execute();
            $stmt->close();

            return $token;
        }

        public function getUserByToken(string $apiToken) : array | null {

            $sql = "SELECT * FROM users WHERE token = ?;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("s", $apiToken);

            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            return $user;
        }

        public function getAllUsers() : array {

            $sql = "SELECT id, uid, wins FROM users ORDER BY wins DESC";

            $stmt = $this->conn->query($sql);

            return $stmt->fetch_all(MYSQLI_ASSOC);
        }

        public function updateUser(array $curruser) : array {

            $sql = "UPDATE users 
                    SET wins = wins + 1
                    WHERE uid = ?"
            ;

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("ss", $curruser["uid"]);
            $stmt->execute();
            $stmt->close();

            $upduser = $this->getUserByToken($curruser["token"]);

            return $upduser;

        }
    }
?>