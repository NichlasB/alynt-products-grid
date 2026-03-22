<?php
/**
 * Shared empty-state partial.
 *
 * @package Alynt_Products_Grid
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$empty_state_title = ! empty( $empty_state_title ) ? $empty_state_title : __( 'No products found.', 'alynt-products-grid' );
?>
<div class="alynt-pg-no-products">
	<p><?php echo esc_html( $empty_state_title ); ?></p>
	<?php if ( ! empty( $empty_state_message ) ) : ?>
		<small><?php echo esc_html( $empty_state_message ); ?></small>
	<?php endif; ?>
</div>
