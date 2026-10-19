<?php

if ( ! function_exists('wpae_settings_field_id') ):
function wpae_settings_field_id($name) {
	return 'wpae-set-' . trim(preg_replace('%[^a-z0-9]+%i', '-', $name), '-');
}
endif;

if ( ! function_exists('wpae_settings_mask_key') ):
function wpae_settings_mask_key($key, $visible = 4) {
	$key = (string) $key;
	$len = strlen($key);
	$tail = wpae_settings_key_tail($key, $visible);

	if ($len <= $visible || '' === $tail) {
		return str_repeat('•', max(4, min(24, $len)));
	}

	return str_repeat('•', min(24, $len - $visible)) . $tail;
}
endif;

if ( ! function_exists('wpae_settings_key_tail') ):
/**
 * Returns '' when the tail is not safely printable: esc_html()/esc_attr() discard the
 * entire string they are embedded in, not just the offending bytes.
 */
function wpae_settings_key_tail($key, $visible = 4) {
	$tail = substr((string) $key, -$visible);

	if ('' === $tail || preg_match('%[^\x20-\x7E]%', $tail)) {
		return '';
	}

	return $tail;
}
endif;

if ( ! function_exists('wpae_settings_row') ):
function wpae_settings_row($args) {
	$args = array_merge(array(
		'label'   => '',
		'for'     => '',
		'control' => '',
		'help'    => '',
		'class'   => '',
		'legend'  => '',
		'help_id' => '',
	), $args);

	if ('' === $args['help_id'] && $args['for']) {
		$args['help_id'] = $args['for'] . '-help';
	}

	$classes = 'wpae-set-row' . ($args['class'] ? ' ' . $args['class'] : '');
	?>
	<div class="<?php echo esc_attr($classes); ?>">
		<div class="wpae-set-row__label">
			<?php if ($args['for']): ?>
				<label for="<?php echo esc_attr($args['for']); ?>"><?php echo esc_html($args['label']); ?></label>
			<?php else: ?>
				<span class="wpae-set-row__label-text"><?php echo esc_html($args['label']); ?></span>
			<?php endif; ?>
		</div>
		<div class="wpae-set-row__field">
			<?php echo $args['control']; ?>
			<?php if ($args['help']): ?>
				<p class="wpae-set-help" <?php if ($args['help_id']): ?>id="<?php echo esc_attr($args['help_id']); ?>"<?php endif; ?>><?php echo $args['help']; ?></p>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
endif;

if ( ! function_exists('wpae_settings_number') ):
function wpae_settings_number($name, $value, $args = array()) {
	$args = array_merge(array(
		'label'       => '',
		'help'        => '',
		'unit'        => '',
		'min'         => 0,
		'max'         => '',
		'step'        => 1,
		'placeholder' => '',
		'note'        => '',
	), $args);

	$id = wpae_settings_field_id($name);

	ob_start();
	?>
	<span class="wpae-set-numeric">
		<input type="number" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"
			class="wpae-set-input wpae-set-input--number"
			value="<?php echo esc_attr($value); ?>"
			min="<?php echo esc_attr($args['min']); ?>" step="<?php echo esc_attr($args['step']); ?>"
			<?php if ($args['max'] !== ''): ?>max="<?php echo esc_attr($args['max']); ?>"<?php endif; ?>
			<?php if ($args['placeholder'] !== ''): ?>placeholder="<?php echo esc_attr($args['placeholder']); ?>"<?php endif; ?>
			<?php if ($args['help']): ?>aria-describedby="<?php echo esc_attr($id); ?>-help"<?php endif; ?> />
		<?php if ($args['unit']): ?><span class="wpae-set-unit"><?php echo esc_html($args['unit']); ?></span><?php endif; ?>
	</span>
	<?php if ($args['note']): ?><p class="wpae-set-note"><?php echo $args['note']; ?></p><?php endif; ?>
	<?php
	$control = ob_get_clean();

	wpae_settings_row(array(
		'label'   => $args['label'],
		'for'     => $id,
		'control' => $control,
		'help'    => $args['help'],
	));
}
endif;

if ( ! function_exists('wpae_settings_text') ):
function wpae_settings_text($name, $value, $args = array()) {
	$args = array_merge(array(
		'label'       => '',
		'help'        => '',
		'placeholder' => '',
		'copy'        => false,
		'after'       => '',
	), $args);

	$id = wpae_settings_field_id($name);

	ob_start();
	?>
	<span class="wpae-set-inputgroup">
		<input type="text"
			id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>"
			class="wpae-set-input"
			value="<?php echo esc_attr($value); ?>"
			autocomplete="off" spellcheck="false"
			<?php if ($args['placeholder'] !== ''): ?>placeholder="<?php echo esc_attr($args['placeholder']); ?>"<?php endif; ?>
			<?php if ($args['help']): ?>aria-describedby="<?php echo esc_attr($id); ?>-help"<?php endif; ?> />
		<?php if ($args['copy']): ?>
			<button type="button" class="wpae-set-iconbtn wpae-set-copy" data-target="<?php echo esc_attr($id); ?>"><?php _e('Copy', 'wp_all_export_plugin'); ?></button>
		<?php endif; ?>
	</span>
	<?php echo $args['after']; ?>
	<?php
	$control = ob_get_clean();

	wpae_settings_row(array(
		'label'   => $args['label'],
		'for'     => $id,
		'control' => $control,
		'help'    => $args['help'],
	));
}
endif;

if ( ! function_exists('wpae_settings_toggle') ):
/** The paired hidden input is what makes an unchecked box save as 0. */
function wpae_settings_toggle($name, $checked, $args = array()) {
	$args = array_merge(array(
		'label'    => '',
		'checkbox' => '',
		'help'     => '',
	), $args);

	$id = wpae_settings_field_id($name);

	ob_start();
	?>
	<input type="hidden" name="<?php echo esc_attr($name); ?>" value="0" />
	<label class="wpae-set-check" for="<?php echo esc_attr($id); ?>">
		<input type="checkbox" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="1"
			<?php checked($checked); ?>
			<?php if ($args['help']): ?>aria-describedby="<?php echo esc_attr($id); ?>-help"<?php endif; ?> />
		<span><?php echo esc_html($args['checkbox']); ?></span>
	</label>
	<?php
	$control = ob_get_clean();

	wpae_settings_row(array(
		'label'   => $args['label'],
		'for'     => $id,
		'control' => $control,
		'help'    => $args['help'],
		'help_id' => $id . '-help',
	));
}
endif;

if ( ! function_exists('wpae_settings_readonly_url') ):
function wpae_settings_readonly_url($id, $label, $value, $help = '') {
	ob_start();
	?>
	<span class="wpae-set-inputgroup wpae-set-inputgroup--wide">
		<input type="text" id="<?php echo esc_attr($id); ?>" class="wpae-set-input wpae-set-input--url"
			value="<?php echo esc_attr($value); ?>" readonly
			<?php if ($help): ?>aria-describedby="<?php echo esc_attr($id); ?>-help"<?php endif; ?> />
		<button type="button" class="wpae-set-iconbtn wpae-set-copy" data-target="<?php echo esc_attr($id); ?>"><?php _e('Copy', 'wp_all_export_plugin'); ?></button>
	</span>
	<?php
	$control = ob_get_clean();

	wpae_settings_row(array(
		'label'   => $label,
		'for'     => $id,
		'control' => $control,
		'help'    => $help,
		'class'   => 'wpae-set-row--stack',
	));
}
endif;

if ( ! function_exists('wpae_settings_readonly_code') ):
function wpae_settings_readonly_code($id, $label, $value, $help = '') {
	ob_start();
	?>
	<div class="wpae-set-urlblock">
		<code class="wpae-set-urlblock__value" id="<?php echo esc_attr($id); ?>"><?php echo esc_html($value); ?></code>
		<button type="button" class="wpae-set-iconbtn wpae-set-copy" data-target="<?php echo esc_attr($id); ?>"><span data-copy-label><?php _e('Copy', 'wp_all_export_plugin'); ?></span><span class="screen-reader-text"> <?php echo esc_html($label); ?></span></button>
	</div>
	<?php
	$control = ob_get_clean();

	wpae_settings_row(array(
		'label'   => $label,
		'for'     => '',
		'control' => $control,
		'help'    => $help,
		'help_id' => $id . '-help',
		'class'   => 'wpae-set-row--stack',
	));
}
endif;

if ( ! function_exists('wpae_settings_group') ):
function wpae_settings_group($title, $description = '') {
	?>
	<div class="wpae-set-group">
		<h4 class="wpae-set-group__title"><?php echo esc_html($title); ?></h4>
		<?php if ($description): ?><p class="wpae-set-group__desc"><?php echo $description; ?></p><?php endif; ?>
	</div>
	<?php
}
endif;
