<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");

if (isset($_POST['my_id'])) {
	$my_id = $_POST['my_id'];
	
	if (isset($_POST['offset'])) {
		$offset = $_POST['offset'];
	} else {
		$offset = 0;
	}
	$end = $offset + 10; // load 10 messages by ony ajax request
	
	$sql = "SELECT jid FROM ".TABLE_PREFIX."chat_members m WHERE m.member_id='".$my_id."'";
	$result = mysql_query($sql, $db);
	if ($result) {
		$row = mysql_fetch_assoc($result);
		$my_jid = $row[jid];
	}
	
	$sql = "
	SELECT * FROM ".TABLE_PREFIX."chat_messages ms
		WHERE ms.to IN (
		 SELECT DISTINCT M.jid FROM ".TABLE_PREFIX."chat_members M
			 WHERE (
				  M.jid IN (
				   SELECT ms.to FROM ".TABLE_PREFIX."chat_members CM INNER JOIN ".TABLE_PREFIX."chat_messages ms
				  		 WHERE (ms.to = CM.jid OR ms.from = CM.jid)
				  			 AND CM.member_id = ".$my_id."
				  ) OR M.jid IN (
				   SELECT ms.from FROM ".TABLE_PREFIX."chat_members CM INNER JOIN ".TABLE_PREFIX."chat_messages ms
				   		WHERE (ms.to = CM.jid OR ms.from = CM.jid)
				   			AND CM.member_id = ".$my_id."
				  )
			  )
		)
		UNION ALL SELECT * FROM ".TABLE_PREFIX."chat_muc_messages mc WHERE mc.to IN (SELECT um.muc_jid FROM ".TABLE_PREFIX."chat_user_mucs um WHERE um.user_jid='".$my_jid."')
		ORDER BY timestamp DESC
	";
	
	$result = mysql_query($sql, $db);
	if ($result) {
		$communicated_with = array();
		while($row = mysql_fetch_assoc($result)){
			if (!in_array($row[to], $communicated_with) && $row[to] != $my_jid){				
	//			debug('TO   '.$row[from]. '   ' .$row[to]);
				$communicated_with[] = $row[to];
				
			} else if (!in_array($row[from], $communicated_with) && $row[from] != $my_jid) {
				if (!in_array($row[to], $communicated_with) && strpos($row[to],'@conference.talkr.im') === false) {
	//				debug('FROM '.$row[from]. '   ' .$row[to]);
					$communicated_with[] = $row[from];
				}
			}
		}
	//	debug($communicated_with, "communicated_with");
	}
	
	$html = '';
	
	for ($offset; $offset < $end; $offset++) {
		if (strpos($communicated_with[$offset],'@conference.talkr.im') === false) {
			// private contact
	
			$sql = "SELECT * FROM ".TABLE_PREFIX."chat_messages C 
				WHERE 
				(C.to='".$communicated_with[$offset]."' OR C.to='".$my_jid."') AND (C.from='".$my_jid."' OR C.from='".$communicated_with[$offset]."') 
				ORDER BY timestamp DESC LIMIT 1		
			";
			$result = mysql_query($sql, $db);
			if ($result) {
				while($row = mysql_fetch_assoc($result)){
					if ($row[to] == $my_jid) {
						$jid = $row[from];
						$inbox_id = $row[from];
					} else {
						$jid = $row[to];
						$inbox_id = $row[to];
					}
					
					$sql_from = "SELECT first_name, last_name, member_id FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
						WHERE C.jid='".$jid."'";
					$result = mysql_query($sql_from, $db);
					$row_from = mysql_fetch_assoc($result);
					
					
					$sql_pass = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
									WHERE C.jid='".$row[from]."'";
					$result_pass = mysql_query($sql_pass, $db);
					$row_pass = mysql_fetch_assoc($result_pass);
					
					$mcrypt = new Anti_Mcrypt($row_pass[password]);
					$msg = $mcrypt->decrypt($row[msg]);
					
					
					$html .= "<li class='inbox_list_item inbox_private' id='inbox_".$inbox_id."' role='listitem' title='Chat with ".$row_from[first_name]. ' ' .$row_from[last_name]."'  tabindex='0' aria-controls='inbox_list' onblur='jQuery(this)[0].showFocus = false;' onfocus='jQuery(this)[0].showFocus = true;' onkeydown='return Interface.optionKeyEvent_inbox_list_item(event);' onkeypress='return Interface.optionKeyEvent_inbox_list_item(event);'>".
					
		         		"<table><tr>".
		         				"<td><img class='picture' src='" .$_base_path. "get_profile_img.php?id=" .$row_from[member_id]. "' alt='userphoto'/></td>".
		                       	"<td class='inbox_list_middle'>".
		                        	"<label class='inbox_list_name'><a href='profile.php?id=" .$row_from[member_id]. "'>".$row_from[first_name]. ' ' .$row_from[last_name]."</a></label>".
		                        	"<div class='inbox_list_info'>".urldecode($msg)."</div>".
		                       	"</td>".
		                        	
		                       	"<td class='inbox_list_time'>".$row[timestamp]."</td>".
		                "</tr></table>".
		            "</li>";
				}		
			}
			
		} else {
			// muc group
			
			$sql = "SELECT * FROM ".TABLE_PREFIX."chat_muc_messages C 
				WHERE C.to='".$communicated_with[$offset]."'
				ORDER BY timestamp DESC LIMIT 1		
			";
			$result = mysql_query($sql, $db);
			if ($result) {
				while($row = mysql_fetch_assoc($result)){	
					$groupname = substr($row[to], 0, strlen($row[to]) - strlen('@conference.talkr.im'));
					
					$sql_from = "SELECT COUNT(*) as nr FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id)
									WHERE C.jid IN
										(SELECT UM.user_jid FROM ".TABLE_PREFIX."chat_user_mucs UM WHERE UM.muc_jid='".$row[to]."')";
					$result = mysql_query($sql_from, $db);
					if ($result) {				
						$row_from = mysql_fetch_assoc($result);
						$nr = $row_from[nr];
					}
					
					
					$sql_pass = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
									WHERE C.jid='".$row[from]."'";
					$result_pass = mysql_query($sql_pass, $db);
					$row_pass = mysql_fetch_assoc($result_pass);
					
					$mcrypt = new Anti_Mcrypt($row_pass[password]);
					$msg = $mcrypt->decrypt($row[msg]);
					
					
					$html .= "<li class='inbox_list_item inbox_muc' id='inbox_".$row[to]."' role='listitem' title='Group chat ".$groupname."' tabindex='0' aria-controls='inbox_list' onblur='jQuery(this)[0].showFocus = false;' onfocus='jQuery(this)[0].showFocus = true;' onkeydown='return Interface.optionKeyEvent_inbox_list_item(event);' onkeypress='return Interface.optionKeyEvent_inbox_list_item(event);'>".
						
		         		"<table><tr>".
		         				"<td><img class='picture' src='".$_base_path."/images/home-acollab.png' alt='group_chat_image'/></td>".
		                       	"<td class='inbox_list_middle'>".
		                        	"<label class='inbox_list_name'>".$groupname."</label>".
		                        	"<label class='inbox_list_nr'> (".$nr." members)</label>".
		                        	"<div class='inbox_list_info'>".urldecode($msg)."</div>".
		                       	"</td>".
		                        	
		                       	"<td class='inbox_list_time'>".$row[timestamp]."</td>".
		                "</tr></table>".
		            "</li>";
				}	
			}
		}
	}
	
	echo $html;






} else if (isset($_POST['group_jid'])) {
	$jid = $_POST['group_jid'];
	$sql = "SELECT COUNT(*) as nr FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id)
				WHERE C.jid IN
					(SELECT UM.user_jid FROM ".TABLE_PREFIX."chat_user_mucs UM WHERE UM.muc_jid='".$jid."')";
	$result = mysql_query($sql, $db);
	if ($result) {
		$row = mysql_fetch_assoc($result);
		
		echo $row[nr]."  ".$_base_path;
	}
	
}

?>