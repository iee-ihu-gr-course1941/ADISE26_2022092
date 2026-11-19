<?php 
    class quoridor {

        private array $pawns = [];
        private array $hWalls = [];//Horizontal Walls
        private array $vWalls = [];//Vertical Walls
        private array $wallsRemaining = [];
        private string $activeColor;

        public function __construct(string $state = "e1/e9 - - r 10/10"){
            $this->loadFromState($state);
        }

        public function loadFromState(string $state) : void {

            $parts = explode(" ", trim($state));

            if(count($parts) !== 5){
                throw new InvalidArgumentException("Invalid Quoridor Format");
            }

            [$pawnStr, $hWallStr, $vWallStr, $color, $wallCounts] = $parts;

            $pawnParts = explode("/", $pawnStr);

            if(count($pawnParts) !== 2){
                throw new InvalidArgumentException(("Invalid Pawn Coordinates"));
            }

            $this->pawns["r"] = $this->squareToCoordinates($pawnParts[0]);
            $this->pawns["b"] = $this->squareToCoordinates($pawnParts[1]);

            $this->vWalls = [];

            if($vWallStr !== "-"){
                foreach(explode(",", $vWallStr) as $sq){
                    $this->vWalls[] = $this->squareToCoordinates($sq);
                }
            }

            if(!in_array($color, ["r", "b"], true)){
                throw new InvalidArgumentException("Invalid Active Color: must be 'r' or 'b'");
            }

            $this->activeColor = $color;

            $counts = explode("/", $wallCounts);

            if(count($counts) !== 2){
                throw new InvalidArgumentException("Invalid Wall Counts");
            }

            $this->wallsRemaining["r"] = (int) $counts[0];
            $this->wallsRemaining["b"] = (int) $counts[0];
        }
    }
?>