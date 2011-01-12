<?php
/* For licensing terms, see /license.txt */
/**
*	Code library for HotPotatoes integration.
*	@package chamilo.exercise
* 	@author
*/

/**
*	QUESTION LIST ADMINISTRATION
*
*	This script allows to manage the question list
*	It is included from the script admin.php
*
*	@author Olivier Brouckaert
*/

// ALLOWED_TO_INCLUDE is defined in admin.php
if(!defined('ALLOWED_TO_INCLUDE')) {
	exit();
}
/*
// moves a question up in the list
if(isset($_GET['moveUp'])) {
	$check = Security::get_token('get');
	if ($check) {
		$objExercise->moveUp(intval($_GET['moveUp']));
		$objExercise->save();
	}
	Security::clear_token();
}

// moves a question down in the list
if(isset($_GET['moveDown'])) {
	$check = Security::get_token('get');
	if ($check) {
		$objExercise->moveDown(intval($_GET['moveDown']));
		$objExercise->save();
	}
	Security::clear_token();
}
*/
// deletes a question from the exercise (not from the data base)
if($deleteQuestion) {

	// if the question exists
	if($objQuestionTmp = Question::read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);

		// if the question has been removed from the exercise
		if($objExercise->removeFromList($deleteQuestion)) {
			$nbrQuestions--;
		}
	}
	// destruction of the Question object
	unset($objQuestionTmp);
}
?>
<style>
    .ui-state-highlight { height: 30px; line-height: 1.2em; }
</style>
<script>
$(function() {
            
        var stop = false;
        $( "#question_list h3" ).click(function( event ) {
            if ( stop ) {
                event.stopImmediatePropagation();
                event.preventDefault();
                stop = false;
            }
        });
        
        $( "#question_list" ) 
        .accordion({         
            autoHeight: false,            
            active: false, // all items closed by default
            collapsible: true,
                header: "> div > h3"
        })
        
        .sortable({
            cursor: "move", // works? 
            update: function(event, ui) {            
                var order = $(this).sortable("serialize") + "&a=update_question_order";
                $.post("<?php echo api_get_path(WEB_AJAX_PATH)?>exercise.ajax.php", order, function(reponse){
                    $("#message").html(reponse);
                });
            	
            },
            axis: "y",
            placeholder: "ui-state-highlight", //defines the yellow highlight
            handle: ".moved", //only the class "moved" 
            stop: function() {
                stop = true;
            }
        });
        
       
});
</script>
<?php


echo '<div class="actionsbig">';
//we filter the type of questions we can add
Question :: display_type_menu ($objExercise->feedbacktype);
echo '</div><div style="clear:both;">';
echo '<div id="message"></div>';
$token = Security::get_token();

if ($nbrQuestions) {
    $my_exercise = new Exercise();
    //forces the query to the database
    $my_exercise->read($_GET['exerciseId']);
	$questionList=$my_exercise->selectQuestionList();    
    
	$i=1;
    
	if (is_array($questionList)) {
        
        echo '<div id="question_list">';
        		
		foreach($questionList as $id) {
			//To avoid warning messages
			if (!is_numeric($id)) {
				continue;
			}	
			$objQuestionTmp = Question :: read($id);
            $question_class = get_class($objQuestionTmp);            
            $label = $question_class->$explanationLangVar;  
            
            
            $edit_link = '<a href="'.api_get_self().'?'.api_get_cidreq().'&type='.$objQuestionTmp->selectType().'&myid=1&editQuestion='.$id.'"><img src="../img/edit.gif" border="0" alt="'.get_lang('Modify').'" /></a>';          
            // this variable  $show_quiz_edition comes from admin.php blocks the exercise/quiz modifications
            if ($show_quiz_edition) {
                 $delete_link = '<a href="'.api_get_self().'?'.api_get_cidreq().'&exerciseId='.$exerciseId.'&deleteQuestion='.$id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'))).' \')) return false;">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a>';                 
            }            
            $actions =  Display::tag('div',$edit_link.$delete_link, array('style'=>'float:right'));

            echo '<div id="question_id_list_'.$id.'" >';                  
            $move = Display::return_icon('move.png',get_lang('Move'), array('class'=>'moved'));            
		    echo Display::tag('h3','<a href="#">'.$move.' '.$objQuestionTmp->selectTitle().'</a>');            
                echo '<div>';
                    echo '<p>';			  	
                        echo $actions;
                        echo get_lang($question_class.$label);
                        echo '<br />';                        
                        echo get_lang('Level').': '.$objQuestionTmp->selectLevel();
                        echo '<br />';                        
                        showQuestion($id, false, '', '',false, true);                   
                    echo '</p>';
                    
                 echo '</div>';
            echo '</div>';
            unset($objQuestionTmp);
		}
        echo '</div>';
	}
}
?>
</table></div>
<table border="0" align="center" cellpadding="2" cellspacing="2" width="100%">
<?php
if(!$i) {
	?>
	<tr>
  	<td><?php echo get_lang('NoQuestion'); ?></td>
	</tr>
<?php
}
?>
</table>