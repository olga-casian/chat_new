<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

require_once("mcrypt/Mcrypt.php");

if (isset($_POST['from']) && isset($_POST['to']) && isset($_POST['offset'])){
	// private messages
	
	$from = $_POST['from'];
	$to = $_POST['to'];
	$offset = $_POST['offset'];
	
	$sql = "SELECT * FROM (SELECT * FROM ".TABLE_PREFIX."chat_messages C 
		WHERE 
		(C.to='".$to."' OR C.to='".$from."') AND (C.from='".$from."' OR C.from='".$to."') 
		ORDER BY timestamp DESC LIMIT ".$offset.",15) AS res ORDER BY timestamp ASC";
		
	$html = '';
	$result = mysql_query($sql, $db);

	while($row = mysql_fetch_assoc($result)){
		$sql_from = "SELECT first_name, last_name, member_id FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
				WHERE C.jid='".$row[from]."'";
		$result_from = mysql_query($sql_from, $db);
		$row_from = mysql_fetch_assoc($result_from);
		
		$sql_pass = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) WHERE C.jid='".$row[from]."'";
		$result_pass = mysql_query($sql_pass, $db);
		$row_pass = mysql_fetch_assoc($result_pass);
					
		$mcrypt = new Anti_Mcrypt($row_pass[password]);
		$msg = $mcrypt->decrypt($row[msg]);
		
		$html .= "<hr/><table><tr>" . 
         					"<td  class='conversations_picture'>" . 
                            "<img class='picture' src='" .$_base_path. "get_profile_img.php?id=" .$row_from[member_id]. "' alt='userphoto'/>" . 
                        	"</td>" .
                        	
                        	"<td  class='conversations_middle'>" . 
                        	"<label class='conversations_name'><a href='profile.php?id=" .$row_from[member_id]. "'>" .$row_from[first_name] . ' ' . $row_from[last_name]. "</a></label>" . 
                        	"<div class='conversations_msg'>"  .urldecode($msg).  
							"</div>" . 
                        	"</td>" . 
                        	
                        	"<td class='conversations_time'>" . $row[timestamp] . "</td>" . 
                 "</tr></table>";
	}	
	echo $html;

} else if (isset($_POST['from']) && isset($_POST['to'])){
	$from = $_POST['from'];
	$to = $_POST['to'];
	
	$sql = "SELECT * FROM (SELECT * FROM ".TABLE_PREFIX."chat_messages C 
		WHERE 
		(C.to='".$to."' OR C.to='".$from."') AND (C.from='".$from."' OR C.from='".$to."') 
		ORDER BY timestamp DESC LIMIT 10) AS res ORDER BY timestamp ASC";
		
	$html = '';
	$result = mysql_query($sql, $db);

	while($row = mysql_fetch_assoc($result)){
		$sql_from = "SELECT first_name, last_name, member_id FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
				WHERE C.jid='".$row[from]."'";
		$result_from = mysql_query($sql_from, $db);
		$row_from = mysql_fetch_assoc($result_from);
		
		$sql_pass = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) WHERE C.jid='".$row[from]."'";
		$result_pass = mysql_query($sql_pass, $db);
		$row_pass = mysql_fetch_assoc($result_pass);
					
		$mcrypt = new Anti_Mcrypt($row_pass[password]);
		$msg = $mcrypt->decrypt($row[msg]);
		
		$html .= "<hr/><table><tr>" . 
         					"<td  class='conversations_picture'>" . 
                            "<img class='picture' src='" .$_base_path. "get_profile_img.php?id=" .$row_from[member_id]. "' alt='userphoto'/>" . 
                        	"</td>" .
                        	
                        	"<td  class='conversations_middle'>" . 
                        	"<label class='conversations_name'><a href='profile.php?id=" .$row_from[member_id]. "'>" .$row_from[first_name] . ' ' . $row_from[last_name]. "</a></label>" . 
                        	"<div class='conversations_msg'>"  .urldecode($msg).  
							"</div>" . 
                        	"</td>" . 
                        	
                        	"<td class='conversations_time'>" . $row[timestamp] . "</td>" . 
                 "</tr></table>";
	}	
	echo $html;





} else if (isset($_POST['to']) && isset($_POST['offset'])) {
	// muc
	
	$to = $_POST['to'];
	$offset = $_POST['offset'];
	
	$sql = "SELECT * FROM (SELECT * FROM ".TABLE_PREFIX."chat_muc_messages C 
		WHERE C.to='".$to."'
		ORDER BY timestamp DESC LIMIT ".$offset.",15) AS res ORDER BY timestamp ASC";
		
	$html = '';
	$result = mysql_query($sql, $db);

	while($row = mysql_fetch_assoc($result)){
		$sql_from = "SELECT first_name, last_name, member_id FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
				WHERE C.jid='".$row[from]."'";
		$result_from = mysql_query($sql_from, $db);
		$row_from = mysql_fetch_assoc($result_from);
		
		$sql_pass = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) WHERE C.jid='".$row[from]."'";
		$result_pass = mysql_query($sql_pass, $db);
		$row_pass = mysql_fetch_assoc($result_pass);
					
		$mcrypt = new Anti_Mcrypt($row_pass[password]);
		$msg = $mcrypt->decrypt($row[msg]);
		
		$html .= "<hr/><table><tr>" . 
         					"<td  class='conversations_picture'>" . 
                            "<img class='picture' src='" .$_base_path. "get_profile_img.php?id=" .$row_from[member_id]. "' alt='userphoto'/>" . 
                        	"</td>" .
                        	
                        	"<td  class='conversations_middle'>" . 
                        	"<label class='conversations_name'><a href='profile.php?id=" .$row_from[member_id]. "'>" .$row_from[first_name] . ' ' . $row_from[last_name]. "</a></label>" . 
                        	"<div class='conversations_msg'>"  .urldecode($msg).  
							"</div>" . 
                        	"</td>" . 
                        	
                        	"<td class='conversations_time'>" . $row[timestamp] . "</td>" . 
                 "</tr></table>";
	}	
	echo $html;
	
} else if (isset($_POST['to'])){
	$to = $_POST['to'];
	
	$sql = "SELECT * FROM (SELECT * FROM ".TABLE_PREFIX."chat_muc_messages C 
		WHERE C.to='".$to."' 
		ORDER BY timestamp DESC LIMIT 10) AS res ORDER BY timestamp ASC";
		
	$html = '';
	$result = mysql_query($sql, $db);

	while($row = mysql_fetch_assoc($result)){
		$sql_from = "SELECT first_name, last_name, member_id FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) 
				WHERE C.jid='".$row[from]."'";
		$result_from = mysql_query($sql_from, $db);
		$row_from = mysql_fetch_assoc($result_from);
		
		$sql_pass = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."members M USING (member_id) WHERE C.jid='".$row[from]."'";
		$result_pass = mysql_query($sql_pass, $db);
		$row_pass = mysql_fetch_assoc($result_pass);
					
		$mcrypt = new Anti_Mcrypt($row_pass[password]);
		$msg = $mcrypt->decrypt($row[msg]);
		
		$html .= "<hr/><table><tr>" . 
         					"<td  class='conversations_picture'>" . 
                            "<img class='picture' src='" .$_base_path. "get_profile_img.php?id=" .$row_from[member_id]. "' alt='userphoto'/>" . 
                        	"</td>" .
                        	
                        	"<td  class='conversations_middle'>" . 
                        	"<label class='conversations_name'><a href='profile.php?id=" .$row_from[member_id]. "'>" .$row_from[first_name] . ' ' . $row_from[last_name]. "</a></label>" . 
                        	"<div class='conversations_msg'>"  .urldecode($msg).  
							"</div>" . 
                        	"</td>" . 
                        	
                        	"<td class='conversations_time'>" . $row[timestamp] . "</td>" . 
                 "</tr></table>";     
	}
	echo $html;
	
}

?>
