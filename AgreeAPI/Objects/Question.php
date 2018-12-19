<?php
    class Question{
        private $con;
        private $user;

        private $id;
        private $title;
        private $creator;
        private $startTime;
        private $hardDeadline;
        private $movingDeadlineSeconds;
        private $trashed;
        private $isValid;

        public function __construct($con = null,$user = null){
            if(!$con){
                throw new Exception("Connection required.");
            }
            if(!$user){
                throw new Exception("User required.");
            } else if(!$user->isAuthenticated()){
                throw new Exception("Please log in.");
            }
            $this->con = $con;
            $this->user = $user;
            // try {
            //     $this->setProperties();
            // } catch (Exception $e) {
            //     echo "Error: ".$e->getMessage();
            // }            
            //$this->con->close(); // Maybe not? Dunno. Check this first, though.
        }

		private function checkRequired(){
			$args = func_get_args();
			foreach($args as $arg){
				if(!isset($this->args[$arg])){
					throw new Exception("Question:: `{$arg}` is required.");
				}
			}
			return true;
		}
        public function getProperties(){
            return array(   "Id"=>$this->id,
                            "Title"=>$this->title,
                            "User"=>$this->user,
                            "StartTime"=>$this->startTime,
                            "HardDeadline"=>$this->hardDeadline,
                            "MovingDeadlineSeconds"=>$this->movingDeadlineSeconds
                        );
        }
        private function setProperties($questionId){
            $this->isValid = false;
            if($query = $this->con->prepare("
                SELECT
                    id,
                    title,
                    userId,
                    startTime,
                    hardDeadline,
                    movingDeadlineSeconds
                FROM questions
                WHERE id = ? AND trashed <> FALSE LIMIT 1")){
                $query->bind_param("s",$questionId);
                $query->execute();
                $query->bind_result($id,$title,$userId,$startTime,$hardDeadline,$movingDeadlineSeconds);
                $query->fetch();
                $query->free_result();
                $query->close();
                $this->id = $id;
                $this->title = $title;
                $this->creator = $userId;
                $this->startTime = $startTime;
                $this->hardDeadline = $hardDeadline;
                $this->movingDeadlineSeconds = $movingDeadlineSeconds;
                $this->isValid = true;
            }
            if($this->con->errno){
                throw new Exception($this->con->error); // TODO: Obfuscate
            }
            return $this->isValid;
        }
        public function isValid(){
            return $this->isValid;
        }
        private function getNewGuid(){
            // From Alix Axel's contribution at http://php.net/manual/en/function.com-create-guid.php
            if (function_exists('com_create_guid') === true)
            {
                return trim(com_create_guid(), '{}');
            }
            return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
        }
		// Create a question
		public function create($title){
			if($query = $this->con->prepare("INSERT INTO questions (id,title,userId) VALUES (?,?,?) ")){
				$questionId = $this->getNewGuid();
				$userId = $this->user->getId();
				$query->bind_param("sss",$questionId,$title,$userId);
				$query->execute();
                $query->free_result();
                $query->close();
                $this->setProperties($questionId);
                if($this->inviteUser($questionId,$this->user->getEmail())){
                    return $questionId;
                } else {
                    throw new Exception("Could not add user to their own question.");
                }
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
            }
			return false;
        }
        public function update($questionId){
            // Not implemented yet
			return false;
        }
		// Create a choice
		public function createChoice($questionId,$title){
            // TODO: Check that the user owns the owning question
			if($query = $this->con->prepare("INSERT INTO choices (id,questionId,title) VALUES (?,?,?) ")){
                $choiceId = $this->getNewGuid();
				$query->bind_param("sss",$choiceId,$questionId,$title);
				$query->execute();
                $query->free_result();
                $query->close();
                return true;
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
            }
			return false;
        }
        public function updateChoice($choiceId,$title){
            // TODO: Check that the user owns the owning question
			if($query = $this->con->prepare("UPDATE choices SET title = ? WHERE id = ? LIMIT 1")){
				$query->bind_param("ss",$title,$choiceId);
				$query->execute();
                $query->free_result();
                $query->close();
                return true;
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
            }
			return false;
        }
        public function deleteChoice($choiceId){
            // TODO: Check that the user owns the owning question
			if($query = $this->con->prepare("DELETE FROM choices WHERE id = ? LIMIT 1")){
				$query->bind_param("s",$choiceId);
				$query->execute();
                $query->free_result();
                $query->close();
                return true;
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
            }
			return false;
		}
		public function getChoices($questionId){ // Not open to users
			if($query = $this->con->prepare("
				SELECT
					c.Id AS Id,
					c.title AS Title
					FROM choices c
					WHERE c.questionId = ?
			")){
				$id = $this->user->getId();
				$email = $this->user->getEmail();
				$query->bind_param("s",$questionId);
				$query->execute();
				$query->bind_result($id,$title);
				$choices = array();
				while($query->fetch()){
					$choices[] = array("Id"=>$id,"Title"=>$title); // TODO: Add priority so they're ordered?
				}
				return $choices;
			} else if($this->con->errno) {
				throw new Exception($this->con->error);
			}
			$query->free_result();
			$query->close();
			return false;
		}
        // Get a question
        public function getQuestion($questionId){
			if($query = $this->con->prepare("
				SELECT
					q.Id AS Id,
					q.title AS Title,
					q.startTime AS StartTime,
					q.hardDeadline AS HardDeadline,
					q.movingDeadlineSeconds AS MovingDeadlineSeconds,
					u.email Creator
					FROM questions q
					INNER JOIN users u ON q.userId = u.id
					LEFT JOIN invitations i ON i.questionId = q.id
					WHERE
						q.Id = ?
						AND
						(q.userId = ? OR i.email = ?)
					LIMIT 1
			")){
				$id = $this->user->getId();
				$email = $this->user->getEmail();
				$query->bind_param("sss",$questionId,$id,$email);
				$query->execute();
				$query->bind_result($id,$title,$startTime,$hardDeadline,$movingDeadlineSeconds,$creator);
				$query->fetch();
				$query->free_result();
				$query->close();
				$choices = $this->getChoices($id);
				$question = array("Id"=>$id,"Title"=>$title,"StartTime"=>$startTime,"HardDeadline"=>$hardDeadline,"MovingDeadlineSeconds"=>$movingDeadlineSeconds,"Creator"=>$creator,"Choices"=>$choices);
				return $question;
			} else if($this->con->errno) {
				throw new Exception($this->con->error);
			}
			return false;
        }
        public function getQuestions(){
			if($query = $this->con->prepare("
				SELECT
					q.Id AS Id,
					q.title AS Title,
					q.startTime AS StartTime,
					q.hardDeadline AS HardDeadline,
					q.movingDeadlineSeconds AS MovingDeadlineSeconds,
					u.email Creator
					FROM questions q
					INNER JOIN users u ON q.userId = u.id
					LEFT JOIN invitations i ON i.questionId = q.id
					WHERE
						(q.userId = ? OR i.email = ?)
			")){
				$id = $this->user->getId();
				$email = $this->user->getEmail();
				$query->bind_param("ss",$id,$email);
				$query->execute();
				$query->bind_result($id,$title,$startTime,$hardDeadline,$movingDeadlineSeconds,$creator);
				$questions = array();
				while($query->fetch()){
					$questions[] = array("Id"=>$id,"Title"=>$title,"StartTime"=>$startTime,"HardDeadline"=>$hardDeadline,"MovingDeadlineSeconds"=>$movingDeadlineSeconds,"Creator"=>$creator);
				}
				return $questions;
			} else if($this->con->errno) {
				throw new Exception($this->con->error);
			}
			return false;
        }
		// Invite a user by email address
		private function inviteUser($questionId,$inviteeEmail){
			//die("Inviting ".$inviteeEmail." to ".$questionId);
			// Did this user create the question?
			// Is the invited user not already invited to this question?
			// Both true? Add the entry.
			// TODO: Note that we tie this to email and not userId on purpose.
			//			We want to be able to invite users to participate, asking them
			//			to register if they haven't yet.

			if($query = $this->con->prepare("
				INSERT INTO invitations
					(id,questionId,email)
				SELECT ?,q.Id,?
				FROM
					questions q
					INNER JOIN users u ON u.Id = q.userId
				WHERE
					q.Id = ?
					AND
					u.Id = ?
					AND
				 	? NOT IN (SELECT email FROM invitations WHERE questionId = ?)")){
				$id = $this->getNewGuid();
                $userId = $this->user->getId();
				$query->bind_param("ssssss",$id,$inviteeEmail,$questionId,$userId,$inviteeEmail,$questionId);
				$query->execute();
                $query->free_result();
                $query->close();
                if($this->isInvited($questionId,$inviteeEmail)){
					return true;
				} else {
					throw new Exception("Could not invite `{$inviteeEmail}`."); // TODO: Perhaps be more specific in this message?
                }
            } else if($this->con->errno) {
                throw new Exception($this->con->error); // TODO: Obfuscate
            }
			return false;
        }
        
		// Check that user is invited
		private function isInvited($questionId,$inviteeEmail){
			if($query = $this->con->prepare("
				SELECT COUNT(*) AS Count FROM invitations WHERE questionId = ? AND email = ?
			")){
				$query->bind_param("ss",$questionId,$inviteeEmail);
				$query->execute();
				$query->bind_result($count);
				$query->fetch();
                $query->free_result();
				$query->close();
				if($count == 1){
					return true;
				} else {
					return false;
				}
			} else if($this->con->errno){
                throw new Exception($this->con->error); // TODO: Obfuscate;
            }
			return false;
		}

		private function markInvitationUsed($invitationId){
			// TODO: Check that the user owns the owning question
			if($query = $this->con->prepare("UPDATE invitations SET used = 1 WHERE email = ? LIMIT 1")){
				$query->bind_param("ss",$invitationId,$this->user->getEmail());
				$query->execute();
                $query->free_result();
                $query->close();
                return true;
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
            }
			return false;
		}

		private function isInvitationUsed($invitationId){
			if($query = $this->con->prepare("SELECT used FROM invitations WHERE id = ? LIMIT 1")){
				$query->bind_param("ss",$invitationId);
				$query->execute();
				$query->bind_result($used);
                $query->free_result();
				$query->close();
				if($used){
					return true;
				} else {
					return false;
				}
			} else if($this->con->errno){
                throw Exception($this->con->error); // TODO: Obfuscate.
            }
			return false;
		}

		private function vote($invitationId,$votes){
			// here we actually 'fill out' the ballot
			//		Any duplicate choices or priorities?
			//		Do priorities skip values? -- can correct by putting
			//		in priority order and assigning values there.
			//		Is user authorized to vote on this?
			if($this->isInvitationUsed()){
				throw new Exception("This invitation was already used."); // TODO: Revealing too much?
			}

			if($query = $this->con->prepare("
				SELECT id,questionId FROM ballots WHERE questionId = ? AND email = ?
			")){
				$query->bind_param("ss",$questionId,$inviteeEmail);
				$query->execute();
				$query->bind_result($count);
				$query->fetch();
                $query->free_result();
				$query->close();
				if($count == 1){
					return true;
				} else {
					return false;
				}
			} else if($this->con->errno){
                throw new Exception($this->con->error); // TODO: Obfuscate;
            }
			return false;
		}

		private function decide($questionId){
			if($query = $this->con->query("
				SELECT * FROM votes WHERE questionId = ?
			")){

			} else if($this->con->errno){
				throw new Exception($this->con->error);
			}
            // this decides the question and closes it.
            // not meant to be called by the user, but to be triggered if the question's
            // ending conditions are met.
			return true;
		}
    }
?>