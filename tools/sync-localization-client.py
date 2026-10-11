"""Build the small, explicit client catalog from stable keys used by application JS.

Run after introducing RNTranslate keys. Uses only Python's standard library.
Does not modify views, dependencies, configuration, or database records.
"""
from pathlib import Path
import re

root = Path(__file__).resolve().parents[1]
keys = set()
for folder in [root / 'resources/views', root / 'resources/js']:
    for path in folder.rglob('*'):
        if path.is_file() and (path.name.endswith('.blade.php') or path.suffix == '.js'):
            keys.update(re.findall(r"RNTranslate\('([^']+)'", path.read_text(encoding='utf-8')))

for locale in ['en', 'bn']:
    catalogs = {}
    for domain in ['sales', 'common']:
        source = (root / 'lang' / locale / (domain + '.php')).read_text(encoding='utf-8')
        catalogs[domain] = dict(re.findall(r"'([^']+)'\s*=>\s*('(?:\\.|[^'])*')", source))
    lines = []
    for key in sorted(keys):
        domain, subkey = key.split('.', 1) if '.' in key else ('sales', key)
        if subkey not in catalogs.get(domain, {}):
            raise RuntimeError('Missing client catalog key: ' + locale + '.' + key)
        lines.append("    '" + key + "' => " + catalogs[domain][subkey] + ",\n")
    (root / 'lang' / locale / 'javascript.php').write_text('<?php\n\nreturn [\n' + ''.join(lines) + '];\n', encoding='utf-8')
print('Client catalog keys:', len(keys))
