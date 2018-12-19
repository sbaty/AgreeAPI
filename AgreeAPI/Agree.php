<?php
	require("Objects/User.php");
	require("Objects/Question.php");
	require("Objects/Action.php");
	class Agree {
		private $con;
		private $args;
		private $user;
		private $response;
		private $httpStatus;
		private $action;
		private $errors;

		## Constructor
		public function __construct($hostname = "localhost",$username="root",$password="root",$database = "agree",$port = null){

			// Connect to the database
			$this->con = new mysqli($hostname,$username,$password,$database,$port);
			if($this->con->errno){
				die("Couldn't connect to the database: ".$this->con->error);
			}

			if(!$this->log()){
				die("Couldn't log.");
			}

			// Register our arguments
			$this->setArgs();

			// Initialize our errors array
			$this->errors = array();

			// Construct our user
			try {
				$this->user = new User($this->con,$this->args);
			} catch (Exception $e) {
				$this->addError($e->getMessage());
				$this->outputResponse();
			}

			// Attempt our action
			try {
				$this->action = new Action($this->con,$this->user,$this->args);
			} catch (Exception $e){
				$this->addError($e->getMessage());
				$this->outputResponse();
			}

			// Return our response
			$this->outputResponse();
			$this->con->close();
		}

		private function addError($message){
			$this->errors[] = $message;
		}

		private function log(){
			if($query = $this->con->prepare("INSERT INTO logs (ipaddr,requestTime,narrative) VALUES (?,?,?) ")){
				$ipaddr = $_SERVER['REMOTE_ADDR'];
				$requestTime = Date("Y-m-d H:i:s",time());
				$narrative = $_SERVER['HTTP_USER_AGENT']??null;
				foreach($_REQUEST as $key=>$val){
					if(strtolower($key) == "password" || strtolower($key) == "newpassword"){
						$val = "****";
					}
					$narrative .= " | ".$key.":".$val;
				}
				$query->bind_param("sss",$ipaddr,$requestTime,$narrative);
				$query->execute();
                $query->free_result();
                $query->close();
                return true;
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
			}
			return false;
		}

		// Save all args, regardless of HTTP request type
		private function setArgs(){
			if(isset($_GET)){
				$this->args = $_GET;
			} else if(isset($_POST)){
				$this->args = $_POST;
			} else if(isset($_PUT)){
				$this->args = $_PUT;
			} else {
				$this->args = Array();
			}
		}

		## Output
		public function outputResponse($response=null){	
			$response;
			if($this->action instanceof Action){
				$response = $this->action->getResult();
				$this->httpStatus = ($this->action->getHttpCode()?$this->action->getHttpCode():400);
			} else if($this->user instanceof User){
				$this->httpStatus = ($this->user->getHttpCode()?$this->action->getHttpCode():401);
			} else {
				$this->httpStatus = 401;
				foreach($this->errors as $error){
					$response["Errors"][] = $error;
				}
			}
			http_response_code($this->httpStatus);
			header('Content-Type: application/json');
			echo json_encode($response);
			exit();
		}
	}
?>