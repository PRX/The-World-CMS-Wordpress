<?php
if ( ! function_exists('wp_all_export_rand_char')){

	/**
	 * These keys authenticate endpoints (the cron key and the Zapier API key), so the
	 * source has to be a CSPRNG. The free plugin already uses random_int() here; this
	 * brings Pro in line.
	 */
	function wp_all_export_rand_char($length) {
		
		$random = '';
	  
		do
		{
	  		$random .= str_replace(array('-', '_'), '', wp_all_export_url_title(chr(random_int(33, 126))));
	  	} 
	  	while (strlen($random) < $length); 

	  	return $random;
	}
}