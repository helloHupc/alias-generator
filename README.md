# alias-generator

## 概述
`alias-generator` 是一个 WordPress 插件，它借助大语言模型（LLM）的能力自动生成文章别名。用户可以通过配置页面设置 LLM API 参数，自定义提示模板，并测试 API 连接。该插件支持在文章列表页面为单篇文章或批量生成文章别名，同时在文章编辑页面的固定链接旁提供一键生成按钮。

## 功能特性
1. **LLM API 配置**：提供专门的配置页面，允许用户设置 LLM API 的相关信息。
2. **自定义提示模板**：支持用户根据需求自定义用于生成文章别名的提示模板。
3. **API 连接测试**：具备 `Test API Connection` 功能，方便用户验证配置是否正确。
4. **单篇及批量生成**：在文章列表页面提供 `generate alias` 按钮，支持单篇和批量生成文章别名。
5. **编辑页快捷生成**：在文章编辑页面的固定链接旁提供 `Generate Alias` 按钮，编辑文章时可直接点击生成别名，无需返回列表页操作。

## 安装步骤
1. 下载 `alias-generator` 插件压缩包。
2. 登录 WordPress 后台，导航到 `插件` -> `添加新插件`。
3. 点击 `上传插件`，选择下载的压缩包并上传。
4. 上传完成后，点击 `激活插件`。

## 使用方法

### 配置 LLM API
1. 登录 WordPress 后台，在侧边栏找到 `Alias Generator` 菜单并点击。
2. 在配置页面中，填写 LLM API 的相关信息，如 API 密钥、端点等。
3. 自定义 `Prompt Template`，根据需要调整生成别名的提示内容。
4. 点击 `Test API Connection` 按钮测试配置是否成功。若连接成功，会显示成功提示；若失败，则显示错误信息。

### 生成文章别名
#### 单篇生成
1. 进入 `文章` -> `所有文章` 页面。
2. 找到需要生成别名的文章，点击 `generate alias` 按钮。
3. 稍等片刻，文章的别名将自动生成并保存。

#### 编辑页快捷生成（v1.1 新增）
1. 进入文章的编辑页面（`文章` -> 点击文章标题进入编辑）。
2. 在页面顶部的固定链接区域找到 `Generate Alias` 按钮。
3. 点击按钮，别名将自动生成并更新固定链接，按钮恢复后即可看到结果。

#### 批量生成
1. 在 `文章` -> `所有文章` 页面，勾选需要生成别名的文章。
2. 在顶部的批量操作下拉菜单中选择 `生成别名`。
3. 点击 `应用` 按钮，插件将批量为选中的文章生成别名。

## 截图说明
![配置页面截图](https://hupc-blog-photo.oss-cn-beijing.aliyuncs.com/wp-content/uploads/2025/05/微信图片_20250527153936.png)
*图 1：LLM API 配置页面*



![高级配置页面](https://hupc-blog-photo.oss-cn-beijing.aliyuncs.com/wp-content/uploads/2025/05/微信图片_20250527153943.png)
*图 2：LLM API 高级配置页面*



![功能测试](https://hupc-blog-photo.oss-cn-beijing.aliyuncs.com/wp-content/uploads/2025/05/微信图片_20250527153952.png)
*图 3：LLM API 功能测试*



![文章列表功能按钮](https://hupc-blog-photo.oss-cn-beijing.aliyuncs.com/wp-content/uploads/2025/05/微信图片_20250527153957.png)
*图 4：文章列表功能按钮*



## 常见问题解答
### 测试 API 连接失败怎么办？
- 检查 API 密钥是否正确，有无拼写错误或过期。
- 确认 API 端点地址是否正确。
- 检查网络连接是否正常，是否可以访问该 API。

### 点击按钮没有反应怎么办？
- 尝试清除浏览器缓存并刷新页面（Ctrl+F5 / Cmd+Shift+R）。
- 打开浏览器开发者工具（F12）Console 面板，检查是否有 JavaScript 错误。
- 确认 API 配置是否正确，API 密钥是否已设置。

### 批量生成别名没有反应怎么办？
- 确保已经勾选了需要生成别名的文章。
- 检查 API 连接是否正常，可通过 `Test API Connection` 功能再次验证。
- 若文章数量较多，可能需要等待一段时间，请耐心等待。

## 贡献与反馈
如果你在使用过程中遇到问题，或者有任何建议和想法，欢迎通过 GitHub 提交 issue。同时，也欢迎你为该项目贡献代码，提交 pull request。

## 许可证
本插件由 [hupengchen](https://github.com/helloHupc) 开发，采用 GNU General Public License v2（GPLv2）许可。详细的许可条款请查看 [LICENSE](LICENSE) 文件。
