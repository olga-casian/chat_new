<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");

// new entry in chat_members table
if (isset($_POST['id']) && isset($_POST['jid']) && isset($_POST['pass']) && isset($_POST['course_id'])){
	$id = $_POST['id'];
	$jid = $_POST['jid'];
	$pass = $_POST['pass'];	
	$course_id = $_POST['course_id'];
	$sql = "SELECT * FROM ".TABLE_PREFIX."chat_members WHERE member_id=$id";
	$result = mysql_query($sql, $db);
	if (count(mysql_fetch_assoc($result)) == 1){
		
		$sql_pass = "SELECT * FROM ".TABLE_PREFIX."members WHERE member_id='".$id."'";
		$result_pass = mysql_query($sql_pass, $db);
		$row_pass = mysql_fetch_assoc($result_pass);
					
		$mcrypt = new Anti_Mcrypt($row_pass[password]);
		$pass = $mcrypt->encrypt($pass);
		
		$sql = "INSERT INTO ".TABLE_PREFIX."chat_members (member_id, jid, password) VALUES ('$id', '$jid', '$pass')";
		$resp = mysql_query($sql,$db);
		if ($resp){
			$sql = "SELECT first_name, last_name, member_id FROM ".TABLE_PREFIX."members WHERE member_id=$id";
			$result = mysql_query($sql, $db);
			$row = mysql_fetch_assoc($result);			
			$to_echo = $jid. ' ' .$row['first_name']. ' ' .$row['last_name']. ' get_profile_img.php?id='.$row['member_id']. ' ' .$row['member_id'];
			
			$sql = "SELECT jid FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."course_enrollment E USING (member_id) INNER JOIN ".TABLE_PREFIX."members M
				WHERE E.course_id=".$course_id."
				AND E.approved='y'
				AND E.member_id=M.member_id
				AND C.jid!='".$jid."'";
			$result = mysql_query($sql, $db);
			while ($row = mysql_fetch_assoc($result)) {
				$to_echo .= ' ' .$row['jid'];
			}
			echo $to_echo;
		} else{
			echo 0;
		}
	}
	exit();
	
}

// called each time on index page load, gets jid and pass to authenticate
if (isset($_POST['id'])){
	$id = $_POST['id'];
	$sql = "SELECT jid, password FROM ".TABLE_PREFIX."chat_members WHERE member_id=$id";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_assoc($result);
	if (count($row) == 1){
		echo 0;
	} else {
		$sql_pass = "SELECT * FROM ".TABLE_PREFIX."members WHERE member_id='".$id."'";
		$result_pass = mysql_query($sql_pass, $db);
		$row_pass = mysql_fetch_assoc($result_pass);
					
		$mcrypt = new Anti_Mcrypt($row_pass[password]);
		$pass = $mcrypt->decrypt($row['password']);
		
		echo $row['jid']. ' ' .$pass;
	}
	exit();
}


?>
