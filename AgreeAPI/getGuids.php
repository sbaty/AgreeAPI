<?php
function createTestUsers(){
	for($i = 0; $i < 20; $i++){
		$id = com_create_guid();
		$email = "test".$i;
		$password = password_hash("pw", PASSWORD_DEFAULT);
		$token = com_create_guid();
		echo 'INSERT INTO users (id,email,password,token) VALUES ("'.$id.'","'.$email.'","'.$password.'","'.$token.'");\r';
	}
}
function inviteTestUsers(){
	for($i = 0; $i < 20; $i++){
		$id = com_create_guid();
		$id = str_replace(array('{','}'),'',$id);
		$questionId = "FEFC3419-EB2D-4CFC-863D-427F1BBBC4BC";
		$email = "test".$i;
		echo 'INSERT INTO invitations (id,questionId,email) VALUES ("'.$id.'","'.$questionId.'","'.$email.'");';
	}
}
?>