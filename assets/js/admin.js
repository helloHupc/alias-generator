jQuery(document).ready(function($) {

	// 定义各API供应商的默认配置
    const apiDefaults = {
        'openai': {
            'base_url': 'https://api.openai.com',
            'path': '/v1/chat/completions',
            'model': 'gpt-3.5-turbo'
        },
        'deepseek': {
            'base_url': 'https://api.deepseek.com',
            'path': '/v1/chat/completions',
            'model': 'deepseek-chat'
        },
        'openrouter': {
            'base_url': 'https://openrouter.ai',
            'path': '/api/v1/chat/completions',
            'model': 'openai/gpt-3.5-turbo'
        },
        'qwen': {
            'base_url': 'https://dashscope.aliyuncs.com',
            'path': '/compatible-mode/v1/chat/completions',
            'model': 'qwen-plus'
        },
		'siliconflow': {
			'base_url': 'https://api.siliconflow.cn',
			'path': '/v1/chat/completions',
			'model': 'deepseek-ai/DeepSeek-V3'
		},
        'custom': {
            'base_url': '',
            'path': '',
            'model': ''
        }
    };

    // 更新表单字段的函数
    function updateApiFields(provider) {
        const defaults = apiDefaults[provider] || {};

        // 只更新空值或与默认值相同的字段
        $('#api_base_url').attr('placeholder', defaults.base_url || '');
        $('#api_path').attr('placeholder', defaults.path || '');
        $('#model_name').attr('placeholder', defaults.model || '');

        // 如果当前值为空或等于旧默认值，则更新值
        if (!$('#api_base_url').val() || Object.values(apiDefaults).some(d => d.base_url === $('#api_base_url').val())) {
            $('#api_base_url').val(defaults.base_url || '');
        }

        if (!$('#api_path').val() || Object.values(apiDefaults).some(d => d.path === $('#api_path').val())) {
            $('#api_path').val(defaults.path || '');
        }

        if(!$('#model_name').val()){
            $('#model_name').val(defaults.model || '');
        }
    }

    // 初始化页面时设置一次
    updateApiFields($('#api_provider').val());

    // 监听API供应商变化
    $('#api_provider').on('change', function() {
        // 清空api_key
        $('#api_key').val('');
        // 清空模型名称
        $('#model_name').val('');
        updateApiFields($(this).val());
    });

    // 重置提示词模板
    $('#reset_prompt').on('click', function(e) {
        e.preventDefault();
        $('#prompt_template').val($('#prompt_template').attr('placeholder'));
    });

    // 单篇文章生成slug
    $(document).on('click', '.llm-generate-alias', function(e) {
        e.preventDefault();
        var $button = $(this);
        var postId = $button.data('post-id');
		console.log('postId',postId)

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
				console.log('response',response)
                if (response.success) {
                    $button.text(alias_generator_vars.success_text);
                    // 刷新页面以显示新slug
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

    // 批量生成slug
    var bulkAction = $('select[name="action"]');
    if (bulkAction.length) {
        bulkAction.append($('<option>').val('batch_generate_alias').text(alias_generator_vars.bulk_action_text));
    }

    // 测试API连接
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