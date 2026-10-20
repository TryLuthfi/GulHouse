import { test } from '@e2e-dev/web';
import { expect } from 'e2e';
import { spawnSync } from 'node:child_process';
import { resolve } from 'node:path';

const master = '/master/index.php';
async function confirmed(browser: any, form: string) {
  await browser.locator(`${form} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-save]').click();
  await expect(browser.locator('.notice.success')).toBeVisible();
}
async function namedRow(browser: any, name: string, extra = '') {
  const index = await browser.evaluate(({ name, extra }) => Array.from(document.querySelectorAll('tbody tr')).findIndex(row => (row.textContent ?? '').includes(name) && (row.textContent ?? '').includes(extra)), { name, extra });
  expect(index, `UI row for ${name} ${extra}`).toBeGreaterThanOrEqual(0);
  return `tbody tr:nth-child(${index + 1})`;
}

test('monthly CLI job bills browser-created active contract once and displays due date', { timeout: 600000 }, async ({ app, screen, browser }) => {
  const database = process.env.GULHOUSE_DATABASE ?? '';
  expect(/^gulhouse_test_[A-Za-z0-9_]+$/.test(database), 'CLI must target explicitly isolated database').toBe(true);
  const period = '2040-02';
  const rate = 1475000;
  await app.open(`${master}/login`);
  await screen.getByLabel('Username').fill(process.env.GULHOUSE_E2E_USER ?? 'codex_trial');
  await screen.getByLabel('Password').fill(process.env.GULHOUSE_E2E_PASS ?? '');
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/dashboard/);
  const width = await browser.evaluate(() => window.innerWidth);
  const roomId = width >= 1900 ? process.env.GULHOUSE_E2E_ROOM_ID_BILLING_WIDE : width < 600 ? process.env.GULHOUSE_E2E_ROOM_ID_BILLING_MOBILE : process.env.GULHOUSE_E2E_ROOM_ID_BILLING;
  expect(roomId, 'dedicated billing job fixture room').not.toBe(undefined);
  const stamp = `${Date.now()}${width}`;
  const name = `Billing Job ${stamp}`;
  const phone = `08996${stamp.slice(-8)}`;

  await app.open(`${master}/tenants`);
  await browser.locator('[data-open-modal="#tenant-create-modal"]').click();
  const roomData = await browser.evaluate((id) => {
    const option = document.querySelector(`#tenant-create-modal [name="room_id"] option[value="${id}"]`) as HTMLOptionElement;
    return { property: option?.dataset.propertyId ?? '', type: option?.dataset.typeId ?? '' };
  }, String(roomId));
  expect(roomData.property).not.toBe('');
  await browser.locator('#tenant-create-modal [data-tenant-property-filter]').selectOption({ value: roomData.property });
  await browser.locator('#tenant-create-modal [data-tenant-type-filter]').selectOption({ value: roomData.type });
  await browser.locator('#tenant-create-modal [name="room_id"]').selectOption({ value: String(roomId) });
  await browser.locator('#tenant-create-modal [name="stay_status"]').selectOption({ value: 'reserved' });
  const fields = { fullname: name, phone, identity_number: `77${stamp.slice(-14)}`, check_in_date: '2040-02-03', check_out_date: '2040-02-29', monthly_price: String(rate), deposit: '0', source_period: 'Synthetic Monthly Job Browser', notes: 'Isolated browser-created billing fixture' };
  for (const [field, value] of Object.entries(fields)) await browser.locator(`#tenant-create-modal [name="${field}"]`).fill(value);
  await confirmed(browser, '#tenant-create-modal form');
  await app.open(`${master}/tenants?q=${phone}`);
  await browser.locator((await namedRow(browser, name)) + ' [data-open-modal^="#stay-contract-"]').click();
  await browser.locator('.modal.is-open [name="start_date"]').fill('2040-02-03');
  await browser.locator('.modal.is-open [name="end_date"]').fill('2040-02-29');
  await browser.locator('.modal.is-open [name="monthly_price"]').fill(String(rate));
  await browser.locator('.modal.is-open [name="deposit_amount"]').fill('0');
  await confirmed(browser, '.modal.is-open form');

  await app.open(`${master}/documents?q=${encodeURIComponent(name)}`);
  for (const type of ['ktp', 'contract']) {
    await browser.locator((await namedRow(browser, name, type)) + ' [data-open-modal^="#ops-edit-"]').click();
    await browser.locator('.modal.is-open [name="status"]').selectOption({ value: 'verified' });
    await browser.locator('.modal.is-open [name="received_at"]').fill('2040-02-03');
    await confirmed(browser, '.modal.is-open form');
  }
  await app.open(`${master}/contracts?q=${encodeURIComponent(name)}`);
  await browser.locator((await namedRow(browser, name)) + ' [data-open-modal^="#contract-flow-"]').click();
  await browser.locator('.modal.is-open [name="signed_at"]').fill('2040-02-03');
  await browser.locator('.modal.is-open [name="inspection_confirmed"]').check();
  await confirmed(browser, '.modal.is-open form[action$="/activate"]');
  await expect(browser.locator('tbody .badge').first()).toHaveText(/active/i);

  const runJob = () => {
    // Argument array and shell:false prevent shell interpolation. No direct SQL or HTTP substitutes.
    const execution = spawnSync(process.env.GULHOUSE_PHP_BINARY ?? 'D:\\XAMPP\\php\\php.exe', [resolve('scripts/run-gulhouse-billing.php'), `--period=${period}`, '--apply'], {
      cwd: process.cwd(), shell: false, encoding: 'utf8', timeout: 120000,
      env: { ...process.env, GULHOUSE_DATABASE: database, GULHOUSE_BILLING_ALLOW_REAL: '0', GULHOUSE_BILLING_DUE_DAY: '17' },
    });
    expect(execution.status, 'actual isolated monthly CLI exit status').toBe(0);
    const result = JSON.parse(execution.stdout.trim());
    expect(result.ok).toBe(true);
    expect(result.database).toBe(database);
    expect(result.period).toBe(period);
    expect(result.mode).toBe('apply');
    return result;
  };
  const assertBillUi = async () => {
    await app.open(`${master}/billing?period=${period}&q=${phone}`);
    await expect(browser.locator('tbody tr')).toHaveCount(1);
    await expect.poll(async () => await browser.locator('tbody').textContent()).toContain(name);
    await expect.poll(async () => await browser.locator('tbody').textContent()).toContain(rate.toLocaleString('id-ID'));
    await app.open(`${master}/payments?q=${phone}`);
    await expect(browser.locator('tbody tr')).toHaveCount(1);
    await expect.poll(async () => await browser.locator('tbody').textContent()).toContain('2040-02-17');
    await expect.poll(async () => await browser.locator('tbody').textContent()).toContain(rate.toLocaleString('id-ID'));
    await expect(browser.locator('tbody .badge').first()).toHaveText(/Unpaid/i);
  };
  runJob();
  await assertBillUi();
  const repeated = runJob();
  expect(repeated.new_bill_rows_since_start, 'rerun inserts no additional invoices').toBe(0);
  await assertBillUi();
});
