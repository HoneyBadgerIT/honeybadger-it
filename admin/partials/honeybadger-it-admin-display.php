<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

require_once WP_PLUGIN_DIR  . '/honeybadger-it/includes/honeybadger.php';
require_once WP_PLUGIN_DIR . '/honeybadger-it/constants.php';
$honeybadger=new honeybadger;

//echo bin2hex(random_bytes(16));

$default_tab = "honeybadger-it";
$tab = isset($_GET['page']) ? $_GET['page'] : $default_tab;
?>
<!-- Our admin page content should all be inside .wrap -->
<div class="wrap honeybadger-wrap">
<!-- Print the page title -->
<h1 id="honeybadger_top_row"><a href="https://<?php echo HONEYBADGER_IT_TARGET_SUBDOMAIN;?>.honeybadger.it/" target="_blank">
    <img src="<?php echo plugin_dir_url( __DIR__ );?>images/honeybadger_500.png" />
    <br />
    HoneyBadger.IT
    </a></h1>
<!-- Here are our tabs -->
<nav class="nav-tab-wrapper">
  <a href="?page=honeybadger-it" class="nav-tab <?php if($tab==='honeybadger-it'):?>nav-tab-active<?php endif; ?>"><?php _e('Status','honeyb');?></a>
  <a href="?page=honeybadger-settings" class="nav-tab <?php if($tab==='honeybadger-settings'):?>nav-tab-active<?php endif; ?>"><?php _e('Settings','honeyb');?></a>
  <a href="?page=honeybadger-rest-api" class="nav-tab <?php if($tab==='honeybadger-rest-api'):?>nav-tab-active<?php endif; ?>"><?php _e('REST API','honeyb');?></a>
  <a href="?page=honeybadger-tools" class="nav-tab <?php if($tab==='honeybadger-tools'):?>nav-tab-active<?php endif; ?>"><?php _e('Tools','honeyb');?></a>
</nav>

<div class="tab-content">
<?php switch($tab) :
case 'honeybadger-settings':
    require_once plugin_dir_path(__FILE__)."honeybadger-it-admin-display-settings.php";
    break;
case 'honeybadger-rest-api':
    require_once plugin_dir_path(__FILE__)."honeybadger-it-admin-display-rest.php";
    break;
case 'honeybadger-tools':
    require_once plugin_dir_path(__FILE__)."honeybadger-it-admin-display-tools.php";
    break;
default:
    require_once plugin_dir_path(__FILE__)."honeybadger-it-admin-display-status.php";
    break;
endswitch; ?>
</div>
</div>

