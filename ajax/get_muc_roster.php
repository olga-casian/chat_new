<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

if (isset($_POST['to']) && isset($_POST['my_jid'])){
	$to = $_POST['to'];
	$my_jid = $_POST['my_jid'];
	
	$roster = '';	
	
	$sql = "SELECT member_id, first_name, last_name, jid FROM ".TABLE_PREFIX."chat_members CM INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
		WHERE CM.jid IN
			(SELECT U.user_jid FROM ".TABLE_PREFIX."chat_user_mucs U
					WHERE U.muc_jid='".$to."')
		ORDER BY M.first_name ASC 
		";
	
	$result = mysql_query($sql, $db);
	
	while($row = mysql_fetch_assoc($result)){
		$profile_link = "<a href='profile.php?id=" .$row[member_id]. "'>" .$row[first_name] . ' ' . $row[last_name] ."</a>";
		
		if ($row[jid] != $my_jid) {        	        
	    	$roster .= "<li class='muclist_" .$row[jid]. "'>" .$profile_link. "</li>";
	    } else {
	       	$roster .= "<li class='muc_roster_me' style='background-color:white; border:2px solid #BBB;'>" .$profile_link. "</li>";
	    }
	}
	
	echo "<div>".$roster."</div>";
	
}

?>
