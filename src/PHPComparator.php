<?php

namespace PHPComparison;

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class PHPComparator
{
    private $results = [];
    private $php72Container = 'php72-env';
    private $php84Container = 'php84-env';
    
    public function __construct()
    {
        // 检查 Docker 容器是否运行
        $this->checkDockerContainers();
    }
    
    /**
     * 检查 Docker 容器状态
     */
    private function checkDockerContainers()
    {
        $containers = [$this->php72Container, $this->php84Container];
        
        foreach ($containers as $container) {
            $command = "docker ps --filter name={$container} --format '{{.Names}}'";
            $output = shell_exec($command);
            
            if (strpos($output, $container) === false) {
                throw new \Exception("Docker 容器 {$container} 未运行。请先启动 Docker 环境。");
            }
        }
        
        echo "✅ Docker 容器检查通过\n";
    }
    
    /**
     * 在指定容器中执行 PHP 代码
     */
    private function executeCodeInContainer($container, $code, $index)
    {
        // 创建临时 PHP 文件
        $tempFileName = "temp_code_{$index}_" . uniqid() . ".php";
        $tempFilePath = "/tmp/{$tempFileName}";
        
        // 包装代码以捕获所有输出和错误
        $wrappedCode = "<?php\n";
        $wrappedCode .= "error_reporting(E_ALL);\n";
        $wrappedCode .= "ini_set('display_errors', 1);\n";
        $wrappedCode .= "ini_set('log_errors', 0);\n";
        $wrappedCode .= "ob_start();\n";
        $wrappedCode .= "set_error_handler(function(\$errno, \$errstr, \$errfile, \$errline) {\n";
        $wrappedCode .= "    echo \"ERROR: [\$errno] \$errstr in \$errfile on line \$errline\";\n";
        $wrappedCode .= "    return true;\n";
        $wrappedCode .= "});\n";
        $wrappedCode .= "try {\n";
        $wrappedCode .= $code . "\n";
        $wrappedCode .= "} catch (Throwable \$e) {\n";
        $wrappedCode .= "    echo 'EXCEPTION: ' . \$e->getMessage() . ' in ' . \$e->getFile() . ' on line ' . \$e->getLine();\n";
        $wrappedCode .= "}\n";
        $wrappedCode .= "\$output = ob_get_clean();\n";
        $wrappedCode .= "echo \$output;\n";
        
        // 将代码写入临时文件
        file_put_contents($tempFilePath, $wrappedCode);
        
        // 在 Docker 容器中执行
        $dockerCommand = "docker exec {$container} php {$tempFilePath} 2>&1";
        $output = shell_exec($dockerCommand);
        
        // 清理临时文件
        unlink($tempFilePath);
        
        return [
            'output' => trim($output ?? ''),
            'success' => !$this->hasError($output),
            'execution_time' => microtime(true)
        ];
    }
    
    /**
     * 检查输出是否包含错误
     */
    private function hasError($output)
    {
        $errorPatterns = ['ERROR:', 'EXCEPTION:', 'Fatal error:', 'Parse error:', 'Warning:', 'Notice:'];
        
        foreach ($errorPatterns as $pattern) {
            if (strpos($output, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 比较两个执行结果
     */
    private function compareResults($result72, $result84)
    {
        $comparison = [
            'identical' => false,
            'differences' => [],
            'summary' => ''
        ];
        
        // 比较输出内容
        if ($result72['output'] === $result84['output']) {
            $comparison['identical'] = true;
            $comparison['summary'] = '✅ 输出完全一致';
        } else {
            $comparison['differences'][] = "输出内容不同";
            
            // 详细差异分析
            if (empty($result72['output']) && !empty($result84['output'])) {
                $comparison['differences'][] = "PHP 7.2 无输出，PHP 8.4 有输出";
            } elseif (!empty($result72['output']) && empty($result84['output'])) {
                $comparison['differences'][] = "PHP 7.2 有输出，PHP 8.4 无输出";
            } else {
                $comparison['differences'][] = "输出内容差异";
            }
        }
        
        // 比较执行状态
        if ($result72['success'] !== $result84['success']) {
            $comparison['differences'][] = "执行状态不同";
            $comparison['differences'][] = "PHP 7.2: " . ($result72['success'] ? '成功' : '失败');
            $comparison['differences'][] = "PHP 8.4: " . ($result84['success'] ? '成功' : '失败');
        }
        
        if (!$comparison['identical']) {
            $comparison['summary'] = '❌ 存在差异 (' . count($comparison['differences']) . ' 项)';
        }
        
        return $comparison;
    }
    
    /**
     * 运行代码比较
     */
    public function runComparison($codeArray)
    {
        $this->results = [];
        $total = count($codeArray);
        
        echo "开始执行 {$total} 个代码片段的比较测试...\n";
        echo str_repeat("=", 50) . "\n";
        
        foreach ($codeArray as $index => $codeInfo) {
            $code = is_array($codeInfo) ? $codeInfo['code'] : $codeInfo;
            $description = is_array($codeInfo) ? $codeInfo['description'] : "代码片段 " . ($index + 1);
            
            echo sprintf("[%d/%d] 执行: %s\n", $index + 1, $total, $description);
            
            // 在两个 PHP 环境中执行代码
            $startTime = microtime(true);
            
            $result72 = $this->executeCodeInContainer($this->php72Container, $code, $index);
            $result84 = $this->executeCodeInContainer($this->php84Container, $code, $index);
            
            $executionTime = microtime(true) - $startTime;
            
            // 比较结果
            $comparison = $this->compareResults($result72, $result84);
            
            $this->results[] = [
                'index' => $index + 1,
                'description' => $description,
                'code' => $code,
                'php72_result' => $result72,
                'php84_result' => $result84,
                'comparison' => $comparison,
                'execution_time' => round($executionTime, 4)
            ];
            
            // 显示简要结果
            echo "  " . $comparison['summary'] . " (耗时: {$executionTime}s)\n";
            
            if (!$comparison['identical']) {
                echo "  PHP 7.2: " . substr($result72['output'], 0, 50) . (strlen($result72['output']) > 50 ? '...' : '') . "\n";
                echo "  PHP 8.4: " . substr($result84['output'], 0, 50) . (strlen($result84['output']) > 50 ? '...' : '') . "\n";
            }
            
            echo "\n";
        }
        
        return $this->results;
    }
    
    /**
     * 生成 Excel 报告
     */
    public function generateExcelReport($filename = null)
    {
        if ($filename === null) {
            $filename = 'php_comparison_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // 设置标题
        $sheet->setTitle('PHP 版本比较报告');
        
        // 设置表头
        $headers = [
            'A1' => '序号',
            'B1' => '描述',
            'C1' => 'PHP 代码',
            'D1' => 'PHP 7.2 输出',
            'E1' => 'PHP 8.4 输出',
            'F1' => '比较结果',
            'G1' => '差异详情',
            'H1' => '执行时间(s)'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // 设置表头样式
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2F5597']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        
        // 填充数据
        $row = 2;
        foreach ($this->results as $result) {
            $sheet->setCellValue('A' . $row, $result['index']);
            $sheet->setCellValue('B' . $row, $result['description']);
            $sheet->setCellValue('C' . $row, $result['code']);
            $sheet->setCellValue('D' . $row, $result['php72_result']['output']);
            $sheet->setCellValue('E' . $row, $result['php84_result']['output']);
            $sheet->setCellValue('F' . $row, $result['comparison']['summary']);
            $sheet->setCellValue('G' . $row, implode("\n", $result['comparison']['differences']));
            $sheet->setCellValue('H' . $row, $result['execution_time']);
            
            // 根据比较结果设置行颜色
            $rowStyle = [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
            ];
            
            if (!$result['comparison']['identical']) {
                $rowStyle['fill'] = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFE6E6']];
            } else {
                $rowStyle['fill'] = ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E6F3E6']];
            }
            
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($rowStyle);
            $row++;
        }
        
        // 设置列宽
        $columnWidths = [
            'A' => 8,   // 序号
            'B' => 25,  // 描述
            'C' => 40,  // PHP 代码
            'D' => 30,  // PHP 7.2 输出
            'E' => 30,  // PHP 8.4 输出
            'F' => 20,  // 比较结果
            'G' => 35,  // 差异详情
            'H' => 12   // 执行时间
        ];
        
        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        
        // 设置文本换行
        $sheet->getStyle('B:G')->getAlignment()->setWrapText(true);
        
        // 添加统计信息
        $totalTests = count($this->results);
        $identicalTests = count(array_filter($this->results, function($r) { return $r['comparison']['identical']; }));
        $differentTests = $totalTests - $identicalTests;
        
        $statsRow = $row + 2;
        $sheet->setCellValue('A' . $statsRow, '统计信息:');
        $sheet->setCellValue('B' . $statsRow, "总测试数: {$totalTests}");
        $sheet->setCellValue('C' . $statsRow, "结果一致: {$identicalTests}");
        $sheet->setCellValue('D' . $statsRow, "存在差异: {$differentTests}");
        $sheet->setCellValue('E' . $statsRow, "一致率: " . round(($identicalTests / $totalTests) * 100, 2) . "%");
        
        $sheet->getStyle('A' . $statsRow . ':E' . $statsRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'F0F0F0']]
        ]);
        
        // 保存文件
        $outputPath = __DIR__ . '/../output/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputPath);
        
        echo "📊 Excel 报告已生成: {$outputPath}\n";
        
        return $outputPath;
    }
    
    /**
     * 获取 PHP 版本信息
     */
    public function getVersionInfo()
    {
        $version72 = shell_exec("docker exec {$this->php72Container} php -v");
        $version84 = shell_exec("docker exec {$this->php84Container} php -v");
        
        return [
            'php72' => trim($version72),
            'php84' => trim($version84)
        ];
    }
    
    /**
     * 打印统计报告
     */
    public function printSummary()
    {
        $total = count($this->results);
        $identical = array_filter($this->results, function($r) { return $r['comparison']['identical']; });
        $different = $total - count($identical);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📋 比较结果统计报告\n";
        echo str_repeat("=", 60) . "\n";
        echo sprintf("总测试数量: %d\n", $total);
        echo sprintf("结果一致: %d (%.1f%%)\n", count($identical), (count($identical) / $total) * 100);
        echo sprintf("存在差异: %d (%.1f%%)\n", $different, ($different / $total) * 100);
        echo str_repeat("=", 60) . "\n";
        
        if ($different > 0) {
            echo "\n❌ 存在差异的测试:\n";
            foreach ($this->results as $result) {
                if (!$result['comparison']['identical']) {
                    echo sprintf("  - [%d] %s\n", $result['index'], $result['description']);
                }
            }
        }
    }
}

