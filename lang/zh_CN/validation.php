<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => '必须接受 :attribute。',
    'accepted_if' => '当 :other 为 :value 时，必须接受 :attribute。',
    'active_url' => ':attribute 必须是有效的网址。',
    'after' => ':attribute 必须是 :date 之后的日期。',
    'after_or_equal' => ':attribute 必须是 :date 或之后的日期。',
    'alpha' => ':attribute 只能包含字母。',
    'alpha_dash' => ':attribute 只能包含字母、数字、短横线和下划线。',
    'alpha_num' => ':attribute 只能包含字母和数字。',
    'any_of' => ':attribute 无效。',
    'array' => ':attribute 必须是数组。',
    'array_keys' => ':attribute 只能包含以下键：:values。',
    'ascii' => ':attribute 只能包含单字节字母数字字符和符号。',
    'base64' => ':attribute 必须是有效的 Base64 字符串。',
    'before' => ':attribute 必须是 :date 之前的日期。',
    'before_or_equal' => ':attribute 必须是 :date 或之前的日期。',
    'between' => [
        'array' => ':attribute 必须包含 :min 至 :max 项。',
        'file' => ':attribute 必须介于 :min 至 :max KB 之间。',
        'numeric' => ':attribute 必须介于 :min 至 :max 之间。',
        'string' => ':attribute 必须介于 :min 至 :max 个字符之间。',
    ],
    'boolean' => ':attribute 必须为是或否。',
    'can' => ':attribute 包含未经授权的值。',
    'confirmed' => ':attribute 的确认内容不匹配。',
    'contains' => ':attribute 缺少必需的值。',
    'current_password' => '密码不正确。',
    'date' => ':attribute 必须是有效的日期。',
    'date_equals' => ':attribute 必须是等于 :date 的日期。',
    'date_format' => ':attribute 必须符合 :format 格式。',
    'decimal' => ':attribute 必须有 :decimal 位小数。',
    'declined' => '必须拒绝 :attribute。',
    'declined_if' => '当 :other 为 :value 时，必须拒绝 :attribute。',
    'different' => ':attribute 和 :other 必须不同。',
    'digits' => ':attribute 必须是 :digits 位数字。',
    'digits_between' => ':attribute 必须是 :min 至 :max 位数字。',
    'dimensions' => ':attribute 的图片尺寸无效。',
    'distinct' => ':attribute 有重复的值。',
    'doesnt_contain' => ':attribute 不能包含以下任何内容：:values。',
    'doesnt_end_with' => ':attribute 不能以下列任一内容结尾：:values。',
    'doesnt_start_with' => ':attribute 不能以下列任一内容开头：:values。',
    'email' => ':attribute 必须是有效的电子邮箱地址。',
    'encoding' => ':attribute 必须使用 :encoding 编码。',
    'ends_with' => ':attribute 必须以下列任一内容结尾：:values。',
    'enum' => '所选的 :attribute 无效。',
    'exists' => '所选的 :attribute 无效。',
    'extensions' => ':attribute 必须是以下扩展名之一：:values。',
    'file' => ':attribute 必须是文件。',
    'filled' => ':attribute 不能为空。',
    'gt' => [
        'array' => ':attribute 必须多于 :value 项。',
        'file' => ':attribute 必须大于 :value KB。',
        'numeric' => ':attribute 必须大于 :value。',
        'string' => ':attribute 必须多于 :value 个字符。',
    ],
    'gte' => [
        'array' => ':attribute 必须至少有 :value 项。',
        'file' => ':attribute 必须大于或等于 :value KB。',
        'numeric' => ':attribute 必须大于或等于 :value。',
        'string' => ':attribute 必须至少有 :value 个字符。',
    ],
    'hex_color' => ':attribute 必须是有效的十六进制颜色。',
    'image' => ':attribute 必须是图片。',
    'in' => '所选的 :attribute 无效。',
    'in_array' => ':attribute 必须存在于 :other 中。',
    'in_array_keys' => ':attribute 必须至少包含以下键之一：:values。',
    'integer' => ':attribute 必须是整数。',
    'ip' => ':attribute 必须是有效的 IP 地址。',
    'ipv4' => ':attribute 必须是有效的 IPv4 地址。',
    'ipv6' => ':attribute 必须是有效的 IPv6 地址。',
    'json' => ':attribute 必须是有效的 JSON 字符串。',
    'list' => ':attribute 必须是列表。',
    'lowercase' => ':attribute 必须为小写。',
    'lt' => [
        'array' => ':attribute 必须少于 :value 项。',
        'file' => ':attribute 必须小于 :value KB。',
        'numeric' => ':attribute 必须小于 :value。',
        'string' => ':attribute 必须少于 :value 个字符。',
    ],
    'lte' => [
        'array' => ':attribute 不能多于 :value 项。',
        'file' => ':attribute 必须小于或等于 :value KB。',
        'numeric' => ':attribute 必须小于或等于 :value。',
        'string' => ':attribute 不能多于 :value 个字符。',
    ],
    'mac_address' => ':attribute 必须是有效的 MAC 地址。',
    'max' => [
        'array' => ':attribute 不能多于 :max 项。',
        'file' => ':attribute 不能大于 :max KB。',
        'numeric' => ':attribute 不能大于 :max。',
        'string' => ':attribute 不能多于 :max 个字符。',
    ],
    'max_digits' => ':attribute 不能多于 :max 位数字。',
    'mimes' => ':attribute 必须是以下类型的文件：:values。',
    'mimetypes' => ':attribute 必须是以下类型的文件：:values。',
    'min' => [
        'array' => ':attribute 必须至少有 :min 项。',
        'file' => ':attribute 必须至少为 :min KB。',
        'numeric' => ':attribute 必须至少为 :min。',
        'string' => ':attribute 必须至少有 :min 个字符。',
    ],
    'min_digits' => ':attribute 必须至少有 :min 位数字。',
    'missing' => ':attribute 必须不存在。',
    'missing_if' => '当 :other 为 :value 时，:attribute 必须不存在。',
    'missing_unless' => '除非 :other 为 :value，否则 :attribute 必须不存在。',
    'missing_with' => '当存在 :values 时，:attribute 必须不存在。',
    'missing_with_all' => '当 :values 都存在时，:attribute 必须不存在。',
    'multiple_of' => ':attribute 必须是 :value 的倍数。',
    'not_in' => '所选的 :attribute 无效。',
    'not_regex' => ':attribute 的格式无效。',
    'numeric' => ':attribute 必须是数字。',
    'password' => [
        'letters' => ':attribute 必须至少包含一个字母。',
        'mixed' => ':attribute 必须至少包含一个大写字母和一个小写字母。',
        'numbers' => ':attribute 必须至少包含一个数字。',
        'symbols' => ':attribute 必须至少包含一个符号。',
        'uncompromised' => '该 :attribute 曾出现在数据泄露中。请选择其他 :attribute。',
    ],
    'present' => ':attribute 必须存在。',
    'present_if' => '当 :other 为 :value 时，:attribute 必须存在。',
    'present_unless' => '除非 :other 为 :value，否则 :attribute 必须存在。',
    'present_with' => '当存在 :values 时，:attribute 必须存在。',
    'present_with_all' => '当 :values 都存在时，:attribute 必须存在。',
    'prohibited' => '不允许填写 :attribute。',
    'prohibited_if' => '当 :other 为 :value 时，不允许填写 :attribute。',
    'prohibited_if_accepted' => '当接受 :other 时，不允许填写 :attribute。',
    'prohibited_if_declined' => '当拒绝 :other 时，不允许填写 :attribute。',
    'prohibited_unless' => '除非 :other 在 :values 中，否则不允许填写 :attribute。',
    'prohibits' => ':attribute 不允许 :other 同时存在。',
    'regex' => ':attribute 的格式无效。',
    'required' => ':attribute 为必填项。',
    'required_array_keys' => ':attribute 必须包含以下条目：:values。',
    'required_if' => '当 :other 为 :value 时，:attribute 为必填项。',
    'required_if_accepted' => '当接受 :other 时，:attribute 为必填项。',
    'required_if_declined' => '当拒绝 :other 时，:attribute 为必填项。',
    'required_unless' => '除非 :other 在 :values 中，否则 :attribute 为必填项。',
    'required_with' => '当存在 :values 时，:attribute 为必填项。',
    'required_with_all' => '当 :values 都存在时，:attribute 为必填项。',
    'required_without' => '当不存在 :values 时，:attribute 为必填项。',
    'required_without_all' => '当 :values 都不存在时，:attribute 为必填项。',
    'same' => ':attribute 必须与 :other 一致。',
    'size' => [
        'array' => ':attribute 必须包含 :size 项。',
        'file' => ':attribute 必须为 :size KB。',
        'numeric' => ':attribute 必须为 :size。',
        'string' => ':attribute 必须为 :size 个字符。',
    ],
    'starts_with' => ':attribute 必须以下列任一内容开头：:values。',
    'string' => ':attribute 必须是字符串。',
    'timezone' => ':attribute 必须是有效的时区。',
    'unique' => ':attribute 已被占用。',
    'uploaded' => ':attribute 上传失败。',
    'uppercase' => ':attribute 必须为大写。',
    'url' => ':attribute 必须是有效的网址。',
    'ulid' => ':attribute 必须是有效的 ULID。',
    'uuid' => ':attribute 必须是有效的 UUID。',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'accent' => '强调色',
        'availability' => '求职意向',
        'avatar' => '照片',
        'bio' => '简短介绍',
        'code' => '验证码',
        'confirm' => '确认内容',
        'confirm_title' => '简历名称',
        'cover_letter' => '求职信',
        'current_password' => '当前密码',
        'description' => '描述',
        'details' => '详细信息',
        'document' => '文件',
        'education' => '教育经历',
        'email' => '电子邮箱地址',
        'experience' => '工作经历',
        'font' => '字体',
        'handle' => '用户名',
        'headline' => '标题',
        'key_language' => '主要语言或工具',
        'language' => '文档语言',
        'letter_to' => '收件对象',
        'links' => '链接',
        'locale' => '语言',
        'location' => '所在地',
        'media' => '图片或视频',
        'media_alt' => '图片或视频的描述',
        'message' => '消息',
        'name' => '姓名',
        'password' => '密码',
        'profile' => '个人简介',
        'profile_visibility' => '个人主页可见性',
        'pronouns' => '人称代词',
        'reason' => '原因',
        'recovery_code' => '恢复代码',
        'resume' => 'JSON Resume 文件',
        'resume_text' => 'JSON',
        'sender_email' => '电子邮箱地址',
        'sender_name' => '姓名',
        'skills' => '技能',
        'slug' => '网址',
        'terms' => '服务条款',
        'theme' => '布局',
        'title' => '标题',
        'university' => '大学或学院',
        'url' => '链接',
        'visibility' => '可见性',
    ],

];
