<?php
/**
 * Custom WPGraphQL UserLoader.
 *
 * @package tw_graphql
 */

use WPGraphQL\Data\Loader\UserLoader;

/**
 * Overrides the WPGraphQL UserLoader to avoid slow query.
 * Only `get_public_users` is overridden.
 */
class TW_Public_User_Loader extends UserLoader {

	/**
	 * Determine which of the provided keys are public users.
	 *
	 * @param int[] $keys Array of author IDs (int).
	 *
	 * @return array<int,bool> Associative array of public author IDs to `true`.
	 */
	public function get_public_users( array $keys ) {
		// The frontend doesn't use or display author data, so we can treat all users as private.
		return array();
	}
}
