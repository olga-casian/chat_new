<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");
require_once("constants.php");

if (isset($_POST['my_id'])) {
	$my_id = $_POST['my_id'];
	
	if (isset($_POST['offset'])) {
		$offset = $_POST['offset'];
	} else {
		$offset = 0;
	}
	$end = $offset + $INBOX_ONE_TIME_LOAD; 
	
	$sql = "SELECT * FROM %schat_members m WHERE m.member_id='%d'";
	$result = queryDB($sql, array(TABLE_PREFIX, $my_id), true);
	$my_jid = $result['jid'];
	
	$sql = "
	SELECT * FROM %schat_messages ms
		WHERE ms.to IN (
		 SELECT DISTINCT M.jid FROM %schat_members M
			 WHERE (
				  M.jid IN (
				   SELECT ms.to FROM %schat_members CM INNER JOIN %schat_messages ms
				  		 WHERE (ms.to = CM.jid OR ms.from = CM.jid)
				  			 AND CM.member_id = %d
				  ) OR M.jid IN (
				   SELECT ms.from FROM %schat_members CM INNER JOIN %schat_messages ms
				   		WHERE (ms.to = CM.jid OR ms.from = CM.jid)
				   			AND CM.member_id = %d
				  )
			  )
		)
		UNION ALL SELECT * FROM %schat_muc_messages mc WHERE mc.to IN (SELECT um.muc_jid FROM %schat_user_mucs um WHERE um.user_jid='%s')
		ORDER BY timestamp DESC
	";
	
	$result = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $my_id, TABLE_PREFIX, TABLE_PREFIX, $my_id, TABLE_PREFIX, TABLE_PREFIX, $my_jid));
	$communicated_with = array();
	foreach($result as $row){
		if (!in_array($row['to'], $communicated_with) && $row['to'] != $my_jid){				
//			debug('TO   '.$row['from']. '   ' .$row['to']);
			$communicated_with[] = $row['to'];
			
		} else if (!in_array($row['from'], $communicated_with) && $row['from'] != $my_jid) {
			if (!in_array($row['to'], $communicated_with) && strpos($row['to'],'@conference.talkr.im') === false) {
//				debug('FROM '.$row['from']. '   ' .$row['to']);
				$communicated_with[] = $row['from'];
			}
		}
	}
//	debug($communicated_with, "communicated_with");
	
	$html = '';
	
	for ($offset; $offset < $end; $offset++) {
		if (strpos($communicated_with[$offset],'@conference.talkr.im') === false) {
			// private contact
	
			$sql = "SELECT * FROM %schat_messages C 
				WHERE 
				(C.to='%s' OR C.to='%s') AND (C.from='%s' OR C.from='$s') 
				ORDER BY timestamp DESC LIMIT 1		
			";
			$result = queryDB($sql, array(TABLE_PREFIX, $communicated_with[$offset], $my_jid, $my_jid, $communicated_with[$offset]));
			foreach($result as $row){
				if ($row['to'] == $my_jid) {
					$jid = $row['from'];
					$inbox_id = $row['from'];
				} else {
					$jid = $row['to'];
					$inbox_id = $row['to'];
				}
				
				$sql_from = "SELECT * FROM %schat_members C INNER JOIN %smembers M USING (member_id) 
					WHERE C.jid='%s'";
				$row_from = queryDB($sql_from, array(TABLE_PREFIX, TABLE_PREFIX, $jid), true);
				
				
				$sql_pass = "SELECT * FROM %schat_members C INNER JOIN %smembers M USING (member_id) 
								WHERE C.jid='%s'";
				$row_pass = queryDB($sql_pass, array(TABLE_PREFIX, TABLE_PREFIX, $row['from']), true);
				
				$mcrypt = new Anti_Mcrypt($row_pass['password']);
				$msg = $mcrypt->decrypt($row['msg']);
				
				
				$html .= "<li class='inbox_list_item inbox_private' id='inbox_".$inbox_id."' role='listitem' title='Chat with ".$row_from['first_name']. ' ' .$row_from['last_name']."'  tabindex='0' aria-controls='inbox_list' onblur='jQuery(this)[0].showFocus = false;' onfocus='jQuery(this)[0].showFocus = true;' onkeydown='return Interface.optionKeyEvent_inbox_list_item(event);' onkeypress='return Interface.optionKeyEvent_inbox_list_item(event);'>".
				
					"<table><tr>".
							"<td><img class='picture' src='" .$_base_path. "get_profile_img.php?id=" .$row_from['member_id']. "' alt='userphoto'/></td>".
							"<td class='inbox_list_middle'>".
								"<label class='inbox_list_name'><a href='profile.php?id=" .$row_from['member_id']. "'>".$row_from['first_name']. ' ' .$row_from['last_name']."</a></label>".
								"<div class='inbox_list_info'>".urldecode($msg)."</div>".
							"</td>".
								
							"<td class='inbox_list_time'>".$row['timestamp']."</td>".
					"</tr></table>".
				"</li>";
			}
			
		} else {
			// muc group
			
			$sql = "SELECT * FROM %schat_muc_messages C 
				WHERE C.to='%s'
				ORDER BY timestamp DESC LIMIT 1		
			";
			$result = queryDB($sql, array(TABLE_PREFIX, $communicated_with[$offset]));
			foreach($result as $row){	
				$groupname = substr($row['to'], 0, strlen($row['to']) - strlen('@conference.talkr.im'));
				
				$sql_from = "SELECT COUNT(*) as nr FROM %schat_members C INNER JOIN %smembers M USING (member_id)
								WHERE C.jid IN
									(SELECT UM.user_jid FROM %schat_user_mucs UM WHERE UM.muc_jid='%s')";
				$row_from = queryDB($sql_from, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $row['to']), true);
				$nr = $row_from['nr'];
				
				
				$sql_pass = "SELECT * FROM %schat_members C INNER JOIN %smembers M USING (member_id) 
								WHERE C.jid='%s'";
				$row_pass = queryDB($sql_pass, array(TABLE_PREFIX, TABLE_PREFIX, $row['from']), true);
				
				$mcrypt = new Anti_Mcrypt($row_pass['password']);
				$msg = $mcrypt->decrypt($row['msg']);
				
				
				$html .= "<li class='inbox_list_item inbox_muc' id='inbox_".$row['to']."' role='listitem' title='Group chat ".$groupname."' tabindex='0' aria-controls='inbox_list' onblur='jQuery(this)[0].showFocus = false;' onfocus='jQuery(this)[0].showFocus = true;' onkeydown='return Interface.optionKeyEvent_inbox_list_item(event);' onkeypress='return Interface.optionKeyEvent_inbox_list_item(event);'>".
					
					"<table><tr>".
							"<td><img class='picture' src='".$_base_path."/images/home-acollab.png' alt='group_chat_image'/></td>".
							"<td class='inbox_list_middle'>".
								"<label class='inbox_list_name'>".$groupname."</label>".
								"<label class='inbox_list_nr'> (".$nr." members)</label>".
								"<div class='inbox_list_info'>".urldecode($msg)."</div>".
							"</td>".
								
							"<td class='inbox_list_time'>".$row['timestamp']."</td>".
					"</tr></table>".
				"</li>";
			}
		}
	}
	
	echo $html;






} else if (isset($_POST['group_jid'])) {
	$jid = $_POST['group_jid'];
	$sql = "SELECT COUNT(*) as nr FROM %schat_members C INNER JOIN %smembers M USING (member_id)
				WHERE C.jid IN
					(SELECT UM.user_jid FROM %schat_user_mucs UM WHERE UM.muc_jid='%s')";
	$row = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $jid), true);
	echo $row['nr']."  ".$_base_path;
	
}

?>