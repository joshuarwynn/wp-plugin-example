<?php
/*
Plugin Name: RUL Products
Plugin URI: https://www.riseuplocal.com/
Description: Custom code for RUL Products infrastructure.
Version: 0.12.4
Author: Rise Up Local
Author URI: https://www.riseuplocal.com/
*/

// Generate the signed in/out menu item
function rul_products_get_nav_menu_items( $items, $menu ){
	// Only add item to Main Navigation menu
	if ( $menu->slug == 'main-navigation' ) {
		// Only add user menu item dropdown if user is logged in, else just show 'Sign In'
		if ( get_current_user_id() ) {
			$current_user = wp_get_current_user();

			// Establish top-level menu item
			$user_menu_top = new stdClass();
			$user_menu_top->url = '/account/';
			$user_menu_top->order = 100;
			$user_menu_top->classes = ['button_solid_color'];
			$user_menu_top->parent = 0;

			$top = _rul_products_custom_nav_menu_item( 'Welcome ' . $current_user->data->user_login . '!', $user_menu_top );

			// Establish 'My Account' submenu details
			$user_menu_account = new stdClass();
			$user_menu_account->url = '/account/';
			$user_menu_account->order = 101;
			$user_menu_account->classes = [];
			$user_menu_account->parent = $top->ID;

			// Establish 'Sign Out' submenu details
			$user_menu_signout = new stdClass();
			$user_menu_signout->url = '/account/customer-logout/';
			$user_menu_signout->order = 102;
			$user_menu_signout->classes = [];
			$user_menu_signout->parent = $top->ID;

			// Push all the menu items to items array
			$items[] = $top;
			$items[] = _rul_products_custom_nav_menu_item( 'My Account', $user_menu_account );
			$items[] = _rul_products_custom_nav_menu_item( 'Log Out', $user_menu_signout );
		} else {
			$user_menu_top = new stdClass();
			$user_menu_top->url = '/account/';
			$user_menu_top->order = 100;
			$user_menu_top->classes = ['button_solid_color'];
			$user_menu_top->parent = 0;
			$items[] = _rul_products_custom_nav_menu_item( 'Log In', $user_menu_top );
		}
	}

	return $items;
}
add_filter( 'wp_get_nav_menu_items', 'rul_products_get_nav_menu_items', 10, 2 );

// Helper function to create menu item objects
function _rul_products_custom_nav_menu_item( $title, $menu_details ) {
	$item = new stdClass();
	$item->ID = 1000000 + $menu_details->order + $menu_details->parent;
	$item->db_id = $item->ID;
	$item->title = $title;
	$item->url = $menu_details->url;
	$item->menu_order = $menu_details->order;
	$item->menu_item_parent = $menu_details->parent;
	$item->type = 'custom';
	$item->type_label = 'IN CODE DO NOT MODIFY HERE';
	$item->object = '';
	$item->object_id = '';
	$item->classes = $menu_details->classes;
	$item->target = '';
	$item->attr_title = '';
	$item->description = '';
	$item->xfn = '';
	$item->status = '';
	return $item;
}

// WooCommerce -- Skip the cart and redirect to checkout URL when clicking on add to cart
// TODO Make this redirect a 301 instead of a 302
function rul_products_add_to_cart_redirect() {
	global $woocommerce;

	// Remove the default `Added to cart` message
	wc_clear_notices();
	return $woocommerce->cart->get_checkout_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'rul_products_add_to_cart_redirect' );

// Set redirection rules
function rul_products_template_redirect() {
	if ( is_page('home') ) {
		// Temporarily redirect /home to /account until WooCommerce account
		// routes can be set to the 'Front page' under Settings->Reading
		// TODO remove this once WooCommerce fixes this issue
		wp_redirect( '/account/', '301' );
		exit;
	} else if ( is_cart() ) {
		// Redirect to /checkout when hitting /cart to mimic direct checkout
		global $woocommerce;
		wp_redirect( $woocommerce->cart->get_checkout_url(), '301' );
		exit;
	} else {
		return;
	}
}
add_action( 'template_redirect', 'rul_products_template_redirect' );

// WooCommerce -- Empty cart each time you click on add cart to avoid multiple element selected
function rul_products_add_cart_item_data () {
	global $woocommerce;
	$woocommerce->cart->empty_cart();
}
add_action( 'woocommerce_add_cart_item_data', 'rul_products_add_cart_item_data', 0 );

// WooCommerce -- Edit default add_to_cart button text
function rul_products_product_single_add_to_cart_text() {
	return __( 'Start Learning', 'rul-products' );
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'rul_products_product_single_add_to_cart_text' );

// WooCommerce -- Alter fields from checkout
function rul_products_checkout_fields( $fields ) {
	// Remove Order Notes field
	unset( $fields['order']['order_comments'] );

	// Remove Phone from Billing Details
	unset( $fields['billing']['billing_phone'] );

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'rul_products_checkout_fields' );

// WooCommerce -- Removes Order Notes title - Additional Information
add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );

// WooCommerce -- Change thank you text at the top of the order received details page
function rul_products_thankyou_order_received_text( $text, $order ) {
	// TODO Conditional messaging based on product purchased.
	// Need to iterate over $items to get product details
	// $order_details = new WC_Order( $order->id );
	// $items = $order_details->get_items();

	// Not wrapping this in a translate function for now...
	$text = 'You are steps away from becoming a local SEO master! Go straight to the <a href="/seo-local-business/">course</a> or visit <a href="/account">your account dashboard</a> to get started!';
	return $text;
}
add_filter( 'woocommerce_thankyou_order_received_text', 'rul_products_thankyou_order_received_text', 10, 2 );

// WooCommerce -- Remove password strength meter
function rul_products_print_scripts() {
	wp_dequeue_script( 'wc-password-strength-meter' );
}
add_action( 'wp_print_scripts', 'rul_products_print_scripts', 100 );

// WooCommerce -- Modify the product quantity line in the order review area of checkout
function rul_products_checkout_cart_item_quantity( $cart_item, $cart_item_key ) {
	// If there is only one item in the cart, hide the "Quantity: 1" line
	if ( $cart_item_key['quantity'] === 1 ) {
		$cart_item = '<span class="product-quantity"></span>';
	}

	return $cart_item;
}
add_filter( 'woocommerce_checkout_cart_item_quantity', 'rul_products_checkout_cart_item_quantity', 10, 2 );

// WooCommerce -- Alter the quantity markup (i.e. Membership x 1) in the Order Details table
// TODO Conditionally remove the quantity markup (if needed)
function rul_products_order_item_quantity_html( $markup, $item ) {
	// Remove 'x <quantity integer>'
	$markup = ' <strong class="product-quantity"></strong>';
	return $markup;
}
add_filter( 'woocommerce_order_item_quantity_html', 'rul_products_order_item_quantity_html', 2, 10 );

// WooCommerce -- Alter the product permalink in the Order Details table
function rul_products_order_item_name( $product_permalink, $item ) {
	// List of product slugs that are available
	$product_slugs = [
		'seo-for-local-business-beta',
		'seo-local-business-otp-1',
		'seo-local-business-pp-1',
	];

	// Loop through the list of product slugs array and check if any slug is present in product permalink markup
	// Then override the markup with a direct link to the course root page
	foreach ( $product_slugs as $product_slug ) {
		$product_slug_found = strpos($product_permalink, $product_slug);
		if ( $product_slug_found !== false ) {
			$product_permalink = '<a href="' . get_site_url() . '/seo-local-business/">' . $item['name'] . '</a>';
			break;
		}
	}

	return $product_permalink;
}
add_filter( 'woocommerce_order_item_name', 'rul_products_order_item_name', 10, 2 );

// WooCommerce -- Automatically set orders to complete if it's a virtual product
function rul_products_payment_complete_order_status( $order_status, $order_id ) {
	$order = new WC_Order( $order_id );

	if ( 'processing' == $order_status && ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {
		$virtual_order = null;

		if ( count( $order->get_items() ) > 0 ) {
			foreach( $order->get_items() as $item ) {
				if ( 'line_item' == $item['type'] ) {
					$_product = $order->get_product_from_item( $item );

					if ( ! $_product->is_virtual() ) {
						// once we've found one non-virtual product we know we're done, break out of the loop
						$virtual_order = false;
						break;
					} else {
						$virtual_order = true;
					}
				}
			}
		}

		// virtual order, mark as completed
		if ( $virtual_order ) {
			return 'completed';
		}
	}

	// non-virtual order, return original status
	return $order_status;
}
add_filter( 'woocommerce_payment_complete_order_status', 'rul_products_payment_complete_order_status', 10, 2 );

// WooCommerce -- Override WooCommerce function to hide the "Order Again" button from below Order Details table
if ( ! function_exists( 'woocommerce_order_again_button' ) ) {
	function woocommerce_order_again_button( $order ) {
		return;
	}
}

// WooCommerce -- Remove subtotal row from Order Details table
function rul_products_get_order_item_totals( $rows, $order ) {
	unset( $rows['cart_subtotal'] );
	return $rows;
}
add_filter( 'woocommerce_get_order_item_totals', 'rul_products_get_order_item_totals', 10, 2 );

// WooCommerce -- Programmatically unset all options related to the cart (WooCommerce Settings->Products->Display)
update_option( 'woocommerce_cart_redirect_after_add', 'no' );
update_option( 'woocommerce_enable_ajax_add_to_cart', 'no' );

// WooCommerce Memberships -- Rename dashboard area title 'My Memberships' to 'My Courses'
function rul_products_my_memberships_title( $title ) {
	return __( 'My Courses', 'rul-products' );
}
add_filter( 'wc_memberships_my_memberships_title', 'rul_products_my_memberships_title' );

// WooCommerce Memberships -- Rename Members Area section 'My Content' to 'My Learning Material'
function rul_products_plan_members_area_sections( $sections ) {
	$sections['my-membership-content'] = __( 'My Learning Material', 'rul-products' );
	return $sections;
}
add_filter( 'wc_membership_plan_members_area_sections', 'rul_products_plan_members_area_sections' );

// WooCommerce Memberships -- Rename Members Area title 'My Content' to 'My Learning Material'
function rul_products_members_area_my_membership_content_title( $title ) {
	return __( 'My Learning Material', 'rul-products' );
}
add_filter( 'wc_memberships_members_area_my_membership_content_title', 'rul_products_members_area_my_membership_content_title' );

// WooCommerce Memberships -- Remove columns and change column names on My Account dashboard
function rul_products_my_memberships_column_names( $columns, $user_id ) {
	// Remove 'Start' column
	unset( $columns['membership-start-date'] );

	// Remove 'Expires' column
	unset( $columns['membership-end-date'] );

	// Remove 'Status' column
	unset( $columns['membership-status'] );

	// Remove 'Next Bill On' column. Extra security check given it's poor array splice approach (or bug).
	if ( isset( $columns[0] ) && $columns[0] === 'Next Bill On' ) {
		unset( $columns[0] );
	}

	// Rename 'Plan' column to 'Course'
	$columns['membership-plan'] = __( 'Course', 'rul-products' );

	// Add 'Actions' column name (empty before)
	$columns['membership-actions'] = __( 'Actions', 'rul-products' );

	return $columns;
}
// Order param is set to 30 to catch the changes that WooCommerce Subscriptions is injecting after the fact
add_filter( 'wc_memberships_my_memberships_column_names', 'rul_products_my_memberships_column_names', 30, 2 );

// WooCommerce Memberships -- Conditionally modify actions on My Account dashboard based on membership IDs
function rul_products_members_area_my_memberships_actions( $actions, $membership, $object ) {
	if ( $membership->status !== 'wcm-cancelled' ) {
		switch ( $membership->plan_id ) {
			// SEO for Small Business course memberships (unlimited duration)
			case 21:
				// Modify URL of 'view' action to go straight to course URL based on membership ID
				$actions['view']['url'] = '/seo-local-business/';

				// Rename button 'View' to 'Go To Course'
				$actions['view']['name'] = __( 'Go To Course', 'rul-products' );

				// Remove button 'Cancel' (since this is an unlimited membership)
				unset( $actions['cancel'] );

				break;
		}
	}

	return $actions;
}
// See https://developer.wordpress.org/reference/functions/add_filter/#parameters for the 10 and 3 function parameters
add_filter( 'wc_memberships_members_area_my-memberships_actions', 'rul_products_members_area_my_memberships_actions', 10, 3 );

// WooCommerce Subscriptions -- Change the thank you messaging at the top of the order details for a subscription purchase
function rul_products_thank_you_message( $thank_you_message, $order_id ) {
	// Completely remove the thank you markup
	$thank_you_message = '';

	return $thank_you_message;
}
add_filter( 'woocommerce_subscriptions_thank_you_message', 'rul_products_thank_you_message', 10, 2);

// WooCommerce Subscriptions -- Change "First renewal" to "Next payment" on checkout page for payment plan purchase
function rul_products_cart_totals_order_total_html ( $order_total_html, $cart ) {
	// TODO utilize the $cart object to key off the product metadata for future conditional changes to the $order_total_html value

	// Simple search of 'First renewal' to be replaced with 'Next payment'
	$order_total_html = str_replace( 'First renewal', 'Next payment', $order_total_html );

	return $order_total_html;
}
// Order param is set to 20 to catch the changes that WooCommerce Subscriptions is injecting after the fact
add_filter( 'wcs_cart_totals_order_total_html', 'rul_products_cart_totals_order_total_html', 20, 2 );

// WooCommerce Subscriptions -- Modify the subscription renewal email subject line
function rul_products_email_subject_customer_completed_renewal_order( $subject, $order ) {
	// TODO Conditionally change this by keying off $order object
	// Get a properly formatted date from the WooCommerce $order object passed in
	$order_date = wcs_format_datetime( wcs_get_objects_property( $order, 'date_created' ) );

	// Modify the email subject line
	$subject = sprintf( _x( 'Your %1$s payment plan installment for %2$s is complete', 'Default email subject for email to customer on completed payment plan installment order', 'woocommerce-subscriptions' ), get_bloginfo( 'name' ) , $order_date );

	return $subject;
}
add_filter( 'woocommerce_subscriptions_email_subject_customer_completed_renewal_order', 'rul_products_email_subject_customer_completed_renewal_order', 10, 2 );

// WooCommerce Subscriptions -- Modify the subscription renewal email main table heading
function rul_products_email_heading_customer_renewal_order( $heading, $order ) {
	// TODO Conditionally change this by keying off $order object
	// Modify the email heading
	$heading = _x( 'Your payment plan installment is complete', 'Default email heading for email to customer on completed payment plan installment order', 'rul-products' );

	return $heading;
}
add_filter( 'woocommerce_email_heading_customer_renewal_order', 'rul_products_email_heading_customer_renewal_order', 10, 2 );
