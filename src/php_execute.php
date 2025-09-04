<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PhpVersionComparer {
    private $testData;
    private $results = [];
    
    public function __construct() {
        $this->testData = require __DIR__ . '/data/testdata.php';
    }
    
    public function runTests() {
        foreach ($this->testData as $functionName => $testCases) {
            echo "Testing function: $functionName\n";
            
            foreach ($testCases as $params) {
                $this->runSingleTest($functionName, $params);
            }
        }
    }
    
    private function runSingleTest($functionName, $params) {
        $result72 = $this->executeInDocker('php72', $functionName, $params);
        $result84 = $this->executeInDocker('php84', $functionName, $params);
        
        $this->results[] = [
            'function' => $functionName,
            'params' => $params,
            'result_72' => $result72,
            'result_84' => $result84,
            'equal' => $result72['output'] === $result84['output']
        ];
        
        $paramStr = $this->formatParams($params);
        echo "  $paramStr - " . ($result72['output'] === $result84['output'] ? 'SAME' : 'DIFF') . "\n";
    }
    
    private function executeInDocker($phpVersion, $functionName, $params) {
        $script = $this->generateTestScript($functionName, $params,$phpVersion);
        $tmpFile = __DIR__."/tmp/test_$phpVersion.php";
        $dockerFile = "/app/tmp/test_$phpVersion.php";
        file_put_contents($tmpFile, $script);
        
        $containerName = "{$phpVersion}-env";
        $cmd = "docker exec $containerName php $dockerFile 2>&1";
        
        $output = shell_exec($cmd);
        $lines = explode("\n", trim($output));
        $result = array_pop($lines);
        $warnings = implode("\n", $lines);
        
        return [
            'output' => $result,
            'warnings' => $warnings
        ];
    }
    
    private function generateTestScript($functionName, $params,$phpVersion) {
        $script = "<?php\n";
        $script .= "error_reporting(E_ALL);\n\n";
        
        // 直接用var_export生成参数
        foreach ($params as $i => $param) {
            $script .= '$param' . $i . ' = ' . var_export($param, true) . ";\n";
        }
        
        $paramList = [];
        for ($i = 0; $i < count($params); $i++) {
            $paramList[] = '$param' . $i;
        }
        if($phpVersion==="php84"){
            $script .= file_get_contents(__DIR__."/function_v2.txt");
        }else{
            $script .= "\n\$result = $functionName(" . implode(', ', $paramList) . ");\n";
            $script .= "echo var_export(\$result, true);\n";
        }
       
        
        return $script;
    }
    
    private function formatParams($params) {
        $formatted = [];
        foreach ($params as $param) {
            $formatted[] = var_export($param, true);
        }
        return '(' . implode(', ', $formatted) . ')';
    }
    
    public function exportToExcel() {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = ['実行パラメータ', '72環境結果', '84環境結果', '等しい', '72警告', '84警告'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }
        
        $row = 2;
        $currentFunction = '';
        
        foreach ($this->results as $result) {
            if ($currentFunction !== $result['function']) {
                $currentFunction = $result['function'];
                $sheet->setCellValueByColumnAndRow(1, $row, "関数: {$currentFunction}");
                $row++;
            }
            
            $sheet->setCellValueByColumnAndRow(1, $row, $this->formatParams($result['params']));
            $sheet->setCellValueByColumnAndRow(2, $row, $result['result_72']['output']);
            $sheet->setCellValueByColumnAndRow(3, $row, $result['result_84']['output']);
            $sheet->setCellValueByColumnAndRow(4, $row, $result['equal'] ? 'true' : 'false');
            $sheet->setCellValueByColumnAndRow(5, $row, $result['result_72']['warnings']);
            $sheet->setCellValueByColumnAndRow(6, $row, $result['result_84']['warnings']);
            
            $row++;
        }
        
        $writer = new Xlsx($spreadsheet);
        $outputPath = __DIR__ . '/../output/php_compare_result.xlsx';
        $writer->save($outputPath);
        
        echo "\nResults saved to: $outputPath\n";
    }
}

// 执行
$comparer = new PhpVersionComparer();
$comparer->runTests();
$comparer->exportToExcel();
