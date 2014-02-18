<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");

// new entry in chat_members table
if (isset($_POST['id']) && isset($_POST['jid']) && isset($_POST['pass']) && isset($_POST['course_id'])){
	$id = $_POST['id'];
	$jid = $_POST['jid'];
	$pass = $_POST['pass'];	
	$course_id = $_POST['course_id'];
	$sql = "SELECT * FROM %schat_members WHERE member_id=%d";
	$result = queryDB($sql, array(TABLE_PREFIX, $id));
	if (count($result) == 0){
		$sql_pass = "SELECT * FROM %smembers WHERE member_id=%d";
		$result_pass = queryDB($sql_pass, array(TABLE_PREFIX, $id), true);
		$mcrypt = new Anti_Mcrypt($result_pass[password]);
		$pass = $mcrypt->encrypt($pass);
		
		$sql = "INSERT INTO %schat_members (member_id, jid, password) VALUES (%d, '%s', '%s')";
		$resp = queryDB($sql, array(TABLE_PREFIX, $id, $jid, $pass), true, false);
		//echo $pass;
		//echo "here";
		//echo $resp;
		//if ($resp){
			$sql = "SELECT first_name, last_name, member_id FROM %smembers WHERE member_id='%s'";
			$row = queryDB($sql, array(TABLE_PREFIX, $id), true);
			$to_echo = $jid. ' ' .$row['first_name']. ' ' .$row['last_name']. ' get_profile_img.php?id='.$row['member_id']. ' ' .$row['member_id'];
			
			$sql = "SELECT jid FROM %schat_members C INNER JOIN %scourse_enrollment E USING (member_id) INNER JOIN %smembers M
				WHERE E.course_id=%d
				AND E.approved='y'
				AND E.member_id=M.member_id
				AND C.jid!='%s'";
			$result = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $course_id, $jid));
			foreach ($result as $row) {
				$to_echo .= ' ' .$row['jid'];
			}
			echo $to_echo;
		//} else{
		//	echo 0;
		//}
	}
	exit();
	
}

// called each time on index page load, gets jid and pass to authenticate
if (isset($_POST['id'])){
	$id = $_POST['id'];
	$sql = "SELECT * FROM %schat_members WHERE member_id=%d";
	$result = queryDB($sql, array(TABLE_PREFIX, $id), true);
	if (count($result) == 0){
		echo 0;
	} else {
		$sql_pass = "SELECT * FROM %smembers WHERE member_id=%d";
		$result_pass = queryDB($sql_pass, array(TABLE_PREFIX, $id), true);
		
		$mcrypt = new Anti_Mcrypt($result_pass[password]);
		$pass = $mcrypt->decrypt($result['password']);
		
		echo $result['jid']. ' ' .$pass;
	}
	exit();
}


?>
