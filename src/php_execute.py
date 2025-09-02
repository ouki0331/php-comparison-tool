import json
import subprocess

def main():
    with open("./src/data/testdata.json", "r", encoding="utf-8") as f:
        testData = json.load(f)
    for func in testData:
        print(f"Testing function: {func}")
        for caseArgs in testData[func]:
            # 参数拼接
            php_args = ', '.join([json.dumps(arg) for arg in caseArgs])
            php_code = f'php -r \'echo {func}({php_args});\''
            # 执行 docker 命令
            result = subprocess.run(
                ['docker', 'exec', 'php72-env', 'bash', '-c', php_code],
                capture_output=True, text=True
            )
            output = result.stdout.strip()
            print(f"  Args: {caseArgs} => Output: {output}")

main()