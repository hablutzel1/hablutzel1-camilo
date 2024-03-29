<?php
/* See license terms in /license.txt */

class AppPlugin {
    var $plugin_regions = array ( 
 //           'loginpage_main',
            'login_top',
            'login_bottom',
            'menu_top', 
            'menu_bottom', 
/*            'campushomepage_main', 
            'campushomepage_menu',
            'mycourses_main', 
            'mycourses_menu',*/
            'content_top',
            'content_bottom',
            'header_main',
            'header_center',
            'header_left',
            'header_right',            
            //'footer',
            'footer_left',
            'footer_center',
            'footer_right',
            'course_tool_plugin'
    );
    
    function __construct() {        
    }
        
    function read_plugins_from_path() {
        /* We scan the plugin directory. Each folder is a potential plugin. */
        $pluginpath = api_get_path(SYS_PLUGIN_PATH);
        $possible_plugins = array();
        $handle = @opendir($pluginpath);
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && is_dir(api_get_path(SYS_PLUGIN_PATH).$file)) {
                $possible_plugins[] = $file;
            }
        }
        @closedir($handle);
        sort($possible_plugins);   
        return $possible_plugins;
    }
    
    function get_installed_plugins_by_region(){
        $used_plugins = array();
        /* We retrieve all the active plugins. */    
        $result = api_get_settings('Plugins');    
        foreach ($result as $row) {
            $used_plugins[$row['variable']][] = $row['selected_value'];
        }
        return $used_plugins;
    }
    
    function get_installed_plugins() {
        $installed_plugins = array();
        $plugin_array = api_get_settings_params(array("variable = ? AND selected_value = ? AND category = ? " =>
                                                array('status', 'installed', 'Plugins'))); 
     
        if (!empty($plugin_array)) {
            foreach ($plugin_array as $row) {
                $installed_plugins[$row['subkey']] = true;
            }
            $installed_plugins = array_keys($installed_plugins);
        }
        return $installed_plugins;
    }
    
    function install($plugin_name, $access_url_id = null) {
        if (empty($access_url_id)) {
            $access_url_id = api_get_current_access_url_id();
        } else {
            $access_url_id = intval($access_url_id);
        }
        api_add_setting('installed', 'status', $plugin_name, 'setting', 'Plugins', $plugin_name, null, null, null, $access_url_id, 1);
        
        //api_add_setting($plugin, $area, $plugin, null, 'Plugins', $plugin, null, null, null, $_configuration['access_url'], 1);
        $pluginpath = api_get_path(SYS_PLUGIN_PATH).$plugin_name.'/install.php';
        
        if (is_file($pluginpath) && is_readable($pluginpath)) {
            //execute the install procedure
            require $pluginpath;
        }    
    }
    
    function uninstall($plugin_name, $access_url_id = null) {
        if (empty($access_url_id)) {
            $access_url_id = api_get_current_access_url_id();
        } else {
            $access_url_id = intval($access_url_id);
        }
        api_delete_settings_params(array('category = ? AND access_url = ? AND subkey = ? ' =>
                                   array('Plugins', $access_url_id, $plugin_name)));
        $pluginpath = api_get_path(SYS_PLUGIN_PATH).$plugin_name.'/uninstall.php';
        if (is_file($pluginpath) && is_readable($pluginpath)) {
            //execute the uninstall procedure
            require $pluginpath;
        }
    }
    
    function get_areas_by_plugin($plugin_name) {
        $result = api_get_settings('Plugins');        
        $areas = array();
        foreach ($result as $row) {
            if ($plugin_name == $row['selected_value']) {
                $areas[] = $row['variable'];
            }
        }
        return $areas;
    }
    
    function is_valid_plugin_location($location) {
        return in_array($location, $this->plugin_list);
    }
    
    function is_valid_plugin($plugin_name) {
        if (is_dir(api_get_path(SYS_PLUGIN_PATH).$plugin_name)) {
            if (is_file(api_get_path(SYS_PLUGIN_PATH).$plugin_name.'/index.php')) {
                return true;
            }
        }
        return false;
    }
    
    function get_plugin_regions() {
        sort($this->plugin_regions);
        return $this->plugin_regions;
    }
    
    function load_region($region, $main_template, $forced = false) {
        ob_start();		
        $this->get_all_plugin_contents_by_region($region, $main_template, $forced);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
    
    /**
     * Loads the translation files inside a plugin if exists. It loads by default english see the hello world plugin
     * 
     * @todo add caching
     * @param string $plugin_name 
     */
    function load_plugin_lang_variables($plugin_name) {        
        global $language_interface;
        $root = api_get_path(SYS_PLUGIN_PATH);                                    

        //1. Loading english if exists 
        $english_path = $root.$plugin_name."/lang/english.php";  
        
        if (is_readable($english_path)) {                        
            include $english_path;
            
            foreach ($strings as $key => $string) {                                            
                //$$key = $string;
                $GLOBALS[$key] = $string;  
            }                        
        }

        //2. Loading the system language
        if ($language_interface != 'english') {
            $path = $root.$plugin_name."/lang/$language_interface.php";
            
            if (is_readable($path)) {
                include $path;
                if (!empty($strings)) {                
                    foreach ($strings as $key => $string) {
                        //$$key = $string;
                        $GLOBALS[$key] = $string;                                           
                    }
                }
            }
        }
    }
    
    /** 
     *
     *
     * @param string $block
     * @param obj   template obj
     * @todo improve this function
     */
    function get_all_plugin_contents_by_region($region, $template, $forced = false) {
        global $_plugins;
        if (isset($_plugins[$region]) && is_array($_plugins[$region])) {
        //if (1) {
            //Load the plugin information
            foreach ($_plugins[$region] as $plugin_name) {                
                
                //The plugin_info variable is available inside the plugin index                
                $plugin_info = $this->get_plugin_info($plugin_name, $forced);                
                
                //We also know where the plugin is
                $plugin_info['current_region'] = $region;    
                
                // Loading the plugin/XXX/index.php file                
                $plugin_file = api_get_path(SYS_PLUGIN_PATH)."$plugin_name/index.php";
                
                if (file_exists($plugin_file)) {
                    
                    //Loading the lang variables of the plugin if exists                    
                    self::load_plugin_lang_variables($plugin_name);        
                    
                    //Printing the plugin index.php file
                    require $plugin_file;
                    
                    //If the variable $_template is set we assign those values to be accesible in Twig
                    if (isset($_template)) {                        
                        $_template['plugin_info'] = $plugin_info;
                    } else {
                        $_template = array();
                        $_template['plugin_info'] = $plugin_info;
                    }
                    
                    //Setting the plugin info available in the template if exists      
                    
                    $template->assign($plugin_name, $_template);

                    //Loading the Twig template plugin files if exists
                    $template_list = array();
                    if (isset($plugin_info) && isset($plugin_info['templates'])) {
                        $template_list = $plugin_info['templates'];
                    }           
                    
                    if (!empty($template_list)) {
                        foreach ($template_list as $plugin_tpl) {
                            if (!empty($plugin_tpl)) {
                                //$template_plugin_file = api_get_path(SYS_PLUGIN_PATH)."$plugin_name/$plugin_tpl"; //for smarty
                                $template_plugin_file = "$plugin_name/$plugin_tpl"; // for twig                                
                                $template->display($template_plugin_file);
                            }
                        }                
                    }
                }      
            }
        }
        return true;
    }
    
    /**
     * Loads plugin info 
     * @staticvar array $plugin_data
     * @param string plugin name
     * @param bool load from DB or from the static array
     * @todo filter setting_form
     * @return array 
     */
    function get_plugin_info($plugin_name, $forced = false) {
        static $plugin_data = array();
        if (isset($plugin_data[$plugin_name]) && $forced == false) {            
            return $plugin_data[$plugin_name]; 
        } else {
            $plugin_file = api_get_path(SYS_PLUGIN_PATH)."$plugin_name/plugin.php";
            
            $plugin_info = array();
            if (file_exists($plugin_file)) {
                require $plugin_file;            
            }            
           
            //extra options
            $plugin_settings = api_get_settings_params(array("subkey = ? AND category = ? AND type = ? " =>
                                            array($plugin_name, 'Plugins','setting')));            
            $settings_filtered = array();
            foreach ($plugin_settings as $item) {
                $settings_filtered[$item['variable']] = $item['selected_value'];
            }
            $plugin_info['settings'] = $settings_filtered;        
            $plugin_data[$plugin_name] = $plugin_info;            
            return $plugin_info;
        }
    }
    
    /*
     * Get the template list
     */
    function get_templates_list($plugin_name) {
        $plugin_info = $this->get_plugin_info($plugin_name);        
        if (isset($plugin_info) && isset($plugin_info['templates'])) {
            return $plugin_info['templates'];
        } else {
            return false;
        }
    }
    
    /* *
     * Remove all regions of an specific plugin
     */
    function remove_all_regions($plugin) {
        $access_url_id = api_get_current_access_url_id();
        if (!empty($plugin)) {
            api_delete_settings_params(array('category = ? AND type = ? AND access_url = ? AND subkey = ? ' =>
                                array('Plugins', 'region', $access_url_id, $plugin)));
    
        }
    }
    
    /* 
     * Add a plugin to a region
     */
    function add_to_region($plugin, $region) {
        $access_url_id = api_get_current_access_url_id();
        api_add_setting($plugin, $region, $plugin, 'region', 'Plugins', $plugin, null, null, null, $access_url_id, 1);      
    }
}