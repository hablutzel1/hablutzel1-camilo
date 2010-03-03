<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
*/

$action = $_GET['a'];
switch ($action) {
	case 'set_visibility':
		require_once '../global.inc.php';
		if(api_is_allowed_to_edit(null,true)) {
				$tool_table = Database::get_course_table(TABLE_TOOL_LIST);
				$tool_id = Security::remove_XSS($_GET["id"]);
				$tool_info = api_get_tool_information($tool_id);
				$tool_visibility   = $tool_info['visibility'];
				$tool_image        = $tool_info['image'];
				$new_image         = str_replace('.gif','_na.gif',$tool_image);
				$requested_image   = ($tool_visibility == 0 ) ? $tool_image : $new_image;
				$requested_clase   = ($tool_visibility == 0 ) ? 'visible' : 'invisible';
				$requested_message = ($tool_visibility == 0 ) ? 'is_active' : 'is_inactive';
			    $requested_view    = ($tool_visibility == 0 ) ? 'visible.gif' : 'invisible.gif';
			    $requested_visible = ($tool_visibility == 0 ) ? 1 : 0;

		    	$requested_view    = ($tool_visibility == 0 ) ? 'visible.gif' : 'invisible.gif';
		    	$requested_visible = ($tool_visibility == 0 ) ? 1 : 0;
			//HIDE AND REACTIVATE TOOL
			if ($_GET["id"]==strval(intval($_GET["id"]))) {

				/* -- session condition for visibility
				 if (!empty($session_id)) {
					$sql = "select session_id FROM $tool_table WHERE id='".$_GET["id"]."' AND session_id = '$session_id'";
					$rs = Database::query($sql);
					if (Database::num_rows($rs) > 0) {
			 			$sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".$_GET["id"]."' AND session_id = '$session_id'";
					} else {
						$sql_select = "select * FROM $tool_table WHERE id='".$_GET["id"]."'";
						$res_select = Database::query($sql_select);
						$row_select = Database::fetch_array($res_select);
						$sql = "INSERT INTO $tool_table(name,link,image,visibility,admin,address,added_tool,target,category,session_id)
								VALUES('{$row_select['name']}','{$row_select['link']}','{$row_select['image']}','0','{$row_select['admin']}','{$row_select['address']}','{$row_select['added_tool']}','{$row_select['target']}','{$row_select['category']}','$session_id')";
					}
				} else $sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".$_GET["id"]."'";
				*/

				$sql="UPDATE $tool_table SET visibility=$requested_visible WHERE id='".$_GET["id"]."'";
				Database::query($sql);
			}
				/*
				-----------------------------------------------------------
					HIDE
				-----------------------------------------------------------
				*/
		/*		if(isset($_GET['visibility']) && $_GET['visibility']==0) // visibility 1 -> 0
				{
					if ($_GET["id"]==strval(intval($_GET["id"]))) {
						$sql="UPDATE $tool_table SET visibility=0 WHERE id='".intval($_GET["id"])."'";
						Database::query($sql);
					}
				}

			  /*
				-----------------------------------------------------------
					REACTIVATE
				-----------------------------------------------------------
				*/
		/*		elseif(isset($_GET['visibility'])&& $_GET['visibility']==1) // visibility 0,2 -> 1
				{
					if ($_GET["id"]==strval(intval($_GET["id"]))) {
						Database::query("UPDATE $tool_table SET visibility=1 WHERE id='".intval($_GET["id"])."'");
					}
				}

		*/
				$response_data = array(
					'image'   => $requested_image,
					'tclass'  => $requested_clase,
					'message' => $requested_message,
		      		'view'    => $requested_view
				);
				print(json_encode($response_data));
			}
	break;
	
	case 'show_course_information' :
	
		$language_file = array ('course_description');
		require_once '../global.inc.php';	
			
		require_once api_get_path(INCLUDE_PATH).'reduced_header.inc.php' ;
				
		// Get the name of the database course.
		$database_course = CourseManager::get_name_database_course($_GET['code']);
		$tbl_course_description = Database::get_course_table(TABLE_COURSE_DESCRIPTION, $database_course);
	
		$sql = "SELECT * FROM $tbl_course_description WHERE session_id=0 ORDER BY id";
		$result = Database::query($sql);
		while ($description = Database::fetch_object($result)) {
			$descriptions[$description->id] = $description;
		}

		// Function that displays the details of the course description in html.
		echo  CourseManager::get_details_course_description_html($descriptions, api_get_system_encoding(), false);
	break;
	default:
		echo '';
}
exit;
?>