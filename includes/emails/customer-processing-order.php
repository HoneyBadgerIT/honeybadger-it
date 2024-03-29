<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */

global $wpdb;
$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='wc-processing' and enabled=%d",1);
$result=$wpdb->get_row($sql);
$email_subheading="";
if(isset($result->id))
{
    $email_heading=$result->heading;
    $email_subheading=$result->subheading;
    $additional_content=$result->content;

    $email_heading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$email_heading);
    $email_heading=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$email_heading);
    $email_heading=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$email_heading);
    $email_heading=str_ireplace("{order_number}",esc_html( $order->get_id() ),$email_heading);
    $email_heading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$email_heading);
    $email_heading=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$email_heading);

    $email_subheading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$email_subheading);
    $email_subheading=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$email_subheading);
    $email_subheading=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$email_subheading);
    $email_subheading=str_ireplace("{order_number}",esc_html( $order->get_id() ),$email_subheading);
    $email_subheading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$email_subheading);
    $email_subheading=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$email_subheading);

    $additional_content=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$additional_content);
    $additional_content=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$additional_content);
    $additional_content=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$additional_content);
    $additional_content=str_ireplace("{order_number}",esc_html( $order->get_id() ),$additional_content);
    $additional_content=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$additional_content);
    $additional_content=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$additional_content);
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if($email_subheading!="")
    echo wp_kses_post( wpautop( wptexturize($email_subheading)));
else
{
?>
<p><?php printf( esc_html__( 'Hi %s,', 'honeybadger-it' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<?php /* translators: %s: Order number */ ?>
<p><?php printf( esc_html__( 'Just to let you know &mdash; we\'ve received your order #%s, and it is now being processed:', 'honeybadger-it' ), esc_html( $order->get_order_number() ) ); ?></p>

<?php
}
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
