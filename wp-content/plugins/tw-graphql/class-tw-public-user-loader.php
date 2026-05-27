<?php
/**
 * Custom WPGraphQL UserLoader.
 *
 * @package tw_graphql
 */

use WPGraphQL\Data\Loader\UserLoader;

/**
 * Overrides the WPGraphQL UserLoader to avoid slow query.
 * Only `get_public_users` is overridden, and only when no env var list is provided
 */
class TW_Public_User_Loader extends UserLoader {

	/**
	 * Name of the environment variable holding the public user IDs.
	 * e.g. "12,34,56"
	 *
	 * @var string
	 */
	const PUBLIC_USER_IDS_ENV = 'TW_GRAPHQL_PUBLIC_USER_IDS';

	/**
	 * Read and parse the known public user IDs from the environment.
	 *
	 * @return int[] Known public user IDs.
	 */
	private function get_public_user_ids() {
		$raw = getenv( self::PUBLIC_USER_IDS_ENV );

		if ( false === $raw || '' === trim( $raw ) ) {
			return array();
		}

		$ids = array_map( 'intval', explode( ',', $raw ) );

		// Drop empty/zero/negative entries (e.g. from stray commas).
		return array_values( array_filter( $ids, static fn( $id ) => $id > 0 ) );
	}

	/**
	 * Determine which of the provided keys are public users.
	 *
	 * When a list of public user IDs is configured via TW_GRAPHQL_PUBLIC_USER_IDS,
	 * returns the intersection of the requested keys and that list
	 *
	 * When no list is configured, falls back to the parent's (slower) database
	 * query so authors still resolve correctly.
	 *
	 * @param int[] $keys Array of author IDs (int).
	 *
	 * @return array<int,bool> Associative array of public author IDs to `true`.
	 */
	public function get_public_users( array $keys ) {

		$public_user_ids = apply_filters( 'tw_graphql_public_user_ids', $this->get_public_user_ids(), $keys );

		/**
		 * No list? fall back to the parent's database query
		 */
		if ( empty( $public_user_ids ) ) {
			return parent::get_public_users( $keys );
		}

		$public = array();

		$intersection = array_intersect(
			array_map( 'intval', $keys ),
			array_map( 'intval', (array) $public_user_ids )
		);

		foreach ( $intersection as $id ) {
			$public[ $id ] = true;
		}

		return $public;
	}
}
