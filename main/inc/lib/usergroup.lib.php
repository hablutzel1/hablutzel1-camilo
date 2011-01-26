<?php
/* For licensing terms, see /license.txt */

/**
*	This class provides methods for the notebook management.
*	Include/require it in your code to use its features.
*	@package chamilo.library
*/

require_once 'model.lib.php';

class UserGroup extends Model {

    var $columns = array('id', 'name','description');
    
	public function __construct() {
        $this->table                        =  Database::get_main_table(TABLE_USERGROUP);
        $this->usergroup_rel_user_table     =  Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $this->usergroup_rel_course_table   =  Database::get_main_table(TABLE_USERGROUP_REL_COURSE);
        $this->usergroup_rel_session_table  =  Database::get_main_table(TABLE_USERGROUP_REL_SESSION);
	}
    
    /**
     * Displays the title + grid
     */
    function display() {
        // action links
        echo '<div class="actions" style="margin-bottom:20px">';
        echo '<a href="career_dashboard.php">'.Display::return_icon('back.png',get_lang('Back')).get_lang('Back').'</a>';       
        echo '<a href="'.api_get_self().'?action=add">'.Display::return_icon('filenew.gif',get_lang('Add')).get_lang('Add').'</a>';                     
        echo '</div>';   
        echo Display::grid_html('usergroups');  
    } 
    
    
    public function get_courses_by_usergroup($id) {        
        $results = Database::select('*',$this->usergroup_rel_course_table, array('where'=>array('usergroup_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['course_id'];            
            }
        }                       
        return $array;
    }
    
    public function get_sessions_by_usergroup($id) {
        $results = Database::select('*',$this->usergroup_rel_session_table, array('where'=>array('usergroup_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['session_id'];            
            }
        }                
        return $array;
    }      
    
    public function get_users_by_usergroup($id) {
        $results = Database::select('*',$this->usergroup_rel_user_table, array('where'=>array('usergroup_id = ?'=>$id)));
        $array = array();
        if (!empty($results)) {    
            foreach($results as $row) {
                $array[]= $row['user_id'];            
            }
        }                       
        return $array; 	
    }
    
    
    /**
     * Subscribes sessions to a group  (also adding the members of the group in the session and course)
     * @param   int     usergroup id
     * @param   array   list of session ids
    */
    function subscribe_sessions_to_usergroup($usergroup_id, $list) {
        require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
        
        $t = Database::get_main_table(TABLE_USERGROUP_REL_SESSION);        
        //Deleting relationships
  
        $current_list = self::get_sessions_by_usergroup($usergroup_id);
        $user_list    = self::get_users_by_usergroup($usergroup_id);
     
        $delete_items = $new_items = array();
        if (!empty($list)) {                
            foreach ($list as $session_id) {
                if (!in_array($session_id, $current_list)) {
                	$new_items[] = $session_id;
                }           	
            }
        }            
        if (!empty($current_list)) {  
            foreach($current_list as $session_id) {
        	   if (!in_array($session_id, $list)) {
                    $delete_items[] = $session_id;
                }  
            }
        }

        //Deleting items
        if (!empty($delete_items)) {
            foreach($delete_items as $session_id) {
                foreach($user_list as $user_id) {
                    SessionManager::unsubscribe_user_from_session($session_id, $user_id);
                    /*foreach ($course_list as $course_data) {
                        foreach($user_list as $user_id) {
                            CourseManager::subscribe_user($user_id, $course_data['code'], $session_id);
                        }
                    }*/
                }
                Database::delete($t, array('usergroup_id = ? AND session_id = ?'=>array($usergroup_id, $session_id)));
            }
        }
     
        
        //Addding new relationships
        if (!empty($new_items)) {
            foreach($new_items as $id) {                
                $params = array('session_id'=>$id, 'usergroup_id'=>$usergroup_id);
                Database::insert($t, $params);                
                SessionManager::suscribe_users_to_session($session_id, $user_list);
                /*
                $course_list = SessionManager::get_course_list_by_session_id($id);
                foreach ($course_list as $course_data) {
                    foreach($user_list as $user_id) {
                        CourseManager::subscribe_user($user_id, $course_data['code'], $id);
                    }
                }*/
            }
        }
    }
    
    /**
     * Subscribes courses to a group (also adding the members of the group in the course)
     * @param   int     usergroup id
     * @param   array   list of course ids
     */
    function subscribe_courses_to_usergroup($usergroup_id, $list) {
        require_once api_get_path(LIBRARY_PATH).'course.lib.php';
        
        $t = Database::get_main_table(TABLE_USERGROUP_REL_COURSE);        
        //Deleting relationships
  
        $current_list = self::get_courses_by_usergroup($usergroup_id);
        $user_list    = self::get_users_by_usergroup($usergroup_id);
     
        $delete_items = $new_items = array();
        if (!empty($list)) {                
            foreach ($list as $id) {
                if (!in_array($id, $current_list)) {
                    $new_items[] = $id;
                }               
            }
        }
        if (!empty($current_list)) {         
            foreach($current_list as $id) {
                if (!in_array($id, $list)) {
                    $delete_items[] = $id;
                }  
            }
        }
        
        //Deleting items
        if (!empty($delete_items)) {
            foreach($delete_items as $course_id) {
                $course_info = api_get_course_info_by_id($course_id);     
                foreach($user_list as $user_id) {                                   
                    CourseManager::unsubscribe_user($user_id, $course_info['code']);                    
                }
                Database::delete($t, array('usergroup_id = ? AND course_id = ?'=>array($usergroup_id, $course_id)));
            }
        }
        
        //Addding new relationships
        if (!empty($new_items)) {
            foreach($new_items as $course_id) {                
                $course_info = api_get_course_info_by_id($course_id);    
                
                foreach($user_list as $user_id) {         
                    CourseManager::subscribe_user($user_id, $course_info['code']);
                }
                 
                $params = array('course_id'=>$id, 'usergroup_id'=>$usergroup_id);
                Database::insert($t, $params);
            }
        }
    }   
    
     /**
     * Subscribes users to a group
     * @param   int     usergroup id
     * @param   array   list of user ids
     */
    function subscribe_users_to_usergroup($usergroup_id, $list) {
        $t = Database::get_main_table(TABLE_USERGROUP_REL_USER);            
        $user_list = self::get_users_by_usergroup($usergroup_id);        
            
        //Deleting relationships
        Database::delete($t, array('usergroup_id = ?'=>$usergroup_id));
        
        //Adding new relationships
        if (!empty($list)) {
            foreach($list as $id) {
                $params = array('user_id'=>$id, 'usergroup_id'=>$usergroup_id);
                Database::insert($t, $params);
            }
        }
    }
    
}