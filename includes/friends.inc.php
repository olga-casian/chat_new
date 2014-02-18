<?php
$sql = "SELECT C.member_id, jid, first_name, last_name FROM %schat_members C INNER JOIN %scourse_enrollment E USING (member_id) INNER JOIN %smembers M
	WHERE E.course_id=%d
	AND E.approved='y'
	AND E.member_id=M.member_id
	ORDER BY first_name ASC";
$result = queryDB($sql, array(TABLE_PREFIX, TABLE_PREFIX, TABLE_PREFIX, $_SESSION[course_id]));
$course_participants = array();
foreach ($result as $row) {
	$current = array($row['jid'], $row['member_id'], $row['first_name'], $row['last_name']);
	$course_participants[] = $current;
}
?>

<div id="friends">
        	<table><tr>
        		<td class="friends_column fl-container-flex35">
                    <h2>Classmates</h2>
                    <div id="friends_list" aria-live="polite" aria-atomic="false">
<?php
		if (count($course_participants) == 0) {
			?> 
			The course has no chat members yet.
			<?php
		} else {
			foreach($course_participants as $participant){
				if ($participant[1] == $_SESSION[member_id]){
					$me = "<div class='friends_column_wrapper_classmates_me' id='classmates_".$participant[0]."'>
					    	<table class='friends_item'><tr>
	         					<td><img class='friends_item_picture' src=".$_base_path."get_profile_img.php?id=".$participant[1]." alt='userphoto'/></td>
	                        	<td class='friends_item_name'>".$participant[2]." ".$participant[3]."</td>
                    		</tr></table>
	              		</div>";
				} else {
					?>
					<div class="friends_column_wrapper_classmates" role="listitem" title="<?php echo $participant[2].' '.$participant[3]; ?> - not member" id="classmates_<?php echo $participant[0]; ?>"
						onkeydown="return Interface.optionKeyEvent_friends_column_wrapper(event);" tabindex="0" aria-controls="roster"
					    onkeypress="return Interface.optionKeyEvent_friends_column_wrapper(event);"
					    onblur="jQuery(this)[0].showFocus = false;"
					    onfocus="jQuery(this)[0].showFocus = true;">
										
					    	<table class="friends_item"><tr>
	         					<td><img class="friends_item_picture" src="<?php echo $_base_path; ?>get_profile_img.php?id=<?php echo $participant[1]; ?>" alt="userphoto"/></td>
	                        	<td class="friends_item_name"><?php echo $participant[2].' '.$participant[3]; ?></td>
                    		</tr></table>
	              	</div>
					<?php
				}
			}
		}

?>        
					</div>
                    </div>
                </td>
                
                <td class="friends_spacer"></td>
                
                <td class="friends_column fl-container-flex35">
                    <h2>Chat room members</h2>
                    <div id="friends_members">
	                    <?php 
	                    echo $me;
	                    ?>
                    </div>
                    
                    <div id="friends_selected">
	                    Members selected: <span id="nr_of_members">1</span><br/>
	                    <div id="friends_selected_label">Please specify group name:</div>
	                    <input id="groupname"  maxlength="100" type="text" onkeypress="Interface.refresh_form();"/><br/>
	                    <input id="friends_selected_bnt" type="button" label="submit" value="Create chat"/>
                    </div>
                </td>
                
        	</tr></table>
        </div>
     