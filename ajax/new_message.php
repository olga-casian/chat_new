<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");

if (isset($_POST['from']) && isset($_POST['to']) && isset($_POST['msg']) && isset($_POST['timestamp']) && isset($_POST['groupchat'])){
	$from = $_POST['from'];
	$to = $_POST['to'];
	$msg = urlencode($_POST['msg']);
	$timestamp = $_POST['timestamp'];
	
	$sql_from = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
					WHERE C.jid='".$from."'";
	$result_from = mysql_query($sql_from, $db);
	$row_from = mysql_fetch_assoc($result_from);
	
	$mcrypt = new Anti_Mcrypt($row_from[password]);
	$msg = $mcrypt->encrypt($msg);
	
	if ($_POST['groupchat'] == 0){
		// chat message
		$sql = "INSERT INTO `".TABLE_PREFIX."chat_messages` (`from`, `to`, `msg`, `timestamp`) VALUES ('$from', '$to', '$msg', '$timestamp')";
		$resp = mysql_query($sql,$db);
		if ($resp){
			echo 1;
		}
		
	} else if ($_POST['groupchat'] == 1) {
		// muc message	
		$sql = "INSERT INTO `".TABLE_PREFIX."chat_muc_messages` (`from`, `to`, `msg`, `timestamp`) VALUES ('$from', '$to', '$msg', '$timestamp')";
		$resp = mysql_query($sql,$db);
		if ($resp){
			echo 1;
		}
	}
}

?>
