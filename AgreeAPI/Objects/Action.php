<?php
	class Action {
		private $con;
		private $data;
		private $user;
		private $args;
		private $availableActions = ["help","createUser","login","updatePassword","createQuestion","updateQuestion","decideQuestion","deleteQuestion","createChoice","updateChoice","deleteChoice","getQuestion","getQuestions","getBallot","vote","logout"];
		private $httpCode = 200;

		public function __construct($con = null, $user = null, $args = null){
			if(!$con){
				throw new Exception("Connection required.");
			} else {
				$this->con = $con;
			}
			if(!$user){
				throw new Exception("A user, even an anonymous one, was not initialized.");
			} else {
				$this->user = $user;
			}
			if(!$args){
				$this->args = array();
				// throw new Exception("No arguments provided.");
				// Might not be a problem. Plan to delete.
			} else {
				$this->args = $args;
			}
			if(!$this->user->isAuthenticated()){
				$requestedAction = "help";
			} else {
				$requestedAction = (isset($this->args["action"]) ? $this->args["action"] : "help");
			}

			if(in_array($requestedAction,$this->availableActions)){
				if(is_callable(array($this,$requestedAction))){
					try{
						$this->data = call_user_func(array($this,$requestedAction));
					} catch(Exception $e){
						throw new Exception($e->getMessage());
					}
				} else {
					throw new Exception("Not callable.");
				}
			} else {
				throw new Exception("`{$requestedAction}` not available.");
			}
		}

		public function getHttpCode(){
			if(!$this->user->isAuthenticated()){
				$this->httpCode = 401;
			}
			return $this->httpCode;
		}
		public function getResult(){
			return $this->data;
		}
		private function checkRequired(){
			$args = func_get_args();
			foreach($args as $arg){
				if(!isset($this->args[$arg])){
					throw new Exception("Action:: `{$arg}` is required.");
				}
			}
			return true;
		}

		private function help(){
			return "Visit https://danielalfred.com/agree/api/help for a list of available commands.";
		}
		private function createUser(){
			$this->checkRequired("newEmail","newPassword");
			
			// Wipe out current user
			unset($this->user);
			
			// Set new arguments
			$newEmail = $this->args["newEmail"];
			$newPassword = $this->args["newPassword"];

			// Assign new user
			$this->user = new User($this->con,array("newEmail"=>$newEmail,"newPassword"=>$newPassword));

			if($this->user->isAuthenticated()){
				$this->httpCode = 200;
				return array("User"=>$this->user->getProfile());
			} else {
				$this->httpCode = 401;
				throw new Exception("Could not authenticate new user.");
			}
		}
		private function login(){
			$this->checkRequired("email","password");
			$this->user->authUserByPassword($this->args["email"],$this->args["password"]);			
			if($this->user->isAuthenticated()){
				$this->httpCode = 200;
				return array("User"=>$this->user->getProfile());
			} else {
				$this->httpCode = 401;
				throw new Exception("Login failed. Please try again.");
			}
		}
		private function updatePassword(){
			$this->checkRequired("newPassword");
			if($this->user->updatePassword($this->args["newPassword"])){
				return $this->user->getToken();
			} else {
				return false;
			}
		}
		private function getQuestion(){
			$this->checkRequired("questionId");
			$question = new Question($this->con,$this->user);
			return $question->getQuestion($this->args["questionId"]);
		}
		private function getQuestions(){
			$question = new Question($this->con,$this->user);
			return $question->getQuestions();
		}
		private function createQuestion(){
			$this->checkRequired("title");
			$question = new Question($this->con,$this->user);
			return $question->create($this->args["title"]);
		}
		private function updateQuestion(){
			$this->checkRequired("questionId");
			$question = new Question($this->con,$this->user);
			return $question->updateQuestion($this->args["questionId"],$this->args);
		}
		private function decideQuestion(){
			$this->checkRequired("questionId");
			$question = new Question($this->con,$this->user);
			return $question->decideQuestion($this->args["questionId"]);
		}
		private function createChoice(){
			$this->checkRequired("questionId","title");
			$question = new Question($this->con,$this->user);
			return $question->createChoice($this->args["questionId"],$this->args["title"]);
		}
		private function updateChoice(){
			$this->checkRequired("choiceId","title");
			$question = new Question($this->con,$this->user);
			return $question->updateChoice($this->args["choiceId"],$this->args["title"]);
		}
		private function deleteChoice(){
			$this->checkRequired("choiceId");
			return $question->deleteChoice($this->args["choiceId"]);
		}
	}