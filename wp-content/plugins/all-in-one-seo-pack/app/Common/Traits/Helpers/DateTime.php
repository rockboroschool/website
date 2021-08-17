<?php
namespace AIOSEO\Plugin\Common\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contains date/time specific helper methods.
 *
 * @since 4.1.2
 */
trait DateTime {
	/**
	 * Formats a date in ISO8601 format.
	 *
	 * @since 4.1.2
	 *
	 * @param  string $date The date.
	 * @return string       The date formatted in ISO8601 format.
	 */
	public function dateToIso8601( $date ) {
		return date( 'Y-m-d', strtotime( $date ) );
	}

	/**
	 * Formats an amount of minutes in ISO8601 format.
	 * This is used in our JSON schema to adhere to Google's standards.
	 *
	 * @since 4.1.2
	 *
	 * @param  integer|string $minutes The minutes.
	 * @return                         The minutes formatted in ISO8601 format.
	 */
	public function minutesToIso8601( $minutes ) {
		return "PT${minutes}M";
	}
}