<?php 
    class controller{

        public function __construct(private gateway $gateway){}

        private function getApiToken() : ? string {

            if(!empty($_SERVER["HTTP_X_TOKEN"])){
                return $_SERVER["HTTP_X_TOKEN"];
            }

            //check case sensitive headers
            if(function_exists("getallheaders")){
                $headers = array_change_key_case(getallheaders(), CASE_LOWER);
                return $headers["x-token"] ?? null ;
            }

            return null;
        } 

        public function processRequest(string $method, ?string $resource, ?string $id) : void {

            switch($resource){
                case "users":
                    $this->handleUsers($method, $id);
                    break;
                case "games":
                    $this->handleGames($method, $id);
                    break;
                case "play":
                    $this->handlePlay($method, $id);
                    break;
                case "matchhistory":
                    $this->handleMatchHistory($method, $id);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(["message" => "Resource Not Found"]);
                    break;
            }
        }

        public function handleUsers(string $method, ?string $id) : void {

            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){

                switch($method){

                    case "GET":
                        $user = $this->gateway->getUser($id);

                        if(!$user){
                            http_response_code(404);
                            echo json_encode(["message" => "User Not Found"]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "User Found",
                            "User" => $user
                        ]);
                        break;
                    
                    case "DELETE":

                        $rows = $this->gateway->deleteUser($user["id"]);

                        if($rows === 0){
                            http_response_code(403);
                            echo json_encode([
                                "message" => "Couldnt Delete User",
                                "rows" => $rows
                            ]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "User Deleted",
                            "rows affected" => $rows
                        ]);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET, DELETE");
                        break;
                    
                }
            }else{
                
                switch($method){

                    case "GET":
                        $users = $this->gateway->getAllUsers();
                        echo json_encode($users);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET");
                        break;
                }
            }

            
        }

        public function handleGames(string $method, ?int $id) : void {
            
            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){

                switch($method){

                    case "GET":

                        $game = $this->gateway->getGame($id);
                         
                        if(!$game){
                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        echo json_encode($game);
                        break;
                    case "PATCH":

                        $game = $this->gateway->getGameByUser($user["uid"]);

                        if($game){
                            http_response_code(409);
                            echo json_encode(["message" => "This User is Arleady In-Game or Waiting"]);
                            return;
                        }

                        $game = $this->gateway->getGame($id);

                        if(!$game){
                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        if($game["status"] === "playing"){
                            http_response_code(403);
                            echo json_encode([
                                "message" => "This Game is Already In-Progress",
                                "game" => $game
                            ]);
                            return;
                        }

                        if($game["status"] === "finished"){
                            http_response_code(403);
                            echo json_encode([
                                "message" => "This Game is Already Finished",
                                "game" => $game
                            ]);
                            return;
                        }

                        $rows = $this->gateway->joinGame($user["uid"], $id);

                        if($rows === 0){
                            http_response_code(409);
                            echo json_encode(["message" => "Could Not Join Game"]);
                            return;
                        }

                        $updatedGame = $this->gateway->getGame($id);

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Game Joint Successfully",
                            "rows" => $rows,
                            "game" => $updatedGame 
                        ]);

                        break;
                    
                    case "DELETE":

                        $game = $this->gateway->getGame($id);
                         
                        if(!$game){
                            http_response_code(404);
                            echo json_encode(["message" => "Game Not Found"]);
                            return;
                        }

                        if($game["red"] !== $user["uid"] && $game["blue"] !== $user["uid"]){
                            http_response_code(403);
                            echo json_encode(["message" => "Could Not Delete Game"]);
                            return;
                        }

                        $rows = $this->gateway->deleteGame($game["id"]);

                        if($rows === 0){
                            http_response_code(403);
                            echo json_encode(["message" => "Could Not Delete Game"]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Game Deleted Successfully",
                            "rows" => $rows,
                        ]);
                    
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET, PATCH, DELETE");
                        break;
                }
            }else{

                switch($method){

                    case "POST":

                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        if(empty($data["color"])){
                            http_response_code(422);
                            echo json_encode(["message" => "color is required"]);
                            return;
                        }

                        $game = $this->gateway->getGameByUser($user["uid"]);

                        if($game){
                            http_response_code(409);
                            echo json_encode(["message" => "User is already participating in a game"]);
                            return;
                        }

                        $game = $this->gateway->createGame($user["uid"], $data["color"]);

                        if(!$game){
                            http_response_code(409);
                            echo json_encode(["message" => "Couldnt Create Game"]);
                            return;
                        }

                        http_response_code(201);
                        echo json_encode([
                            "message" => "Game Created Successfully",
                            "game" => $game
                        ]);

                        break;
                
                    case "GET":
                        
                        $games = $this->gateway->getAvailableGames();

                        if(!$games){
                            http_response_code(200);
                            echo json_encode([
                                "message" => "No Available Games",
                            ]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Available Games",
                            "games" => $games
                        ]);

                        break;

                    default:
                        http_response_code(405);
                        header("Allow: GET, POST");
                        break;
                }
            }
        }

        public function handleMatchHistory(string $method, ?string $id) : void {

            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            if($id !== null){
                
                $user = $this->gateway->getUser($id);

                if(!$user){
                    http_response_code(404);
                    echo json_encode(["message" => "User Not Found"]);
                    return;
                }
                switch($method){
                    //TODO get match History of a single player
                    case "GET":
                        $history = $this->gateway->getPlayerHistory($id);

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Match History",
                            "games" => $history
                        ]);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET");
                        break;
                }
            }else{

                switch($method){
                    case "GET":

                        $games = $this->gateway->getAllMatchHistory();

                        if(!$games){
                            http_response_code(200);
                            echo json_encode([
                                "message" => "No Games Recorded"
                            ]);
                            return;
                        }

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Match History",
                            "games" => $games
                        ]);

                        break;
                    default:
                        http_response_code(405);
                        header("Allow: GET");
                        break;
                }
            }
        }

        public function handlePlay(string $method, ?int $id) : void{

            $token = $this->getApiToken();

            if(!$token){
                http_response_code(401);
                echo json_encode(["message" => "Missing API token"]);
                return;
            }

            $user = $this->gateway->getUserByToken($token);

            if(!$user){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find user with the token used"]);
                return;
            }

            $game = $this->gateway->getGameByUser($user["uid"]);

            if(!$game){
                http_response_code(404);
                echo json_encode(["message" => "Didnt find a game where this user is participating"]);
                return;
            }

            if($game["status"] !== "playing"){
                http_response_code(409);
                echo json_encode(["message" => "This game is not in play"]);
                return;
            }

            if($id !== null){

                switch($method){
                    case "PATCH"://forfeit

                        if($game["id"] !== $id){
                            http_response_code(403);
                            echo json_encode(["message" => "Game ID does not match your active game ID"]);
                            return;
                        }

                        $winnerColor = ($game["red"] === $user["uid"]) ? "b" : "r";
                        $winneruid = ($winnerColor === "r") ? $game["red"] : $game["blue"];

                        $this->gateway->finishGame($id, $winnerColor, $winneruid);

                        $updgame = $this->gateway->getGame($id);

                        http_response_code(200);
                        echo json_encode([
                            "message" => "Game forfeited",
                            "game" => $updgame
                        ]);

                        break;
                    default:
                        http_response_code(405);
                        header("Allow: PATCH");
                        break;
                }

            }else{
                switch($method){
                    case "POST"://play move

                        $board = new quoridor(($game["board_state"]));

                        $activeColor = $board->getActiveColor();
                        $turnuid = ($activeColor === "r") ? $game["red"] : $game["blue"];

                        if($user["uid"] !== $turnuid){
                            http_response_code(403);
                            echo json_encode(["message" => "Not this user's turn"]);
                            return;
                        }

                        $data = (array) json_decode(file_get_contents("php://input"), true);

                        if(empty($data["move"])){
                            http_response_code(409);
                            echo json_encode(["message" => "Move Notations is required"]);
                            return;
                        }

                        if(!$board->move($data["move"])){
                            http_response_code(409);
                            echo json_encode(["message" => "Illegal Move"]);
                            return;
                        }

                        $newState = $board->toState();
                        $winner = $board->getWinner();
                        $newStatus = ($winner !== null) ? "finished" : "playing";
                        $winneruid = ($winner === "r") ? $game["red"] : (($winner === "b") ? $game["blue"] : null);

                        $this->gateway->playMove($game["id"], $newState, $newStatus, $winner, $winneruid);
                        $updgame = $this->gateway->getGame($game["id"]);

                        http_response_code(200);
                        echo json_encode([
                            "message" => $winner ? "Move played - You win!" : "Move played",
                            "game" => $updgame
                        ]);
                        break;
                    default:
                        http_response_code(405);
                        header("Allow: POST");
                        break;
                }

            }
        }
    }
?>