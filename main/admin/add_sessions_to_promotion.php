<?php
/* For licensing terms, see /license.txt */
/**
*   @package chamilo.admin
*/

// name of the language file that needs to be included
$language_file=array('admin','registration');

// resetting the course id
$cidReset=true;

// including some necessary files
require_once '../inc/global.inc.php';
require_once '../inc/lib/xajax/xajax.inc.php';
require_once api_get_path(LIBRARY_PATH).'promotion.lib.php';
require_once api_get_path(LIBRARY_PATH).'career.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';

$xajax = new xajax();

//$xajax->debugOn();
$xajax -> registerFunction ('search_sessions');

// setting the section (for the tabs)
$this_section = SECTION_PLATFORM_ADMIN;

// Access restrictions
api_protect_admin_script(true);

// setting breadcrumbs
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'career_dashboard.php','name' => get_lang('CareerAndPromotions'));

// Database Table Definitions

// setting the name of the tool
$tool_name=get_lang('SubscribeSessionsToPromotions');

$add_type = 'multiple';
if(isset($_REQUEST['add_type']) && $_REQUEST['add_type']!=''){
    $add_type = Security::remove_XSS($_REQUEST['add_type']);
}

//checking for extra field with filter on

function search_sessions($needle,$type) {
    global $tbl_user,$tbl_session_rel_user,$id_session;
    $xajax_response = new XajaxResponse();
    $return = '';

    if (!empty($needle) && !empty($type)) {

        // xajax send utf8 datas... datas in db can be non-utf8 datas
        $charset = api_get_system_encoding();
        $needle = Database::escape_string($needle);
        $needle = api_convert_encoding($needle, $charset, 'utf-8');
        $user_anonymous=api_get_anonymous_id();

        $order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
        $cond_user_id = '';
        if (!empty($id_session)) {
        $id_session = Database::escape_string($id_session);
            // check id_user from session_rel_user table
            $sql = 'SELECT id_user FROM '.$tbl_session_rel_user.' WHERE id_session ="'.(int)$id_session.'" AND relation_type<>'.SESSION_RELATION_TYPE_RRHH.' ';
            $res = Database::query($sql);
            $user_ids = array();
            if (Database::num_rows($res) > 0) {
                while ($row = Database::fetch_row($res)) {
                    $user_ids[] = (int)$row[0];
                }
            }
            if (count($user_ids) > 0){
                $cond_user_id = ' AND user.user_id NOT IN('.implode(",",$user_ids).')';
            }
        }

        if ($type == 'single') {
            // search users where username or firstname or lastname begins likes $needle
            $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                    WHERE (username LIKE "'.$needle.'%"
                    OR firstname LIKE "'.$needle.'%"
                OR lastname LIKE "'.$needle.'%") AND user.user_id<>"'.$user_anonymous.'"   AND user.status<>'.DRH.''.
                $order_clause.
                ' LIMIT 11';
        } else {
            $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                    WHERE '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.status<>'.DRH.' AND user.user_id<>"'.$user_anonymous.'"'.$cond_user_id.
                    $order_clause;
        }

        global $_configuration;
        if ($_configuration['multiple_access_urls']) {
            $tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1){
                if ($type == 'single') {
                    $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                    INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
                    WHERE access_url_id = '.$access_url_id.'  AND (username LIKE "'.$needle.'%"
                    OR firstname LIKE "'.$needle.'%"
                    OR lastname LIKE "'.$needle.'%") AND user.user_id<>"'.$user_anonymous.'" AND user.status<>'.DRH.' '.
                    $order_clause.
                    ' LIMIT 11';
                } else {
                    $sql = 'SELECT user.user_id, username, lastname, firstname FROM '.$tbl_user.' user
                    INNER JOIN '.$tbl_user_rel_access_url.' url_user ON (url_user.user_id=user.user_id)
                    WHERE access_url_id = '.$access_url_id.'
                    AND '.(api_sort_by_first_name() ? 'firstname' : 'lastname').' LIKE "'.$needle.'%" AND user.status<>'.DRH.' AND user.user_id<>"'.$user_anonymous.'"'.$cond_user_id.
                    $order_clause;
                }

            }
        }

        $rs = Database::query($sql);
        $i=0;
        if ($type=='single') {
            while ($user = Database :: fetch_array($rs)) {
                $i++;
                if ($i<=10) {
                    $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                    $return .= '<a href="javascript: void(0);" onclick="javascript: add_user_to_session(\''.$user['user_id'].'\',\''.$person_name.' ('.$user['username'].')'.'\')">'.$person_name.' ('.$user['username'].')</a><br />';
                } else {
                    $return .= '...<br />';
                }
            }

            $xajax_response -> addAssign('ajax_list_users_single','innerHTML',api_utf8_encode($return));

        } else {
            global $nosessionUsersList;
            $return .= '<select id="origin_users" name="nosessionUsersList[]" multiple="multiple" size="15" style="width:360px;">';
            while ($user = Database :: fetch_array($rs)) {
                $person_name = api_get_person_name($user['firstname'], $user['lastname']);
                $return .= '<option value="'.$user['user_id'].'">'.$person_name.' ('.$user['username'].')</option>';
            }
            $return .= '</select>';
            $xajax_response -> addAssign('ajax_list_users_multiple','innerHTML',api_utf8_encode($return));
        }
    }

    return $xajax_response;
}

$xajax -> processRequests();

$htmlHeadXtra[] = $xajax->getJavascript('../inc/lib/xajax/');
$htmlHeadXtra[] = '
<script type="text/javascript">
function add_user_to_session (code, content) {

    document.getElementById("user_to_add").value = "";
    document.getElementById("ajax_list_users_single").innerHTML = "";

    destination = document.getElementById("session_in_promotion");

    for (i=0;i<destination.length;i++) {
        if(destination.options[i].text == content) {
                return false;
        }
    }

    destination.options[destination.length] = new Option(content,code);
    destination.selectedIndex = -1;
    sortOptions(destination.options);
}
function remove_item(origin) {
    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            origin.options[i]=null;
            i = i-1;
        }
    }
}

function validate_filter() {
        document.formulaire.add_type.value = \''.$add_type.'\';
        document.formulaire.form_sent.value=0;
        document.formulaire.submit();
}
</script>';


$form_sent=0;
$errorMsg=$firstLetterUser=$firstLetterSession='';
$UserList=$SessionList=array();
$users=$sessions=array();
$noPHP_SELF=true;

$promotion = new Promotion();
$id = intval($_GET['id']);
//echo '<pre>';
if($_POST['form_sent']) {
    $form_sent          = $_POST['form_sent'];
    $firstLetterUser    = $_POST['firstLetterUser'];
    $firstLetterSession = $_POST['firstLetterSession'];
    $session_in_promotion_posted       = $_POST['session_in_promotion_name'];     
    if (!is_array($session_in_promotion_posted)) {
        $session_in_promotion_posted=array();
    }
    if ($form_sent == 1) {
        //added a parameter to send emails when registering a user        
        SessionManager::suscribe_sessions_to_promotion($id, $session_in_promotion_posted);
        header('Location: promotions.php');
        exit;        
    }
}

Display::display_header($tool_name);

$promotion_data = $promotion->get($id);
$session_list = SessionManager::get_sessions_list();

//api_display_tool_title($tool_name.' ('.$session_info['name'].')');
$session_not_in_promotion = $session_in_promotion= array();

if (!empty($session_list)) {
    foreach($session_list as $session) {
        $promotion_id = $session['promotion_id'];    
        if (isset($promotion_id) && !empty($promotion_id)) {
            if ($promotion_id == $id) {                
                $session_in_promotion[$session['id']] = $session['name'];
            } else {
            	$session_not_in_promotion[$session['id']] = $session['name'];
            } 
        } else {
        	$session_not_in_promotion[$session['id']] = $session['name'];
        }
    }
}
$ajax_search = $add_type == 'unique' ? true : false;

if ($ajax_search) {
    $sql="SELECT user_id, lastname, firstname, username, id_session
            FROM $tbl_user
            INNER JOIN $tbl_session_rel_user
                ON $tbl_session_rel_user.id_user = $tbl_user.user_id AND $tbl_session_rel_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                AND $tbl_session_rel_user.id_session = ".intval($id_session)."
                WHERE status<>".DRH." $order_clause";

    if ($_configuration['multiple_access_urls']) {
        $tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1){
            $sql="SELECT u.user_id, lastname, firstname, username, id_session
            FROM $tbl_user u
            INNER JOIN $tbl_session_rel_user
                ON $tbl_session_rel_user.id_user = u.user_id AND $tbl_session_rel_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                AND $tbl_session_rel_user.id_session = ".intval($id_session)."
                INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
                WHERE access_url_id = $access_url_id AND u.status<>".DRH."
                $order_clause";
        }
    }
    $result=Database::query($sql);
    $users=Database::store_result($result);
    foreach ($users as $user) {
        $sessionUsersList[$user['user_id']] = $user ;
    }
} else {
          $sql="SELECT  user_id, lastname, firstname, username, id_session
            FROM $tbl_user u
            LEFT JOIN $tbl_session_rel_user
            ON $tbl_session_rel_user.id_user = u.user_id AND $tbl_session_rel_user.id_session = '$id_session' AND $tbl_session_rel_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
            WHERE u.status<>".DRH."
        $order_clause";
       
        if ($_configuration['multiple_access_urls']) {
            $tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1){
                $sql="SELECT  u.user_id, lastname, firstname, username, id_session
                FROM $tbl_user u
                LEFT JOIN $tbl_session_rel_user
                    ON $tbl_session_rel_user.id_user = u.user_id AND $tbl_session_rel_user.id_session = '$id_session' AND $tbl_session_rel_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
                WHERE access_url_id = $access_url_id  $where_filter AND u.status<>".DRH."
            $order_clause";
            }
        }

        $result=Database::query($sql);
        $users=Database::store_result($result);
        //var_dump($_REQUEST['id_session']);
        foreach ($users as $user) {
            if($user['id_session'] != $id_session)
                $nosessionUsersList[$user['user_id']] = $user ;
        }
        $user_anonymous=api_get_anonymous_id();
        if (count($nosessionUsersList) > 0) {
            foreach($nosessionUsersList as $key_user_list =>$value_user_list) {
                if ($nosessionUsersList[$key_user_list]['user_id']==$user_anonymous) {
                    unset($nosessionUsersList[$key_user_list]);
                }
            }
        }
        //filling the correct users in list
        $sql="SELECT  user_id, lastname, firstname, username, id_session
            FROM $tbl_user u
            LEFT JOIN $tbl_session_rel_user
            ON $tbl_session_rel_user.id_user = u.user_id AND $tbl_session_rel_user.id_session = '$id_session' AND $tbl_session_rel_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
            WHERE u.status<>".DRH." $order_clause";

        if ($_configuration['multiple_access_urls']) {
            $tbl_user_rel_access_url= Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1){
                $sql="SELECT  u.user_id, lastname, firstname, username, id_session
                FROM $tbl_user u
                LEFT JOIN $tbl_session_rel_user
                    ON $tbl_session_rel_user.id_user = u.user_id AND $tbl_session_rel_user.id_session = '$id_session' AND $tbl_session_rel_user.relation_type<>".SESSION_RELATION_TYPE_RRHH."
                INNER JOIN $tbl_user_rel_access_url url_user ON (url_user.user_id=u.user_id)
                WHERE access_url_id = $access_url_id AND u.status<>".DRH."
                $order_clause";
            }
        }
    $result=Database::query($sql);
    $users=Database::store_result($result);

    foreach($users as $key_user_list =>$value_user_list) {
        if ($users[$key_user_list]['user_id']==$user_anonymous) {
            unset($users[$key_user_list]);
            }
        }

    foreach ($users as $user) {
        if($user['id_session'] == $id_session){
            $sessionUsersList[$user['user_id']] = $user;
            if (array_key_exists($user['user_id'],$nosessionUsersList))
                unset($nosessionUsersList[$user['user_id']]);
        }

    }
}

if ($add_type == 'multiple') {
    $link_add_type_unique = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=unique">'.Display::return_icon('single.gif').get_lang('SessionAddTypeUnique').'</a>';
    $link_add_type_multiple = Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple');
} else {
    $link_add_type_unique = Display::return_icon('single.gif').get_lang('SessionAddTypeUnique');
    $link_add_type_multiple = '<a href="'.api_get_self().'?id_session='.$id_session.'&add='.Security::remove_XSS($_GET['add']).'&add_type=multiple">'.Display::return_icon('multiple.gif').get_lang('SessionAddTypeMultiple').'</a>';
}
/*
<div class="actions">
    <?php echo $link_add_type_unique ?>&nbsp;|&nbsp;<?php echo $link_add_type_multiple ?>
</div>
*/
?>


<?php echo '<div class="row"><div class="form_header">'.$tool_name.' '.$promotion_data['name'].'</div></div><br/>'; ?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>?id=<?php echo $id; if(!empty($_GET['add'])) echo '&add=true' ; ?>" style="margin:0px;" <?php if($ajax_search){echo ' onsubmit="valide();"';}?>>

<?php
if ($add_type=='multiple') {
    if (is_array($extra_field_list)) {
        if (is_array($new_field_list) && count($new_field_list)>0 ) {
            echo '<h3>'.get_lang('FilterUsers').'</h3>';
            foreach ($new_field_list as $new_field) {
                echo $new_field['name'];
                $varname = 'field_'.$new_field['variable'];
                echo '&nbsp;<select name="'.$varname.'">';
                echo '<option value="0">--'.get_lang('Select').'--</option>';
                foreach ($new_field['data'] as $option) {
                    $checked='';
                    if (isset($_POST[$varname])) {
                        if ($_POST[$varname]==$option[1]) {
                            $checked = 'selected="true"';
                        }
                    }
                    echo '<option value="'.$option[1].'" '.$checked.'>'.$option[1].'</option>';
                }
                echo '</select>';
                echo '&nbsp;&nbsp;';
            }
            echo '<input type="button" value="'.get_lang('Filter').'" onclick="validate_filter()" />';
            echo '<br /><br />';
        }
    }
}
echo Display::input('hidden','id',$id);
echo Display::input('hidden','form_sent','1');
echo Display::input('hidden','add_type');
if(!empty($errorMsg)) {
    Display::display_normal_message($errorMsg); //main API
}
?>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<!-- Users -->
<tr>
  <td align="center"><b><?php echo get_lang('SessionsInPlatform') ?> :</b>
  </td>
  <td></td>
  <td align="center"><b><?php echo get_lang('SessionsInPromotion') ?> :</b></td>
</tr>

<?php if ($add_type=='multiple') { ?>
<tr>
<td align="center">

<?php echo get_lang('FirstLetterSessions'); ?> :
     <select name="firstLetterUser" onchange = "xajax_search_sessions(this.value,'multiple')" >
      <option value = "%">--</option>
      <?php
        echo Display :: get_alphabet_options();
      ?>
     </select>
</td>
<td align="center">&nbsp;</td>
</tr>
<?php } ?>
<tr>
  <td align="center">
  <div id="content_source">
      <?php
      
      
      if (!($add_type=='multiple')) {
        ?>
        <input type="text" id="user_to_add" onkeyup="xajax_search_users(this.value,'single')" />
        <div id="ajax_list_users_single"></div>
        <?php
      } else {        
        echo Display::select('session_not_in_promotion_name',$session_not_in_promotion, '',array('style'=>'width:360px', 'multiple'=>'multiple','id'=>'session_not_in_promotion','size'=>'15px'),false);
      ?>
      <div id="ajax_list_users_multiple">
        
      </div>
    <?php
      }
      unset($nosessionUsersList);
     ?>
  </div>
  </td>
  <td width="10%" valign="middle" align="center">
  <?php
  if ($ajax_search) {
  ?>
    <button class="arrowl" type="button" onclick="remove_item(document.getElementById('session_in_promotion'))" ></button>
  <?php
  } else {
  ?>
    <button class="arrowr" type="button" onclick="moveItem(document.getElementById('session_not_in_promotion'), document.getElementById('session_in_promotion'))" onclick="moveItem(document.getElementById('session_not_in_promotion'), document.getElementById('session_in_promotion'))"></button>
    <br /><br />
    <button class="arrowl" type="button" onclick="moveItem(document.getElementById('session_in_promotion'), document.getElementById('session_not_in_promotion'))" onclick="moveItem(document.getElementById('session_in_promotion'), document.getElementById('session_not_in_promotion'))"></button>
    <?php
  }
  ?>
    <br /><br /><br /><br /><br /><br />
  </td>
  <td align="center">
<?php

    echo Display::select('session_in_promotion_name[]', $session_in_promotion, '', array('style'=>'width:360px', 'multiple'=>'multiple','id'=>'session_in_promotion','size'=>'15px'),false );
    unset($sessionUsersList);
?>
 </td>
</tr>
<tr>
    <td colspan="3" align="center">
        <br />
        <?php
        echo '<button class="save" type="button" value="" onclick="valide()" >'.get_lang('SubscribeSessionsToPromotion').'</button>';
        ?>
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
<!--
function moveItem(origin , destination){

    for(var i = 0 ; i<origin.options.length ; i++) {
        if(origin.options[i].selected) {
            destination.options[destination.length] = new Option(origin.options[i].text,origin.options[i].value);
            origin.options[i]=null;
            i = i-1;
        }
    }
    destination.selectedIndex = -1;
    sortOptions(destination.options);

}

function sortOptions(options) {

    newOptions = new Array();
    for (i = 0 ; i<options.length ; i++)
        newOptions[i] = options[i];

    newOptions = newOptions.sort(mysort);
    options.length = 0;
    for(i = 0 ; i < newOptions.length ; i++)
        options[i] = newOptions[i];

}

function mysort(a, b){
    if(a.text.toLowerCase() > b.text.toLowerCase()){
        return 1;
    }
    if(a.text.toLowerCase() < b.text.toLowerCase()){
        return -1;
    }
    return 0;
}

function valide(){
    var options = document.getElementById('session_in_promotion').options;
    for (i = 0 ; i<options.length ; i++)
        options[i].selected = true;
    document.forms.formulaire.submit();
}


function loadUsersInSelect(select){

    var xhr_object = null;

    if(window.XMLHttpRequest) // Firefox
        xhr_object = new XMLHttpRequest();
    else if(window.ActiveXObject) // Internet Explorer
        xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
    else  // XMLHttpRequest non supporté par le navigateur
    alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

    //xhr_object.open("GET", "loadUsersInSelect.ajax.php?id_session=<?php echo $id_session ?>&letter="+select.options[select.selectedIndex].text, false);
    xhr_object.open("POST", "loadUsersInSelect.ajax.php");

    xhr_object.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");


    nosessionUsers = makepost(document.getElementById('session_not_in_promotion'));
    sessionUsers = makepost(document.getElementById('session_in_promotion'));
    nosessionClasses = makepost(document.getElementById('origin_classes'));
    sessionClasses = makepost(document.getElementById('destination_classes'));
    xhr_object.send("nosessionusers="+nosessionUsers+"&sessionusers="+sessionUsers+"&nosessionclasses="+nosessionClasses+"&sessionclasses="+sessionClasses);

    xhr_object.onreadystatechange = function() {
        if(xhr_object.readyState == 4) {
            document.getElementById('content_source').innerHTML = result = xhr_object.responseText;
            //alert(xhr_object.responseText);
        }
    }
}

function makepost(select){

    var options = select.options;
    var ret = "";
    for (i = 0 ; i<options.length ; i++)
        ret = ret + options[i].value +'::'+options[i].text+";;";

    return ret;

}
-->

</script>
<?php
Display::display_footer();
