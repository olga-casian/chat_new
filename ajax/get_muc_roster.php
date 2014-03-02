<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

if (isset($_POST['to']) && isset($_POST['my_jid'])){
	$to = $_POST['to'];
	$my_jid = $_POST['my_jid'];
	
	$roster = '';	
	
	$sql = "SELECT * FROM %schat_members CM INNER JOIN %smembers M USING (member_id) 
		WHERE CM.jid IN
			(SELECT U.user_jid FROM %schat_user_mucs U
					WHERE U.muc_jid='%s')
		ORDER BY M.first_name ASC 
		";
	
	$result = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $to));
	
	foreach($result as $row){
		$profile_link = "<a href='profile.php?id=" .$row['member_id']. "'>" .$row['first_name'] . ' ' . $row['last_name'] ."</a>";
		
		if ($row['jid'] != $my_jid) {        	        
	    	$roster .= "<li class='muclist_" .$row['jid']. "'>" .$profile_link. "</li>";
	    } else {
	       	$roster .= "<li class='muc_roster_me' style='background-color:white; border:2px solid #BBB;'>" .$profile_link. "</li>";
	    }
	}
	
	echo "<div>".$roster."</div>";
	
}

?>
