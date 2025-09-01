<?php

return [
    [
        'description' => '基本输出测试',
        'code' => 'echo "Hello World";'
    ],
    [
        'description' => '数组函数测试',
        'code' => '$arr = [1, 2, 3]; print_r(array_map(function($x) { return $x * 2; }, $arr));'
    ],
    [
        'description' => '类型声明测试',
        'code' => 'function test(int $a): int { return $a * 2; } echo test(5);'
    ],
    [
        'description' => '空合并运算符测试',
        'code' => '$arr = ["a" => 1, "b" => 2]; echo $arr["c"] ?? "default";'
    ],
    [
        'description' => '匿名类测试',
        'code' => '$obj = new class { public function test() { return "anonymous class"; } }; echo $obj->test();'
    ],
    [
        'description' => 'JSON 处理测试',
        'code' => 'echo json_encode(["test" => "value", "number" => 123, "array" => [1,2,3]]);'
    ],
    [
        'description' => '错误处理测试 - 未定义数组键',
        'code' => '$arr = []; echo @$arr["nonexistent"];'
    ],
    [
        'description' => '字符串函数测试',
        'code' => 'echo str_replace("world", "PHP", "Hello world");'
    ],
    [
        'description' => '数学函数测试',
        'code' => 'echo round(3.14159, 2);'
    ],
    [
        'description' => '日期时间测试',
        'code' => 'echo date("Y-m-d");'
    ],
    [
        'description' => 'PHP 8 新特性 - 命名参数（会在 PHP 7.2 中报错）',
        'code' => 'function greet($name, $greeting = "Hello") { return "$greeting, $name!"; } echo greet(name: "World", greeting: "Hi");'
    ],
    [
        'description' => 'PHP 8 新特性 - Match 表达式（会在 PHP 7.2 中报错）',
        'code' => '$value = 2; echo match($value) { 1 => "one", 2 => "two", default => "other" };'
    ],
    [
        'description' => '数组解构测试',
        'code' => '[$a, $b] = [1, 2]; echo "a=$a, b=$b";'
    ],
    [
        'description' => 'Null 合并赋值运算符测试',
        'code' => '$var = null; $var ??= "default"; echo $var;'
    ],
    [
        'description' => '严格类型检查',
        'code' => 'declare(strict_types=1); function add(int $a, int $b): int { return $a + $b; } echo add(1, 2);'
    ]
];

