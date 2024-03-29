<?php
/* For licensing terms, see /license.txt */
/**
 *	This file allows creating new svg and png documents with an online editor.
 *
 *	@package chamilo.document
 *  @todo used the document_id instead of the curdirpath
 *
 * @author Juan Carlos Ra�a Trabado
 * @since 30/january/2011
*/
/**
 * Code
 */

/*	INIT SECTION */

$language_file = array('document');

require_once '../inc/global.inc.php';

$_SESSION['whereami'] = 'document/editpaint';
$this_section = SECTION_COURSES;

require_once api_get_path(SYS_CODE_PATH).'document/document.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

api_protect_course_script();
api_block_anonymous_users();

$document_data = DocumentManager::get_document_data_by_id($_GET['id'], api_get_course_id());    
if (empty($document_data)) {
    api_not_allowed();
} else {    
    $document_id    = $document_data['id'];
    $file_path      = $document_data['path'];
    $dir            = dirname($document_data['path']);
    $parent_id      = DocumentManager::get_document_id(api_get_course_info(), $dir);
}

$dir= str_replace('\\', '/',$dir);//and urlencode each url $curdirpath (hack clean $curdirpath under Windows - Bug #3261)

/* Constants & Variables */
$current_session_id=api_get_session_id();
//path for pixlr save
$_SESSION['paint_dir']=Security::remove_XSS($dir);
if($_SESSION['paint_dir']=='/'){
    $_SESSION['paint_dir']='';
}
$_SESSION['paint_file']=basename(Security::remove_XSS($file_path));

$get_file = Security::remove_XSS($file_path);

$file = basename($get_file);

$temp_file = explode(".",$file);
$filename=$temp_file[0];
$nameTools = get_lang('EditDocument') . ': '.$filename;

$courseDir   = $_course['path'].'/document';

$is_allowed_to_edit = api_is_allowed_to_edit(null, true);

/*	Other initialization code */

/* Please, do not modify this dirname formatting */

if (strstr($dir, '..')) {
	$dir = '/';
}

if ($dir[0] == '.') {
	$dir = substr($dir, 1);
}

if ($dir[0] != '/') {
	$dir = '/'.$dir;
}

if ($dir[strlen($dir) - 1] != '/') {
	$dir .= '/';
}

$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document'.$dir;

if (!is_dir($filepath)) {
	$filepath = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document/';
	$dir = '/';
}

//groups //TODO:clean
if (isset ($_SESSION['_gid']) && $_SESSION['_gid'] != 0) {
	
	$req_gid = '&amp;gidReq='.$_SESSION['_gid'];
	$interbreadcrumb[] = array ('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace'));
	$group_document = true;
	$noPHP_SELF = true;	
}


if (!$is_certificate_mode)
    $interbreadcrumb[]=array("url"=>"./document.php?id=".$document_id.$req_gid, "name"=> get_lang('Documents'));
else
    $interbreadcrumb[]= array ( 'url' => '../gradebook/'.$_SESSION['gradebook_dest'], 'name' => get_lang('Gradebook'));

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || $_SESSION['group_member_with_upload_rights'] || is_my_shared_folder(api_get_user_id(), $dir, $current_session_id);

if (!$is_allowedToEdit) {
	api_not_allowed(true);
}

event_access_tool(TOOL_DOCUMENT);

Display :: display_header($nameTools, 'Doc');
echo '<div class="actions">';		
		echo '<a href="document.php?id='.$parent_id.'">'.Display::return_icon('back.png',get_lang('BackTo').' '.get_lang('DocumentsOverview'),'',ICON_SIZE_MEDIUM).'</a>';		
		echo '<a href="edit_document.php?'.api_get_cidreq().'&id='.$document_id.$req_gid.'&origin=editpaint">'.Display::return_icon('edit.png', get_lang('Rename').'/'.get_lang('Comment'  ),'',ICON_SIZE_MEDIUM).'</a>';
echo '</div>'; 

///pixlr
$title=$file;//disk name. No sql name because pixlr return this when save
//$image=urlencode(api_get_path(WEB_COURSE_PATH).$courseDir.$dir.$file);//TODO: only work with public courses. Doesn't remove please
//
$pixlr_code_translation_table = array('' => 'en', 'pt' => 'pt-Pt', 'sr' => 'sr_latn');
$langpixlr  = api_get_language_isocode();
$langpixlr = isset($pixlr_code_translation_table[$langpixlr]) ? $pixlredit_code_translation_table[$langpixlr] : $langpixlr;
$loc=$langpixlr;// deprecated ?? TODO:check pixlr read user browser

$exit_path=api_get_path(WEB_CODE_PATH).'document/exit_pixlr.php';
$_SESSION['exit_pixlr']= Security::remove_XSS($parent_id);

$referrer="Chamilo";

$target_path=api_get_path(WEB_CODE_PATH).'document/save_pixlr.php';
$target=$target_path;

$locktarget="true";
$locktitle="false";

//make temp images
$temp_folder=api_get_path(SYS_ARCHIVE_PATH).'temp/images';
if (!file_exists($temp_folder)) {
    @mkdir($temp_folder, api_get_permissions_for_new_directories(), true);//TODO:check $permissions value, now empty;
}

//make htaccess with allow from all, and file index.html into temp/images
$htaccess=api_get_path(SYS_ARCHIVE_PATH).'temp/images/.htacess';
if (!file_exists($htaccess)) {
	$htaccess_content="order deny,allow\r\nallow from all";
	$fp = @ fopen(api_get_path(SYS_ARCHIVE_PATH).'temp/images/.htaccess', 'w');
	if ($fp) {
		fwrite($fp, $htaccess_content);
		fclose($fp);
	}
}

$html_index=api_get_path(SYS_ARCHIVE_PATH).'temp/images/index.html';
if (!file_exists($html_index)) {	
	$html_index_content="<html><head></head><body></body></html>";
	$fp = @ fopen(api_get_path(SYS_ARCHIVE_PATH).'temp/images/index.html', 'w');
	if ($fp) {
		fwrite($fp, $html_index_content);
		fclose($fp);
	}
}

//encript temp name file
$name_crip=sha1(uniqid());//encript 
$findext= explode(".", $file);
$extension= $findext[count($findext)-1];
$file_crip=$name_crip.'.'.$extension;

//copy file to temp/images directory
$from=$filepath.$file;
$to=api_get_path(SYS_ARCHIVE_PATH).'temp/images/'.$file_crip;
copy($from, $to);
$_SESSION['temp_realpath_image']=$to;

//load image to url
$to_url=api_get_path(WEB_ARCHIVE_PATH).'temp/images/'.$file_crip;
$image=urlencode($to_url);
$pixlr_url = 'http://pixlr.com/editor/?title='.$title.'&amp;image='.$image.'&amp;loc='.$loc.'&amp;referrer='.$referrer.'&amp;target='.$target.'&amp;exit='.$exit_path.'&amp;locktarget='.$locktarget.'&amp;locktitle='.$locktitle;
 
//make frame an send image
echo '<iframe style="height: 600px; width: 100%;" scrolling="no" frameborder="0" src="'.$pixlr_url.'">';
echo '</iframe>';
Display::display_footer();
