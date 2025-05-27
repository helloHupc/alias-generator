<?php
class Alias_Generator_Core {
    
    public function __construct() {
        // 初始化操作
    }
    
	public function activate() {
		if (!get_option('alias_generator_settings')) {
			$default_settings = array(
				'api_provider' => 'openai',
				'api_base_url' => 'https://api.openai.com',
				'api_path' => '/v1/chat/completions',
				'api_key' => '',
				'model_name' => 'gpt-3.5-turbo',
				'temperature' => 0.7,
				'max_tokens' => 60,
				'prompt_template' => 'Generate a SEO-friendly URL slug for the following article title: "{title}". The slug should be concise, use lowercase letters, hyphens as separators, and contain only alphanumeric characters. Respond with only the slug, nothing else.'
			);
			update_option('alias_generator_settings', $default_settings);
		}
	}
    
    public function deactivate() {
        // 插件停用时执行
    }
    
    /**
     * 生成slug
     * @param string $title 文章标题
     * @param int $post_id 文章ID
     * @return string|false 生成的slug或false
     */
	public function generate_alias($title, $post_id = 0) {
		$settings = get_option('alias_generator_settings');
		
		if (empty($settings['api_key'])) {
			return false;
		}
		
		$prompt = str_replace('{title}', $title, $settings['prompt_template']);
		
		// 构建完整的API URL
		$api_url = trailingslashit($settings['api_base_url']) . ltrim($settings['api_path'], '/');
		
		// 根据供应商选择不同的请求体结构
		switch ($settings['api_provider']) {
			case 'openai':
			case 'deepseek':
			case 'openrouter':
			case 'siliconflow':
			case 'qwen':
				return $this->call_openai_style_api($api_url, $settings, $prompt);
			case 'custom':
				return $this->call_custom_api($api_url, $settings, $prompt);
			default:
				return false;
		}
	}
	
	private function call_openai_style_api($api_url, $settings, $prompt) {
		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $settings['api_key']
		);
		
		// OpenRouter需要额外的头部
		if ($settings['api_provider'] === 'openrouter') {
			$headers['HTTP-Referer'] = get_site_url();
			$headers['X-Title'] = 'WordPress Slug Generator';
		}
		
		$body = array(
			'model' => $settings['model_name'],
			'messages' => array(
				array(
					'role' => 'user',
					'content' => $prompt
				)
			),
			'temperature' => floatval($settings['temperature']),
			'max_tokens' => intval($settings['max_tokens'])
		);
		
		return $this->make_api_request($api_url, $headers, $body);
	}

	private function call_custom_api($api_url, $settings, $prompt) {
		// 自定义API的通用实现
		$headers = array(
			'Content-Type' => 'application/json'
		);
		
		if (!empty($settings['api_key'])) {
			$headers['Authorization'] = 'Bearer ' . $settings['api_key'];
		}
		
		$body = array(
			'model' => $settings['model_name'],
			'prompt' => $prompt,
			'temperature' => floatval($settings['temperature']),
			'max_tokens' => intval($settings['max_tokens'])
		);
		
		return $this->make_api_request($api_url, $headers, $body);
	}

	private function make_api_request($api_url, $headers, $body) {
		$args = array(
			'headers' => $headers,
			'body' => json_encode($body),
			'timeout' => 30
		);

		$response = wp_remote_post($api_url, $args);
		
		if (is_wp_error($response)) {
			error_log('LLM Slug Generator API Error: ' . $response->get_error_message());
			return false;
		}
		
		$response_body = json_decode(wp_remote_retrieve_body($response), true);
		// 尝试解析不同格式的响应
		if (isset($response_body['choices'][0]['message']['content'])) {
			return sanitize_title(trim($response_body['choices'][0]['message']['content']));
		} elseif (isset($response_body['output']['text'])) {
			return sanitize_title(trim($response_body['output']['text']));
		} elseif (isset($response_body['result'])) {
			return sanitize_title(trim($response_body['result']));
		} elseif (isset($response_body['response'])) {
			return sanitize_title(trim($response_body['response']));
		}
		
		error_log('LLM Slug Generator API Error: Unexpected response format');
		return false;
	}
    

}