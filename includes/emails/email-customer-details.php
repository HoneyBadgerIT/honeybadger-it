<?php
/**
 * @package    Honeybadger_IT
 * @subpackage Honeybadger_IT/admin
 * @author     Claudiu Maftei <claudiu@honeybadger.it>
 */

defined( 'ABSPATH' ) || exit;
?>
<?php 
if ( ! empty( $fields ) ) : ?>
	<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
		<h2><?php esc_html_e( 'Customer details', 'woocommerce' ); ?></h2>
		<ul>
			<?php foreach ( $fields as $field ) : ?>
				<li><strong><?php echo wp_kses_post( $field['label'] ); ?>:</strong> <span class="text"><?php echo wp_kses_post( $field['value'] ); ?></span></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>
