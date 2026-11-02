<?php

namespace Wpae\WordPress;

class WpmlLanguage {

	public static function switchTo( $code ) {
		if ( ! class_exists( 'SitePress' ) || empty( $code ) ) {
			return false;
		}

		do_action( 'wpml_switch_language', $code );

		return true;
	}

	// WPML only pops its switch stack (and clears icl_language_switched) when given null; passing the previous code pushes another frame.
	public static function restore( $switched ) {
		if ( $switched ) {
			do_action( 'wpml_switch_language', null );
		}
	}
}
