<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

if (isset($_POST['id'])){
	$my_id = $_POST['id'];
	
	$sql = "SELECT jid FROM AT_chat_members m WHERE m.member_id='".$my_id."'";
	$result = mysql_query($sql, $db);
	if ($result) {
		$row = mysql_fetch_assoc($result);
		$my_jid = $row[jid];
		
		$sql = "SELECT um.muc_jid FROM ".TABLE_PREFIX."chat_user_mucs um WHERE um.user_jid='".$my_jid."'";
		$result = mysql_query($sql, $db);
		if ($result) {
			$mucs = '';
			while($row = mysql_fetch_assoc($result)) {
				$mucs .= $row[muc_jid]."  ";
			}
			echo substr($mucs, 0, strlen($mucs) - 2);
			
		} else {
			echo -1;
		}
		
	}
	
}

?>
