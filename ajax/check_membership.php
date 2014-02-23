<?php
if (!defined('AT_INCLUDE_PATH')) { exit; }

// called when we need to check if received data is from course member

if (isset($_POST['jid'])){
	$jid = $_POST['jid'];
	// if user is from the current course
	$sql = "SELECT * FROM %schat_members C INNER JOIN %scourse_enrollment E USING (member_id)
		WHERE jid='%s' 
		AND E.course_id=%d
		AND E.approved='y'";
	$row = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, $jid, $_SESSION[course_id]), true);
	if (is_array($row)){
		echo 1;
	} else {
		echo 0;
	}

	exit();
}
?>
