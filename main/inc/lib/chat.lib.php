<?php
/* For licensing terms, see /license.txt */
/**
*	This is the array library for Chamilo.
*	Include/require it in your code to use its functionality.
*
*	@package chamilo.library
*/

class Chat extends Model {
	
	var $table;
    var $columns = array('id', 'from_user','to_user','message','sent','recd');	
	var $window_list = array();
        
	public function __construct() {
        $this->table =  Database::get_main_table(TABLE_MAIN_CHAT);
		$this->window_list = $_SESSION['window_list'] = isset($_SESSION['window_list']) ? $_SESSION['window_list'] : array();
	}    
    
    /**
     * Get user chat status
     * @return type 
     */
    function get_user_status() {
        $status = UserManager::get_extra_user_data_by_field(api_get_user_id(), 'user_chat_status', false, true);
        return $status['user_chat_status'];
    }
    
    /*
     * Set user chat status
     */
    function set_user_status($status) {
        UserManager::update_extra_field_value(api_get_user_id(), 'user_chat_status', $status);
    }
	
    /* 
     * Starts a chat session
     */
	public function start_session() {				
		$items = array();		
		if (isset($_SESSION['chatHistory'])) {
			$items = $_SESSION['chatHistory'];
		}             
        //print_r($items);
		$return = array('user_status' => $this->get_user_status(), 'me' => get_lang('Me'), 'items' => $items);		
		echo json_encode($return);           
		exit;
	}
    
	public function heartbeat() {		
		$to_user_id	= api_get_user_id();				
		$minutes    = 60;
		$now        = time() - $minutes*60;
		$now        = api_get_utc_datetime($now);	
		
		//OR  sent > '$now'
		$sql = "SELECT * FROM ".$this->table." 
                WHERE to_user = '".intval($to_user_id)."' AND ( recd  = 0 ) ORDER BY id ASC";
		$result = Database::query($sql);
		
		$chat_list = array();
		
		while ($chat = Database::fetch_array($result,'ASSOC')) {	
			$chat_list[$chat['from_user']]['items'][] = $chat;
		}		        
        
		$items = array();
		
		foreach ($chat_list as $from_user_id => $rows) {
			$rows = $rows['items'];
			$user_info = api_get_user_info($from_user_id, true);
			
			//Cleaning tsChatBoxes
			unset($_SESSION['tsChatBoxes'][$from_user_id]);
			
			foreach ($rows as $chat) {
				$chat['message'] = Security::remove_XSS($chat['message']);
				
				$item = array(	's'			=> '0', 
								'f'			=> $from_user_id, 
								'm'			=> $chat['message'], 								
								'username'	=> $user_info['complete_name'],							
								'id'		=> $chat['id']								
							);
				$items[$from_user_id]['items'][] = $item;				
                $items[$from_user_id]['user_info']['user_name'] = $user_info['complete_name'];
                $items[$from_user_id]['user_info']['online'] = $user_info['user_is_online'];
				$_SESSION['openChatBoxes'][$from_user_id] = api_strtotime($chat['sent'],'UTC');				
			}
			$_SESSION['chatHistory'][$from_user_id]['items'][] = $item;					
            $_SESSION['chatHistory'][$from_user_id]['user_info']['user_name'] = $user_info['complete_name'];	
            $_SESSION['chatHistory'][$from_user_id]['user_info']['online'] = $user_info['user_is_online'];            
		}
		
		if (!empty($_SESSION['openChatBoxes'])) {
			foreach ($_SESSION['openChatBoxes'] as $user_id => $time) {
				if (!isset($_SESSION['tsChatBoxes'][$user_id])) {
					$now = time() - $time;
					$time = api_convert_and_format_date($time, DATE_TIME_FORMAT_SHORT_TIME_FIRST);
					$message = sprintf(get_lang('SentAtX'), $time);
					
					if ($now > 180) {		
						$item = array('s' => '2', 'f' => $user_id, 'm' => $message);			
						
						if (isset($_SESSION['chatHistory'][$user_id])) {
							$_SESSION['chatHistory'][$user_id]['items'][] = $item;					                            			
						}						
						$_SESSION['tsChatBoxes'][$user_id] = 1;
					}
				}
			}
		}
        
        //print_r($_SESSION['chatHistory']);
        
		/*
		var_dump($_SESSION['openChatBoxes']);
		var_dump($_SESSION['tsChatBoxes']);
		var_dump($_SESSION['chatHistory']);
		var_dump($items);
		*/
		//print_r($_SESSION['chatHistory']);
		$sql = "UPDATE ".$this->table." SET recd = 1 WHERE to_user = '".$to_user_id."' AND recd = 0";
		Database::query($sql);
		
		if ($items != '') {
			//$items = substr($items, 0, -1);
		}		
		echo json_encode(array('items' => $items));
	}
	
	/* 
	 * chatBoxSession
	 */
	function box_session($user_id) {
		$items = array();
		if (isset($_SESSION['chatHistory'][$user_id])) {
			$items = $_SESSION['chatHistory'][$user_id];
		}
		return $items;
	}
	
	function save_window($user_id){
		$this->window_list[$user_id] = true; 
		$_SESSION['window_list']  = $this->window_list;
	}
	
	function send($from_user_id, $to_user_id, $message) {
        $user_info = api_get_user_info($to_user_id, true);
		$this->save_window($to_user_id);
	
		$_SESSION['openChatBoxes'][$to_user_id] = api_get_utc_datetime();
		$messagesan = self::sanitize($message);

		if (!isset($_SESSION['chatHistory'][$to_user_id])) {
			$_SESSION['chatHistory'][$to_user_id] = array();
		}
		$item = array (	"s"			=> "1", 
						"f"			=> $from_user_id,
						"m"			=> $messagesan,
						"username"	=> get_lang('Me')
					);
		$_SESSION['chatHistory'][$to_user_id]['items'][] = $item;	
        $_SESSION['chatHistory'][$to_user_id]['user_info']['user_name'] = $user_info['complete_name'];	
        $_SESSION['chatHistory'][$to_user_id]['user_info']['online'] = $user_info['user_is_online'];	
				
		unset($_SESSION['tsChatBoxes'][$to_user_id]);
		
		$params = array();
		$params['from_user']	= intval($from_user_id);
		$params['to_user']		= intval($to_user_id);
		$params['message']		= $message;
		$params['sent']			= api_get_utc_datetime();
		
		if (!empty($from_user_id) && !empty($to_user_id)) {		
			$this->save($params);
		}
        //print_r($_SESSION['chatHistory']);
		echo "1";
		exit;
	}

	function close() {
		unset($_SESSION['openChatBoxes'][$_POST['chatbox']]);
		unset($_SESSION['chatHistory'][$_POST['chatbox']]);		
		echo "1";
		exit;
	}

	function sanitize($text) {
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = str_replace("\n\r","\n",$text);
		$text = str_replace("\r\n","\n",$text);
		$text = str_replace("\n","<br>",$text);
		return $text;
	}
}