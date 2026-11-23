<?php     
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

        public function getUser(string $uid) : ?array{

            $sql = "SELECT uid, wins FROM users where uid = ?;";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("s", $uid);
            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            return $user;
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

        public function createGame(string $uid, string $color) : ?array  {

            $sql = null;

            if($color === "b"){
                $sql = "INSERT INTO games (blue)
                    VALUES (?);";
                $stmt = $this->conn->prepare($sql);

                $stmt->bind_param("s", $uid);

            }elseif($color === "r"){
                $sql = "INSERT INTO games (red)
                    VALUES (?);";
                $stmt = $this->conn->prepare($sql);

                $stmt->bind_param("s", $uid);

            }else{
                return null;
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
                    SET red = ?, status = 'playing'
                    WHERE id = ?;"
                ;
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $uid, $id);
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

        public function deleteGame(int $id) : int {
            $sql = "DELETE FROM games WHERE id = ? AND status ='waiting'";

            $stmt = $this->conn->prepare($sql);

            $stmt->bind_param("i", $id);
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

        public function getAllMatchHistory() : ?array{
            $sql = "SELECT * FROM games WHERE status = 'finished';";

            $stmt = $this->conn->query($sql);

            return $stmt->fetch_all(MYSQLI_ASSOC);
        }

        public function finishGame(int $id, string $winnerColor, string $winneruid) : int{

            $sql = "UPDATE games 
                    SET status = 'finished', winner = ? 
                    WHERE id = ?;"
            ;

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $winnerColor, $id);
            $stmt ->execute();

            $rows = $stmt->affected_rows;
            $stmt->close();

            if($rows > 0){
                $sql = "UPDATE users
                        SET wins = wins + 1
                        WHERE uid = ?;";
                ;

                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("s", $winneruid);
                $stmt->execute();
                $stmt->close();
            }

            return $rows;
        }

        public function playMove(int $id, string $newState, string $newStatus, ?string $winner, ?string $winneruid){
            
            $sql = "UPDATE games
                    SET board_state = ?, status = ?, winner = ?
                    WHERE id = ?"
            ;

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", $newState, $newStatus, $winner , $id);
            $stmt->execute();

            $rows = $stmt->affected_rows;
            $stmt->close();

            if($newStatus === "finished" && $winneruid !== null){
                $sqlwins = "UPDATE users
                            SET wins = wins + 1
                            WHERE uid = ?"
                ;

                $stmtwins = $this->conn->prepare($sqlwins);
                $stmtwins->bind_param("s" , $winneruid);
                $stmtwins->execute();
                $stmtwins->close();
            }

            return $rows;
        }

        public function getPlayerHistory(string $uid) : ?array{

            $sql = "SELECT * FROM games WHERE red = ? OR blue = ?;";

            $stmt= $this->conn->prepare($sql);
            $stmt->bind_param("s", $uid);
            $stmt->execute();

            $history = $stmt->get_result()->fetch_assoc();

            $stmt->close();

            return $history;
        }
    }
?>