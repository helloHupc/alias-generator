<?php
class Alias_Generator_Ajax {
    
    public function __construct() {
        add_action('wp_ajax_llm_generate_alias', array($this, 'handle_generate_alias'));
        add_action('wp_ajax_llm_test_api', array($this, 'handle_test_api'));
        add_action('wp_ajax_llm_batch_generate_alias', array($this, 'handle_batch_generate_alias'));
    }
    
    public function handle_generate_alias() {
        check_ajax_referer('alias_generator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if ($post_id) {
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => 'Post not found.'));
            }
            $title = $post->post_title;
        }
        
        if (empty($title)) {
            wp_send_json_error(array('message' => 'Title is required.'));
        }
        
        $core = new Alias_Generator_Core();
        $slug = $core->generate_alias($title, $post_id);
        
        if ($slug) {
            if ($post_id) {
                // 更新文章slug
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_name' => $slug
                ));
            }
            wp_send_json_success(array('slug' => $slug));
        } else {
            wp_send_json_error(array('message' => 'Failed to generate slug.'));
        }
    }
    
    public function handle_test_api() {
        check_ajax_referer('alias_generator_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        
        if (empty($title)) {
            wp_send_json_error(array('message' => 'Test title is required.'));
        }
        
        $core = new Alias_Generator_Core();
        $slug = $core->generate_alias($title);
        
        if ($slug) {
            wp_send_json_success(array(
                'slug' => $slug,
                'message' => 'API connection successful!'
            ));
        } else {
            wp_send_json_error(array('message' => 'API connection failed. Check your settings.'));
        }
    }
    
    public function handle_batch_generate_alias() {
        check_ajax_referer('alias_generator_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Permission denied.'));
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_send_json_error(array('message' => 'No posts selected.'));
        }
        
        $core = new Alias_Generator_Core();
        $results = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $slug = $core->generate_alias($post->post_title, $post_id);
                if ($slug) {
                    // 更新文章slug
                    wp_update_post(array(
                        'ID' => $post_id,
                        'post_name' => $slug
                    ));
                    $results[] = array(
                        'post_id' => $post_id,
                        'slug' => $slug,
                        'success' => true
                    );
                } else {
                    $results[] = array(
                        'post_id' => $post_id,
                        'success' => false,
                        'message' => 'Failed to generate slug'
                    );
                }
            }
        }
        
        wp_send_json_success(array('results' => $results));
    }
}