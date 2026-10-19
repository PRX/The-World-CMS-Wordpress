<?php

defined( 'ABSPATH' ) || exit;

function pmxe_menu_order($menu_order) {
	$import = array_search('pmxi-admin-home', $menu_order, true);
	$export = array_search('pmxe-admin-home', $menu_order, true);
	if ($export === false) {
		$export = array_search('pmxe-admin-manage', $menu_order, true);
	}
	if ($import === false || $export === false) {
		return $menu_order;
	}

	$export_slug = $menu_order[$export];
	array_splice($menu_order, $export, 1);
	$import = array_search('pmxi-admin-home', $menu_order, true);
	array_splice($menu_order, $import + 1, 0, array($export_slug));
	return $menu_order;
}
