<?php
define('AT_INCLUDE_PATH', '../../include/');
require (AT_INCLUDE_PATH.'vitals.inc.php');

$_custom_head .= '<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
		<script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/jquery-cookie/jquery.cookie.js"></script>
		
				
		<script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/xmpp_client.js"></script>
		<script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/xmpp_console.js"></script>
		<script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/interface.js"></script>
				
		<script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/strophe&flXHR/strophe_sha1.js"></script>
		<script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/strophe&flXHR/sha1.js"></script>	
	    <script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/strophe&flXHR/strophe.muc.js"></script>
	    <script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/strophe&flXHR/flXHR.js"></script>
	    <script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/strophe&flXHR/strophe.flxhr.js"></script>	   
	    		
	    <script type="text/javascript" src="'.$_base_path.'mods/chat_new/js/libraries/moment.min.js"></script>';

$_custom_css = $_base_path.'mods/chat_new/module.css'; // use a custom stylesheet
require (AT_INCLUDE_PATH.'header.inc.php');


?>

	<div id="welcome" class="fl-container-flex90">
		Welcome to the new version of chat!<br/><br/>
		
		In order to login you need a free account on <a href="https://www.talkr.im/">talkr.im</a> server that is used by the chat. 
		By logging in you agree with the fact that <a href="https://www.talkr.im/">talkr.im</a> hosts the messages.<br/><br/>
		
		It is highly recommended that you use the registered account only within the ATutor chat client to avoid loss of data or other undesirable consequences.<br/>
		
		Please see <a href="<?php echo $_base_path; ?>mods/chat_new/ATutor_XMPP_Chat_READ_ME.pdf" target="_blank">the helping document</a> for more details.
		
		<table id="welcome_form">
			<tr>
				<td><label>Nickname:</label></td>
		        <td><input class="welcome_form_input" id="welcome_form_jid" maxlength="100" type="text" name="jid"/> @ talkr.im</td>
	        </tr>
			<tr>
				<td><label>Password:</label></td>
		        <td><input class="welcome_form_input" id="welcome_form_pass" maxlength="100" type="password" name="pass"/></td>
	        </tr>
	        <tr>
				<td><input type="hidden" name="member_id" id="welcome_form_member_id" value="<?php echo $_SESSION['member_id']; ?>"/></td>
		        <td><input id="welcome_form_login" type="button" value="Log In" onclick="Interface.connect(null, null);"/></td>
	        </tr>        
		</table>
		<div id='log'>
   		</div>
	</div><!--end welcome-->
	
	
	<div id="chat">
		<div id="<?php echo $_SESSION[course_id]; ?>"></div>
		<div id="<?php echo $_SESSION[member_id]; ?>"></div>
		<div class="fl-container-flex90 fl-left democ-linearize-sections ui-tabs ui-widget ui-widget-content ui-corner-all" id="tabs">
		    <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all" role="tablist">
		        <li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active" role="presentation"><a href="#tab_inbox">Inbox list</a></li>
		        <li class="ui-state-default ui-corner-top"><a href="#tab_conversations">Conversations</a></li>
		        <li class="ui-state-default ui-corner-top" role="presentation"><a href="#tab_friends">Group chat</a></li>
		        <li class="ui-state-default ui-corner-top" role="presentation"><a href="#tab_settings">Help</a></li>
		    </ul>		  
		    <div id="tab_inbox">
		    <?php require ('includes/inbox_list.inc.php'); ?>
		    </div>
		
			<div id="tab_conversations">
			<?php require ('includes/conversations.inc.php'); ?>
			</div>
		
			<div id="tab_friends">
			<?php require ('includes/friends.inc.php'); ?>
			</div>
		
			<div id="tab_settings">
			<?php require ('includes/settings.inc.php'); ?>
			</div>		
		</div>  
	</div><!--end chat-->
	
	<ul id="xmpp-logs" aria-live="polite" aria-atomic="false" style="list-style: none; list-style: none; font-size: 0.1em; color: white;">
	</ul>


	<div id="dialog_message" title="Connecting...">
		<p>
			<span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
			The XMPP Chat is currently connecting to the server.
			This can take several seconds, please wait.
		</p>
	</div>
	

	<!--Peek XMPP console (comment to hide) -->
	<!--<h4>Peek XMPP console</h4>
	<div id="peek">
		<div id='console'></div>
		<textarea id='console_input' class='disabled' disabled='disabled'></textarea>
		
		<div id='buttonbar'>
			<input id='send_button' type='button' value='Send Data' disabled='disabled' class='button' onclick="console_send();">
			<input id='disconnect_button' type='button' value='Disconnect' disabled='disabled' class='button' onclick="console_disconnect();">
		</div>
	</div>-->
	<!--end Peek XMPP console-->
	
	
	
	
	<script>
	    jQuery("#tabs, #subtabs").tabs();
	    
	    jQuery('#subtabs').tabs({
			select: function(event, ui){
		        var jid_id = ui.tab.hash;
		        Interface.on_select_subtab(jid_id.slice(6, jid_id.length));
			}
		});
		
		jQuery('#tabs').tabs({
			select: function(event, ui){
				if (ui.tab.hash == "#tab_conversations") {
					Interface.on_select_conversation_tab();
				}
			}
		});
		
	    
	    Interface.refresh_form();
	    Interface.hide_div();
	    
	    Interface.load_inbox();
	    
//	     window.onbeforeunload = function(event)
//    {
//       alert('asasa');
//    };

//window.onbeforeunload = function (e) {
//  e = e || window.event;
//
//  if (e) {
//    alert('asasas');
//  }
//
//  return 'Any string';
//};

	    
	</script>

<?php require (AT_INCLUDE_PATH.'footer.inc.php'); ?>
