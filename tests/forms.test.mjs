/**
 * Copyright (c) 2023-2026 Dartmoon S.r.l. <hello@dartmoon.io>
 * SPDX-License-Identifier: MIT
 * See LICENSE for the full license text.
 */
import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = await readFile(new URL('../resources/js/forms.js', import.meta.url), 'utf8');
const {initForms} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('vector inputs synchronize the submitted value and initialize only once', () => {
  const listeners = [];
  const root = {addEventListener: (type, callback) => listeners.push({type, callback})};
  const changes = [];
  const hidden = {value: '0 0 0', dispatchEvent: event => changes.push(event)};
  const field = {
    querySelectorAll: () => [{value: '-1'}, {value: '0'}, {value: '2.5'}],
    querySelector: () => hidden,
  };
  initForms(root);
  initForms(root);
  assert.equal(listeners.length, 1);
  assert.equal(listeners[0].type, 'input');
  listeners[0].callback({target: {matches: () => false}});
  assert.equal(changes.length, 0);
  listeners[0].callback({target: {matches: () => true, closest: () => field}});
  assert.equal(hidden.value, '-1 0 2.5');
  assert.equal(changes.length, 1);
  assert.equal(changes[0].type, 'change');
  assert.equal(changes[0].bubbles, true);
});

test('independent forms can initialize separately', () => {
  let count = 0;
  initForms({addEventListener: () => count++});
  initForms({addEventListener: () => count++});
  assert.equal(count, 2);
});
