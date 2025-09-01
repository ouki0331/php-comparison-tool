<?php

require_once __DIR__ . '/vendor/autoload.php';

use PHPComparison\PHPComparator;

try {
    echo "🚀 PHP 版本比较工具启动\n";
    echo str_repeat("=", 50) . "\n";
    
    // 初始化比较器
    $comparator = new PHPComparator();
    
    // 获取并显示 PHP 版本信息
    echo "📋 获取 PHP 版本信息...\n";
    $versions = $comparator->getVersionInfo();
    echo "PHP 7.2 版本:\n" . explode("\n", $versions['php72'])[0] . "\n";
    echo "PHP 8.4 版本:\n" . explode("\n", $versions['php84'])[0] . "\n\n";
    
    // 加载测试代码
    $testCodes = require __DIR__ . '/test_codes.php';
    
    echo "📝 加载了 " . count($testCodes) . " 个测试代码\n\n";
    
    // 运行比较
    $results = $comparator->runComparison($testCodes);
    
    // 生成 Excel 报告
    echo "📊 生成 Excel 报告...\n";
    $reportPath = $comparator->generateExcelReport();
    
    // 打印统计摘要
    $comparator->printSummary();
    
    echo "\n✅ 比较完成！\n";
    echo "📄 详细报告已保存到: {$reportPath}\n";
    
} catch (Exception $e) {
    echo "❌ 错误: " . $e->getMessage() . "\n";
    exit(1);
}

