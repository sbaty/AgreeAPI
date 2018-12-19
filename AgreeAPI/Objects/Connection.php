<?php

    class Connection {
        public function __construct($hostname = "localhost",$username="root",$password="2244",$database = "SchoolDB",$port = null){
            $this->con = new mysqli($hostname,$username,$password,$database,$port);
            if($this->con->errno){
                die("Couldn't connect to the database: ".$this->con->error);
            }
        }
    }
?>