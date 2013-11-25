<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

if (isset($_POST['from_bare'])){
	$jid = $_POST['from_bare'];
	$sql = "SELECT C.member_id, jid, first_name, last_name FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id)
		WHERE C.jid='" . $jid . "'";
	$result = mysql_query($sql, $db);
	if ($result) { 
		$row = mysql_fetch_assoc($result);		
		$sql_status = "SELECT * FROM ".TABLE_PREFIX."users_online WHERE member_id = '" . $row[member_id] . "'";
		$result_status = mysql_query($sql, $db);
		if ($result_status) {
			$row_status = mysql_fetch_assoc($result_status);
			if (count($row_status) == 1) {
				$to_return = "<div class='friends_column_wrapper offline' id=" . $row[jid] . " role='listitem' title='" . $participant[2].' '.$participant[3] ." - Offline'>".
						"onkeydown='return Interface.optionKeyEvent_friends_column_wrapper(event);' tabindex='0' aria-controls='roster'".
					    "onkeypress='return Interface.optionKeyEvent_friends_column_wrapper(event);'".
					    "onblur='jQuery(this)[0].showFocus = false;'".
					    "onfocus='jQuery(this)[0].showFocus = true;'>".				
								"<table class='friends_item' id=" . $row[member_id] . "><tr>".
									"<td><img src=" . $_base_path . "get_profile_img.php?id=" . $row[member_id] . " class='friends_item_picture' alt='userphoto' /></td>".
									"<td class='friends_item_name'>" . $row[first_name] . " " . $row[last_name] . "</td>".
									"<td class='friends_item_status'></td>".
								"</tr></table>".
					  "</div>";
					  
			} else {
				$to_return = "<div class='friends_column_wrapper online' id=" . $row[jid] . " role='listitem' title='" . $participant[2].' '.$participant[3] ." - Online'>".
						"onkeydown='return Interface.optionKeyEvent_friends_column_wrapper(event);' tabindex='0' aria-controls='roster'".
					    "onkeypress='return Interface.optionKeyEvent_friends_column_wrapper(event);'".
					    "onblur='jQuery(this)[0].showFocus = false;'".
					    "onfocus='jQuery(this)[0].showFocus = true;'>".	
								"<table class='friends_item' id=" . $row[member_id] . "><tr>".
									"<td><img src=" . $_base_path . "get_profile_img.php?id=" . $row[member_id] . " class='friends_item_picture' alt='userphoto' /></td>".
									"<td class='friends_item_name'>" . $row[first_name] . " " . $row[last_name] . "</td>".
									"<td class='friends_item_status'>Online</td>".
								"</tr></table>".
					  "</div>";
			}
			echo $to_return;
		}
	}
	
}

?>
