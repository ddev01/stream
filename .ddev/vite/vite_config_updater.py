#!/usr/bin/env python3
#ddev-generated

import os
import re
import sys

PORT_VALUE = "5173"
SERVER_CONFIG_TEMPLATE = """    server: {
        host: '0.0.0.0',
        port: port,
        strictPort: true,
        origin: origin,
        cors: {
            origin: process.env.DDEV_PRIMARY_URL,
        },
    },"""

def find_config_file():
    js_config = os.path.join(os.getcwd(), "vite.config.js")
    ts_config = os.path.join(os.getcwd(), "vite.config.ts")

    if os.path.isfile(js_config):
        return js_config
    elif os.path.isfile(ts_config):
        return ts_config
    else:
        return None

def needs_constants(content):
    return not re.search(r"const port = 5173;", content)

def add_constants(content):
    import_matches = list(re.finditer(r"^import .* from", content, re.MULTILINE))
    if not import_matches:
        return content

    last_import_position = import_matches[-1].end()
    last_import_line_end = content.find('\n', last_import_position)

    constants_block = f"\n\nconst port = {PORT_VALUE};\nconst origin = `${{process.env.DDEV_PRIMARY_URL}}:${{port}}`;\n\n"

    return content[:last_import_line_end+1] + constants_block + content[last_import_line_end+1:]

def process_server_section(content):
    if re.search(r"server\s*:", content):
        pattern = r"(server\s*:\s*\{[^{}]*(?:\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}[^{}]*)*\}),?"
        content = re.sub(pattern, SERVER_CONFIG_TEMPLATE.strip(), content, count=1)
    else:
        content = re.sub(r"export default defineConfig\(\{",
                        r"export default defineConfig({\n" + SERVER_CONFIG_TEMPLATE,
                        content)

    return content

def remove_consecutive_empty_lines(content):
    return re.sub(r'\n{3,}', '\n\n', content)

def main():
    if 'DDEV_APPROOT' in os.environ:
        os.chdir(os.environ['DDEV_APPROOT'])
    elif os.path.exists('/app'):
        os.chdir('/app')

    config_file = find_config_file()
    if not config_file:
        sys.exit(1)

    try:
        with open(config_file, 'r') as f:
            content = f.read()

        backup_file = config_file + ".bak"
        with open(backup_file, 'w') as f:
            f.write(content)

        if needs_constants(content):
            content = add_constants(content)

        content = process_server_section(content)

        content = remove_consecutive_empty_lines(content)

        with open(config_file, 'w') as f:
            f.write(content)

        os.remove(backup_file)

    except Exception as e:
        if os.path.exists(backup_file):
            os.replace(backup_file, config_file)
        sys.exit(1)

if __name__ == "__main__":
    main()
