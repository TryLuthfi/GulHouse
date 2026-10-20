import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

const master = '/master/index.php';
async function save(browser: any, form: string, success = true) {
  await browser.locator(`${form} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-save]').click();
  await expect(browser.locator(success ? '.notice.success' : '.notice.error')).toBeVisible();
}
async function row(browser: any, name: string, extra = '') {
  const index = await browser.evaluate(({ name, extra }) => Array.from(document.querySelectorAll('tbody tr')).findIndex(row => (row.textContent ?? '').includes(name) && (row.textContent ?? '').includes(extra)), { name, extra });
  expect(index, `${name} ${extra}`).toBeGreaterThanOrEqual(0);
  return `tbody tr:nth-child(${index + 1})`;
}
async function invoice(app: any, browser: any, phone: string, period: string) {
  await app.open(`${master}/payments?q=${phone}`);
  return row(browser, phone, period);
}
async function pay(app: any, browser: any, phone: string, period: string, amount: number, reference: string) {
  const entry = await invoice(app, browser, phone, period);
  const button = browser.locator(entry + ' [data-open-modal^="#payment-add-"]');
  const modal = await button.getAttribute('data-open-modal');
  await button.click();
  await browser.locator(`${modal} [name="payment_date"]`).fill('2030-01-10');
  await browser.locator(`${modal} [name="amount"]`).fill(String(amount));
  await browser.locator(`${modal} [name="method"]`).fill('Transfer browser financial');
  await browser.locator(`${modal} [name="reference_no"]`).fill(reference);
  await save(browser, `${modal} form`);
}
async function ledger(app: any, browser: any, name: string, phone: string, room: string, type: string, amount: number, success = true, inspection = true, reason = 'Synthetic final inspection reference FIN-INSPECT') {
  await app.open(`${master}/deposits?q=${phone}`);
  await browser.locator('[data-open-modal="#ops-create-modal"]').click();
  await browser.locator('#ops-create-modal [name="tenant_id"]').selectOption({ label: `${name} - ${phone}` });
  await browser.locator('#ops-create-modal [name="room_id"]').selectOption({ value: room });
  await browser.locator('#ops-create-modal [name="transaction_date"]').fill('2030-01-10');
  await browser.locator('#ops-create-modal [name="transaction_type"]').selectOption({ value: type });
  await browser.locator('#ops-create-modal [name="amount"]').fill(String(amount));
  await browser.locator('#ops-create-modal [name="method"]').fill('Manual browser financial');
  await browser.locator('#ops-create-modal [name="description"]').fill(`Synthetic ${type} verified by browser trial`);
  if (type === 'refund') {
    if (inspection) await browser.locator('#ops-create-modal [name="refund_inspection_confirmed"]').check();
    await browser.locator('#ops-create-modal [name="refund_reason"]').fill(reason);
  }
  await save(browser, '#ops-create-modal form', success);
}

test('financial UI payment edit move delete contract print and deposit ledger', { timeout: 600000 }, async ({ app, screen, browser }) => {
  await app.open(`${master}/login`);
  await screen.getByLabel('Username').fill(process.env.GULHOUSE_E2E_USER ?? 'codex_trial');
  await screen.getByLabel('Password').fill(process.env.GULHOUSE_E2E_PASS ?? '');
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/dashboard/);
  const width = await browser.evaluate(() => window.innerWidth);
  const roomId = width >= 1900 ? process.env.GULHOUSE_E2E_ROOM_ID_FINANCIAL_WIDE : width < 600 ? process.env.GULHOUSE_E2E_ROOM_ID_FINANCIAL_MOBILE : process.env.GULHOUSE_E2E_ROOM_ID_FINANCIAL;
  expect(roomId, 'dedicated financial fixture').not.toBe(undefined);
  const stamp = String(Date.now());
  const name = `Financial Browser ${stamp}`;
  const phone = `08996${stamp.slice(-7)}`;
  const rent = 1000000;
  await app.open(`${master}/tenants`);
  await browser.locator('[data-open-modal="#tenant-create-modal"]').click();
  await browser.locator('#tenant-create-modal [name="room_id"]').selectOption({ value: String(roomId) });
  await browser.locator('#tenant-create-modal [name="stay_status"]').selectOption({ value: 'reserved' });
  for (const [field, value] of Object.entries({ fullname: name, phone, identity_number: `0000${stamp.slice(-12)}`, check_in_date: '2030-01-01', check_out_date: '2030-02-28', monthly_price: String(rent), deposit: '1000000', notes: 'Synthetic financial browser test' })) await browser.locator(`#tenant-create-modal [name="${field}"]`).fill(value);
  await save(browser, '#tenant-create-modal form');
  await app.open(`${master}/documents?q=${phone}`);
  for (const type of ['ktp', 'contract']) {
    const entry = await row(browser, name, type);
    await browser.locator(entry + ' [data-open-modal^="#ops-edit-"]').click();
    await browser.locator('.modal.is-open [name="status"]').selectOption({ value: 'verified' });
    await browser.locator('.modal.is-open [name="received_at"]').fill('2030-01-01');
    await save(browser, '.modal.is-open form');
  }
  await ledger(app, browser, name, phone, String(roomId), 'received', 1000000);
  await app.open(`${master}/contracts?q=${phone}`);
  await browser.locator('[data-open-modal^="#contract-flow-"]').first().click();
  await browser.locator('.modal.is-open [name="signed_at"]').fill('2030-01-01');
  await browser.locator('.modal.is-open [name="inspection_confirmed"]').check();
  await save(browser, '.modal.is-open form[action$="/activate"]');
  await app.open(`${master}/contracts?q=${phone}`);
  await browser.locator('tbody tr').first().getByRole('link', 'Print').click();
  await expect.poll(async () => await browser.locator('pre').textContent()).toContain(name);
  await expect.poll(async () => await browser.locator('pre').textContent()).toContain('2030-01-01');
  await expect.poll(async () => await browser.locator('pre').textContent()).toContain('1.000.000');
  await expect.poll(async () => await browser.locator('pre').textContent()).not.toContain('{tenant_name}');
  await screen.getByRole('button', 'Print / PDF').click();
  await browser.back();
  for (const period of ['2030-01', '2030-02']) {
    await app.open(`${master}/billing?period=${period}`);
    await browser.locator('[data-open-modal="#billing-generate-modal"]').click();
    await browser.locator('#billing-generate-modal [name="period_key"]').fill(period);
    await save(browser, '#billing-generate-modal form');
  }

  await pay(app, browser, phone, 'JANUARY 2030', 200000, `FIN-${stamp}`);
  let entry = await invoice(app, browser, phone, 'JANUARY 2030');
  let button = browser.locator(entry + ' [data-open-modal^="#payment-edit-"]');
  let modal = await button.getAttribute('data-open-modal');
  await button.click();
  await browser.locator(`${modal} [name="amount"]`).fill('300000');
  await save(browser, `${modal} form`);
  entry = await row(browser, phone, 'JANUARY 2030');
  await expect(browser.locator(entry)).toHaveText(/300\.000/);
  button = browser.locator(entry + ' [data-open-modal^="#payment-edit-"]');
  modal = await button.getAttribute('data-open-modal');
  await button.click();
  const destination = await browser.evaluate(({ modal, name }) => Array.from((document.querySelector(modal + ' [name="room_bill_id"]') as HTMLSelectElement).options).find(option => option.textContent?.includes(name) && option.textContent?.includes('FEBRUARY 2030'))?.value ?? '', { modal: String(modal), name });
  expect(destination).not.toBe('');
  await browser.locator(`${modal} [name="room_bill_id"]`).selectOption({ value: destination });
  await save(browser, `${modal} form`);
  await expect(browser.locator((await row(browser, phone, 'JANUARY 2030')) + ' .badge')).toHaveText(/unpaid/i);
  entry = await row(browser, phone, 'FEBRUARY 2030');
  await expect(browser.locator(entry + ' .badge')).toHaveText(/partial/i);
  await browser.locator(entry + ' [data-delete-url]').click();
  await expect(browser.locator('[data-delete-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-delete]').click();
  await expect(browser.locator('.notice.success')).toBeVisible();
  await expect(browser.locator((await row(browser, phone, 'FEBRUARY 2030')) + ' .badge')).toHaveText(/unpaid/i);
  await ledger(app, browser, name, phone, String(roomId), 'refund', 100000, false); // unsettled rent blocks withdrawal
  await pay(app, browser, phone, 'JANUARY 2030', rent, `FIN-JAN-${stamp}`);
  await pay(app, browser, phone, 'FEBRUARY 2030', rent, `FIN-FEB-${stamp}`);
  await ledger(app, browser, name, phone, String(roomId), 'adjustment', 50000);
  await app.open(`${master}/deposits?q=${phone}`);
  entry = await row(browser, name, 'received');
  button = browser.locator(entry + ' [data-open-modal^="#ops-edit-"]');
  modal = await button.getAttribute('data-open-modal');
  await button.click();
  await browser.locator(`${modal} [name="amount"]`).fill('900000');
  await save(browser, `${modal} form`);
  await ledger(app, browser, name, phone, String(roomId), 'deduction', 25000);
  await ledger(app, browser, name, phone, String(roomId), 'refund', 25000, false, false); // final inspection confirmation is mandatory
  await ledger(app, browser, name, phone, String(roomId), 'refund', 25000, false, true, ''); // reference/reason is mandatory
  await ledger(app, browser, name, phone, String(roomId), 'refund', 999999999, false);
  await ledger(app, browser, name, phone, String(roomId), 'refund', 25000);
  await app.open(`${master}/deposits?period=2030-01&q=${phone}`);
  await expect(browser.locator('.metric-card').last()).toHaveText(/900\.000/);
  await browser.locator('main form.filter-form [name="period"]').fill('2030-02');
  await browser.locator('main form.filter-form button[type="submit"]').click();
  await expect(browser.locator('.metric-card').last()).toHaveText(/Rp 0/i);
  await expect.poll(async () => await browser.locator('tbody').textContent()).not.toContain(name);
});
