<?php

class User {
    private $id;
    private $email;
    private $token;
    private $lastActivity;
    private $activityProfile;
	private $isAuthenticated = false;
	private $args;
	private $httpCode;

	public function getHttpCode(){
		return $this->httpCode;
	}

    public function getId(){
        return $this->id;
    }

    public function getEmail(){
        return $this->email;
    }
    
    public function getToken(){
        return $this->token;
    }

	public function getProfile(){
		// Do we really need to send anything but the token?
		//  The GUI might want the email or, in the future, a first name. So keep this.
		if($this->isAuthenticated){
			return array("Email"=>$this->getEmail(),"Token"=>$this->getToken());
		}
		return null;
		//return array("Error"=>"Not authenticated.");
	}

    private function saveChanges(){
        // save current values of 
        if($this->isAuthenticated()){
            if($query = $this->con->prepare("UPDATE users SET email = ?, token = ?, lastActivity = CURRENT_TIMESTAMP, activityProfile = ? WHERE id = ? LIMIT 1")){
                $email = $this->email;
                $token = $this->token;
                $activityProfile = '"ip":"'.$_SERVER['REMOTE_ADDR'].'", "Browser":"'.$_SERVER['HTTP_USER_AGENT'].'"';
                $id = $this->id;
                $query->bind_param("ssss",$email,$token,$activityProfile,$id);
                $query->execute();
                $query->free_result();
				$query->close();
				return true;
            } else if($this->con->errno){
				throw new Exception("Could not authenticate user: ".$this->con->error);
			}
        }
        return false;
    }

	private function getNewGuid(){
		// From Alix Axel's contribution at http://php.net/manual/en/function.com-create-guid.php
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

    private function setNewToken(){
        $this->token = $this->getNewGuid();
    }

    public function isAuthenticated(){
        return $this->isAuthenticated;
    }

    private function authUserByToken($token){
		$this->isAuthenticated = false;
        if($query = $this->con->prepare("SELECT id,email FROM users WHERE token = ? AND lastActivity > DATE_SUB(NOW(), INTERVAL 30 MINUTE) LIMIT 1")){
			$query->bind_param("s",$token);
            $query->execute();
            $query->bind_result($id,$email);
			$query->fetch();
            $query->free_result();
			if($email != null){
				$this->id = $id;
				$this->email =$email;
				$this->token = $token;
				$this->saveChanges();
				$query->close();
				$this->isAuthenticated = true;
				return true;
			}
			$query->close();
			return false;
        } else if($this->con->errno){
			die($this->con->error);
		}
        return false;
    }

    public function authUserByPassword($email,$password){
        if($query = $this->con->prepare("SELECT id,password FROM users WHERE email = ? LIMIT 1")) {
			$query->bind_param("s",$email);
            $query->execute();
            $query->bind_result($id,$userPassword);
            $query->fetch();
			$query->free_result();
			if(password_verify($password,$userPassword)){
				$this->id = $id;
				$this->email = $email;
				$this->setNewToken();
				$this->saveChanges();
				$query->close();
				return true;
			}
        } else if($this->con->errno){
			throw new Exception("Could not authenticate user by password: ".$this->con->error);
		}
        return false;
    }

	private function hashPassword($password){
		$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
		return $hashedPassword;
	}

	public function updatePassword($newPassword){
		if(!$this->isAuthenticated){
			throw new Exception("Not authenticated.");
		}
		$userId = $this->id;
		$hashedPassword = $this->hashPassword($newPassword);
		if($query = $this->con->prepare("UPDATE users SET password = ? WHERE id = ? LIMIT 1")){
			$query->bind_param("ss",$hashedPassword,$userId);
			$query->execute();
			$query->free_result();
			$query->close();
			return true;
		} else if($this->con->errno){
			throw new Exception($this->con->error); //TODO: suppress
		}
	}

    // TODO: Test for brute-force guessing or changing activity profile
    private function authenticate(){
        $this->isAuthenticated = false;
        if($this->args){
            if(isset($this->args["token"])){
				$this->isAuthenticated = ($this->authUserByToken($this->args["token"]) ? true : false);
            } else if(isset($this->args["email"],$this->args["password"])){
                $this->isAuthenticated = ($this->authUserByPassword($this->args["email"],$this->args["password"]) ? true : false);
            } else {
				$this->isAuthenticated = false;
				throw new Exception("Please provide an `email` and `password` or a `token`.");
			}
        }
		$this->saveChanges();
		return $this->isAuthenticated;
    }

	private function createUser($email,$password){
		$id = $this->getNewGuid();
		$hashedPassword = $this->hashPassword($password);
		// TODO: Be sure we don't overwrite an existing user
		//			And don't say why it failed
		//				(don't want to enumerate our user emails)
		if($query=$this->con->prepare("
			INSERT INTO users
				(id,email,password)
			SELECT ?,?,?")){ // Table will reject duplicate emails
			$query->bind_param("sss",$id,$email,$hashedPassword);
			$query->execute();
			$query->free_result();
			$query->close();
			if($this->con->errno){
				die("Couldn't create user: ".$this->con->error);
				return false;
			} else {
				$this->args["email"] = $email;
				$this->args["password"] = $password;
				return true;
			}
		}
		die($this->con->error);
		return false;
	}

    public function __construct($con,$args){

        $this->isAuthenticated = false;

		$this->con = $con;
		$this->args = $args;

		if(isset($this->args["newEmail"]) && isset($this->args["newPassword"])){
			$this->createUser($this->args["newEmail"],$this->args["newPassword"]);
		}
		
		if(!$this->authenticate()){
			$this->httpCode = 401;
			throw new Exception("Could not authenticate!");
		}
    }
}

?>