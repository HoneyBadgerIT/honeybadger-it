<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes the e-mail header.
 *
 * @hooked WC_Emails::email_header() Output the email header
 */

global $wpdb;
$sql=$wpdb->prepare("select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='wc-customer-invoice' and enabled=%d",1);
$result=$wpdb->get_row($sql);
$email_subheading="";
if(isset($result->id))
{
    $email_heading=$result->heading;
    $email_subheading=$result->subheading;
    $additional_content=$result->content;
    $other_heading=$result->other_heading;
    $other_subheading_1=$result->other_subheading_1;
    $other_subheading_2=$result->other_subheading_2;

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

    $other_subheading_2=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$other_subheading_2);
    $other_subheading_2=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$other_subheading_2);
    $other_subheading_2=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$other_subheading_2);
    $other_subheading_2=str_ireplace("{order_number}",esc_html( $order->get_id() ),$other_subheading_2);
    $other_subheading_2=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$other_subheading_2);
    $other_subheading_2=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$other_subheading_2);

    if ( $order->needs_payment() )
    {
        $email_heading=$other_heading;
        $payment_link='<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html($other_subheading_2) . '</a>';
        $other_subheading_1=str_ireplace("{payment_link}", $payment_link, $other_subheading_1);
        $email_subheading=$other_subheading_1;
        $additional_content=str_ireplace("{payment_link}",$payment_link,$additional_content);
    }
}

do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
if($email_subheading!="")
    echo wp_kses_post( wpautop( wptexturize($email_subheading)));
else
{
    ?>
    <?php /* translators: %s: Customer first name */ ?>
    <p><?php printf( esc_html__( 'Hi %s,', 'woocommerce' ), esc_html( $order->get_billing_first_name() ) ); ?></p>

    <?php if ( $order->needs_payment() ) { ?>
    	<p>
    	<?php
    	printf(
    		wp_kses(
    			/* translators: %1$s Site title, %2$s Order pay link */
    			__( 'An order has been created for you on %1$s. Your invoice is below, with a link to make payment when you’re ready: %2$s', 'woocommerce' ),
    			array(
    				'a' => array(
    					'href' => array(),
    				),
    			)
    		),
    		esc_html( get_bloginfo( 'name', 'display' ) ),
    		'<a href="' . esc_url( $order->get_checkout_payment_url() ) . '">' . esc_html__( 'Pay for this order', 'woocommerce' ) . '</a>'
    	);
    	?>
    	</p>

    <?php } else { ?>
    	<p>
    	<?php
    	/* translators: %s Order date */
    	printf( esc_html__( 'Here are the details of your order placed on %s:', 'woocommerce' ), esc_html( wc_format_datetime( $order->get_date_created() ) ) );
    	?>
    	</p>
    	<?php
    }
}
/**
 * Hook for the woocommerce_email_order_details.
 *
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the woocommerce_email_order_meta.
 *
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/**
 * Hook for woocommerce_email_customer_details.
 *
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

/**
 * Executes the email footer.
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
