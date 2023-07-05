<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header
 */

global $wpdb;
$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='wc-refunded' and enabled=%d",1);
$result=$wpdb->get_row($sql);
$email_subheading="";
if(isset($result->id))
{
    $email_heading=__($result->heading,"honeyb");
    $email_subheading=__($result->subheading,"honeyb");
    $additional_content=__($result->content,"honeyb");
    $other_heading=__($result->other_heading,"honeyb");
    $other_subheading_1=__($result->other_subheading_1,"honeyb");

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

    $other_heading=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$other_heading);
    $other_heading=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$other_heading);
    $other_heading=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$other_heading);
    $other_heading=str_ireplace("{order_number}",esc_html( $order->get_id() ),$other_heading);
    $other_heading=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$other_heading);
    $other_heading=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$other_heading);

    $other_subheading_1=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{order_number}",esc_html( $order->get_id() ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$other_subheading_1);
    $other_subheading_1=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$other_subheading_1);

    if ( $partial_refund )
    {
        $email_heading=$other_heading;
        $email_subheading=$other_subheading_1;
    }
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if($email_subheading!="")
    echo wp_kses_post( wpautop( wptexturize($email_subheading)));
else
{
?>
<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p>
<?php
if ( $partial_refund ) {
	/* translators: %s: Site title */
	printf( esc_html__( 'Your order on %s has been partially refunded. There are more details below for your reference:', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
} else {
	/* translators: %s: Site title */
	printf( esc_html__( 'Your order on %s has been refunded. There are more details below for your reference:', 'woocommerce' ), wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
}
?>
</p>
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
