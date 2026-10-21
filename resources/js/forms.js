/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */
const initialized = new WeakSet();

// Import from a module's back.js, then call initForms() once the DOM is ready.
export function initForms(root = document) {
  if (initialized.has(root)) return;
  initialized.add(root);
  root.addEventListener('input', event => {
    if (!event.target.matches('[data-dartmoon-vector] [data-axis]')) return;
    const field = event.target.closest('[data-dartmoon-vector]');
    const value = Array.from(field.querySelectorAll('[data-axis]'), input => input.value).join(' ');
    const hidden = field.querySelector('input[type="hidden"]');
    hidden.value = value;
    hidden.dispatchEvent(new Event('change', {bubbles: true}));
  });
}
