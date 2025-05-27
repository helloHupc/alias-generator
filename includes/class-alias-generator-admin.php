<?php
class Alias_Generator_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('post_row_actions', array($this, 'add_post_row_action'), 10, 2);
        add_filter('bulk_actions-edit-post', array($this, 'add_bulk_action'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Alias Generator Settings',
            'Alias Generator',
            'manage_options',
            'Alias-generator',
            array($this, 'render_settings_page')
        );	
    }
    
	public function register_settings() {
		register_setting('alias_generator_settings_group', 'alias_generator_settings');
		
		// API基本设置部分
		add_settings_section(
			'alias_generator_api_basic_section',
			'Basic API Settings',
			array($this, 'render_api_basic_section'),
			'Alias-generator'
		);
		
		// API供应商选择
		add_settings_field(
			'api_provider',
			'API Provider',
			array($this, 'render_api_provider_field'),
			'Alias-generator',
			'alias_generator_api_basic_section'
		);
		
		// API基础URL
		add_settings_field(
			'api_base_url',
			'API Base URL',
			array($this, 'render_api_base_url_field'),
			'Alias-generator',
			'alias_generator_api_basic_section'
		);
		
		// API路径
		add_settings_field(
			'api_path',
			'API Path',
			array($this, 'render_api_path_field'),
			'Alias-generator',
			'alias_generator_api_basic_section'
		);
		
		// API密钥
		add_settings_field(
			'api_key',
			'API Key',
			array($this, 'render_api_key_field'),
			'Alias-generator',
			'alias_generator_api_basic_section'
		);
		
		// API高级设置部分
		add_settings_section(
			'alias_generator_api_advanced_section',
			'Advanced API Settings',
			array($this, 'render_api_advanced_section'),
			'Alias-generator'
		);
		
		// 模型名称
		add_settings_field(
			'model_name',
			'Model Name',
			array($this, 'render_model_name_field'),
			'Alias-generator',
			'alias_generator_api_advanced_section'
		);
		
		// 温度值
		add_settings_field(
			'temperature',
			'Temperature',
			array($this, 'render_temperature_field'),
			'Alias-generator',
			'alias_generator_api_advanced_section'
		);
		
		// 最大token数
		add_settings_field(
			'max_tokens',
			'Max Tokens',
			array($this, 'render_max_tokens_field'),
			'Alias-generator',
			'alias_generator_api_advanced_section'
		);
		
		// 提示词模板
		add_settings_field(
			'prompt_template',
			'Prompt Template',
			array($this, 'render_prompt_template_field'),
			'Alias-generator',
			'alias_generator_api_advanced_section'
		);
	}
	
	// 渲染各个设置字段的方法
	public function render_api_basic_section() {
		echo '<p>Configure the basic API connection settings.</p>';
	}

	public function render_api_advanced_section() {
		echo '<p>Adjust advanced parameters for the API.</p>';
	}

	/**
	 * 渲染API部分描述
	 */
	public function render_api_section() {
		echo '<p>Configure your LLM API settings below. You will need an API key from your preferred provider.</p>';
	}
	
	/**
	 * 渲染API提供商选择字段
	 */
	public function render_api_provider_field() {
		$settings = get_option('alias_generator_settings');
		$providers = array(
			'openai' => 'OpenAI',
			'deepseek' => 'DeepSeek',
			'openrouter' => 'OpenRouter',
			'qwen' => 'Qwen (通义千问)',
			'siliconflow' => 'SiliconFlow',
			'custom' => 'Custom'
		);
		?>
		<select name="alias_generator_settings[api_provider]" id="api_provider">
			<?php foreach ($providers as $value => $label): ?>
				<option value="<?php echo esc_attr($value); ?>" <?php selected(isset($settings['api_provider']) ? $settings['api_provider'] : '', $value); ?>>
					<?php echo esc_html($label); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description">Select your API provider or choose Custom for self-hosted solutions.</p>
		<?php
	}

	/**
	 * 渲染API密钥字段
	 */
	public function render_api_key_field() {
		$settings = get_option('alias_generator_settings');
		$api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
		?>
		<input type="password" name="alias_generator_settings[api_key]" id="api_key" 
			   value="<?php echo esc_attr($api_key); ?>" class="regular-text">
		<p class="description">Enter your API key for the selected provider.</p>
		<?php
	}
	
	public function render_api_base_url_field() {
		$settings = get_option('alias_generator_settings');
		$default_urls = array(
			'openai' => 'https://api.openai.com',
			'deepseek' => 'https://api.deepseek.com',
			'openrouter' => 'https://openrouter.ai',
			'qwen' => 'https://dashscope.aliyuncs.com'
		);
		$current_provider = isset($settings['api_provider']) ? $settings['api_provider'] : 'openai';
		$default_url = isset($default_urls[$current_provider]) ? $default_urls[$current_provider] : '';
		?>
		<input type="url" name="alias_generator_settings[api_base_url]" id="api_base_url" 
			   value="<?php echo esc_attr(isset($settings['api_base_url']) ? $settings['api_base_url'] : $default_url); ?>" 
			   class="regular-text" placeholder="<?php echo esc_attr($default_url); ?>">
		<p class="description">Base URL for the API endpoint (e.g. https://api.openai.com)</p>
		<?php
	}
	
	public function render_api_path_field() {
		$settings = get_option('alias_generator_settings');
		$default_paths = array(
			'openai' => '/v1/chat/completions',
			'deepseek' => '/v1/chat/completions',
			'openrouter' => '/api/v1/chat/completions',
			'qwen' => '/api/v1/services/aigc/text-generation/generation'
		);
		$current_provider = isset($settings['api_provider']) ? $settings['api_provider'] : 'openai';
		$default_path = isset($default_paths[$current_provider]) ? $default_paths[$current_provider] : '';
		?>
		<input type="text" name="alias_generator_settings[api_path]" id="api_path" 
			   value="<?php echo esc_attr(isset($settings['api_path']) ? $settings['api_path'] : $default_path); ?>" 
			   class="regular-text" placeholder="<?php echo esc_attr($default_path); ?>">
		<p class="description">API path (e.g. /v1/chat/completions)</p>
		<?php
	}
	
	public function render_model_name_field() {
		$settings = get_option('alias_generator_settings');
		$default_models = array(
			'openai' => 'gpt-3.5-turbo',
			'deepseek' => 'deepseek-chat',
			'openrouter' => 'openai/gpt-3.5-turbo',
			'qwen' => 'qwen-turbo'
		);
		$current_provider = isset($settings['api_provider']) ? $settings['api_provider'] : 'openai';
		$default_model = isset($default_models[$current_provider]) ? $default_models[$current_provider] : '';
		?>
		<input type="text" name="alias_generator_settings[model_name]" id="model_name" 
			   value="<?php echo esc_attr(isset($settings['model_name']) ? $settings['model_name'] : $default_model); ?>" 
			   class="regular-text" placeholder="<?php echo esc_attr($default_model); ?>">
		<p class="description">Model name to use (e.g. gpt-3.5-turbo)</p>
		<?php
	}

	/**
	 * 渲染温度值字段
	 */
	public function render_temperature_field() {
		$settings = get_option('alias_generator_settings');
		$temperature = isset($settings['temperature']) ? $settings['temperature'] : 0.7;
		?>
		<input type="number" name="alias_generator_settings[temperature]" id="temperature" 
			   value="<?php echo esc_attr($temperature); ?>" min="0" max="1" step="0.1">
		<p class="description">Controls randomness (0 = deterministic, 1 = very creative).</p>
		<?php
	}

	/**
	 * 渲染最大token字段
	 */
	public function render_max_tokens_field() {
		$settings = get_option('alias_generator_settings');
		$max_tokens = isset($settings['max_tokens']) ? $settings['max_tokens'] : 60;
		?>
		<input type="number" name="alias_generator_settings[max_tokens]" id="max_tokens" 
			   value="<?php echo esc_attr($max_tokens); ?>" min="10" max="200">
		<p class="description">Maximum number of tokens to generate.</p>
		<?php
	}

	/**
	 * 渲染提示词模板字段
	 */
	public function render_prompt_template_field() {
		$settings = get_option('alias_generator_settings');
		$default_prompt = 'Generate a SEO-friendly URL slug for the following article title: "{title}". The slug should be concise, use lowercase letters, hyphens as separators, and contain only alphanumeric characters. Respond with only the slug, nothing else.';
		?>
		<textarea name="alias_generator_settings[prompt_template]" id="prompt_template" 
				  rows="5" cols="50" class="large-text" 
				  placeholder="<?php echo esc_attr($default_prompt); ?>"><?php 
					  echo esc_textarea(isset($settings['prompt_template']) ? $settings['prompt_template'] : $default_prompt); 
				  ?></textarea>
		<p class="description">Prompt template for generating slugs. Use {title} as placeholder for the post title.</p>
		<button type="button" class="button button-secondary" id="reset_prompt">Reset to Default</button>
		<script>
		jQuery(document).ready(function($) {
			$('#reset_prompt').on('click', function() {
				$('#prompt_template').val('<?php echo esc_js($default_prompt); ?>');
			});
		});
		</script>
		<?php
	}
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('alias_generator_settings_group');
                do_settings_sections('Alias-generator');
                submit_button('Save Settings');
                ?>
            </form>
            
            <div class="llm-test-section">
                <h2>Test API Connection</h2>
                <p>Enter a test title to verify the API connection:</p>
                <input type="text" id="llm_test_title" class="regular-text">
                <button id="llm_test_button" class="button button-primary">Test</button>
                <div id="llm_test_result"></div>
            </div>
        </div>
        <?php
    }
    
    public function add_post_row_action($actions, $post) {
        if (current_user_can('edit_post', $post->ID)) {
            $actions['generate_alias'] = sprintf(
                '<a href="#" class="llm-generate-alias" data-post-id="%d">%s</a>',
                $post->ID,
                __('Generate Alias', 'Alias-generator')
            );
        }
        return $actions;
    }
    
    public function add_bulk_action($actions) {
        $actions['batch_generate_alias'] = __('Generate Aliass', 'Alias-generator');
        return $actions;
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'settings_page_Alias-generator' || $hook === 'edit.php') {
            wp_register_script(
                'Alias-generator-admin',
                ALIAS_GENERATOR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                ALIAS_GENERATOR_VERSION,
                true
            );

            wp_localize_script(
                'Alias-generator-admin',
                'alias_generator_vars',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('alias_generator_nonce')
                )
            );
        }
    
        // 只在设置页面加载样式
        if ($hook === 'settings_page_Alias-generator') {
            wp_enqueue_style(
                'Alias-generator-admin',
                ALIAS_GENERATOR_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                ALIAS_GENERATOR_VERSION
            );
        }

        // 最后加载脚本
        wp_enqueue_script('Alias-generator-admin');
    }
    
    // 其他渲染方法...
}