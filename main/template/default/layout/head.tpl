<meta charset="{$system_charset}" />
<link href="http://www.chamilo.org/documentation.php" rel="Help" />
<link href="http://www.chamilo.org/team.php" rel="author" />
<link href="http://www.chamilo.org" rel="Copyright" />
{$favico}
{* This fires some HTML5 errors *}
{* <link rel="top"	href="{$_p.web_main}index.php" title="" />
<link rel="courses" href="{$_p.web_main}auth/courses.php" title="{"OtherCourses"|get_lang}"/>
<link rel="profil"  href="{$_p.web_main}auth/profile.php" title="{"ModifyProfile"|get_lang}"/>  *}
<meta name="Generator" content="{$_s.software_name} {$_s.system_version|substr:0:1}" /> 
{* Use the latest engine in ie8/ie9 or use google chrome engine if available *}
<!--[if ie]><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><![endif]-->
{* Improve usability in portal devices*}
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title_string}</title>
<style type="text/css" media="screen">
	/*<![CDATA[*/
	{$css_style}
	/*]]>*/
</style>
<style type="text/css" media="print">
	/*<![CDATA[*/
	{$css_style_print}
	/*]]>*/
</style>
<script type="text/javascript">
//<![CDATA[
// This is a patch for the "__flash__removeCallback" bug, see FS#4378.
{literal}
if ((navigator.userAgent.toLowerCase().indexOf('msie') != -1 ) && ( navigator.userAgent.toLowerCase().indexOf('opera') == -1 )) {
    window.attachEvent( 'onunload', function() {
            window['__flash__removeCallback'] = function ( instance, name ) {
                try {
                    if ( instance ) {
                        instance[name] = null ;
                    }
                } catch ( flashEx ) {
                }
            } ;
    });
}
{/literal}
//]]>

/* Global chat variables */
var ajax_url        = '{$_p.web_ajax}chat.ajax.php';
var online_button   = '{$online_button}';
var offline_button  = '{$offline_button}';
var connect_lang    = '{"ChatConnected"|get_lang}';
var disconnect_lang = '{"ChatDisconnected"|get_lang}';
</script>

{$js_file_to_string}
{$css_file_to_string}
{$extra_headers}

<script type="text/javascript">

$(document).scroll(function() {
    // Top bar scroll effect
    if($('body').width() > 959) {
    if ($('.subnav').length) {
        if (!$('.subnav').attr('data-top')) {
            // If already fixed, then do nothing
            if ($('.subnav').hasClass('subnav-fixed')) return;
            // Remember top position
            var offset = $('.subnav').offset()
            $('.subnav').attr('data-top', offset.top);
        }

        if ($('.subnav').attr('data-top') - $('.subnav').outerHeight() <= $(this).scrollTop())
            $('.subnav').addClass('subnav-fixed');
        else
            $('.subnav').removeClass('subnav-fixed');
        }
    }
});

$(document).ready(function() {

    //Dropdown effect
    $('.dropdown-toggle').dropdown();   
    
    //Responsive effect 
    $(".collapse").collapse();
    
    $('.ajax').on('click', function() {
            var url     = this.href;
            var dialog  = $("#dialog");
            if ($("#dialog").length == 0) {
                    dialog  = $('<div id="dialog" style="display:none"></div>').appendTo('body');
            }

            // load remote content
            dialog.load(
                            url,                    
                            {},
                            function(responseText, textStatus, XMLHttpRequest) {
                                    dialog.dialog({
                                            modal	: true, 
                                            width	: 580, 
                                            height	: 450        
                                    });	                    
            });
            //prevent the browser to follow the link
            return false;
    });
    
    //old jquery.menu.js
    $('#navigation a').stop().animate({
        'marginLeft':'50px'
    },1000);
 
    $('#navigation> li').hover(
        function () {
            $('a',$(this)).stop().animate({
                'marginLeft':'1px'
            },200);
        },
        function () {
            $('a',$(this)).stop().animate({
                'marginLeft':'50px'
            },200);
        }
    );    
    /*
    $(".td_actions").hide();    
    
    $(".td_actions").parent('tr').mouseover(function() {
       $(".td_actions").show();
    });
    
    $(".td_actions").parent('tr').mouseout(function() {
        $(".td_actions").hide();
    });*/
});
</script>
{$header_extra_content}
<!--  head end-->