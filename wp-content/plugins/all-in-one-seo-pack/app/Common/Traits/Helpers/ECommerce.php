<?php
namespace AIOSEO\Plugin\Common\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains helper functions for eCommerce plugins.
 *
 * @since 4.1.3
 */
trait ECommerce {
	/**
	 * Checks whether WooCommerce is active.
	 *
	 * @since 4.0.0
	 *
	 * @return boolean Whether WooCommerce is active.
	 */
	public function isWooCommerceActive() {
		return class_exists( 'woocommerce' );
	}

	/**
	 * Checks whether the queried object is the WooCommerce shop page.
	 *
	 * @since 4.0.0
	 *
	 * @param  int  $id The post ID to check against (optional).
	 * @return bool     Whether the current page is the WooCommerce shop page.
	 */
	public function isWooCommerceShopPage( $id = 0 ) {
		if ( ! $this->isWooCommerceActive() ) {
			return false;
		}

		if ( ! is_admin() && ! aioseo()->helpers->isAjaxCronRest() && function_exists( 'is_shop' ) ) {
			return is_shop();
		}

		$id = ! $id && ! empty( $_GET['post'] ) ? (int) wp_unslash( $_GET['post'] ) : (int) $id; // phpcs:ignore HM.Security.ValidatedSanitizedInput
		return $id && wc_get_page_id( 'shop' ) === $id;
	}

	/**
	 * Checks whether the queried object is the WooCommerce cart page.
	 *
	 * @since 4.1.3
	 *
	 * @param  int  $id The post ID to check against (optional).
	 * @return bool     Whether the current page is the WooCommerce cart page.
	 */
	public function isWooCommerceCartPage( $id = 0 ) {
		if ( ! $this->isWooCommerceActive() ) {
			return false;
		}

		if ( ! is_admin() && ! aioseo()->helpers->isAjaxCronRest() && function_exists( 'is_cart' ) ) {
			return is_cart();
		}

		$id = ! $id && ! empty( $_GET['post'] ) ? (int) wp_unslash( $_GET['post'] ) : (int) $id; // phpcs:ignore HM.Security.ValidatedSanitizedInput
		return $id && wc_get_page_id( 'cart' ) === $id;
	}

	/**
	 * Checks whether the queried object is the WooCommerce checkout page.
	 *
	 * @since 4.1.3
	 *
	 * @param  int  $id The post ID to check against (optional).
	 * @return bool     Whether the current page is the WooCommerce checkout page.
	 */
	public function isWooCommerceCheckoutPage( $id = 0 ) {
		if ( ! $this->isWooCommerceActive() ) {
			return false;
		}

		if ( ! is_admin() && ! aioseo()->helpers->isAjaxCronRest() && function_exists( 'is_checkout' ) ) {
			return is_checkout();
		}

		$id = ! $id && ! empty( $_GET['post'] ) ? (int) wp_unslash( $_GET['post'] ) : (int) $id; // phpcs:ignore HM.Security.ValidatedSanitizedInput
		return $id && wc_get_page_id( 'checkout' ) === $id;
	}

	/**
	 * Checks whether the queried object is the WooCommerce account page.
	 *
	 * @since 4.1.3
	 *
	 * @param  int  $id The post ID to check against (optional).
	 * @return bool     Whether the current page is the WooCommerce account page.
	 */
	public function isWooCommerceAccountPage( $id = 0 ) {
		if ( ! $this->isWooCommerceActive() ) {
			return false;
		}

		if ( ! is_admin() && ! aioseo()->helpers->isAjaxCronRest() && function_exists( 'is_account_page' ) ) {
			return is_account_page();
		}

		$id = ! $id && ! empty( $_GET['post'] ) ? (int) wp_unslash( $_GET['post'] ) : (int) $id; // phpcs:ignore HM.Security.ValidatedSanitizedInput
		return $id && wc_get_page_id( 'myaccount' ) === $id;
	}
}