# 语言文件说明

本目录包含插件的翻译文件。

## 文件说明

- `ai-seo-content-booster.pot` - 翻译模板文件
- `ai-seo-content-booster-zh_CN.po` - 中文（简体）翻译源文件
- `ai-seo-content-booster-en_US.po` - 英文（美国）翻译源文件

## 编译 .mo 文件

`.po` 文件需要编译为 `.mo` 文件才能被 WordPress 使用。

### 方法 1：使用 Poedit（推荐）

1. 下载并安装 [Poedit](https://poedit.net/)
2. 打开 `.po` 文件
3. 点击"保存"，会自动生成对应的 `.mo` 文件

### 方法 2：使用命令行工具 msgfmt

如果系统已安装 gettext 工具：

```bash
msgfmt ai-seo-content-booster-zh_CN.po -o ai-seo-content-booster-zh_CN.mo
msgfmt ai-seo-content-booster-en_US.po -o ai-seo-content-booster-en_US.mo
```

### 方法 3：使用在线工具

可以使用在线 PO 到 MO 转换工具，如：
- https://po2mo.net/
- https://www.easytranslation.com/po-to-mo-converter

## 语言切换

WordPress 会根据后台设置的语言自动加载对应的翻译文件：

- 当 WordPress 语言设置为 `zh_CN`（中文）时，会显示中文界面
- 当 WordPress 语言设置为 `en_US`（英文）或其他语言时，会显示英文界面

如果找不到对应语言的翻译文件，将使用代码中的原始文本（中文）。










