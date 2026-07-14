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
				'model_name' => 'gpt-4o-mini',
				'temperature' => 0.7,
				'max_tokens' => 120,
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
			error_log('[Alias Generator] API key not set');
			return false;
		}
		
		$prompt = str_replace('{title}', $title, $settings['prompt_template']);
		
		// 构建完整的API URL
		$api_url = trailingslashit($settings['api_base_url']) . ltrim($settings['api_path'], '/');
		
		// 根据供应商选择不同的请求体结构
		// 旧供应商（openrouter/qwen/siliconflow）按 custom（OpenAI-compatible）处理，保证向后兼容
		switch ($settings['api_provider']) {
			case 'anthropic':
				return $this->call_anthropic_api($api_url, $settings, $prompt);
			case 'openai':
			case 'deepseek':
				return $this->call_openai_style_api($api_url, $settings, $prompt);
			case 'custom':
			default:
				return $this->call_custom_api($api_url, $settings, $prompt);
		}
	}
	
	private function call_openai_style_api($api_url, $settings, $prompt) {
		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $settings['api_key']
		);
		
		$body = array(
			'model' => $settings['model_name'],
			'messages' => array(
				array(
					'role' => 'user',
					'content' => $prompt
				)
			),
			'temperature' => floatval($settings['temperature']),
			'max_tokens' => intval($settings['max_tokens']),
			'stream' => false
		);
		
		return $this->make_api_request($api_url, $headers, $body);
	}

	private function call_custom_api($api_url, $settings, $prompt) {
		// 自定义 API：兼容 OpenAI chat completions 格式（如 ModelScope、OpenRouter、SiliconFlow 等）
		$headers = array(
			'Content-Type' => 'application/json'
		);
		
		if (!empty($settings['api_key'])) {
			$headers['Authorization'] = 'Bearer ' . $settings['api_key'];
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
			'max_tokens' => intval($settings['max_tokens']),
			'stream' => false
		);
		
		return $this->make_api_request($api_url, $headers, $body);
	}

	private function call_anthropic_api($api_url, $settings, $prompt) {
		$headers = array(
			'Content-Type' => 'application/json',
			'x-api-key' => $settings['api_key'],
			'anthropic-version' => '2023-06-01'
		);
		
		$body = array(
			'model' => $settings['model_name'],
			'messages' => array(
				array(
					'role' => 'user',
					'content' => $prompt
				)
			),
			'max_tokens' => intval($settings['max_tokens']),
			'temperature' => floatval($settings['temperature'])
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
			error_log('[Alias Generator] API request error: ' . $response->get_error_message());
			return false;
		}

		$status_code = wp_remote_retrieve_response_code($response);
		$raw_body = wp_remote_retrieve_body($response);

		if ($status_code < 200 || $status_code >= 300) {
			error_log('[Alias Generator] API HTTP error ' . $status_code . ' from ' . $api_url . ': ' . $raw_body);
			return false;
		}
		
		$text = $this->extract_text_from_response($raw_body);
		if ($text !== false && $text !== '') {
			return sanitize_title($text);
		}
		
		error_log('[Alias Generator] Unexpected or empty response from ' . $api_url . ': ' . $raw_body);
		return false;
	}

	/**
	 * 从响应体中提取文本内容
	 * 兼容单个 JSON 对象或多个 JSON 对象拼接的情况（如 ModelScope 流式/分块响应）
	 */
	private function extract_text_from_response($raw_body) {
		// 先尝试标准单 JSON 对象
		$data = json_decode($raw_body, true);
		$text = $this->get_text_from_decoded($data);
		if ($text !== false && $text !== '') {
			return $text;
		}

		// 尝试提取多个 JSON 对象（有些服务会直接把多个 chunk 拼在一起返回）
		if (!preg_match_all('/(\{(?:[^{}]++|(?1))*\})/s', $raw_body, $matches)) {
			return false;
		}

		$delta_text = '';
		$message_text = '';

		foreach ($matches[0] as $json) {
			$data = json_decode($json, true);
			if (!is_array($data)) {
				continue;
			}

			// 优先取最终聚合的 message.content
			if (isset($data['choices'][0]['message']['content'])) {
				$message_text .= $data['choices'][0]['message']['content'];
			}

			// 同时收集流式 delta.content（中间 chunk 可能在这里）
			if (isset($data['choices'][0]['delta']['content']) && $data['choices'][0]['delta']['content'] !== '') {
				$delta_text .= $data['choices'][0]['delta']['content'];
			}

			// Anthropic / 其他格式
			if (isset($data['content'][0]['text'])) {
				return trim($data['content'][0]['text']);
			} elseif (isset($data['output']['text'])) {
				return trim($data['output']['text']);
			} elseif (isset($data['result'])) {
				return trim($data['result']);
			} elseif (isset($data['response'])) {
				return trim($data['response']);
			}
		}

		$message_text = trim($message_text);
		$delta_text = trim($delta_text);

		if ($message_text !== '') {
			return $message_text;
		}
		if ($delta_text !== '') {
			return $delta_text;
		}

		return false;
	}

	/**
	 * 从已解析的响应数组中提取文本
	 */
	private function get_text_from_decoded($data) {
		if (!is_array($data)) {
			return false;
		}

		if (isset($data['choices'][0]['message']['content'])) {
			return trim($data['choices'][0]['message']['content']);
		} elseif (isset($data['content'][0]['text'])) {
			// Anthropic Messages API
			return trim($data['content'][0]['text']);
		} elseif (isset($data['output']['text'])) {
			return trim($data['output']['text']);
		} elseif (isset($data['result'])) {
			return trim($data['result']);
		} elseif (isset($data['response'])) {
			return trim($data['response']);
		}

		return false;
	}

}