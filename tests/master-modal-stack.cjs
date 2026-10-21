'use strict';
// Isolated DOM regression: a lazy editor is appended after an existing confirmation.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../master/assets/master/js/master.js'), 'utf8');
const prefix = source.slice(0, source.indexOf('    function initModals() {'));
const body = { children: [], appendChild(node) { this.children = this.children.filter(x => x !== node); this.children.push(node); node.parentNode = this; } };
const document = {
  body,
  activeElement: null,
  querySelectorAll() { return body.children.filter(x => x.classList.contains('is-open')); },
  getElementById(id) { return body.children.map(x => x.heading).find(x => x.id === id) || null; }
};
function target(label) { return { label, disabled: false, isConnected: true, focus() { document.activeElement = this; } }; }
function modal(id) {
  const attributes = {};
  const classes = new Set();
  const heading = { id: '' };
  const button = target(id + '-close');
  const dialog = { querySelector() { return heading; }, setAttribute(k, v) { attributes[k] = v; }, hasAttribute(k) { return Object.hasOwn(attributes, k); } };
  return { id, heading, attributes, button, parentNode: body,
    style: { zIndex: '', removeProperty(k) { assert.equal(k, 'z-index'); this.zIndex = ''; } },
    classList: { add(c) { classes.add(c); }, remove(c) { classes.delete(c); }, contains(c) { return classes.has(c); } },
    setAttribute(k, v) { attributes[k] = v; },
    querySelector(s) { return s === '.modal-card' ? dialog : button; }
  };
}
const { openModal, closeModal } = vm.runInNewContext(prefix + 'return { openModal, closeModal };}());', {
  document, window: { getComputedStyle(node) { return { zIndex: node.style.zIndex || '40' }; } }
});
const confirm = modal('save-confirm');
const editor = modal('tenant-edit-6');
const tableEdit = target('Edit');
const editorSave = target('Simpan Penghuni');
editor.unsaved = 'manual changes remain';
body.appendChild(confirm); // Existing confirmation appears earlier in the document.
body.appendChild(editor);  // Lazy editor arrives later.
openModal(editor, tableEdit);
document.activeElement = editorSave;
openModal(confirm);
assert.ok(Number(confirm.style.zIndex) > Number(editor.style.zIndex), 'confirmation overlays late-appended editor');
assert.equal(body.children.at(-1), confirm, 'shared DOM-based focus trap selects the visible top dialog');
assert.equal(document.activeElement, confirm.button, 'focus enters the confirmation');
assert.equal(confirm.attributes.role, 'dialog');
assert.equal(confirm.attributes['aria-modal'], 'true');
closeModal(confirm);
assert.equal(confirm.style.zIndex, '', 'close clears the temporary layer');
assert.equal(document.activeElement, editorSave, 'cancel restores the editor submit control');
assert.ok(editor.classList.contains('is-open'), 'underlying editor remains open');
assert.equal(editor.unsaved, 'manual changes remain', 'moving modal nodes preserves unsaved editor data');
editor.style.zIndex = '2147483647';
openModal(confirm, editorSave);
assert.ok(Number(confirm.style.zIndex) > Number(editor.style.zIndex), 'unexpected high layer is normalized');
assert.ok(Number(confirm.style.zIndex) < 10000, 'normalization keeps layers bounded');
for (let i = 0; i < 1100; i++) { closeModal(confirm); openModal(confirm, editorSave); }
assert.ok(Number(confirm.style.zIndex) < 10000, 'repeated opening does not exhaust the stack');
closeModal(confirm); closeModal(editor);
assert.equal(editor.style.zIndex, '');
assert.equal(document.activeElement, tableEdit, 'editor close restores its own trigger');
console.log(JSON.stringify({ passed: 14, failed: 0, scope: 'isolated modal stacking and focus regression; no browser or database writes' }));
