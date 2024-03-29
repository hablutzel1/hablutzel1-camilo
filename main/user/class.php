<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.user
*/
/**
 * INIT SECTION
 */
// name of the language file that needs to be included
$language_file = array('registration','admin');
require_once '../inc/global.inc.php';
$this_section = SECTION_COURSES;
/**
 * MAIN CODE	
 */
api_protect_course_script();

if (api_get_setting('use_session_mode')=='true') {
	api_not_allowed();
}

$tool_name = get_lang("Classes");
//extra entries in breadcrumb
$interbreadcrumb[] = array ("url" => "user.php", "name" => get_lang("ToolUser"));
Display :: display_header($tool_name, "User");

api_display_tool_title($tool_name);
if (api_is_allowed_to_edit()) {
	echo '<a class="btn" href="subscribe_class.php?'.api_get_cidreq().'">'.get_lang("AddClassesToACourse").'</a><br />';
}

/*		MAIN SECTION*/

if(api_is_allowed_to_edit()) {
	if (isset ($_GET['unsubscribe'])) {
		ClassManager::unsubscribe_from_course($_GET['class_id'],$_course['sysCode']);
		Display::display_normal_message(get_lang('ClassesUnSubscribed'));
	}
	if (isset ($_POST['action'])) {
		switch ($_POST['action']) {
			case 'unsubscribe' :
				if (is_array($_POST['class']))
				{
					foreach ($_POST['class'] as $index => $class_id)
					{
						ClassManager::unsubscribe_from_course($class_id,$_course['sysCode']);
					}
					Display::display_normal_message(get_lang('ClassesUnSubscribed'));
				}
				break;
		}
	}
}
/*
		SHOW LIST OF CLASSES
*/

/**
 *  * Get the number of classes to display on the current page.
 */
function get_number_of_classes()
{
	$class_table = Database :: get_main_table(TABLE_MAIN_CLASS);
	$course_class_table = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
	$sql = "SELECT c.id	FROM $class_table c, $course_class_table cc WHERE cc.class_id = c.id AND cc.course_code ='".$_SESSION['_course']['id']."'";
	if (isset ($_GET['keyword']))
	{
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " AND (c.name LIKE '%".$keyword."%')";
	}
	$res = Database::query($sql);
	$result = Database::num_rows($res);
	return $result;
}
/**
 * Get the classes to display on the current page.
 */
function get_class_data($from, $number_of_items, $column, $direction)
{
	$class_table = Database :: get_main_table(TABLE_MAIN_CLASS);
	$course_class_table = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
	$class_user_table = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
	$sql = "SELECT c.id AS col0, c.name   AS col1, COUNT(cu.user_id) AS col2";
	if (api_is_allowed_to_edit()) {
		$sql .=	" ,c.id AS col3";
	}
	// '(' and ')' in next query is necessary (see http://bugs.mysql.com/bug.php?id=19053)
	$sql .= " FROM ($class_table c, $course_class_table cc)";
	$sql .= " LEFT JOIN $class_user_table cu ON cu.class_id = c.id";
	$sql .= " WHERE c.id = cc.class_id AND cc.course_code = '".$_SESSION['_course']['id']."'";
	if (isset ($_GET['keyword']))
	{
		$keyword = Database::escape_string(trim($_GET['keyword']));
		$sql .= " AND (c.name LIKE '%".$keyword."%')";
	}
	$sql .= " GROUP BY c.id, c.name ";
	$sql .= " ORDER BY col$column $direction ";
	$sql .= " LIMIT $from,$number_of_items";
	$res = Database::query($sql);
	$classes = array ();
	while ($class = Database::fetch_row($res))
	{
		$classes[] = $class;
	}
	return $classes;
}
/**
 * Build the reg-column of the table
 * @param int $class_id The class id
 * @return string Some HTML-code
 */
function reg_filter($class_id) {
	global $charset;
	$result = '<a href="'.api_get_self().'?'.api_get_cidreq().'&unsubscribe=yes&amp;class_id='.$class_id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;"><img src="../img/delete.gif"/></a>';
	return $result;
}
// Build search-form
$form = new FormValidator('search_class', 'get','','', array('class' => 'well form-inline'),false);
$form->add_textfield('keyword', '', false);
$form->addElement('button', 'submit', get_lang('SearchButton'));

// Build table
$table = new SortableTable('user_class', 'get_number_of_classes', 'get_class_data', 1);
$parameters['keyword'] = $_GET['keyword'];
$table->set_additional_parameters($parameters);
$col = 0;
$table->set_header($col ++, '', false);
$table->set_header($col ++, get_lang('ClassName'));
$table->set_header($col ++, get_lang('NumberOfUsers'));
if (api_is_allowed_to_edit()) {
	$table->set_header($col ++, '', false);
	$table->set_column_filter($col -1, 'reg_filter');
	$table->set_form_actions(array ('unsubscribe' => get_lang('Unreg')), 'class');
}

// Display form & table
echo '<br />';
$form->display();
echo '<br />';
$table->display();

Display :: display_footer();
