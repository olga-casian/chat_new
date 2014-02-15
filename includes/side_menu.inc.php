<?php 
if (!defined('AT_INCLUDE_PATH')) { exit; }
global $_base_path, $include_all, $include_one, $stripslashes;
global $savant;
	
$sql = "SELECT C.member_id, jid, first_name, last_name FROM %schat_members C INNER JOIN %scourse_enrollment E USING (member_id) INNER JOIN %smembers M
	WHERE E.course_id=%d
	AND E.approved='y'
	AND E.member_id=M.member_id
	AND E.member_id IN (SELECT member_id FROM %susers_online)
	ORDER BY first_name ASC";
$result = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $_SESSION[course_id], TABLE_PREFIX));
$course_participants_online = array();
foreach ($result as $row) {
	$current = array($row['jid'], $row['member_id'], $row['first_name'], $row['last_name']);
	$course_participants_online[] = $current;
}

$sql = "SELECT C.member_id, jid, first_name, last_name FROM %schat_members C INNER JOIN %scourse_enrollment E USING (member_id) INNER JOIN %smembers M
	WHERE E.course_id=%d
	AND E.approved='y'
	AND E.member_id=M.member_id
	AND E.member_id NOT IN (SELECT member_id FROM %susers_online)
	ORDER BY first_name ASC";
$result = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $_SESSION[course_id], TABLE_PREFIX));
$course_participants_offline = array();
foreach ($result as $row) {
	$current = array($row['jid'], $row['member_id'], $row['first_name'], $row['last_name']);
	$course_participants_offline[] = $current;
}


ob_start();
?>
<link rel="stylesheet" href="<?php echo $_base_path; ?>/mods/chat_new/includes/side_menu.css" type="text/css" />
	<div id="roster" aria-label="XMPP Chat contact list">
		<?php
		if (count($course_participants_online) == 0 && count($course_participants_offline) == 0) {
			?> 
			The course has no chat members yet. Join course chat <a href="<?php echo $_base_path; ?>mods/chat_new/index.php">here</a>.
			<?php
		}
		if (count($course_participants_online) != 0) {
			foreach($course_participants_online as $participant){
				if ($participant[1] == $_SESSION[member_id]){
					?>
					<div class="friends_column_wrapper online me" role="listitem" title="<?php echo $participant[2].' '.$participant[3]; ?> - Me" id="<?php echo $participant[0]; ?>"
						tabindex="0"
					    onblur="jQuery(this)[0].showFocus = false;"
					    onfocus="jQuery(this)[0].showFocus = true;">
					<?php 
				} else {
					?>
					<div class="friends_column_wrapper online" role="listitem" title="<?php echo $participant[2].' '.$participant[3]; ?> - Online" id="<?php echo $participant[0]; ?>" 
						onkeydown="return Interface.optionKeyEvent_friends_column_wrapper(event);" tabindex="0" aria-controls="roster"
					    onkeypress="return Interface.optionKeyEvent_friends_column_wrapper(event);"
					    onblur="jQuery(this)[0].showFocus = false;"
					    onfocus="jQuery(this)[0].showFocus = true;">
					<?php
				}
				?>
				    		<table class="friends_item" id="<?php echo $participant[1]; ?>"><tr>
	         					<td><img src="<?php echo $_base_path; ?>get_profile_img.php?id=<?php echo $participant[1]; ?>" class="friends_item_picture" alt="userphoto" />
	         					</td>
	                        	<td class="friends_item_name"><?php echo $participant[2].' '.$participant[3]; ?></td>
	                        	<td class="friends_item_status">Online</td>
	                    	</tr></table>
	              	</div>
				<?php
			}
		}
		if (count($course_participants_offline) != 0) {			
			foreach($course_participants_offline as $participant){
				?><div class="friends_column_wrapper offline" role="listitem" title="<?php echo $participant[2].' '.$participant[3]; ?> - Offline" id="<?php echo $participant[0]; ?>"
						onkeydown="return Interface.optionKeyEvent_friends_column_wrapper(event);" tabindex="0" aria-controls="roster"
					    onkeypress="return Interface.optionKeyEvent_friends_column_wrapper(event);"
					    onblur="jQuery(this)[0].showFocus = false;"
					    onfocus="jQuery(this)[0].showFocus = true;">
	                    	<table class="friends_item" id="<?php echo $participant[1]; ?>"><tr>
	         					<td><img src="<?php echo $_base_path; ?>get_profile_img.php?id=<?php echo $participant[1]; ?>" class="friends_item_picture" alt="userphoto" /></td>
	                        	<td class="friends_item_name"><?php echo $participant[2].' '.$participant[3]; ?></td>
	                        	<td class="friends_item_status"></td>
	                    	</tr></table>
	              </div>
			<?php 
			}
		}		
		?>
     </div>

<?php
$savant->assign('dropdown_contents', ob_get_contents());
ob_end_clean();

$savant->assign('title', _AT('chat_new')); // the box title
$savant->display('include/box.tmpl.php');
?>
