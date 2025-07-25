<?php
if (!class_exists('TaxoPressAiApi')) {
    class TaxoPressAiApi
    {
        const DANDELION_API_URL = 'https://api.dandelion.eu/datatxt/nex/v1';

        const OPEN_CALAIS_API_URL = 'https://api-eit.refinitiv.com/permid/calais';

        ## https://cloud.ibm.com/docs/natural-language-understanding?topic=natural-language-understanding-release-notes#active-version-dates
        const IBM_WATSON_API_VERSION = '2022-08-10';

        const OPEN_AI_MODEL = 'gpt-3.5-turbo';

        const OPEN_AI_API_URL = 'https://api.openai.com/v1/chat/completions';

        /**
         * Get dandelion data
         *
         * @param  array $args
         * @return array
         */
        public static function get_dandelion_results($args)
        {

            if (empty(SimpleTags_Plugin::get_option_value('enable_dandelion_ai_source'))) {
                return [
                    'status' => 'error',
                    'message' => esc_html__('The Dandelion integration is disabled in the Legacy AI Sources settings.', 'simple-tags'),
                    'results' => [],
                ];
            }

            $return['status'] = 'error';
            $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
            $return['results'] = [];

            $settings_data = $args['settings_data'];
            $content = $args['content'];
            $clean_content = $args['clean_content'];
            $content_source = $args['content_source'];

            $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : 0;
            $dandelion_api_token = !empty($settings_data['dandelion_api_token']) ? $settings_data['dandelion_api_token'] : '';
            $dandelion_api_confidence_value = !empty($settings_data['dandelion_api_confidence_value']) ? $settings_data['dandelion_api_confidence_value'] : '0.6';
            $dandelion_cache_result = !empty($settings_data['dandelion_cache_result']) ? $settings_data['dandelion_cache_result'] : '';

            $existing_dandelion_result_key = '_taxopress_dandelion_' . $content_source . '_result';
            $old_saved_content_key = '_taxopress_dandelion_saved_' . $content_source . '_content';

            if (empty(trim($dandelion_api_token))) {
                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'The Dandelion integration requires an API Key. Please add your API Key in the Auto Term settings.',
                    'simple-tags'
                );
            } elseif (empty(trim($content))) {

                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'Selected content is empty.',
                    'simple-tags'
                );

            } else {
                $existing_dandelion_result = '';
                $old_saved_content = '';

                if ($post_id > 0 && $dandelion_cache_result) {
                    $existing_dandelion_result = get_post_meta($post_id, $existing_dandelion_result_key, true);
                    $old_saved_content = get_post_meta($post_id, $old_saved_content_key, true);
                }

                if (!empty($existing_dandelion_result) && strcmp($old_saved_content, $content) === 0) {
                    $return['status'] = 'success';
                    $return['results'] = $existing_dandelion_result;
                    $return['message'] = esc_html__(
                        'Result from cache.',
                        'simple-tags'
                    );
                } else {
                    $request_ws_args = [];
                    $request_ws_args['text'] = $clean_content;
                    $request_ws_args['min_confidence'] = $dandelion_api_confidence_value;
                    $request_ws_args['token'] = $dandelion_api_token;
                    $response = wp_remote_post(self::DANDELION_API_URL, array(
                        'user-agent' => 'WordPress simple-tags',
                        'body' => $request_ws_args
                    )
                    );

                    if (!is_wp_error($response) && $response != null) {
                        $status_code = wp_remote_retrieve_response_code($response);
                        $body_data = json_decode(wp_remote_retrieve_body($response));

                        if ($status_code !== 200) {
                            $error_message = (is_object($body_data) && isset($body_data->message)) ? $body_data->message : $status_code;
                            $return['status'] = 'error';
                            $return['message'] = sprintf(esc_html__('API Error: %1s.', 'simple-tags'), $error_message);
                        } else {
                            $data = is_object($body_data) ? $body_data->annotations : '';
                            $terms = [];
                            if (!empty($data)) {
                                $terms = (array) $data;
                                $terms = array_column($terms, 'title');
                                $return['status'] = 'success';
                                $return['results'] = $terms;
                                $return['message'] = esc_html__(
                                    'Result from api.',
                                    'simple-tags'
                                );

                                update_post_meta($post_id, $existing_dandelion_result_key, $terms);
                                update_post_meta($post_id, $old_saved_content_key, $content);
                                
                            } else {
                                $return['status'] = 'error';
                                $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
                            }

                        }
                    } else {
                        $return['status'] = 'error';
                        $return['message'] = esc_html__(
                            'Error establishing connection with the API server. Try again.',
                            'simple-tags'
                        );
                    }
                }

            }

            return $return;
        }

        /**
         * Get open calais data
         *
         * @param  array $args
         * @return array
         */
        public static function get_open_calais_results($args)
        {

            if (empty(SimpleTags_Plugin::get_option_value('enable_lseg_ai_source'))) {
                return [
                    'status' => 'error',
                    'message' => esc_html__('The LSEG / Refinitiv integration is disabled in the Legacy AI Sources settings.', 'simple-tags'),
                    'results' => [],
                ];
            }
            $return['status'] = 'error';
            $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
            $return['results'] = [];

            $settings_data = $args['settings_data'];
            $content = $args['content'];
            $clean_content = $args['clean_content'];
            $content_source = $args['content_source'];

            $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : 0;
            $open_calais_api_key = !empty($settings_data['open_calais_api_key']) ? $settings_data['open_calais_api_key'] : '';
            $open_calais_cache_result = !empty($settings_data['open_calais_cache_result']) ? $settings_data['open_calais_cache_result'] : '';

            $existing_open_calais_result_key = '_taxopress_open_calais_' . $content_source . '_result';
            $old_saved_content_key = '_taxopress_open_calais_saved_' . $content_source . '_content';

            if (empty(trim($open_calais_api_key))) {
                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'The LSEG / Refinitiv integration requires an API Key. Please add your API Key in the Auto Term settings.',
                    'simple-tags'
                );
            } elseif (empty(trim($content))) {

                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'Selected content is empty.',
                    'simple-tags'
                );

            } else {
                $existing_open_calais_result = '';
                $old_saved_content = '';

                if ($post_id > 0 && $open_calais_cache_result) {
                    $existing_open_calais_result = get_post_meta($post_id, $existing_open_calais_result_key, true);
                    $old_saved_content = get_post_meta($post_id, $old_saved_content_key, true);
                }

                if (!empty($existing_open_calais_result) && strcmp($old_saved_content, $content) === 0) {
                    $return['status'] = 'success';
                    $return['results'] = $existing_open_calais_result;
                    $return['message'] = esc_html__(
                        'Result from cache.',
                        'simple-tags'
                    );
                } else {

                    $response = wp_remote_post(self::OPEN_CALAIS_API_URL, array(
                        'timeout' => 30,
                        'headers' => array(
                            'X-AG-Access-Token' => $open_calais_api_key,
                            'Content-Type' => 'text/html',
                            'outputFormat' => 'application/json'
                        ),
                        'body' => $clean_content
                    )
                    );

                    if (!is_wp_error($response) && $response != null) {
                        $status_code = wp_remote_retrieve_response_code($response);
                        $body_data = json_decode(wp_remote_retrieve_body($response), true);

                        if ($status_code !== 200) {
                            $error_message = (is_object($body_data) && isset($body_data->message)) ? $body_data->message : $status_code;
                            $return['status'] = 'error';
                            $return['message'] = sprintf(esc_html__('API Error: %1s.', 'simple-tags'), $error_message);
                        } else {
                            $data = is_array($body_data) ? $body_data : [];
                            $terms = [];

                            if (!empty($data)) {
                                foreach ($data as $_data_raw) {
                                    if (isset($_data_raw['_typeGroup']) && $_data_raw['_typeGroup'] == 'socialTag' && !empty(trim($_data_raw['name']))) {
                                        $terms[] = $_data_raw['name'];
                                    }
                                }

                                if (!empty($terms)) {
                                    $return['status'] = 'success';
                                    $return['results'] = $terms;
                                    $return['message'] = esc_html__('Result from api.', 'simple-tags');

                                    update_post_meta($post_id, $existing_open_calais_result_key, $terms);
                                    update_post_meta($post_id, $old_saved_content_key, $content);
                                } else {
                                    $return['status'] = 'error';
                                    $return['message'] = esc_html__('API Error: No matched result for content.', 'simple-tags');
                                }
                                
                            } else {
                                $return['status'] = 'error';
                                $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
                            }
                        }
                    } else {
                        $return['status'] = 'error';
                        $return['message'] = esc_html__(
                            'Error establishing connection with the API server. Try again.',
                            'simple-tags'
                        );
                    }
                }

            }

            return $return;
        }

        /**
         * Get ibm watson data
         *
         * @param  array $args
         * @return array
         */
        public static function get_ibm_watson_results($args)
        {

            if (empty(SimpleTags_Plugin::get_option_value('enable_ibm_watson_ai_source'))) {
                return [
                    'status' => 'error',
                    'message' => esc_html__('The IBM Watson integration is disabled in the Legacy AI Sources settings.', 'simple-tags'),
                    'results' => [],
                ];
            }

            $return['status'] = 'error';
            $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
            $return['results'] = [];

            $settings_data = $args['settings_data'];
            $content = $args['content'];
            $clean_content = $args['clean_content'];
            $content_source = $args['content_source'];

            $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : 0;
            $ibm_watson_api_url = !empty($settings_data['ibm_watson_api_url']) ? $settings_data['ibm_watson_api_url'] : '';
            $ibm_watson_api_key = !empty($settings_data['ibm_watson_api_key']) ? $settings_data['ibm_watson_api_key'] : '';
            $ibm_watson_cache_result = !empty($settings_data['ibm_watson_cache_result']) ? $settings_data['ibm_watson_cache_result'] : '';

            $existing_ibm_watson_result_key = '_taxopress_ibm_watson_' . $content_source . '_result';
            $old_saved_content_key = '_taxopress_ibm_watson_saved_' . $content_source . '_content';

            if (empty(trim($ibm_watson_api_url)) || empty(trim($ibm_watson_api_key))) {
                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'The IBM Watson integration requires an API Key and URL. Please add your API Key in the Auto Term settings.',
                    'simple-tags'
                );
            } elseif (empty(trim($content))) {

                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'Selected content is empty.',
                    'simple-tags'
                );

            } else {
                $existing_ibm_watson_result = '';
                $old_saved_content = '';

                if ($post_id > 0 && $ibm_watson_cache_result) {
                    $existing_ibm_watson_result = get_post_meta($post_id, $existing_ibm_watson_result_key, true);
                    $old_saved_content = get_post_meta($post_id, $old_saved_content_key, true);
                }

                if (!empty($existing_ibm_watson_result) && strcmp($old_saved_content, $content) === 0) {
                    $return['status'] = 'success';
                    $return['results'] = $existing_ibm_watson_result;
                    $return['message'] = esc_html__(
                        'Result from cache.',
                        'simple-tags'
                    );
                } else {
                    
                    $endpoint_base_url = trailingslashit($ibm_watson_api_url) . 'v1/analyze';
                    $api_endpoint = esc_url(add_query_arg(['version' => self::IBM_WATSON_API_VERSION], $endpoint_base_url));

                    $request_body = [
                        'features' => [
                            'keywords' => [
                                'emotion'   => false,
                                'sentiment' => false,
                                'limit' => 50
                            ]
                        ],
                        'text' => $clean_content
                    ];

                    $response = wp_remote_post($api_endpoint, array(
                        'headers' => array(
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Basic ' . base64_encode('apikey:' . $ibm_watson_api_key)
                        ),
                        'body' => wp_json_encode($request_body)
                    ));

                    if (!is_wp_error($response) && $response != null) {
                        $status_code = wp_remote_retrieve_response_code($response);
                        $body_data = json_decode(wp_remote_retrieve_body($response));

                        if ($status_code !== 200) {
                            $error_message = (is_object($body_data) && isset($body_data->error)) ? $body_data->error : $status_code;
                            $return['status'] = 'error';
                            $return['message'] = sprintf(esc_html__('API Error: %1s.', 'simple-tags'), $error_message);
                        } else {
                            $data = is_object($body_data) ? $body_data->keywords : '';
                            $terms = [];
                            if (!empty($data)) {
                                $terms = (array) $data;

                                if (!empty($terms)) {
                                    $terms = array_column($terms, 'text');
                                    $return['status'] = 'success';
                                    $return['results'] = $terms;
                                    $return['message'] = esc_html__(
                                        'Result from api.',
                                        'simple-tags'
                                    );

                                    update_post_meta($post_id, $existing_ibm_watson_result_key, $terms);
                                    update_post_meta($post_id, $old_saved_content_key, $content);
                                    
                                }
                            } else {
                                $return['status'] = 'error';
                                $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
                            }

                        }
                    } else {
                        $return['status'] = 'error';
                        $return['message'] = esc_html__(
                            'Error establishing connection with the API server. Try again.',
                            'simple-tags'
                        );
                    }
                }

            }

            return $return;
        }

        /**
         * Clean api response to fix //https://github.com/TaxoPress/TaxoPress/issues/2258
         */
        public static function clean_api_response($content) {
                    
            //- OpenAI sometimes start response like "Tags:
            $content = str_replace('Tags: ', '', $content);
            $content = str_replace('Extracted tags: ', '', $content);
            $content = str_replace('Extracted text: ', '', $content);
            //o3-mini seems to be more of reasoning than tags
            $content = preg_replace('/Explanation:.*/', '', $content);
            $content = preg_replace('/^.*?:\s*/', '', $content); // remove texts after column making sure explanation are removed
            $content = str_replace(['•','*'], '', $content);
            $content = preg_replace('/^(.*?)\b(These Tags:|Extracted tags:|Extracted text:|Based on the content:|A simple extraction de-duplicating the words results in the following tags:|Therefore, the extracted tags are:)\s*/is', '', $content);


            // Remove leading dashes, numbers with a dot, and any extra whitespace
            $content = preg_replace("/[\r\n]+|\s*[-–—]\s*|\d+\.\s*/", "|", $content);

            $content = str_replace('�', '', $content);
            
            // Split by the delimiter "|"
            $words = array_filter(array_map('trim', explode("|", $content)));
            
            // Join into comma-separated list
            $cleaned_content = implode(", ", $words);

            return $cleaned_content;
        }
        

        /**
         * Get open data
         *
         * @param  array $args
         * @return array
         */
        public static function get_open_ai_results($args)
        {
            $return['status'] = 'error';
            $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
            $return['results'] = [];

            $settings_data  = $args['settings_data'];
            $content        = $args['content'];
            $clean_content  = $args['clean_content'];
            $content_source = $args['content_source'];
            $preview_feature = !empty($args['preview_feature']) ? $args['preview_feature'] : '';
            $taxonomy           = !empty($args['taxonomy']) ? $args['taxonomy'] : '';
            
            $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : 0;
            $open_ai_api_key = !empty($settings_data['open_ai_api_key']) ? $settings_data['open_ai_api_key'] : '';
            $open_ai_model = !empty($settings_data['open_ai_model']) ? $settings_data['open_ai_model'] : self::OPEN_AI_MODEL;
            $open_ai_cache_result = !empty($settings_data['open_ai_cache_result']) ? $settings_data['open_ai_cache_result'] : '';

            $existing_open_ai_result_key = '_taxopress_open_ai_' . $content_source . '_result';
            $old_saved_content_key = '_taxopress_open_ai_saved_' . $content_source . '_content';

            if (empty(trim($open_ai_api_key))) {
                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'The OpenAI integration requires an API Key. Please add your API Key in the Auto Term settings.',
                    'simple-tags'
                );
            } elseif (empty(trim($content))) {

                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'Selected content is empty.',
                    'simple-tags'
                );

            } else {
                $existing_open_ai_result = '';
                $old_saved_content = '';

                if ($post_id > 0 && $open_ai_cache_result) {
                    $existing_open_ai_result = get_post_meta($post_id, $existing_open_ai_result_key, true);
                    $old_saved_content = get_post_meta($post_id, $old_saved_content_key, true);
                }

                if (!empty($existing_open_ai_result) && strcmp($old_saved_content, $content) === 0) {
                    $return['status'] = 'success';
                    $return['results'] = $existing_open_ai_result;
                    $return['message'] = esc_html__(
                        'Result from cache.',
                        'simple-tags'
                    );
                } else {
                    $prompt = "Extract tags from the following content: '$clean_content'. Tags:";
                    $post_terms_in_prompt = false;
                    $existing_post_terms = [];
                    if (!empty($settings_data['open_ai_tag_prompt'])) {
                        $post_terms_in_prompt = strpos($settings_data['open_ai_tag_prompt'], '{post_terms}') !== false;
                        $custom_prompt = sanitize_textarea_field(stripslashes_deep($settings_data['open_ai_tag_prompt']));
                        $prompt = str_replace('{content}', $clean_content, $custom_prompt);
                        if (!empty($taxonomy) && !empty($post_id)) {
                            $post_terms_results = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
                            $existing_post_terms = $post_terms_results;
                            $post_terms_comma_join = !empty($post_terms_results) ? join(', ', $post_terms_results) : '';
                            $prompt = str_replace('{post_terms}', $post_terms_comma_join, $prompt);
                        }
                    }

                    if ($post_terms_in_prompt && empty($existing_post_terms)) {
                        $return['status'] = 'error';
                        $return['message'] = esc_html__('You added {post_terms} in the prompt but your post does not contain any terms.', 'simple-tags');
                    } else {
                        $body_data = [
                            'model'         => $open_ai_model,
                            'messages'    => [
                                [
                                    'role'    => 'system', //'system', 'assistant', 'user', 'function', 'tool', and 'developer'.
                                    'content' => $prompt,
                                ],
                            ]
                        ];

                        
                        if (!in_array($open_ai_model, ['o3-mini', 'o1-mini', 'o1'])) {
                            $body_data['max_tokens'] = 50;
                            $body_data['temperature'] = 0.9;
                        }
                        
                        if (in_array($open_ai_model, ['o1-mini'])) {
                            $body_data['messages'][0]['role'] = 'user';
                        }
                        
                        $headers = [
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer ' . $open_ai_api_key,
                        ];
                    
                        $response = wp_remote_post(self::OPEN_AI_API_URL, array(
                            'timeout' => 60,
                            'headers' => $headers,
                            'body' => wp_json_encode($body_data),
                        ));

                        if (!is_wp_error($response) && $response != null) {
                            $status_code = wp_remote_retrieve_response_code($response);
                            $body_data = json_decode(wp_remote_retrieve_body($response), true);

                            if ($status_code !== 200) {
                                $error_message = (is_array($body_data) && !empty($body_data['error']['message'])) ? $body_data['error']['message'] : $status_code;
                                if (strpos($error_message, 'You exceeded your current quota, please check your plan and billing details') !== false) {
                                    // https://github.com/TaxoPress/TaxoPress/issues/1951
                                    $error_message = esc_html__('Error: OpenAI says there is an issue with this API key. Please check your plan or billing details.', 'simple-tags');
                                }
                                $return['status'] = 'error';
                                $return['message'] = $error_message;//sprintf(esc_html__('API Error: %1s.', 'simple-tags'), $error_message);
                            } else {

                                $data = [];
                                if (!empty($body_data['choices'] )) {
                                    foreach ( $body_data['choices'] as $choice ) {
                                        if ( isset( $choice['message'], $choice['message']['content'] ) ) {
                                            $cleaned_response = self::clean_api_response($choice['message']['content']);
                                            if (count(array_merge($data, explode(', ', sanitize_text_field( trim( $cleaned_response, ' "\'' ) )))) === 1) {
                                                $data = array_merge($data, [$cleaned_response]);
                                            } else {
                                                $data = array_merge($data, explode(', ', sanitize_text_field( trim( $cleaned_response, ' "\'' ) )));
                                            }
                                        }
                                    }
                                }

                                if (!empty($data) &&!empty($settings_data['open_ai_exclude_post_terms']) && $post_terms_in_prompt) {
                                    $data = array_intersect($data, $existing_post_terms);
                                }

                                $terms = [];
                                if (!empty($data)) {
                                    $terms = (array) $data;
                                    if (!empty($terms)) {
                                        $return['status'] = 'success';
                                        $return['results'] = $terms;
                                        $return['message'] = esc_html__(
                                            'Result from api.',
                                            'simple-tags'
                                        );

                                        update_post_meta($post_id, $existing_open_ai_result_key, $terms);
                                        update_post_meta($post_id, $old_saved_content_key, $content);
                                    }
                                } 
                                
                                if (empty(array_filter($terms))) {
                                    $return['status'] = 'error';
                                    $return['message'] = esc_html__('No matched result from the API Server.', 'simple-tags');
                                }

                            }
                        } else {
                            $return['status'] = 'error';
                            $return['message'] = esc_html__(
                                'Error establishing connection with the API server. Try again.',
                                'simple-tags'
                            );
                        }
                    }
                }

            }

            return $return;
        }

    }
}