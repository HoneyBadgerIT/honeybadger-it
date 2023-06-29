<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */
namespace HoneyBadgerIT;
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Email_Default_HoneyBadger' ) && class_exists('\WC_Email')) :

	/**
	 * Customer Completed Order Email.
	 *
	 * Order complete emails are sent to the customer when the order is marked complete and usual indicates that the order has been shipped.
	 *
	 * @class       WC_Email_Customer_Completed_Order
	 * @version     2.0.0
	 * @package     WooCommerce\Classes\Emails
	 * @extends     WC_Email
	 */
	class WC_Email_Default_HoneyBadger extends \WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'wc_custom_order_status_honeybadger';
			$this->customer_email = true;
			$this->title          = __( 'Default email', 'woocommerce' );
			$this->description    = __( 'Used for custom order statuses', 'woocommerce' );
			$this->template_html  = 'emails/email-default.php';
			$this->template_plain = '';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			// Call parent constructor.
			parent::__construct();
		}

		/**
		 * Trigger the sending of this email.
		 *
		 * @param int            $order_id The order ID.
		 * @param WC_Order|false $order Order object.
		 */
		public function trigger( $order_id, $order = false ) {
			$this->setup_locale();

			if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
				$order = wc_get_order( $order_id );
			}

			if ( is_a( $order, 'WC_Order' ) ) {
				$this->object                         = $order;
				$this->recipient                      = $this->object->get_billing_email();
				$this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
				$this->placeholders['{order_number}'] = $this->object->get_order_number();
			}
			if ( $this->is_enabled() && $this->get_recipient() ) {
				$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			}

			$this->restore_locale();
		}

		/**
		 * Get email subject.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_subject() {
			global $wpdb;

			$order_status=isset($_POST['order_status'])?sanitize_text_field($_POST['order_status']):"";
			$order_id=isset($_POST['order_id'])?(int)$_POST['order_id']:0;
			$sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='wc-email-default'";
			if($order_status!="")
			    $sql="select * from ".$wpdb->prefix."honeybadger_wc_emails where wc_status='".esc_sql($order_status)."' and enabled=1";
			if($order_id>0)
			{
				$order=wc_get_order( $order_id );
				$result=$wpdb->get_row($sql);
				if(isset($result->subject))
				{
					$subject=$result->subject;
			        $subject=str_ireplace("{site_title}",esc_html( get_bloginfo( 'name', 'display' ) ),$subject);
			        $subject=str_ireplace("{customer}",esc_html( $order->get_billing_first_name() ),$subject);
			        $subject=str_ireplace("{customer_full_name}",esc_html( $order->get_formatted_billing_full_name() ),$subject);
			        $subject=str_ireplace("{order_number}",esc_html( $order->get_id() ),$subject);
			        $subject=str_ireplace("{site_url}",esc_html( get_bloginfo( 'url', 'display' ) ),$subject);
			        $subject=str_ireplace("{order_date}",esc_html( wc_format_datetime( $order->get_date_created() ) ),$subject);
			        return $subject;
		    	}
	    	}
			return __( 'Something went wrong', 'woocommerce' );
		}

		/**
		 * Get email heading.
		 *
		 * @since  3.1.0
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'You should not see this', 'woocommerce' );
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				)
			);
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				)
			);
		}

		/**
		 * Default content to show below main email content.
		 *
		 * @since 3.7.0
		 * @return string
		 */
		public function get_default_additional_content() {
			return __( 'Thanks for shopping with us.', 'woocommerce' );
		}
	}

endif;