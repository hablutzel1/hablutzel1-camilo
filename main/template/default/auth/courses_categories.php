<?php
/* For licensing terms, see /license.txt */

/**
* View (MVC patter) for courses categories
* @author Christian Fasanando <christian1827@gmail.com> - Beeznest
* @package chamilo.auth
*/

$stok = Security::get_token();
/*
  <?php if ($action != 'createcoursecategory') { ?>
	&nbsp;<a href="<?php echo api_get_self(); ?>?action=createcoursecategory"><?php echo Display::return_icon('new_folder.png', get_lang('CreateCourseCategory'),'','32'); ?></a>
    <?php } ?>
    */
?>
<!-- Actions: The menu with the different options in cathe course management -->

<?php if(intval($_GET['hidden_links']) != 1) { ?>

<div id="actions" class="actions">
    <?php if ($action != 'subscribe') { ?>
        &nbsp;<a href="<?php echo api_get_self(); ?>?action=subscribe"><?php echo Display::return_icon('user_subscribe_course.png', get_lang('SubscribeToCourse'),'','32'); ?></a>
    <?php } ?>

    <?php if ($action != 'sortmycourses' && isset($action)) { ?>
            &nbsp;<a href="<?php echo api_get_self(); ?>?action=sortmycourses"><?php echo Display::return_icon('course_move.png', get_lang('SortMyCourses'),'','32'); ?></a>
    <?php } ?>

        <span id="categories-search">
            <form class="course_list" method="post" action="<?php echo api_get_self(); ?>?action=subscribe&amp;hidden_links=<?php echo $hidden_links; ?>">
            <label for="search_term"><?php echo get_lang('SearchCourse'); ?></label>
                <input type="hidden" name="sec_token" value="<?php echo $stok; ?>">
                <input type="hidden" name="search_course" value="1" />
                <input type="text" size="12" name="search_term" value="<?php echo (empty($_POST['search_term']) ? '' : api_htmlentities(Security::remove_XSS($_POST['search_term']))); ?>" />
                &nbsp;<button class="search" type="submit"><?php echo get_lang('_search'); ?></button>
            </form>
        </span>
</div>

<?php 
    $hidden_links = 0;
} else { 
    $hidden_links = 1; 
} ?>

<div id="categories-content" >
    <div id="categories-content-first">
        <div id="categories-list">

            <?php if (!empty($browse_course_categories)) {

                    // level 1
                    foreach ($browse_course_categories[0] as $category) {
                        $category_name = $category['name'];
                        $category_code = $category['code'];
                        $count_courses_lv1 = $category['count_courses'];

                        if ($code == $category_code) {
                            $category_link = '<strong>'.$category_name.' ('.$count_courses_lv1.')</strong>';
                        } else {
                            $category_link = '<a href="'. api_get_self().'?action=display_courses&amp;category_code='.$category_code.'&amp;hidden_links='.$hidden_links.'">'.$category_name.'</a> ('.$count_courses_lv1.')';
                        }

                        echo '<div>'.$category_link.'</div>';
                        // level 2
                        if (!empty($browse_course_categories[$category_code])) {
                            foreach ($browse_course_categories[$category_code] as $subcategory1) {
                                $subcategory1_name = $subcategory1['name'];
                                $subcategory1_code = $subcategory1['code'];
                                $count_courses_lv2 = $subcategory1['count_courses'];
                                if ($code == $subcategory1_code) {
                                    $subcategory1_link = '<strong>'.$subcategory1_name.' ('.$count_courses_lv2.')</strong>';
                                } else {
                                    $subcategory1_link = '<a href="'. api_get_self().'?action=display_courses&amp;category_code='.$subcategory1_code.'&amp;hidden_links='.$hidden_links.'">'.$subcategory1_name.'</a> ('.$count_courses_lv2.')';
                                }
                                echo '<div style="margin-left:20px;">'.$subcategory1_link.'</div>';
                            }
                            // level 3
                            if (!empty($browse_course_categories[$subcategory1_code])) {
                                foreach ($browse_course_categories[$subcategory1_code] as $subcategory2) {
                                    $subcategory2_name = $subcategory2['name'];
                                    $subcategory2_code = $subcategory2['code'];
                                    $count_courses_lv3 = $subcategory2['count_courses'];
                                    if ($code == $subcategory2_code) {
                                        $subcategory2_link = '<strong>'.$subcategory2_name.' ('.$count_courses_lv3.')</strong>';
                                    } else {
                                        $subcategory2_link = '<a href="'. api_get_self().'?action=display_courses&amp;category_code='.$subcategory2_code.'&amp;hidden_links='.$hidden_links.'">'.$subcategory2_name.'</a> ('.$count_courses_lv3.')';
                                    }
                                    echo '<div style="margin-left:40px;">'.$subcategory2_link.'</div>';
                                }
                            }
                        }
                    }
            } 
            ?>


        </div>

    </div>

    <div id="categories-content-second">

        <?php 
        if (!empty($message)) { Display::display_confirmation_message($message, false); }         
        if (!empty($error)) { Display::display_error_message($error, false); } 

        if (!empty($search_term)) {
            echo "<p><strong>".get_lang('SearchResultsFor')." ".Security::remove_XSS($_POST['search_term'])."</strong><br />";
        }

        if (!empty($browse_courses_in_category)) {

            foreach ($browse_courses_in_category as $course) {

                $title = $course['title'];
                $tutor_name = $course['tutor'];
                $creation_date = substr($course['creation_date'],0,10);
                $count_connections = $course['count_connections'];

                $course_path = api_get_path(SYS_COURSE_PATH).$course['directory'];   // course path

                if (file_exists($course_path.'/course-pic85x85.png')) {
                    $course_web_path = api_get_path(WEB_COURSE_PATH).$course['directory'];   // course web path
                    $course_medium_image = $course_web_path.'/course-pic85x85.png'; // redimensioned image 85x85
                } else {
                    $course_medium_image = api_get_path(WEB_IMG_PATH).'without_picture.png'; // without picture
                }

                echo '<div class="categories-block-course">
                        <div class="categories-content-course">

                            <div class="categories-course-description">
                                <div class="course-block-title">'.strtoupper($title).'</div>
                                <div>'.get_lang('Teacher').'</div><div class="course-block-teacher">'.$tutor_name.'</div>
                                <div>'.get_lang('CreationDate').'</div><div class="course-block-date">'.api_format_date($creation_date,DATE_FORMAT_SHORT).'</div>
                            </div>
                            <div class="categories-course-picture">
                                <img src="'.$course_medium_image.'" />
                            </div>
                            <div class="course-block-popularity"><span>'.get_lang('ConnectionsLastMonth').'</span><div class="course-block-popularity-score">'.$count_connections.'</div></div>

                        </div>
                        <div style="clear:both;"></div>
                        <div class="categories-course-links">';

                        // we display the icon to subscribe or the text already subscribed
                        if (!in_array($course['code'], $user_coursecodes)) {
                            if ($course['subscribe'] == SUBSCRIBE_ALLOWED) {
                                    echo '<span class="course-link-desc"><a href="'. api_get_self().'?action=subscribe_course&amp;sec_token='.$stok.'&amp;subscribe_course='.$course['code'].'&amp;search_term='.$search_term.'&amp;category_code='.$code.'">'.get_lang('Subscribe').'</a></span>';
                            }
                        }
                        if (api_get_setting('show_courses_descriptions_in_catalog') == 'true') {
                            echo '<span class="course-link-desc"><a href="'.api_get_path(WEB_CODE_PATH).'inc/ajax/course_home.ajax.php?a=show_course_information&amp;code='.$course['code'].'" title="'.$icon_title.'" rel="gb_page_center[778]">'.get_lang('CourseDetails').'</a></span>';
                        }

                     echo  '</div>
                    </div>';
            }
        } else {
            echo '<div id="course-message">'.get_lang('ThereAreNoCoursesInThisCategory').'</div>';
        }
        ?>
        <div class="clear"></div>
    </div>
</div>
