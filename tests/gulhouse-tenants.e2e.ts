import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

const master = '/master/index.php';

async function confirmed(browser: any, form: string) {
  await browser.locator(`${form} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-save]').click();
}
async function row(browser: any, name: string) {
  const index = await browser.evaluate((name) => Array.from(document.querySelectorAll('tbody tr')).findIndex(row => (row.textContent ?? '').includes(name)), name);
  expect(index).toBeGreaterThanOrEqual(0);
  return `tbody tr:nth-child(${index + 1})`;
}
async function edit(browser: any, name: string) {
  const button = browser.locator((await row(browser, name)) + ' [data-open-modal^="#tenant-edit-"]');
  const selector = await button.getAttribute('data-open-modal');
  await button.click();
  await expect(browser.locator(`${selector}.is-open`)).toBeVisible();
  return String(selector);
}

test('manual tenant profile cascade validation draft and guarded cancellation', { timeout: 600000 }, async ({ app, screen, browser }) => {
  await app.open(`${master}/login`);
  await screen.getByLabel('Username').fill(process.env.GULHOUSE_E2E_USER ?? 'codex_trial');
  await screen.getByLabel('Password').fill(process.env.GULHOUSE_E2E_PASS ?? '');
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/dashboard/);
  const width = await browser.evaluate(() => window.innerWidth);
  const roomId = width >= 1900 ? process.env.GULHOUSE_E2E_ROOM_ID_TENANT_WIDE : width < 600 ? process.env.GULHOUSE_E2E_ROOM_ID_TENANT_MOBILE : process.env.GULHOUSE_E2E_ROOM_ID_TENANT;
  expect(roomId, 'dedicated manual tenant fixture room').not.toBe(undefined);
  const stamp = String(Date.now());
  const name = `Manual Tenant ${stamp}`;
  const phone = `08997${stamp.slice(-7)}`;
  const nik = `0000${stamp.slice(-12)}`;

  await app.open(`${master}/tenants`);
  await browser.locator('[data-open-modal="#tenant-create-modal"]').click();
  const roomData = await browser.evaluate((roomId) => {
    const option = document.querySelector(`#tenant-create-modal [name="room_id"] option[value="${roomId}"]`) as HTMLOptionElement;
    return { property: option.dataset.propertyId ?? '', type: option.dataset.typeId ?? '', label: option.textContent?.trim() ?? '' };
  }, String(roomId));
  await browser.locator('#tenant-create-modal [data-tenant-property-filter]').selectOption({ value: roomData.property });
  await browser.locator('#tenant-create-modal [data-tenant-type-filter]').selectOption({ value: roomData.type });
  await browser.locator('#tenant-create-modal [name="room_id"]').selectOption({ value: String(roomId) });
  await browser.locator('#tenant-create-modal [name="stay_status"]').selectOption({ value: 'reserved' });
  const fields = { fullname: name, phone, identity_number: nik, birth_place: 'Jakarta', birth_date: '1995-02-10', religion: 'Demo', marital_status: 'Lajang', occupation: 'Analis demo', address: 'Alamat simulasi browser', emergency_name: 'Kontak Demo', emergency_relationship: 'Saudara', emergency_phone: '089970000001', check_in_date: '2027-01-10', monthly_price: '1250000', deposit: '750000', source_period: 'Manual Browser Demo', notes: 'Synthetic browser test only' };
  for (const [field, value] of Object.entries(fields)) await browser.locator(`#tenant-create-modal [name="${field}"]`).fill(value);
  // Open-ended reservation is legal; the contract wizard supplies the confirmed end date.
  await confirmed(browser, '#tenant-create-modal form');
  await expect(browser.locator('.notice.success')).toBeVisible();
  await app.open(`${master}/tenants?q=${phone}`);
  await expect(browser.locator('tbody .badge').first()).toHaveText(/booking/i);

  let modal = await edit(browser, name);
  await expect(browser.locator(`${modal} [name="identity_number"]`)).toHaveValue(nik);
  await expect(browser.locator(`${modal} [name="birth_place"]`)).toHaveValue('Jakarta');
  await expect(browser.locator(`${modal} [name="birth_date"]`)).toHaveValue('1995-02-10');
  await expect(browser.locator(`${modal} [name="emergency_phone"]`)).toHaveValue('089970000001');
  await browser.locator(`${modal} [name="occupation"]`).fill('Analis demo diperbarui');
  await browser.locator(`${modal} [name="emergency_name"]`).fill('Kontak Demo Baru');
  await browser.locator(`${modal} [name="check_out_date"]`).fill('2027-01-31');
  await confirmed(browser, `${modal} form`);
  await expect(browser.locator('.notice.success')).toBeVisible();
  modal = await edit(browser, name);
  await expect(browser.locator(`${modal} [name="occupation"]`)).toHaveValue('Analis demo diperbarui');
  await expect(browser.locator(`${modal} [name="emergency_name"]`)).toHaveValue('Kontak Demo Baru');
  await expect(browser.locator(`${modal} [name="monthly_price"]`)).toHaveValue('Rp 1.250.000');
  await browser.locator(`${modal} [name="check_out_date"]`).fill('2027-01-01');
  await confirmed(browser, `${modal} form`);
  await expect(browser.locator('.notice.error')).toBeVisible();

  await browser.locator('[data-open-modal="#tenant-create-modal"]').click();
  await browser.locator('#tenant-create-modal [name="room_id"]').selectOption({ value: String(roomId) });
  await browser.locator('#tenant-create-modal [name="stay_status"]').selectOption({ value: 'reserved' });
  await browser.locator('#tenant-create-modal [name="fullname"]').fill(`Duplicate NIK ${stamp}`);
  await browser.locator('#tenant-create-modal [name="identity_number"]').fill(nik);
  await browser.locator('#tenant-create-modal [name="check_in_date"]').fill('2027-03-01');
  await browser.locator('#tenant-create-modal [name="check_out_date"]').fill('2027-03-31');
  await browser.locator('#tenant-create-modal [name="monthly_price"]').fill('1250000');
  await browser.locator('#tenant-create-modal [name="deposit"]').fill('750000');
  await confirmed(browser, '#tenant-create-modal form');
  await expect(browser.locator('.notice.error')).toBeVisible();

  await browser.locator('main form.filter-form [data-tenant-property-filter]').selectOption({ value: roomData.property });
  await browser.locator('main form.filter-form [data-tenant-type-filter]').selectOption({ value: roomData.type });
  await browser.locator('main form.filter-form [name="q"]').fill(phone);
  await browser.locator('main form.filter-form button[type="submit"]').click();
  await expect.poll(async () => await browser.locator('tbody').textContent()).toContain(name);
  await browser.locator((await row(browser, name)) + ' [data-open-modal^="#stay-contract-"]').click();
  const draft = '.modal.is-open form';
  await browser.locator(`${draft} [name="start_date"]`).fill('2027-01-10');
  await browser.locator(`${draft} [name="end_date"]`).fill('2027-01-31');
  await browser.locator(`${draft} [name="monthly_price"]`).fill('1250000');
  await browser.locator(`${draft} [name="deposit_amount"]`).fill('750000');
  await confirmed(browser, draft);
  await expect(browser.locator('.notice.success')).toBeVisible();
  await app.open(`${master}/contracts?q=${encodeURIComponent(name)}`);
  await expect(browser.locator('tbody .badge').first()).toHaveText(/draft/i);
  await browser.locator('[data-open-modal^="#contract-flow-"]').first().click();
  await browser.locator('.modal.is-open [name="signed_at"]').fill('2027-01-10');
  await browser.locator('.modal.is-open [name="inspection_confirmed"]').check();
  await confirmed(browser, '.modal.is-open form[action$="/activate"]');
  await expect(browser.locator('.notice.error')).toBeVisible();

  await app.open(`${master}/tenants?q=${phone}`);
  await browser.locator((await row(browser, name)) + ' [data-delete-url]').click();
  await expect(browser.locator('[data-delete-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-delete]').click();
  await expect(browser.locator('.notice.error')).toBeVisible();
  await app.open(`${master}/contracts?q=${encodeURIComponent(name)}`);
  await browser.locator('[data-open-modal^="#contract-flow-"]').first().click();
  await confirmed(browser, '.modal.is-open form[action$="/cancel"]');
  await expect(browser.locator('.notice.success')).toBeVisible();
  await app.open(`${master}/tenants?q=${phone}`);
  await expect(browser.locator('tbody .badge').first()).toHaveText(/selesai/i);
  const roomCode = roomData.label.match(/\(([^/]+)\//)?.[1]?.trim() ?? '';
  expect(roomCode).not.toBe('');
  await app.open(`${master}/rooms?q=${encodeURIComponent(roomCode)}`);
  await expect(browser.locator('tbody .badge.available').first()).toHaveText(/available/i);
});
