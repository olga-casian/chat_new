<?php
/*******
 * doesn't allow this file to be loaded with a browser.
 */
if (!defined('AT_INCLUDE_PATH')) { exit; }

/******
 * this file must only be included within a Module obj
 */
if (!isset($this) || (isset($this) && (strtolower(get_class($this)) != 'module'))) { exit(__FILE__ . ' is not a Module'); }

/*******
 * assign the instructor and admin privileges to the constants.
 */
define('AT_PRIV_CHAT_NEW',       $this->getPrivilege());
define('AT_ADMIN_PRIV_CHAT_NEW', $this->getAdminPrivilege());

/*******
 * create a side menu box/stack.
 */
$this->_stacks['chat_new'] = array('title_var'=>'chat_new', 'file'=>AT_INCLUDE_PATH.'../mods/chat_new/includes/side_menu.inc.php');

/*******
 * create optional sublinks for module "detail view" on course home page
 * when this line is uncommented, "mods/hello_world/sublinks.php" need to be created to return an array of content to be displayed
 */
 
// the text to display on module "detail view" when sublinks are not available
$this->_pages['mods/chat_new/index.php']['text']      = _AT('chat_new_text');

/*******
 * if this module is to be made available to students on the Home or Main Navigation.
 */
$_group_tool = $_student_tool = 'mods/chat_new/index.php';


/*******
 * student page.
 */
$this->_pages['mods/chat_new/index.php']['title_var'] = 'chat_new';
$this->_pages['mods/chat_new/index.php']['img']       = 'mods/chat_new/chat_new.jpg';



function chat_new_get_group_url($group_id) {
	return 'mods/chat_new/index.php';
}
?>
