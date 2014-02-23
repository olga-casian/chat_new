<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

if (isset($_POST['roomname']) && isset($_POST['members'])){
	$roomname = $_POST['roomname'];
	$members = explode("  ", $_POST['members']);
	
	for ($i = 0; $i < count($members); $i++) {
		$sql = "SELECT * FROM %schat_user_mucs WHERE muc_jid='%s' AND user_jid='%s'";
		$result = queryDB($sql, array(TABLE_PREFIX, $roomname, $members[$i]), true);
		if (empty($result)) {
			// does not exist
			$sql = "INSERT INTO `%schat_user_mucs` (`muc_jid`, `user_jid`) VALUES ('%s', '%s')";
			$resp = queryDB($sql, array(TABLE_PREFIX, $roomname, $members[$i]));
			
		} else {
			// exists
			echo 1;
		}
	}
	
}

?>
