<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'career.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Jquery ui
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.4.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.min.js" type="text/javascript" language="javascript"></script>';

//Grid js
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/css/ui.jqgrid.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/i18n/grid.locale-en.js" type="text/javascript" language="javascript"></script>'; 
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript" language="javascript"></script>';

// The header.
Display::display_header($tool_name);


// Tool name
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $tool = 'Add';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Career'));
}
if (isset($_GET['action']) && $_GET['action'] == 'editnote') {
    $tool = 'Modify';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Career'));
}

//jqgrid will use this URL to do the selects

$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_careers';

//The order is important you need to check the the $column variable in the model.ajax.php file 
$columns        = array(get_lang('Name'),get_lang('Description'),get_lang('Actions'));

//Column config
$column_model   = array(array('name'=>'name',           'index'=>'name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'description',    'index'=>'description', 'width'=>'500',  'align'=>'left'),
                        array('name'=>'actions',        'index'=>'actions',     'formatter'=>'action_formatter','width'=>'100',  'align'=>'left'),
                       );            
//Autowidth             
$extra_params['autowidth'] = 'true';
//height auto 
$extra_params['height'] = 'auto'; 

//With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
                         return \'<a href="?action=edit&id=\'+options.rowId+\'"><img src="../img/edit.gif" title="'.get_lang('Edit').'"></a> <a href="?action=delete&id=\'+options.rowId+\'"><img title="'.get_lang('Delete').'" src="../img/delete.gif"></a>\'; 
                 }';
?>
<script>
$(function() {    
<?php 
    // grid definition see the $career->display() function
    echo Display::grid_js('careers',  $url,$columns,$column_model,$extra_params, array(), $action_links);       
?> 
});
</script>   
<?php
// Tool introduction
Display::display_introduction_section(get_lang('Careers'));

$career = new Career();

// Action handling: Adding a note
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
        api_not_allowed();
    }

    $_SESSION['notebook_view'] = 'creation_date';
    //@todo move this in the career.lib.php
    
    // Initiate the object
    $form = new FormValidator('note', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']));
    // Settting the form elements
    $form->addElement('header', '', get_lang('Add'));
    $form->addElement('text', 'name', get_lang('name'), array('size' => '95', 'id' => 'name'));
    //$form->applyFilter('note_title', 'html_filter');
    $form->addElement('html_editor', 'description', get_lang('Description'), null);
    $form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="add"');

    // Setting the rules
    $form->addRule('name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();       
            $res = $career->save($values);            
            if ($res) {
                Display::display_confirmation_message(get_lang('Added'));
            }
        }
        Security::clear_token();
        $career->display();
    } else {
        echo '<div class="actions">';
        echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png').' '.get_lang('Back').'</a>';
        echo '</div>';
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}// Action handling: Editing a note
elseif (isset($_GET['action']) && $_GET['action'] == 'edit' && is_numeric($_GET['id'])) {
    // Initialize the object
    $form = new FormValidator('career', 'post', api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.Security::remove_XSS($_GET['id']));
    // Settting the form elements
    $form->addElement('header', '', get_lang('Modify'));
    $form->addElement('hidden', 'id',intval($_GET['id']));
    $form->addElement('text', 'name', get_lang('Name'), array('size' => '100'));
    $form->addElement('html_editor', 'description', get_lang('description'), null);
    $form->addElement('style_submit_button', 'submit', get_lang('Modify'), 'class="save"');

    // Setting the defaults
    $defaults = $career->get($_GET['id']);
    $form->setDefaults($defaults);

    // Setting the rules
    $form->addRule('name', '<div class="required">'.get_lang('ThisFieldIsRequired'), 'required');

    // The validation or display
    if ($form->validate()) {
        $check = Security::check_token('post');
        if ($check) {
            $values = $form->exportValues();            
            $res = $career->update($values);
            if ($res) {
                Display::display_confirmation_message(get_lang('Updated'));
            }
        }
        Security::clear_token();
        $career->display();
    } else {
        echo '<div class="actions">';
        echo '<a href="'.api_get_self().'">'.Display::return_icon('back.png').' '.get_lang('Back').'</a>';
        echo '</div>';
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}
// Action handling: deleting a note
elseif (isset($_GET['action']) && $_GET['action'] == 'delete' && is_numeric($_GET['id'])) {
    $res = $career->delete(Security::remove_XSS($_GET['id']));
    if ($res) {
        Display::display_confirmation_message(get_lang('Deleted'));
    }
    $career->display();
} else {
    $career->display();   
}

Display :: display_footer();