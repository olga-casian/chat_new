<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

// called when we need to check if received data is from course member

if (isset($_POST['jid'])){
	$jid = $_POST['jid'];
	// if user is from the current course
	$sql = "SELECT * FROM ".TABLE_PREFIX."chat_members C INNER JOIN ".TABLE_PREFIX."course_enrollment E USING (member_id)
		WHERE jid='".$jid."' 
		AND E.course_id=$_SESSION[course_id]
		AND E.approved='y'";
	$result = mysql_query($sql, $db);
	$row = mysql_fetch_assoc($result);
	if (is_array($row)){
		echo 1;
	} else {
		echo 0;
	}

	exit();
}
?>
