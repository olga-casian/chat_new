<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

if (isset($_POST['id'])){
	$my_id = $_POST['id'];
	
	$sql = "SELECT * FROM %schat_members m WHERE m.member_id='%d'";
	$row = queryDB($sql, array(TABLE_PREFIX, $my_id), true);
	if (!empty($row)) {
		$my_jid = $row['jid'];
		
		$sql = "SELECT um.muc_jid FROM %schat_user_mucs um WHERE um.user_jid='%s'";
		$result = queryDB($sql, array(TABLE_PREFIX, $my_jid));
		if (!empty($result)) {
			$mucs = '';
			foreach($result as $row) {
				$mucs .= $row['muc_jid']."  ";
			}
			echo substr($mucs, 0, strlen($mucs) - 2);
			
		} else {
			echo -1;
		}		
	}
	
}

?>
