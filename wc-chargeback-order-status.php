<?php
/**
 * Plugin Name:       Chargeback Order Status for Woocommerce
 * Plugin URI:        https://github.com/artes-dev/woocommerce-chargeback-order-status
 * Description:       Adds a custom order status 'Chargeback' to accurately record net sales including chargebacks.
 * Version:           1.0.1
 * Author:            Robert Artes
 * Author URI:        https://github.com/artes-dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woocommerce-chargeback-order-status
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'init', 'wccos_register_chargeback_order_status' );
/**
 * Create the custom Chargeback order status
 *
 * @since 1.0.0
 */
function wccos_register_chargeback_order_status() {
	register_post_status( 'wc-chargeback', array(
		'label'                     => _x( 'Chargeback', 'Order status', 'woocommerce' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Chargebacks <span class="count">(%s)</span>', 'Chargebacks<span class="count">(%s)</span>', 'woocommerce' )
	) );
}

add_filter( 'wc_order_statuses', 'wccos_chargeback_order_statuses' );
/**
 * Add custom status to order_statuses
 *
 * @param $order_statuses
 *
 * @return mixed
 */
function wccos_chargeback_order_statuses( $order_statuses ) {
	$order_statuses['wc-chargeback'] = _x( 'Chargeback', 'Order status', 'woocommerce' );

	return $order_statuses;
}

add_action( 'woocommerce_order_status_chargeback', 'wccos_refund_chargeback' );
/**
 * Record a refund of the full order amount
 *
 * @param $order_id
 *
 * @throws Exception
 */
function wccos_refund_chargeback( $order_id ) {
	$order = wc_get_order( $order_id );

	// Do not change order status after refunding
	add_filter( 'woocommerce_order_fully_refunded_status', '__return_false()' );

	wc_create_refund(
		array(
			'amount'     => $order->get_total(),
			'reason'     => __( 'Manual record of transaction chargeback.', 'woocommerce' ),
			'order_id'   => $order_id,
			'line_items' => array(),
		)
	);

	$order->add_order_note( __( 'Order marked as chargeback. Check payment gateway for details.', 'woocommerce' ) );
}

function wccos_load_admin_style(){
	wp_register_style( 'wccos_admin_css', plugins_url('/woocommerce-chargeback-order-status.css', __FILE__) );
	wp_enqueue_style( 'wccos_admin_css' );
}
add_action('admin_enqueue_scripts', 'wccos_load_admin_style');