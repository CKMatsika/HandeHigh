// Exercise the exact onchange code extracted from the rendered student create form.
// This checks handler behaviour; it is not a browser layout or accessibility audit.
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const evidencePath = path.resolve(__dirname, '../../storage/logs/scholarship-functional-evidence.json');
const evidence = JSON.parse(fs.readFileSync(evidencePath, 'utf8'));
const handler = new Function(evidence.category_handler);
const checks = {};
for (const value of ['boarding', 'day', 'scholarship']) {
  const select = { required: false };
  const field = { style: {}, querySelector: selector => {
    assert.equal(selector, 'select');
    return select;
  }};
  const control = { value, closest: selector => {
    assert.equal(selector, '[data-category-fields]');
    return { querySelector: selector => {
      assert.equal(selector, '[data-scholarship-residency]');
      return field;
    }};
  }};
  handler.call(control);
  assert.equal(field.style.display, value === 'scholarship' ? 'block' : 'none');
  assert.equal(select.required, value === 'scholarship');
  checks[value] = { residency_visible: field.style.display === 'block', residency_required: select.required };
}
evidence.handler_checks = checks;
fs.writeFileSync(evidencePath, JSON.stringify(evidence, null, 2));
console.log('PASS: 3 category changes; scholarship residency is visible and required only for Scholarship.');
