<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');

$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'promotion.lib.php';
require_once api_get_path(LIBRARY_PATH).'career.lib.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_jqgrid_js();

// setting breadcrumbs
$interbreadcrumb[] = array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => 'career_dashboard.php','name' => get_lang('CareersAndPromotions'));

$action = isset($_GET['action']) ? $_GET['action'] : null;

$check = Security::check_token('request');
$token = Security::get_token();    

if ($action == 'add') {
    $interbreadcrumb[]=array('url' => 'promotions.php','name' => get_lang('Promotions'));
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Add'));
} elseif ($action == 'edit') {
    $interbreadcrumb[]=array('url' => 'promotions.php','name' => get_lang('Promotions'));    
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Edit'));
} else {
    $interbreadcrumb[]=array('url' => '#','name' => get_lang('Promotions'));
}

// The header.
Display::display_header($tool_name);

// Tool name
if (isset($_GET['action']) && $_GET['action'] == 'add') {
    $tool = 'Add';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Promotion'));    
}
if (isset($_GET['action']) && $_GET['action'] == 'edit') {
    $tool = 'Modify';
    $interbreadcrumb[] = array ('url' => api_get_self(), 'name' => get_lang('Promotion'));
}

$url            = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_promotions';
//The order is important you need to check the model.ajax.php the $column variable
$columns        = array(get_lang('Name'),get_lang('Career'),get_lang('Description'),get_lang('Actions'));
$column_model   = array(
                        array('name'=>'name',           'index'=>'name',        'width'=>'80',   'align'=>'left'),
                        array('name'=>'career',         'index'=>'career',      'width'=>'100',  'align'=>'left'),
                        array('name'=>'description',    'index'=>'description', 'width'=>'500',  'align'=>'left','sortable'=>'false'),
                        array('name'=>'actions',        'index'=>'actions',     'width'=>'100',  'align'=>'left','formatter'=>'action_formatter','sortable'=>'false'),
                       );                        
$extra_params['autowidth'] = 'true'; //use the width of the parent
//$extra_params['editurl'] = $url; //use the width of the parent

$extra_params['height'] = 'auto'; //use the width of the parent
//With this function we can add actions to the jgrid
$action_links = 'function action_formatter (cellvalue, options, rowObject) {
                    return \'<a href="add_sessions_to_promotion.php?id=\'+options.rowId+\'">'.Display::return_icon('session_to_promotion.png',get_lang('SubscribeSessionsToPromotions'),'',ICON_SIZE_SMALL).'</a>'.
                    '&nbsp;<a href="?action=edit&id=\'+options.rowId+\'">'.Display::return_icon('edit.png',get_lang('Edit'),'',ICON_SIZE_SMALL).'</a>'.
					'&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=copy&id=\'+options.rowId+\'">'.Display::return_icon('copy.png',get_lang('Copy'),'',ICON_SIZE_SMALL).'</a>'.
                    '&nbsp;<a onclick="javascript:if(!confirm('."\'".addslashes(api_htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES))."\'".')) return false;"  href="?sec_token='.$token.'&action=delete&id=\'+options.rowId+\'">'.Display::return_icon('delete.png',get_lang('Delete'),'',ICON_SIZE_SMALL).'</a> \'; 
                 }';

?>
<script>
$(function() { 
    <?php 
         echo Display::grid_js('promotions',  $url,$columns,$column_model,$extra_params,array(), $action_links, true);       
    ?>
});
</script>   
<?php
$promotion = new Promotion();

switch ($action) {
    case 'add':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }

        //First you need to create a Career
        $career = new Career();
        $careers = $career->get_all();
        if (empty($careers)) {
            $url = Display::url(get_lang('YouNeedToCreateACareerFirst'), 'careers.php?action=add');
            Display::display_normal_message($url, false);
            Display::display_footer();
            exit;
        }

        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']);
        $form = $promotion->return_form($url, 'add');    

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();       
                $res    = $promotion->save($values);            
                if ($res) {
                    Display::display_confirmation_message(get_lang('ItemAdded'));
                }
            }            
            $promotion->display();
        } else {
            echo '<div class="actions">';        
            echo Display::url(Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM), api_get_self());
            echo '</div>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'edit':
        //Editing 
        $url  = api_get_self().'?action='.Security::remove_XSS($_GET['action']).'&id='.intval($_GET['id']);    
        $form = $promotion->return_form($url, 'edit');

        // The validation or display
        if ($form->validate()) {            
            if ($check) {
                $values = $form->exportValues();                    
                $res    = $promotion->update($values);
                $promotion->update_all_sessions_status_by_promotion_id($values['id'], $values['status']);    
                if ($values['status']) {
                    Display::display_confirmation_message(sprintf(get_lang('PromotionXUnarchived'), $values['name']), false);
                } else {
                    Display::display_confirmation_message(sprintf(get_lang('PromotionXArchived'), $values['name']), false);
                }
            }            
            $promotion->display();
        } else {
            echo '<div class="actions">';        
            echo Display::url(Display::return_icon('back.png',get_lang('Back'),'',ICON_SIZE_MEDIUM), api_get_self());
            echo '</div>';            
            $form->addElement('hidden', 'sec_token');
            $form->setConstants(array('sec_token' => $token));
            $form->display();
        }
        break;
    case 'delete':        
        if ($check) {
            // Action handling: deleting an obj
            $res = $promotion->delete($_GET['id']);
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemDeleted'));
            }
        }
        $promotion->display();        
        break;
    case 'copy':
        if (api_get_session_id() != 0 && !api_is_allowed_to_session_edit(false, true)) {
            api_not_allowed();
        }
        if ($check) {
            $res = $promotion->copy($_GET['id'], null, true);    
            if ($res) {
                Display::display_confirmation_message(get_lang('ItemCopied').' - '.get_lang('ExerciseAndLPsAreInvisibleInTheNewCourse'));
            }            
        }
        $promotion->display();
        break;
    default:
        $promotion->display();
        break;
}
Display::display_footer();