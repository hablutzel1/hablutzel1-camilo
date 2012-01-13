<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once '../global.inc.php';

$action = $_REQUEST['a'];

$user_id = api_get_user_id();

switch ($action) {    
    case 'course_vote':
        if (!api_is_anonymous()) {
     	    $course_id = intval($_REQUEST['course_id']);
            $star      = intval($_REQUEST['star']);
            
            if (!in_array($star, array(1,2,3,4,5))) {
                //trying to hack the star rating ...
                exit;
            }
            CourseManager::add_course_vote($user_id, $course_id, 0);
        }
        break;
    default:
        echo '';
}
exit;