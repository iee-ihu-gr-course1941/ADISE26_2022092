<?php //TODO getAvailableGames and getMatchHistory
    class gateway{
        private mysqli $conn;

        public function __construct(database $database){
            $this->conn = $database->getConnection();
        }

        public function getTokenOAuthUser(string $uid) : ?string{

            $token = bin2hex(random_bytes(16));

            $sql = "INSERT INTO users(uid, token) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE token = VALUES(token);"
            ;

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("ss", $uid, $token);
            $stmt->execute();
            $stmt->close();

            return $token;
        }

        public function getUserByToken(string $apiToken) : ?array {

            $sql = "SELECT * FROM users WHERE token = ?;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("s", $apiToken);
            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            return $user;
        }

        public function getAllUsers() : ?array {

            $sql = "SELECT id, uid, wins FROM users ORDER BY wins DESC;";

            $stmt = $this->conn->query($sql);

            return $stmt->fetch_all(MYSQLI_ASSOC);
        }

        public function updateUser(array $curruser) : ?array {

            $sql = "UPDATE users 
                    SET wins = wins + 1
                    WHERE uid = ?;"
            ;

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("s", $curruser["uid"]);
            $stmt->execute();
            $stmt->close();

            $upduser = $this->getUserByToken($curruser["token"]);

            return $upduser;

        }

        public function deleteUser(int $id) : int {

            $sql = "DELETE FROM users WHERE id = ?;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $rows = $stmt->affected_rows;

            $stmt->close();

            return $rows;
        }

        public function getGame(int $id) : ?array {

            $sql = "SELECT * FROM games WHERE id = ?;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("i", $id);
            $stmt->execute();

            $game = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            return $game; 
        }

        public function getGameByUser(string $uid) : ?array{

            $sql = "SELECT * FROM games WHERE (red = ? OR blue = ?) AND status <> 'finished';";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("ss", $uid, $uid);
            $stmt->execute();

            $game = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            return $game; 
        }

        public function createGame(string $uid, string $color) : array | false {

            $sql = null;

            if($color === "b"){
                $sql = "INSERT INTO games (blue)
                    VALUES (?);";
                $stmt = $this->conn->prepare($sql);

                $stmt->bind_param("s", $uid);

            }elseif($color === "r"){
                $sql = "INSERT INTO games (red, player_turn)
                    VALUES (?, ?);";
                $stmt = $this->conn->prepare($sql);

                $stmt->bind_param("ss", $uid, $uid);

            }else{
                return false;
            }

            $stmt->execute();

            $stmt->close();

            $id = $this->conn->insert_id;

            return $this->getGame($id);
        }

        public function joinGame(string $uid, int $id) : int{

            $game = $this->getGame($id);

            if(empty($game)){
                return 0;
            }

            $sql = null;

            if($game["red"] === null){
                $sql = "UPDATE games
                    SET red = ?, status = 'playing', player_turn = ?
                    WHERE id = ?;"
                ;
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssi", $uid, $uid, $id);
            }else{
                $sql = "UPDATE games
                    SET blue = ?, status = 'playing'
                    WHERE id = ?;"
                ;
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $uid, $id);
            }

            $stmt->execute();

            $rows = $stmt->affected_rows;

            $stmt->close();

            return $rows;

        }

        public function getAvailableGames() : ?array{

            $sql = "SELECT * FROM games WHERE status = 'waiting';";

            $stmt = $this->conn->query($sql);

            return $stmt->fetch_all(MYSQLI_ASSOC);

        }

        public function getMatchHistory() : ?array{
            $sql = "SELECT * FROM games WHERE status = 'finished';";

            $stmt = $this->conn->query($sql);

            return $stmt->fetch_all(MYSQLI_ASSOC);
        }
    }
?>