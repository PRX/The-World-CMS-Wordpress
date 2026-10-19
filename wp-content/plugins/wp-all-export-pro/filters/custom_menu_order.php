<?php

defined( 'ABSPATH' ) || exit;

function pmxe_custom_menu_order($custom_order) {
	return $custom_order || class_exists('PMXI_Plugin');
}
