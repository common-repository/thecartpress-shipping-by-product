<?php
/*
Plugin Name: TheCartPress Shipping by Product
Plugin URI: http://extend.thecartpress.com/ecommerce-plugins/shipping-by-product/
Description: Adds a shipping cost by product
Version: 1.1
Author: TheCartPress team
Author URI: http://thecartpress.com
License: GPL
Parent: thecartpress
*/

/**
 * This file is part of TheCartPress-Shipping-By-Product.
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'TCPShippingByProduct' ) ) {

require_once( WP_PLUGIN_DIR . '/thecartpress/classes/TCP_Plugin.class.php' );

class TCPShippingByProduct {

	function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	function init() {
		add_action( 'tcp_product_metabox_custom_fields_after_price'	, array( $this, 'tcp_product_metabox_custom_fields_after_price' ) );
		add_action( 'tcp_product_metabox_save_custom_fields'		, array( $this, 'tcp_product_metabox_save_custom_fields' ) );
		add_action( 'tcp_product_metabox_delete_custom_fields'		, array( $this, 'tcp_product_metabox_delete_custom_fields' ) );

		add_filter( 'tcp_buy_button_column_titles'					, array( $this, 'tcp_buy_button_column_titles' ), 10, 2 );
		add_filter( 'tcp_buy_button_column_values'					, array( $this, 'tcp_buy_button_column_values' ), 10, 2 );

		add_filter( 'tcp_shipping_flat_rate_calculate_by_methods'	, array( $this, 'tcp_shipping_flat_rate_calculate_by_methods' ) );
		add_filter( 'tcp_shipping_flat_rate_calculate_by_script'	, array( $this, 'tcp_shipping_flat_rate_calculate_by_script' ) );
		add_action( 'tcp_shipping_flat_rate_edit_fields'			, array( $this, 'tcp_shipping_flat_rate_edit_fields' ), 10, 2 );
		add_filter( 'tcp_shipping_flat_rate_save_edit_fields'		, array( $this, 'tcp_shipping_flat_rate_save_edit_fields' ) );
		add_filter( 'tcp_shipping_flat_rate_get_cost'				, array( $this, 'tcp_shipping_flat_rate_get_cost' ), 10, 4 );
	}
	
	function tcp_product_metabox_custom_fields_after_price( $post_id ) { ?>
		<tr valign="top">
			<th scope="row">
				<label for="tcp_shipping"><?php _e( 'Shipping cost', 'tcp-shipping-by-product' );?>:</label>
			</th>
			<td>
				<input type="text" min="0" placeholder="<?php tcp_get_number_format_example(); ?>" name="tcp_shipping" id="tcp_shipping" value="<?php echo tcp_number_format( tcp_get_the_shipping_by_product( $post_id ) );?>" class="regular-text" style="width:12em" />&nbsp;<?php tcp_the_currency();?>
			</td>
		</tr><?php
	}

	function tcp_product_metabox_save_custom_fields( $post_id ) {
		$shipping = isset( $_POST['tcp_shipping'] ) ? $_POST['tcp_shipping'] : 0;
		$shipping = tcp_input_number( $shipping );
		update_post_meta( $post_id, 'tcp_shipping', $shipping );
	}

	function tcp_product_metabox_delete_custom_fields( $post_id ) {
		delete_post_meta( $post_id, 'tcp_shipping' );
	}

	function tcp_buy_button_column_titles( $titles, $post_id ) {
		array_splice( $titles, 1, 0, __( 'Shipping', 'tcp-shipping-by-product') );
		return $titles;
	}

	function tcp_buy_button_column_values( $values, $post_id ) {
		$value = array( array(
			'html'		=> tcp_the_shipping_by_product( false ),
			'td_class'	=> 'tcp_shipping_by_product',
		) );
		array_splice( $values, 1, 0, $value );
		return $values;
	}

	function tcp_shipping_flat_rate_calculate_by_methods( $calculate_by_methods ) {
		$calculate_by_methods['product'] = __( 'Product', 'tcp-shipping-by-product' );
		return $calculate_by_methods;
	}

	function tcp_shipping_flat_rate_calculate_by_script( $script ) {
		$script .= 'if ( jQuery(this).val() == \'product\' ) {
			jQuery(\'.tcp_fixed_cost\').hide();
			jQuery(\'.tcp_type\').hide();
			jQuery(\'.tcp_percentage\').hide();
			jQuery(\'.tcp_minimum\').hide();
			jQuery(\'.tcp_maximum\').show();
		} else {
			jQuery(\'.tcp_maximum\').hide();
		}';
		return $script;
	}

	function tcp_shipping_flat_rate_edit_fields( $calculate_by, $data ) {
		if ( $calculate_by == 'product' ) { ?>
		<tr valign="top" class="tcp_maximum" <?php if ( $calculate_by != 'product' ) : ?>style="display: none;"<?php endif; ?>>
			<th scope="row">
				<label for="maximum"><?php _e( 'Maximum', 'tcp-shipping-by-product' );?>:</label>
			</th>
			<td>
				<input type="text" id="maximum" name="maximum" value="<?php echo isset( $data['maximum'] ) ? $data['maximum'] : 0; ?>" size="8" maxlength="8"/><?php tcp_the_currency();?>
				<p class="description"><?php tcp_number_format_example(); ?></p>
			</td>
		</tr>
		<?php }
	}

	function tcp_shipping_flat_rate_save_edit_fields( $data ) {
		$data['maximum'] = isset( $_REQUEST['maximum'] ) ? tcp_input_number( $_REQUEST['maximum'] ) : 0;
		return $data;
	}

	function tcp_shipping_flat_rate_get_cost( $total, $data, $shippingCountry, $shoppingCart ) {
		if ( $data['calculate_by'] == 'product' ) {
			$items = $shoppingCart->getItems();
			$total = 0;
			foreach( $items as $item ) {
				//$new_total = tcp_get_the_shipping_by_product( $item->getPostId() ) * $item->getCount();
				//if ( $new_total > $total ) $total = $new_total;//to get the maximun of all values
				$total += tcp_get_the_shipping_by_product( $item->getPostId() ) * $item->getCount();
			}
		}
		$maximum = isset( $data['maximum'] ) ? $data['maximum'] : 0;
		if ( $maximum > 0 && $total > $maximum ) return $maximum;
		return $total;
	}
}

if ( ! function_exists( 'tcp_get_the_shipping_by_product' ) ) {
	function tcp_get_the_shipping_by_product( $post_id = 0 ) {
		$shipping = (float)tcp_get_the_meta( 'tcp_shipping', $post_id );
		$shipping = (float)apply_filters( 'tcp_get_the_shipping_by_product', $shipping, $post_id );
		return $shipping;
	}
}
if ( ! function_exists( 'tcp_the_shipping_by_product' ) ) {
	function tcp_the_shipping_by_product( $echo = true ) {
		$shipping = tcp_format_the_price( tcp_get_the_shipping_by_product() );
		if ( $echo ) echo $shipping;
		else return $shipping;
	}
}
new TCPShippingByProduct();
} // class_exists check