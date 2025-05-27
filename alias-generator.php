<?php
/*
Plugin Name: Alias Generator
Text Domain: Alias Generator
Domain Path: /languages
Description: Generate post alias using LLM APIs
Version: 1.0
Author: hupengchen
License: GPLv2
*/

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('ALIAS_GENERATOR_VERSION', '1.0');
define('ALIAS_GENERATOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALIAS_GENERATOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// 包含必要文件
require_once ALIAS_GENERATOR_PLUGIN_DIR . 'includes/class-alias-generator-admin.php';
require_once ALIAS_GENERATOR_PLUGIN_DIR . 'includes/class-alias-generator-ajax.php';
require_once ALIAS_GENERATOR_PLUGIN_DIR . 'includes/class-alias-generator-core.php';

// 初始化插件
function alias_generator_init() {
    $admin = new Alias_Generator_Admin();
    $ajax = new Alias_Generator_Ajax();
    $core = new Alias_Generator_Core();
    
    // 注册激活/停用钩子
    register_activation_hook(__FILE__, array($core, 'activate'));
    register_deactivation_hook(__FILE__, array($core, 'deactivate'));
}

function alias_generator_load_textdomain() {
    load_plugin_textdomain(
        'alias_generator', // 文本域
        false,
        dirname(plugin_basename(__FILE__)) . '/languages' // 路径
    );
}

add_action('plugins_loaded', 'alias_generator_load_textdomain');
add_action('plugins_loaded', 'alias_generator_init');