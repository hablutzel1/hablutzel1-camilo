<?php
// Language files that should be included.
$language_file = array('courses', 'index');
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'course.lib.php';
//require_once $libpath.'usermanager.lib.php';
require_once $libpath.'sessionmanager.lib.php';
require_once $libpath.'usermanager.lib.php';
require_once $libpath.'formvalidator/FormValidator.class.php';
require_once $libpath.'text.lib.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';

api_block_anonymous_users(); // Only users who are logged in can proceed.


$this_section = SECTION_COURSES;
//Tab js
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-1.4.4.min.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery-ui/cupertino/jquery-ui-1.8.7.custom.min.js" type="text/javascript" language="javascript"></script>';
//Grid js
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/css/ui.jqgrid.css" type="text/css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/i18n/grid.locale-en.js" type="text/javascript" language="javascript"></script>'; 
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jqgrid/js/jquery.jqGrid.min.js" type="text/javascript" language="javascript"></script>';

    
Display :: display_header($nameTools);



$session_id     = intval($_GET['session_id']);
$session_info   = SessionManager::fetch($session_id);
$session_list   = SessionManager::get_sessions_by_coach(api_get_user_id());


$course_list    = SessionManager::get_course_list_by_session_id($session_id);
$course_select = array();

$session_select = array();
foreach ($session_list as $item) {
    $session_select[$item['id']] =  $item['name'];
}
/*
foreach ($course_list as $course_item) {
	$course_select[$course_item['id']] =  $course_item['title'];
}*/
// Session list form

if (count($session_select) > 1) {
    $form = new FormValidator('exercise_admin', 'get', api_get_self().'?session_id='.$session_id);
    $form->addElement('select', 'session_id', get_lang('SessionList'), $session_select, 'onchange="javascript:change_session()"');
    $defaults['session_id'] = $session_id;
    $form->setDefaults($defaults);
    $form->display();
    
    
    if ($form->validate()) {
        
    }
}

echo Display::tag('h1', $session_info['name']);


//Listing LPs from all courses
$lps = array();

foreach ($course_list as $item) {    
    $list       = new LearnpathList(api_get_user_id(),$item['code']);
    $flat_list  = $list->get_flat_list();        
    $lps[$item['code']] = $flat_list;
    foreach ($flat_list as $item) {        
        //var_dump(get_week_from_day($item['publicated_on']));	
    }    
}


//Getting all sessions where I'm subscribed 

$new_session_list = UserManager::get_personal_session_course_list(api_get_user_id());

//echo '<pre>';
$my_session_list = array();
$final_array = array();

if (!empty($new_session_list)) {
    foreach($new_session_list as $item) {
        $my_session_id = $item['id_session'];    
        if (isset($my_session_id) && !in_array($my_session_id, $my_session_list)) {
        	$final_array[$my_session_id]['name'] = $item['session_name'];
            
         
            $my_course_list = UserManager::get_courses_list_by_session(api_get_user_id(), $my_session_id );            
            $course = array();        
            foreach ($my_course_list as $my_course) {
            
                $course_info    = api_get_course_info($my_course['code']);            
                $exercise_list = get_all_exercises($course_info);
           
                
                //Exercises we skip
                /*if (empty($exercise_list)) {
                    continue;
                } */   
                $exercise_course_list = array();
                $course['name'] = $course_info['name'];
                if (!empty($exercise_list)) {        
                    foreach($exercise_list as $exercise_item) {                
                        $exercise = new Exercise($course_info['real_id']);
                        $exercise->read($exercise_item['id']);                                    
                        $exercise_course_list[$exercise_item['id']] = $exercise;
                        $user_results = get_all_exercise_results_by_user(api_get_user_id(), $exercise_item['id'], $my_course['code'], $my_session_id);
                        //print_r($user_results);
                        
                        $course['exercises'][$exercise_item['id']]['name'] =  $exercise->exercise;
                            
                        $course['exercises'][$exercise_item['id']]['data'] =  $user_results;      
                        //print_r($user_results);          
                    }
                    $final_array[$my_session_id]['data'][$my_course['code']] = $course;        
                }   
            }            
        }
        $my_session_list[] =  $my_session_id;      
    }
}

//print_r($final_array); exit;
require_once api_get_path(LIBRARY_PATH).'pear/HTML/Table.php';
$html = '';
foreach($final_array as $session_data) {    
	$html .=Display::tag('h1',$session_data['name']);
    $course_list = $session_data['data'];         
    foreach ($course_list as $course_data) {        
        $table = new HTML_Table(array('class' => 'data_table'));
        $row = 0;
        $column = 0;
        $header_names = array(get_lang('Course'),get_lang('Exercise'),get_lang('Attempt'),get_lang('Results'),get_lang('Score'), get_lang('Ranking'));
        foreach ($header_names as $item) {
            $table->setHeaderContents($row, $column, $item);
            $column++;
        }        
        $row = 1;
        $column = 0;
        $table->setCellContents($row, $column, $course_data['name']);
        $column++;           
        if (!empty($course_data['exercises'])) {            
            foreach ($course_data['exercises'] as $exercise_data) {                                                  
                foreach ($exercise_data['data'] as $exercise_result) {                    
                    $my_exercise_result = array($exercise_data['name'], $exercise_result['exe_id']);
                    $column = 1;
                    foreach ($my_exercise_result as $data) {                                                
                        $table->setCellContents($row, $column, $data);                        
                        //$table->updateCellAttributes($row, $column, 'align="center"');
                        $column++;                        
                    }
                    $row++;
                }               
            }
        }
        $html .=$table->toHtml();
    }
}     



//print_r($my_session_list);

//Exercise list
/*
$exercise_grid_url            = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id;
$exercise_grid_columns        =array(get_lang('Session'), get_lang(''))
$exercise_grid_column_model
$exercise_grid_settings       =
*/

//Default grid settings
$url            = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_default&session_id='.$session_id;
$columns        = array('Date','Course', 'LP');
$column_model   = array(array('name'=>'date',   'index'=>'date',   'width'=>'120', 'align'=>'right'),
                        array('name'=>'course', 'index'=>'course', 'width'=>'120', 'align'=>'right'),
                        array('name'=>'lp',     'index'=>'lp',     'width'=>'120', 'align'=>'right'));
                        
//Course grid settings
$url_course             = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_course&session_id='.$session_id;
$extra_params_course['grouping'] = 'true';
$extra_params_course['groupingView'] = array('groupField'=>array('course'),
                                            'groupColumnShow'=>array('false'),
                                            'groupText' => array('<b>Course {0} - {1} Item(s)</b>'));
                              
//Week grid
$url_week             = api_get_path(WEB_AJAX_PATH).'course_home.ajax.php?a=session_courses_lp_by_week&session_id='.$session_id;
$column_week = array('Week','Date','Course', 'LP');
$column_week_model =array(array('name'=>'week',     'index'=>'week',    'width'=>'120', 'align'=>'right'),       
                          array('name'=>'date',     'index'=>'date',    'width'=>'120', 'align'=>'right'),
                          array('name'=>'course',   'index'=>'course',  'width'=>'120', 'align'=>'right'),
                          array('name'=>'lp',       'index'=>'lp',      'width'=>'120', 'align'=>'right'));
$extra_params_week['grouping'] = 'true';
$extra_params_week['groupingView'] = array('groupField'=>array('week'),
                                            'groupColumnShow'=>'false',
                                            'groupText' => array('<b>Week {0} - {1} Item(s)</b>'));
?>
<br />
<script>
    function change_session() {
            document.exercise_admin.submit();
    }
        
    
$(function() {
    $( "#tabs" ).tabs();
    $( "#sub_tab" ).tabs();        
<?php 
     echo Display::grid_js('list_default',  $url,       $columns,$column_model);
     echo Display::grid_js('list_course',   $url_course,$columns,$column_model,$extra_params_course);
     echo Display::grid_js('list_week',     $url_week,  $column_week,$column_week_model, $extra_params_week);    
?>  
});
</script>

<?php 

$headers = array(get_lang('LearningPaths'), get_lang('MyQCM'), get_lang('MyResults'));
$sub_header = array(get_lang('AllLearningPaths'), get_lang('PerWeek'), get_lang('ByCourse'));
$tabs =  Display::tabs($sub_header, array(Display::grid_html('list_default'), Display::grid_html('list_week'), Display::grid_html('list_course')),'sub_tab');
echo Display::tabs($headers, array($tabs, $html,'ccc'));

// Footer
Display :: display_footer();