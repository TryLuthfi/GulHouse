import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

const master = '/master/index.php';
const landing = '/landing/index.php';

async function rowSelector(browser: any, name: string, extra = '') {
  const index = await browser.evaluate(({ name, extra }) => Array.from(document.querySelectorAll('tbody tr')).findIndex(row => (row.textContent ?? '').includes(name) && (row.textContent ?? '').includes(extra)), { name, extra });
  expect(index, `table row ${name} ${extra}`).toBeGreaterThanOrEqual(0);
  return `tbody tr:nth-child(${index + 1})`;
}

async function confirmed(browser: any, form: string) {
  await browser.locator(`${form} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-save]').click();
}
async function notice(browser: any, kind: string) {
  await expect(browser.locator(`.notice.${kind}`)).toBeVisible();
}
async function closeModal(browser: any) {
  await browser.locator('.modal.is-open .modal-head [data-close-modal]').click();
}
async function lifecycleModal(browser: any, name: string) {
  const row = await rowSelector(browser, name);
  const button = browser.locator(row + ' [data-open-modal^="#contract-flow-"]');
  const selector = await button.getAttribute('data-open-modal');
  expect(selector).not.toBe(null);
  await button.click();
  await expect(browser.locator(`${selector}.is-open`)).toBeVisible();
  return String(selector);
}
async function billing(app: any, browser: any, period: string) {
  await app.open(`${master}/billing?period=${period}`);
  await browser.locator('[data-open-modal="#billing-generate-modal"]').click();
  await browser.locator('#billing-generate-modal [name="period_key"]').fill(period);
  await confirmed(browser, '#billing-generate-modal form');
  await notice(browser, 'success');
}
async function payment(app: any, browser: any, phone: string, amount: number, ref: string, type = 'rent', testInvalid = false) {
  await app.open(`${master}/payments?q=${phone}`);
  const row = await rowSelector(browser, phone);
  const button = browser.locator(row + ' [data-open-modal^="#payment-add-"]');
  const selector = await button.getAttribute('data-open-modal');
  await button.click();
  const form = `${selector} form`;
  await browser.locator(`${form} [name="payment_date"]`).fill('2026-11-01');
  if (testInvalid) {
    await browser.locator(`${form} [name="method"]`).fill('Transfer demo browser');
    for (const invalid of ['-100', '1e3']) {
      await browser.locator(`${form} [name="amount"]`).fill(invalid);
      expect(await browser.evaluate((form) => (document.querySelector(form + ' [name="amount"]') as HTMLInputElement).validity.valid, form)).toBe(false);
      await browser.locator(`${form} button[type="submit"]`).click();
      await expect(browser.locator(`${selector}.is-open`)).toBeVisible();
      await expect(browser.locator('[data-save-confirm-modal].is-open')).not.toBeVisible();
    }
  }
  await browser.locator(`${form} [name="amount"]`).fill(String(amount));
  await browser.locator(`${form} [name="payment_type"]`).selectOption({ value: type });
  await browser.locator(`${form} [name="method"]`).fill('Transfer demo browser');
  await browser.locator(`${form} [name="reference_no"]`).fill(ref);
  await confirmed(browser, form);
  await notice(browser, 'success');
}

test('full browser lifecycle booking contracts billing payment receipt renewal checkout', { timeout: 900000 }, async ({ app, screen, browser }) => {
  await app.open(`${landing}/`);
  const width = await browser.evaluate(() => window.innerWidth);
  const room = width >= 1900 ? process.env.GULHOUSE_E2E_ROOM_ID_WIDE : width < 600 ? process.env.GULHOUSE_E2E_ROOM_ID_MOBILE : process.env.GULHOUSE_E2E_ROOM_ID;
  const stamp = `${Date.now()}${width}`;
  const name = `Browser Lifecycle ${stamp}`;
  const phone = `08998${String(Date.now()).slice(-7)}`;

  await browser.locator('.booking-modal-trigger [data-booking-open]').click();
  await expect(browser.locator('[data-booking-modal].is-open')).toBeVisible();
  await browser.locator('.booking-form [name="fullname"]').fill(name);
  await browser.locator('.booking-form [name="phone"]').fill(phone);
  const interest = await browser.evaluate(() => {
    const select = document.querySelector('.booking-form [name="room_interest"]') as HTMLSelectElement;
    return Array.from(select.options).find(option => option.value && !option.disabled)?.value ?? '';
  });
  await browser.locator('.booking-form [name="room_interest"]').selectOption({ value: interest });
  await browser.locator('.booking-form [name="move_in_plan"]').fill('1 November 2026');
  await browser.locator('.booking-form [name="message"]').fill('Synthetic full lifecycle browser trial.');
  await screen.getByRole('button', 'Kirim Booking').click();
  await expect.poll(async () => await browser.locator('body').textContent()).toContain('Permintaan booking sudah');

  await app.open(`${master}/login`);
  await screen.getByLabel('Username').fill(process.env.GULHOUSE_E2E_USER ?? 'codex_trial');
  await screen.getByLabel('Password').fill(process.env.GULHOUSE_E2E_PASS ?? '');
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/dashboard/);
  await app.open(`${master}/booking?q=${phone}`);
  await browser.locator((await rowSelector(browser, name)) + ' [name="status"]').selectOption({ value: 'survey' });
  await browser.locator('tbody tr').filter({ hasText: name }).getByRole('button', 'Follow-up').click();
  await notice(browser, 'success');
  await browser.locator('[data-open-modal^="#booking-convert-"]').first().click();
  await browser.locator('.modal.is-open [name="room_id"]').selectOption({ value: String(room) });
  await browser.locator('.modal.is-open [name="start_date"]').fill('2026-11-01');
  await browser.locator('.modal.is-open [name="end_date"]').fill('2026-11-30');
  await confirmed(browser, '.modal.is-open form');
  await notice(browser, 'success');
  await expect(browser.locator((await rowSelector(browser, name)) + ' .badge')).toHaveText(/converted/i);

  await app.open(`${master}/contracts`);
  const contractRow = await rowSelector(browser, name);
  const edit = await browser.locator(contractRow + ' [data-open-modal^="#ops-edit-"]').getAttribute('data-open-modal');
  const rent = Number((await browser.locator(`${edit} [name="monthly_price"]`).inputValue()).replace(/\D/g, ''));
  const deposit = Number((await browser.locator(`${edit} [name="deposit_amount"]`).inputValue()).replace(/\D/g, ''));
  expect(rent).toBeGreaterThan(0);
  let flow = await lifecycleModal(browser, name);
  await browser.locator(`${flow} [name="inspection_confirmed"]`).check();
  await confirmed(browser, `${flow} form[action$="/activate"]`);
  await notice(browser, 'error'); // required documents/deposit block premature check-in

  await billing(app, browser, '2026-11');
  await app.open(`${master}/documents`);
  for (const type of ['ktp', 'contract']) {
    const row = await rowSelector(browser, name, type);
    await browser.locator(row + ' [data-open-modal^="#ops-edit-"]').click();
    await browser.locator('.modal.is-open [name="status"]').selectOption({ value: 'verified' });
    await browser.locator('.modal.is-open [name="received_at"]').fill('2026-11-01');
    await confirmed(browser, '.modal.is-open form');
    await notice(browser, 'success');
  }
  await app.open(`${master}/deposits`);
  await browser.locator('[data-open-modal="#ops-create-modal"]').click();
  await browser.locator('#ops-create-modal [name="tenant_id"]').selectOption({ label: `${name} - ${phone}` });
  await browser.locator('#ops-create-modal [name="room_id"]').selectOption({ value: String(room) });
  await browser.locator('#ops-create-modal [name="transaction_date"]').fill('2026-11-01');
  await browser.locator('#ops-create-modal [name="transaction_type"]').selectOption({ value: 'received' });
  await browser.locator('#ops-create-modal [name="amount"]').fill(String(deposit));
  await browser.locator('#ops-create-modal [name="method"]').fill('Transfer deposit demo');
  await confirmed(browser, '#ops-create-modal form');
  await notice(browser, 'success');

  await app.open(`${master}/contracts`);
  flow = await lifecycleModal(browser, name);
  await browser.locator(`${flow} [name="signed_at"]`).fill('2026-11-01');
  await browser.locator(`${flow} [name="inspection_confirmed"]`).check();
  await confirmed(browser, `${flow} form[action$="/activate"]`);
  await notice(browser, 'success');
  await expect(browser.locator((await rowSelector(browser, name)) + ' .badge')).toHaveText(/active/i);
  await app.open(`${master}/tenants?q=${phone}`);
  const activeEditButton = browser.locator('[data-open-modal^="#tenant-edit-"]').first();
  const activeEdit = await activeEditButton.getAttribute('data-open-modal');
  await activeEditButton.click();
  await browser.locator(`${activeEdit} [name="monthly_price"]`).fill(String(rent + 100000));
  await confirmed(browser, `${activeEdit} form`);
  await notice(browser, 'error'); // active financial terms must change through renewal
  await billing(app, browser, '2026-11'); // refresh earlier reserved placeholder
  await app.open(`${master}/payments?q=${phone}`);
  await expect(browser.locator((await rowSelector(browser, name)) + ' .badge')).toHaveText(/Unpaid/i);
  const partial = Math.floor(rent / 2);
  await payment(app, browser, phone, partial, `PART-${stamp}`, 'rent', true);
  await expect(browser.locator((await rowSelector(browser, name)) + ' .badge')).toHaveText(/Partial/i);
  await payment(app, browser, phone, partial, `PART-${stamp}`); // same reference remains one payment
  await payment(app, browser, phone, 50000, `TOPUP-${stamp}`, 'deposit');
  await expect(browser.locator((await rowSelector(browser, name)) + ' .badge')).toHaveText(/Partial/i);

  await app.open(`${master}/contracts`);
  flow = await lifecycleModal(browser, name);
  const checkout = `${flow} form[action$="/checkout"]`;
  await browser.locator(`${checkout} [name="checkout_date"]`).fill('2026-11-30');
  await browser.locator(`${checkout} [name="refund_amount"]`).fill(String(deposit));
  await browser.locator(`${checkout} [name="inspection_confirmed"]`).check();
  await confirmed(browser, checkout);
  await notice(browser, 'error'); // unpaid rent prevents refund
  await payment(app, browser, phone, rent - partial, `FULL-${stamp}`);
  await expect(browser.locator((await rowSelector(browser, name)) + ' .badge')).toHaveText(/Paid/i);
  await browser.locator('tbody tr').filter({ hasText: name }).first().getByRole('link', 'Kuitansi').click();
  await expect(browser).toHaveURL(/print_receipt/);
  await expect.poll(async () => await browser.locator('body').textContent()).toContain('Kuitansi pembayaran manual');
  await screen.getByRole('button', 'Cetak / Simpan PDF').click();
  await screen.getByRole('link', 'Kembali ke Pembayaran').click();

  await app.open(`${master}/contracts`);
  flow = await lifecycleModal(browser, name);
  await browser.locator(`${flow} form[action$="/renew"] [name="end_date"]`).fill('2026-12-31');
  await browser.locator(`${flow} form[action$="/renew"] [name="monthly_price"]`).fill(String(rent + 100000));
  await confirmed(browser, `${flow} form[action$="/renew"]`);
  await notice(browser, 'success');
  await billing(app, browser, '2026-12');
  await app.open(`${master}/billing?period=2026-12&q=${phone}`);
  await expect.poll(async () => await browser.locator('tbody').textContent()).toContain((rent + 100000).toLocaleString('id-ID'));
  await app.open(`${master}/contracts`);
  flow = await lifecycleModal(browser, name);
  const renewedCheckout = `${flow} form[action$="/checkout"]`;
  await browser.locator(`${renewedCheckout} [name="checkout_date"]`).fill('2026-11-30');
  await browser.locator(`${renewedCheckout} [name="refund_amount"]`).fill('999999999');
  await browser.locator(`${renewedCheckout} [name="inspection_confirmed"]`).check();
  await confirmed(browser, renewedCheckout);
  await notice(browser, 'error');
  flow = await lifecycleModal(browser, name);
  const finalCheckout = `${flow} form[action$="/checkout"]`;
  await browser.locator(`${finalCheckout} [name="checkout_date"]`).fill('2026-11-30');
  await browser.locator(`${finalCheckout} [name="refund_amount"]`).fill(String(deposit));
  await browser.locator(`${finalCheckout} [name="deduction_amount"]`).fill('50000');
  await browser.locator(`${finalCheckout} [name="deduction_reason"]`).fill('Synthetic inspected damage');
  await browser.locator(`${finalCheckout} [name="inspection_confirmed"]`).check();
  await browser.locator(`${finalCheckout} [name="needs_maintenance"]`).check();
  await confirmed(browser, finalCheckout);
  await notice(browser, 'success');
  await app.open(`${master}/billing?period=2026-12&q=${phone}`);
  await expect(browser.locator('.badge').first()).toHaveText(/empty/i);
  await app.open(`${master}/tenants?q=${phone}`);
  await expect(browser.locator('tbody .badge').first()).toHaveText(/selesai/i);
  await app.open(`${master}/deposits?period=2026-11&q=${encodeURIComponent(name)}`);
  await expect(browser.locator('.metric-card').last()).toHaveText(/Rp 0/i);
});
