import json
import subprocess
from openpyxl import Workbook

PHP_WARNINGS = [
    "Warning:",
    "Notice:",
    "Deprecated:",
    "Fatal error:",
    "Recoverable fatal error:",
]

def split_php_output(output):
    warning_lines = []
    result_lines = []
    for line in output.split('\n'):
        if any(w in line for w in PHP_WARNINGS):
            warning_lines.append(line)
        else:
            result_lines.append(line)
    warning = "\n".join(warning_lines).strip()
    result = "\n".join(result_lines).strip()
    return warning, result

def php_arg_str(arg):
    if isinstance(arg, dict) and "type" in arg:
        if arg["type"] == "null":
            return "null"
        elif arg["type"] == "stdClass":
            return "new stdClass"
    return json.dumps(arg)

def format_args(args):
    return "(" + ", ".join([php_arg_str(arg) for arg in args]) + ")"

def main():
    with open("./src/data/testdata.json", "r", encoding="utf-8") as f:
        testData = json.load(f)

    wb = Workbook()
    ws = wb.active
    ws.title = "PHP Comparison"

    for func in testData:
        # 标题行
        ws.append([f"関数: {func}"])
        # 表头
        ws.append(["実行パラメータ", "72環境結果", "84環境結果", "等しい", "72警告", "84警告"])
        for caseArgs in testData[func]:
            php_args = ', '.join([php_arg_str(arg) for arg in caseArgs])
            args_str = format_args(caseArgs)

            # 72环境
            php_code_72 = f'php -r \'var_dump({func}({php_args}));\''
            result_72 = subprocess.run(
                ['docker', 'exec', 'php72-env', 'bash', '-c', php_code_72],
                capture_output=True, text=True
            )
            output_72 = result_72.stdout.strip()
            warning_72, result_val_72 = split_php_output(output_72)

            # 84环境
            php_code_84 = (
                "php -r 'require \"/app/function.php\"; "
                f"var_dump({func}_v2({php_args}));'"
            )
            result_84 = subprocess.run(
                ['docker', 'exec', 'php84-env', 'bash', '-c', php_code_84],
                capture_output=True, text=True
            )
            output_84 = result_84.stdout.strip()
            warning_84, result_val_84 = split_php_output(output_84)

            # 结果是否相等
            is_equal = (result_val_72 == result_val_84)

            ws.append([
                args_str,
                result_val_72,
                result_val_84,
                "true" if is_equal else "false",
                warning_72,
                warning_84
            ])
        # 空行分隔不同函数
        ws.append([])

    wb.save("./output/php_compare_result.xlsx")

main()