<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

defined( 'ABSPATH' ) || exit;

global $wpdb;
$sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='wc-customer-new-account' and enabled=1";
$result=$wpdb->get_row($sql);
$email_subheading="";
if(isset($result->id))
{
    $email_heading=__($result->heading,"honeyb");
    $email_subheading=__($result->subheading,"honeyb");
    $additional_content=__($result->content,"honeyb");
    $other_subheading_1=__($result->other_subheading_1,"honeyb");

    $email_heading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$email_heading);
    $email_heading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$email_heading);
    $email_heading=str_ireplace("{username}",esc_html( $user_login ),$email_heading);
    $email_heading=str_ireplace("{password}",esc_html( $user_pass ),$email_heading);
    $email_heading=str_ireplace("{my_account_link}", make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ),$email_heading);

    $email_subheading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$email_subheading);
    $email_subheading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$email_subheading);
    $email_subheading=str_ireplace("{username}",esc_html( $user_login ),$email_subheading);
    $email_subheading=str_ireplace("{password}",esc_html( $user_pass ),$email_subheading);
    $email_subheading=str_ireplace("{my_account_link}", make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ),$email_subheading);

    $additional_content=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$additional_content);
    $additional_content=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$additional_content);
    $additional_content=str_ireplace("{username}",esc_html( $user_login ),$additional_content);
    $additional_content=str_ireplace("{password}",esc_html( $user_pass ),$additional_content);
    $additional_content=str_ireplace("{my_account_link}", make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ),$additional_content);

    $other_subheading_1=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{username}",esc_html( $user_login ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{password}",esc_html( $user_pass ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{my_account_link}", make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ),$other_subheading_1);
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>
<?php
if($email_subheading!="")
{
    echo $email_subheading;
    if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated )
        echo $other_subheading_1;
}
else
{
    ?>
    <?php /* translators: %s: Customer username */ ?>
    <p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $user_login ) ); ?></p>
    <?php /* translators: %1$s: Site title, %2$s: Username, %3$s: My account link */ ?>
    <p><?php printf( esc_html__( 'Thanks for creating an account on %1$s. Your username is %2$s. You can access your account area to view orders, change your password, and more at: %3$s', 'woocommerce' ), esc_html( $blogname ), '<strong>' . esc_html( $user_login ) . '</strong>', make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
    <?php if ( 'yes' === get_option( 'woocommerce_registration_generate_password' ) && $password_generated ) : ?>
    	<?php /* translators: %s: Auto generated password */ ?>
    	<p><?php printf( esc_html__( 'Your password has been automatically generated: %s', 'woocommerce' ), '<strong>' . esc_html( $user_pass ) . '</strong>' ); ?></p>
    <?php endif; ?>

    <?php
    /**
     * Show user-defined additional content - this is set in each email's settings.
     */
    if ( $additional_content ) {
    	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    }
}
do_action( 'woocommerce_email_footer', $email );
