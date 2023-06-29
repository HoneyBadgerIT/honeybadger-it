<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

require_once HONEYBADGER_PLUGIN_PATH  . 'includes/honeybadger.php';
require_once HONEYBADGER_PLUGIN_PATH . 'constants.php';
$honeybadger=new HoneyBadgerIT\honeybadger;

$default_tab = "honeybadger-it";
$tab = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : $default_tab;
?>
<div class="wrap honeybadger-wrap">
<!-- Print the page title -->
<h1 id="honeybadger_top_row"><a href="<?php echo esc_url("https://".HONEYBADGER_IT_TARGET_SUBDOMAIN.".honeybadger.it/");?>" target="_blank">
    <img src="<?php echo esc_url(plugin_dir_url( __DIR__ )."images/honeybadger_500.png");?>" />
    <br />
    HoneyBadger.IT
    </a></h1>
<!-- Here are our tabs -->
<nav class="nav-tab-wrapper">
  <a href="<?php echo esc_url(admin_url()."admin.php?page=honeybadger-it");?>" class="nav-tab <?php if($tab==='honeybadger-it'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Status','honeyb');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=honeybadger-settings");?>" class="nav-tab <?php if($tab==='honeybadger-settings'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Settings','honeyb');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=honeybadger-rest-api");?>" class="nav-tab <?php if($tab==='honeybadger-rest-api'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('REST API','honeyb');?></a>
  <a href="<?php echo esc_url(admin_url()."admin.php?page=honeybadger-tools");?>" class="nav-tab <?php if($tab==='honeybadger-tools'):?>nav-tab-active<?php endif; ?>"><?php esc_html_e('Tools','honeyb');?></a>
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

