<?php
/* For licensing terms, see /license.txt */

//@todo this could be integrated in the inc/lib/model.lib.php + try to clean this file

$language_file = array('admin','exercice');

require_once '../global.inc.php';

$libpath = api_get_path(LIBRARY_PATH);

// 1. Setting variables needed by jqgrid

$action = $_GET['a'];
$page   = intval($_REQUEST['page']); //page
$limit  = intval($_REQUEST['rows']); //quantity of rows
$sidx   = $_REQUEST['sidx'];         //index (field) to filter         
$sord   = $_REQUEST['sord'];         //asc or desc

if (!in_array($sord, array('asc','desc'))) {
    $sord = 'desc'; 
}

if (!in_array($action, array('get_exercise_results', 'get_work_user_list', 'get_timelines'))) {
	api_protect_admin_script(true);
}

//Search features

$ops = array (
    'eq' => '=',        //equal
    'ne' => '<>',       //not equal
    'lt' => '<',        //less than
    'le' => '<=',       //less than or equal
    'gt' => '>',        //greater than
    'ge' => '>=',       //greater than or equal
    'bw' => 'LIKE',     //begins with
    'bn' => 'NOT LIKE', //doesn't begin with
    'in' => 'LIKE',     //is in
    'ni' => 'NOT LIKE', //is not in
    'ew' => 'LIKE',     //ends with
    'en' => 'NOT LIKE', //doesn't end with
    'cn' => 'LIKE',     //contains
    'nc' => 'NOT LIKE'  //doesn't contain
);

//@todo move this in the display_class or somewhere else

function get_where_clause($col, $oper, $val) {
    global $ops;
    if (empty($col)){
        return '';
    } 
    if($oper == 'bw' || $oper == 'bn') $val .= '%';
    if($oper == 'ew' || $oper == 'en' ) $val = '%'.$val;
    if($oper == 'cn' || $oper == 'nc' || $oper == 'in' || $oper == 'ni') $val = '%'.$val.'%';
    $val = Database::escape_string($val);
    return " $col {$ops[$oper]} '$val' ";
}

$where_condition = ""; //if there is no search request sent by jqgrid, $where should be empty

$search_field    = isset($_REQUEST['searchField'])  ? $_REQUEST['searchField']  : false;
$search_oper     = isset($_REQUEST['searchOper'])   ? $_REQUEST['searchOper']   : false;
$search_string   = isset($_REQUEST['searchString']) ? $_REQUEST['searchString'] : false;

if ($_REQUEST['_search'] == 'true') {
    $where_condition = ' 1 = 1 ';
    $where_condition_in_form = get_where_clause($search_field, $search_oper, $search_string);
        
    if (!empty($where_condition_in_form)) {
        $where_condition .= ' AND '.$where_condition_in_form;
    }
    
    $filters   = isset($_REQUEST['filters']) ? json_decode($_REQUEST['filters']) : false;
    if (!empty($filters)) {
        $where_condition .= ' AND ( ';
        $counter = 0;
        foreach ($filters->rules as $key=>$rule) {
            $where_condition .= get_where_clause($rule->field,$rule->op, $rule->data);
            
            if ($counter < count($filters->rules) -1) {     
                $where_condition .= $filters->groupOp;
            }
            $counter++;
        }
        $where_condition .= ' ) ';
    }        
}
//var_dump($where_condition);

// get index row - i.e. user click to sort $sord = $_GET['sord']; 
// get the direction 
if (!$sidx) $sidx = 1;
 
//2. Selecting the count FIRST
//@todo rework this

switch ($action) {
    case 'get_work_user_list':
        require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';
        $work_id = $_REQUEST['work_id'];
        $count = get_count_work($work_id);        
        break;
	case 'get_exercise_results':
		require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.lib.php';
		require_once $libpath.'groupmanager.lib.php';
        $exercise_id = $_REQUEST['exerciseId'];
		$count = get_count_exam_results($exercise_id);
		break;
    case 'get_sessions':
        require_once $libpath.'sessionmanager.lib.php';        
        $count = SessionManager::get_count_admin();
        break;
    case 'get_timelines':
        require_once $libpath.'timeline.lib.php';
        $obj        = new Timeline();
        $count      = $obj->get_count();
        break;
    case 'get_gradebooks':
        require_once $libpath.'gradebook.lib.php';
        $obj        = new Gradebook();
        $count      = $obj->get_count();
        break;
    case 'get_careers':        
        require_once $libpath.'career.lib.php';
        $obj        = new Career();
        $count      = $obj->get_count();
        break;
    case 'get_promotions':
       require_once $libpath.'promotion.lib.php';        
        $obj        = new Promotion();        
        $count      = $obj->get_count();   
        break;
    case 'get_grade_models':        
        require_once $libpath.'grade_model.lib.php';
        $obj        = new GradeModel();
        $count      = $obj->get_count();
        break;
    case 'get_usergroups':
        require_once $libpath.'usergroup.lib.php';        
        $obj        = new UserGroup();        
        $count      = $obj->get_count();   
        break;
    default:
        exit;   
}

//3. Calculating first, end, etc       
$total_pages = 0;
if ($count > 0) { 
    if (!empty($limit)) {
        $total_pages = ceil($count/$limit);
    }
}
if ($page > $total_pages) { 
    $page = $total_pages;
}     

$start = $limit * $page - $limit;
if ($start < 0 ) {
	$start = 0;
} 

//4. Deleting an element if the user wants to
if ($_REQUEST['oper'] == 'del') {
    $obj->delete($_REQUEST['id']);
}

$is_allowedToEdit           = api_is_allowed_to_edit(null,true);
$is_tutor                   = api_is_allowed_to_edit(true);

//5. Querying the DB for the elements
$columns = array();

switch ($action) {    
    case 'get_work_user_list':        
        if (isset($_GET['type'])  && $_GET['type'] == 'simple') {
            $columns = array('type', 'firstname', 'lastname',  'username', 'qualification', 'sent_date', 'qualificator_id', 'actions');	
        } else {
            $columns = array('type', 'firstname', 'lastname',  'username', 'sent_date', 'actions');	
        }
        $result = get_work_user_list($start, $limit, $sidx, $sord, $work_id, $where_condition);        
        break;
	case 'get_exercise_results':
		$course                     = api_get_course_info();        
        //used inside get_exam_results_data()
		$documentPath				= api_get_path(SYS_COURSE_PATH) . $course['path'] . "/document"; 		
		if ($is_allowedToEdit || $is_tutor) {
			$columns = array('firstname', 'lastname', 'username', 'group_name', 'exe_duration', 'start_date', 'exe_date', 'score','status','actions');
		} else {
			$columns = array('exe_duration', 'start_date', 'exe_date', 'score', 'status', 'actions');
		}        
		$result = get_exam_results_data($start, $limit, $sidx, $sord, $exercise_id, $where_condition);
		
		break;
    case 'get_sessions':
        $columns = array('name', 'nbr_courses', 'nbr_users', 'category_name', 'date_start','date_end', 'coach_name', 'session_active', 'visibility');            
        $result = SessionManager::get_sessions_admin(array('where'=> $where_condition, 'order'=>"$sidx $sord", 'limit'=> "$start , $limit"));        
        break;    
     case 'get_timelines': 
        $columns = array('headline', 'actions');   
        //$columns = array('headline', 'type', 'start_date', 'end_date', 'text', 'media', 'media_credit', 'media_caption', 'title_slide', 'parent_id');
   
        if(!in_array($sidx, $columns)) {
        	$sidx = 'headline';
        }
        $course_id = api_get_course_int_id();
        $result     = Database::select('*', $obj->table, array('where' => array('parent_id = ? AND c_id = ?' => array('0', $course_id)), 'order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';                
            }
            $item['headline'] = Display::url($item['headline'], api_get_path(WEB_CODE_PATH).'timeline/view.php?id='.$item['id']);
            $item['actions'] = Display::url(Display::return_icon('add.png', get_lang('AddItems')), api_get_path(WEB_CODE_PATH).'timeline/?action=add_item&parent_id='.$item['id']);
            $item['actions'] .= Display::url(Display::return_icon('edit.png', get_lang('Edit')), api_get_path(WEB_CODE_PATH).'timeline/?action=edit&id='.$item['id']);
            $item['actions'] .= Display::url(Display::return_icon('delete.png', get_lang('Delete')), api_get_path(WEB_CODE_PATH).'timeline/?action=delete&id='.$item['id']);
            
            $new_result[] = $item;
        } 
        $result = $new_result;        
        break;
    case 'get_gradebooks': 
        $columns = array('name', 'certificates','skills', 'actions', 'has_certificates');                
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result     = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach($result as $item) {
            if ($item['parent_id'] != 0) {
                continue;
            }
            $skills = $obj->get_skills_by_gradebook($item['id']);
            
            //Fixes bug when gradebook doesn't have names
            if (empty($item['name'])) {
                $item['name'] = $item['course_code'];                 
            } else {
                //$item['name'] =  $item['name'].' ['.$item['course_code'].']';
            }
                 
            $item['name'] = Display::url($item['name'], api_get_path(WEB_CODE_PATH).'gradebook/index.php?id_session=0&cidReq='.$item['course_code']);
                         
            if (!empty($item['certif_min_score']) && !empty($item['document_id'])) {
                $item['certificates'] = Display::return_icon('accept.png', get_lang('WithCertificate'), array(), ICON_SIZE_SMALL);
                 $item['has_certificates'] = '1'; 
            } else {
                $item['certificates'] = Display::return_icon('warning.png', get_lang('NoCertificate'), array(), ICON_SIZE_SMALL);
                $item['has_certificates'] = '0';
            }            
            if (!empty($skills)) {
                foreach($skills as $skill) {
                    $item['skills'] .= Display::span($skill['name'], array('class' => 'label_tag skill'));  
                }
            }
            $new_result[] = $item;
        } 
        $result = $new_result;
        break;
    case 'get_careers': 
        $columns = array('name', 'description', 'actions');                
        if(!in_array($sidx, $columns)) {
        	$sidx = 'name';
        }
        $result     = Database::select('*', $obj->table, array('order'=>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach ($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        } 
        $result = $new_result;        
        break;
    case 'get_promotions':        
        $columns = array('name', 'career', 'description', 'actions');
        if(!in_array($sidx, $columns)) {
            $sidx = 'name';
        }                  
        $result     = Database::select('p.id,p.name, p.description, c.name as career, p.status', "$obj->table p LEFT JOIN ".Database::get_main_table(TABLE_CAREER)." c  ON c.id = p.career_id ", array('order' =>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach($result as $item) {
            if (!$item['status']) {
                $item['name'] = '<font style="color:#AAA">'.$item['name'].'</font>';
            }
            $new_result[] = $item;
        } 
        $result = $new_result;
        break;
    case 'get_grade_models':
        $columns = array('name', 'description', 'actions');
        if (!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        $result     = Database::select('*', "$obj->table ", array('order' =>"$sidx $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        foreach($result as $item) {
            $new_result[] = $item;
        }
        $result = $new_result;
        break;
    case 'get_usergroups':
        $columns = array('name', 'users', 'courses','sessions','actions');
        $result     = Database::select('*', $obj->table, array('order'=>"name $sord", 'LIMIT'=> "$start , $limit"));
        $new_result = array();
        if (!empty($result)) {
            foreach ($result as $group) {            
                $group['sessions']   = count($obj->get_sessions_by_usergroup($group['id']));
                $group['courses']    = count($obj->get_courses_by_usergroup($group['id']));
                $group['users']      = count($obj->get_users_by_usergroup($group['id']));
                $new_result[]        = $group;
            }
            $result = $new_result;
        }        
        $columns = array('name', 'users', 'courses','sessions');                
        if(!in_array($sidx, $columns)) {
            $sidx = 'name';
        }
        //Multidimensional sort
        msort($result, $sidx);
        break;      
    default:    
        exit;            
}
//var_dump($result);

$allowed_actions = array('get_careers', 'get_promotions', 'get_usergroups', 'get_gradebooks', 
                         'get_sessions', 'get_exercise_results', 'get_work_user_list', 'get_timelines', 'get_grade_models');
//5. Creating an obj to return a json
if (in_array($action, $allowed_actions)) {
    $response           = new stdClass();           
    $response->page     = $page; 
    $response->total    = $total_pages; 
    $response->records  = $count; 
    $i=0;
    if (!empty($result)) {
        foreach ($result as $row) {
            //print_r($row);
            // if results tab give not id, set id to $i otherwise id="null" for all <tr> of the jqgrid - ref #4235
            if ($row['id'] == "") {
                $response->rows[$i]['id']=$i;
            } else {
                $response->rows[$i]['id']=$row['id'];
            }             
            $array = array();            
            foreach ($columns as $col) {
                $array[] = $row[$col];
            }                   
            $response->rows[$i]['cell']=$array;
            $i++; 
        }
    }    
    echo json_encode($response);       
}
exit;