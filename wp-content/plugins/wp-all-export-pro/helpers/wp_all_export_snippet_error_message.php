<?php

function wp_all_export_snippet_error_message(Exception $e, $template)
{
	if ($e instanceof WpaeTooMuchRecursionException) {
		return __('There was a problem parsing the custom XML template', 'wp_all_export_plugin');
	}

	$functionName = $e->getMessage();
	$isUndefinedFunction = $e instanceof WpaeMethodNotFoundException;
	$functionLabel = '<em>"' . esc_html($functionName) . '"</em>';

	$lineNumbers = array();
	foreach (explode("\n", (string) $template) as $i => $line) {
		if ($functionName !== '' && strpos($line, $functionName) !== false) {
			$lineNumbers[] = $i + 1;
		}
	}

	$errorMessage = $isUndefinedFunction
		? 'Error parsing XML feed: Call to undefined function ' . $functionLabel
		: 'Error parsing XML feed: Unterminated string';

	if (count($lineNumbers) === 1) {
		$errorMessage .= ($isUndefinedFunction ? ' on Line ' : ' on line ') . $lineNumbers[0];
	} elseif ($lineNumbers) {
		$errorMessage .= ($isUndefinedFunction ? ' on Lines ' : ' on lines ') . implode(', ', $lineNumbers);
	}

	return $errorMessage;
}
