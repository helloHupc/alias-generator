jQuery(document).ready(function($) {

    // ========== 编辑页/新建页：固定链接后的生成按钮 ==========
    $(document).on('click', '.alias-generator-edit-btn', function(e) {
        e.preventDefault();

        if (typeof alias_generator_vars === 'undefined') {
            console.error('[Alias Generator] alias_generator_vars is not defined');
            return;
        }

        var $btn = $(this);
        var postId = $btn.data('post-id');
        var originalText = $btn.text();

        console.log('[Alias Generator] Sending AJAX:', {
            action: 'llm_generate_alias',
            post_id: postId
        });

        $btn.prop('disabled', true).text(alias_generator_vars.generating_text || 'Generating...');

        $.ajax({
            url: alias_generator_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_generate_alias',
                post_id: postId,
                nonce: alias_generator_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    var newSlug = response.data.slug;
                    // 更新隐藏的 slug 输入框
                    $('#post_name').val(newSlug);
                    // 更新显示的固定链接
                    var $samplePermalink = $('#sample-permalink');
                    if ($samplePermalink.length) {
                        var baseUrl = $samplePermalink.data('permalink-orig');
                        if (!baseUrl) {
                            // 从当前 permalink 结构提取基础 URL
                            var fullHtml = $samplePermalink.html();
                            var match = fullHtml.match(/(https?:\/\/[^<]+)\//);
                            baseUrl = match ? match[1] + '/' : '';
                        }
                        $('#editable-post-name').text(newSlug);
                        $('#editable-post-name-full').text(baseUrl + newSlug);
                    }
                    // 更新隐藏的原始 slug（和 WP 内置逻辑同步）
                    if (typeof wp !== 'undefined' && wp.heartbeat) {
                        $('#editable-post-name').text(newSlug);
                    }
                    $btn.text(alias_generator_vars.success_text || 'Generated!');
                } else {
                    alert(response.data.message || 'Generation failed.');
                    $btn.text(alias_generator_vars.error_text || 'Error!');
                }
            },
            error: function() {
                $btn.text(alias_generator_vars.error_text || 'Error!');
                alert('Network error.');
            },
            complete: function() {
                $btn.prop('disabled', false);
                setTimeout(function() {
                    if ($btn.text() !== originalText) {
                        $btn.text(originalText);
                    }
                }, 2000);
            }
        });
    });

    // 各 API 供应商的默认配置
    const apiDefaults = {
        'openai': {
            'base_url': 'https://api.openai.com',
            'path': '/v1/chat/completions',
            'model': 'gpt-4o-mini'
        },
        'anthropic': {
            'base_url': 'https://api.anthropic.com',
            'path': '/v1/messages',
            'model': 'claude-3-5-sonnet-20241022'
        },
        'deepseek': {
            'base_url': 'https://api.deepseek.com',
            'path': '/v1/chat/completions',
            'model': 'deepseek-chat'
        },
        'modelscope': {
            'base_url': 'https://api-inference.modelscope.cn',
            'path': '/v1/chat/completions',
            'model': 'Tencent-Hunyuan/Hy3'
        },
        'custom': {
            'base_url': '',
            'path': '/v1/chat/completions',
            'model': ''
        }
    };

    /**
     * 更新 API 表单字段
     * @param {string} provider 供应商 key
     * @param {boolean} force 为 true 时强制覆盖为默认值（用户主动切换供应商）
     */
    function updateApiFields(provider, force) {
        const defaults = apiDefaults[provider] || {};

        $('#api_base_url').attr('placeholder', defaults.base_url || '');
        $('#api_path').attr('placeholder', defaults.path || '');
        $('#model_name').attr('placeholder', defaults.model || '');

        if (force) {
            $('#api_base_url').val(defaults.base_url || '');
            $('#api_path').val(defaults.path || '');
            $('#model_name').val(defaults.model || '');
            return;
        }

        // 初始化或刷新页面时：只填充空值，保留用户已保存的自定义内容
        var baseUrl = $('#api_base_url').val();
        var path = $('#api_path').val();
        var model = $('#model_name').val();

        if (!baseUrl) {
            $('#api_base_url').val(defaults.base_url || '');
        }
        if (!path) {
            $('#api_path').val(defaults.path || '');
        }
        if (!model) {
            $('#model_name').val(defaults.model || '');
        }
    }

    // 初始化页面时设置一次（不强制覆盖）
    updateApiFields($('#api_provider').val(), false);

    // 监听 API 供应商变化（强制覆盖为官方默认值）
    $('#api_provider').on('change', function() {
        $('#api_key').val('');
        updateApiFields($(this).val(), true);
    });

    // 重置提示词模板
    $('#reset_prompt').on('click', function(e) {
        e.preventDefault();
        $('#prompt_template').val($('#prompt_template').attr('placeholder'));
    });

    // 单篇文章生成 slug
    $(document).on('click', '.llm-generate-alias', function(e) {
        e.preventDefault();
        var $button = $(this);
        var postId = $button.data('post-id');
        console.log('postId', postId);

        $button.text(alias_generator_vars.generating_text);

        $.ajax({
            url: alias_generator_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_generate_alias',
                post_id: postId,
                nonce: alias_generator_vars.nonce
            },
            success: function(response) {
                console.log('response', response);
                if (response.success) {
                    $button.text(alias_generator_vars.success_text);
                    // 刷新页面以显示新 slug
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $button.text(alias_generator_vars.error_text);
                    alert(response.data.message);
                }
            },
            error: function() {
                $button.text(alias_generator_vars.error_text);
            }
        });
    });

    // 批量生成 slug
    var bulkAction = $('select[name="action"]');
    if (bulkAction.length) {
        bulkAction.append($('<option>').val('batch_generate_alias').text(alias_generator_vars.bulk_action_text));
    }

    // 测试 API 连接
    $('#llm_test_button').on('click', function() {
        var $button = $(this);
        var testTitle = $('#llm_test_title').val();

        if (!testTitle) {
            alert('Please enter a test title');
            return;
        }

        $button.prop('disabled', true).text('Testing...');

        $.ajax({
            url: alias_generator_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'llm_test_api',
                title: testTitle,
                nonce: alias_generator_vars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#llm_test_result').html(
                        '<div class="notice notice-success"><p>' +
                        response.data.message + '<br>Generated slug: <strong>' +
                        response.data.slug + '</strong></p></div>'
                    );
                } else {
                    $('#llm_test_result').html(
                        '<div class="notice notice-error"><p>' +
                        response.data.message + '</p></div>'
                    );
                }
            },
            complete: function() {
                $button.prop('disabled', false).text('Test');
            }
        });
    });

    // 处理批量操作
    $('#doaction').on('click', function(e) {
        var action = $('select[name="action"]').val();
        if (action === 'batch_generate_alias') {
            e.preventDefault();

            var postIds = [];
            $('input[name="post[]"]:checked').each(function() {
                postIds.push($(this).val());
            });

            if (postIds.length === 0) {
                alert('Please select at least one post.');
                return;
            }

            if (!confirm('Generate alias for ' + postIds.length + ' selected posts?')) {
                return;
            }

            $.ajax({
                url: alias_generator_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'llm_batch_generate_alias',
                    post_ids: postIds,
                    nonce: alias_generator_vars.nonce
                },
                success: function(response) {
                    console.log('[Alias Generator] AJAX response:', response);
                    if (response.success) {
                        var successCount = 0;
                        var errorCount = 0;

                        $.each(response.data.results, function(i, result) {
                            if (result.success) {
                                successCount++;
                            } else {
                                errorCount++;
                            }
                        });

                        alert('Completed! Success: ' + successCount + ', Failed: ' + errorCount);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                }
            });
        }
    });
});
