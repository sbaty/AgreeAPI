<?php

class Ballot {
	private $id;
	private $questionId;
	private $complete;

	private function saveBallot(){
		return array("Notice"=>"Saving ballot (placeholder).");
	}

	public function __construct($questionId){
		if(is_integer($questionId)){
			$this->saveBallot();
		} else {
			return array("Error","A ballot requires a Question ID.");
		}
	}
}

?>