(() => {
  const mark = /\p{M}/u;
  const isLetter = /^\p{L}$/u;
  const initial = value => {
    const tokens = String(value || '').match(/[\p{L}\p{M}\p{N}]+/gu) || [];
    const word = tokens.find(token => isLetter.test(Array.from(token)[0] || ''));
    if (!word) return null;
    const segment = new Intl.Segmenter(document.documentElement.lang?.startsWith('bn') ? 'bn' : 'en', { granularity: 'grapheme' }).segment(word)[Symbol.iterator]().next().value?.segment;
    if (!segment) return null;
    const points = Array.from(segment);
    let first = points[0] || '';
    for (let i = 1; i < points.length && mark.test(points[i]); i++) first += points[i];
    return /^\p{Script=Latin}/u.test(first) ? first.toLocaleUpperCase('en') : first;
  };
  const toneIndex = value => {
    const bytes = new TextEncoder().encode(String(value || ''));
    let hash = 2166136261;
    for (const byte of bytes) hash = Math.imul(hash ^ byte, 16777619) >>> 0;
    return hash % 8;
  };
  window.RNAvatar = Object.freeze({ initial, toneIndex, toneClass: value => `rn-m-avatar--tone-${toneIndex(value)}` });
})();
