<?php

//Set the time limit for the tests
ini_set('memory_limit','256M');

$maindir = dirname(__FILE__).'/../main/';
$libdir = dirname(__FILE__).'/../main/inc/lib/';


$incdir = dirname(__FILE__).'/../main/inc/';
//Need the ob start and clean else will show the objects 
require_once $incdir.'global.inc.php';

//List of files than need the tests
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/web_tester.php';
require_once 'simpletest/mock_objects.php';
require_once 'simpletest/autorun.php';
require_once $libdir.'urlmanager.lib.php';
require_once $libdir.'fileDisplay.lib.php';
require_once $maindir.'permissions/permissions_functions.inc.php';
require_once $libdir.'groupmanager.lib.php';
require_once $libdir.'course.lib.php';
require_once $libdir.'usermanager.lib.php';
require_once $libdir.'social.lib.php';
require_once $libdir.'xht.lib.php';
require_once $libdir.'xmd.lib.php';
require_once $libdir.'formvalidator/FormValidator.class.php';


//Need the ob start and clean else will show the objects 

ob_start();
require_once $incdir.'lib/course_document.lib.php';
require_once $incdir.'lib/banner.lib.php';
require_once $incdir.'lib/add_course.lib.inc.php';
require_once $incdir.'tool_navigation_menu.inc.php';
require_once $incdir.'banner.inc.php';
ob_end_clean();

//List of files than need the tests since chamilo
require_once $maindir.'admin/calendar.lib.php';
require_once $maindir.'admin/statistics/statistics.lib.php';
require_once $incdir.'lib/usermanager.lib.php';
require_once $maindir.'survey/survey.lib.php';
require_once $maindir.'install/install_upgrade.lib.php';
require_once $incdir.'lib/fileManage.lib.php';

class AllTestsSuite extends TestSuite {
	function setUp() {
		global $_configuration, $_user, $_course, $cidReq;
		$cidReq = 'COURSEX';
        // check if course exists 
        $table_course = Database::get_main_table(TABLE_MAIN_COURSE);
        $sql = "SELECT code FROM  $table_course WHERE code = '$cidReq' ";
        $rs = Database::query($sql, __FILE__, __LINE__);
        $row = Database::fetch_row($rs);
        if (empty($row[0])) {
        
	        // create a course
	        $course_datos = array(
	                'wanted_code'=> $cidReq,
	                'title'=>$cidReq,
	                'tutor_name'=>'John Doe',
	                'category_code'=>'LANG',
	                'course_language'=>'spanish',
	                'course_admin_id'=>'001',
	                'db_prefix'=> $_configuration['db_prefix'],
	                'firstExpirationDelay'=>'999'
	                );
	        $res = create_course($course_datos['wanted_code'], $course_datos['title'],
	                             $course_datos['tutor_name'], $course_datos['category_code'],
	                             $course_datos['course_language'],$course_datos['course_admin_id'],
	                             $course_datos['db_prefix'], $course_datos['firstExpirationDelay']);
	    }
	
	    $course_table = Database::get_main_table(TABLE_MAIN_COURSE);
	    $course_cat_table = Database::get_main_table(TABLE_MAIN_CATEGORY);
	    $sql =    "SELECT course.*, course_category.code faCode, course_category.name faName
	             FROM $course_table
	             LEFT JOIN $course_cat_table
	             ON course.category_code = course_category.code
	             WHERE course.code = '$cidReq'";
	    $result = Database::query($sql,__FILE__,__LINE__);
	
	    //create the session
	    
	    if (Database::num_rows($result)>0) {
	        $cData = Database::fetch_array($result);
	        $_cid                            = $cData['code'             ];
	        $_course = array();
	        $_course['id'          ]         = $cData['code'             ]; //auto-assigned integer
	        $_course['name'        ]         = $cData['title'         ];
	        $_course['official_code']         = $cData['visual_code'        ]; // use in echo
	        $_course['sysCode'     ]         = $cData['code'             ]; // use as key in db
	        $_course['path'        ]         = $cData['directory']; // use as key in path
	        $_course['dbName'      ]         = $cData['db_name'           ]; // use as key in db list
	        $_course['dbNameGlu'   ]         = $_configuration['table_prefix'] . $cData['db_name'] . $_configuration['db_glue']; // use in all queries
	        $_course['titular'     ]         = $cData['tutor_name'       ];
	        $_course['language'    ]         = $cData['course_language'   ];
	        $_course['extLink'     ]['url' ] = $cData['department_url'    ];
	        $_course['extLink'     ]['name'] = $cData['department_name'];
	        $_course['categoryCode']         = $cData['faCode'           ];
	        $_course['categoryName']         = $cData['faName'           ];
	        $_course['visibility'  ]         = $cData['visibility'];
	        $_course['subscribe_allowed']    = $cData['subscribe'];
	        $_course['unubscribe_allowed']   = $cData['unsubscribe'];
	
	        api_session_register('_cid');
	        api_session_register('_course');
	    }
	         
	    $_SESSION['_user']['user_id'] = 1;    
	    $_user['user_id'] = $_SESSION['_user']['user_id'];
	    $_SESSION['is_courseAdmin'] = 1;
		
	}
    function AllTestsSuite() {
    	$this->setUp();
    	$this->TestSuite('All tests suite');

		//List of files from all.test.php

/*		//funciones corregidas por Ricardo
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/add_course.lib.inc.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/blog.lib.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/classmanager.lib.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/course.lib.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/database.lib.test.php'); 
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/debug.lib.inc.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/display.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/document.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/events.lib.inc.test.php');

		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileDisplay.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileUpload.lib.test.php');
		
		
		// problemas con el envio de cabeceras -  faltan corregir estas funciones
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/export.lib.inc.test.php'); //20 excepciones en testExportTableCsv, testExportTableXls, testExportTableXml, testExportComplexTableXml.
	    //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/fileManage.lib.test.php'); // 1excepcion testSetDefaultSettings
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/geometry.lib.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/groupmanager.lib.test.php');	 
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/image.lib.test.php'); 
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/import.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/internationalization.lib.test.php');  
 	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/legal.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/mail.lib.inc.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/main_api.lib.test.php');
   	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/message.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/online.inc.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/lib/security.lib.test.php');
        //carlos*/
         
		
        
	   //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/stats.lib.inc.test.php'); 
	   //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/surveymanager.lib.test.php');
	   //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/system_announcements.lib.test.php');
  
	    
        //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/usermanager.lib.test.php'); 
	    //$this->addTestFile(dirname(__FILE__).'/main/inc/lib/xht.lib.test.php');
	    $this->addTestFile(dirname(__FILE__).'/main/inc/lib/xmd.lib.test.php');







		//Funciones probadas por carlos
		
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tracking.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/urlmanager.lib.test.php');
		
		
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/session_handler.class.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/sessionmanager.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/specific_fields_manager.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/social.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/sortabletable.class.test.php'); 
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/statsUtils.lib.inc.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/tablesort.lib.test.php');
		$this->addTestFile(dirname(__FILE__).'/main/inc/lib/text.lib.test.php'); /*
		
		//Funciones corregidas por Arthur
		//List of files from all.test1.php
	/*	$this->addTestFile(dirname(__FILE__).'/main/inc/banner.lib.test.php');       
        $this->addTestFile(dirname(__FILE__).'/main/inc/course_document.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/inc/tool_navigation_menu.inc.test.php');
		//List of files from all.test2.php
    	$this->addTestFile(dirname(__FILE__).'/main/admin/calendar.lib.test.php');
    	$this->addTestFile(dirname(__FILE__).'/main/admin/statistics/statistics.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/lost_password.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/auth/openid/xrds.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/chat/chat_functions.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/conference/get_translation.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/exercice/hotpotatoes.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/newscorm/scorm.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/survey/survey.lib.test.php');
        $this->addTestFile(dirname(__FILE__).'/main/user/userInfoLib.test.php');        
        $this->addTestFile(dirname(__FILE__).'/main/webservices/user_import/import.lib.test.php');        
        $this->addTestFile(dirname(__FILE__).'/main/work/work.lib.test.php');       
        $this->addTestFile(dirname(__FILE__).'/main/install/install_upgrade.lib.test.php');
        //List of files from all.test3.php
        $this->addTestFile(dirname(__FILE__).'/main/admin/sub_language.class.test.php');
*/
    }
}
$test = &new AllTestsSuite();



?>