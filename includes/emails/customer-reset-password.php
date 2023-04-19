<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wpdb;
$sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='wc-customer-reset-password' and enabled=1";
$result=$wpdb->get_row($sql);
$email_subheading="";
if(isset($result->id))
{
    $email_heading=__($result->heading,"honeyb");
    $email_subheading=__($result->subheading,"honeyb");
    $additional_content=__($result->content,"honeyb");
    $other_subheading_1=__($result->other_subheading_1,"honeyb");

    $reset_link='<a class="link" href="'.esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ).'">'.$other_subheading_1.'</a>';

    $email_heading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$email_heading);
    $email_heading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$email_heading);
    $email_heading=str_ireplace("{reset_password_link}",$reset_link,$email_heading);
    $email_heading=str_ireplace("{username}",esc_html( $user_login ),$email_heading);

    $email_subheading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$email_subheading);
    $email_subheading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$email_subheading);
    $email_subheading=str_ireplace("{reset_password_link}",$reset_link,$email_subheading);
    $email_subheading=str_ireplace("{username}",esc_html( $user_login ),$email_subheading);

    $additional_content=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$additional_content);
    $additional_content=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$additional_content);
    $additional_content=str_ireplace("{reset_password_link}",$reset_link,$additional_content);
    $additional_content=str_ireplace("{username}",esc_html( $user_login ),$additional_content);
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<?php
if($email_subheading!="")
    echo $email_subheading;
else
{
?>
<?php /* translators: %s: Customer username */ ?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_login ) ); ?></p>
<?php /* translators: %s: Store name */ ?>
<p><?php printf( esc_html__( 'Someone has requested a new password for the following account on %s:', 'woocommerce' ), esc_html( wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ) ); ?></p>
<?php /* translators: %s: Customer username */ ?>
<p><?php printf( esc_html__( 'Username: %s', 'woocommerce' ), esc_html( $user_login ) ); ?></p>
<p><?php esc_html_e( 'If you didn\'t make this request, just ignore this email. If you\'d like to proceed:', 'woocommerce' ); ?></p>
<p>
	<a class="link" href="<?php echo esc_url( add_query_arg( array( 'key' => $reset_key, 'id' => $user_id ), wc_get_endpoint_url( 'lost-password', '', wc_get_page_permalink( 'myaccount' ) ) ) ); ?>"><?php // phpcs:ignore ?>
		<?php esc_html_e( 'Click here to reset your password', 'woocommerce' ); ?>
	</a>
</p>

<?php
}
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
