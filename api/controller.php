<?php 
    class controller{

        public function __construct(private gateway $gateway){
            
        }

        public function processRequest(string $method, ?string $resource, ?string $id) : void {

            switch($resource){
                case "users":
                    $this->handleUsers($method, $id !== null ? (int) $id : null);
                    break;
                case "games":
                    break;
                case "play":
                    break;
                case "matchhistory":
                    break;
            }
        }

        public function handleUsers(string $method, ?int $id) : void {

            if($id !== null){

                switch($method){
                    
                }
            }else{
                
                switch($method){

                    case "POST":
                        $data = (array) json_decode(file_get_contents("php://input"), true);
                        
                        if(empty($data["uid"]))
                        break;
                }
            }
        }
    }
?>