<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");

if (isset($_POST['from']) && isset($_POST['to']) && isset($_POST['msg']) && isset($_POST['timestamp']) && isset($_POST['groupchat'])){
	$from = $_POST['from'];
	$to = $_POST['to'];
	$msg = urlencode($_POST['msg']);
	$timestamp = $_POST['timestamp'];
	
	$sql_from = "SELECT * FROM %schat_members C INNER JOIN %smembers M USING (member_id) 
					WHERE C.jid='%s'";
	$row_from = queryDB($sql_from, array(TABLE_PREFIX, TABLE_PREFIX, $from), true);
	
	$mcrypt = new Anti_Mcrypt($row_from['password']);
	$msg = $mcrypt->encrypt($msg);
	
	if ($_POST['groupchat'] == 0){
		// chat message
		$sql = "INSERT INTO `%schat_messages` (`from`, `to`, `msg`, `timestamp`) VALUES ('%s', '%s', '%s', '%s')";
		$resp = queryDB($sql, array(TABLE_PREFIX, $from, $to, $msg, $timestamp));
		// TODO: check if inserted
		echo 1;
		
	} else if ($_POST['groupchat'] == 1) {
		// muc message	
		$sql = "INSERT INTO `%schat_muc_messages` (`from`, `to`, `msg`, `timestamp`) VALUES ('%s', '%s', '%s', '%s')";
		$resp = queryDB($sql, array(TABLE_PREFIX, $from, $to, $msg, $timestamp));
		// TODO: check if inserted
		echo 1;
	}
}

?>
