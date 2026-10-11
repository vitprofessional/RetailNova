const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
function catalog(locale, domain) {
  const text = fs.readFileSync(path.join(root, 'lang', locale, domain + '.php'), 'utf8');
  return Object.fromEntries([...text.matchAll(/'([^']+)'\s*=>\s*'((?:\\.|[^'])*)'/g)]
    .map(m => [m[1], m[2].replace(/\\'/g, "'").replace(/\\\\/g, '\\')]));
}
function translateSource(source, locale = 'en') {
  return source.replace(/\{\{\s*__\('([a-z]+)\.([^']+)'\)\s*\}\}/g,
    (_, domain, key) => catalog(locale, domain)[key] || catalog('en', domain)[key] || key);
}
function bridge(locale = 'en') {
  const messages = {...catalog('en', 'sales'), ...catalog(locale, 'sales')};
  const common = {...catalog('en', 'common'), ...catalog(locale, 'common')};
  return `<script>window.RNMessages=${JSON.stringify(messages)};window.RNCommonMessages=${JSON.stringify(common)};
  window.RNTranslate=function(key, values){var text=RNMessages[key]||RNCommonMessages[key.replace(/^common\\./,'')]||key;Object.keys(values||{}).forEach(function(k){text=text.split(':'+k).join(String(values[k]));});return text;};</script>`;
}
module.exports = {catalog, translateSource, bridge};
