import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

const username = process.env.GULHOUSE_E2E_USER ?? 'codex_trial';
const password = process.env.GULHOUSE_E2E_PASS ?? 'CodexTrial123!';
let roomId = process.env.GULHOUSE_E2E_ROOM_ID_JOURNEY ?? '';
const masterPrefix = process.env.GULHOUSE_MASTER_PREFIX ?? '/master/index.php';
const landingPrefix = process.env.GULHOUSE_LANDING_PREFIX ?? '/landing/index.php';
const slowMs = Number(process.env.GULHOUSE_E2E_SLOW_MS ?? '0');

const stamp = String(Date.now()).slice(-6);
const guestName = `E2E Journey ${stamp}`;
const phone = `089990${stamp}`;

async function login(app: any, screen: any, browser: any) {
  await app.open(`${masterPrefix}/login`);
  await pause();
  await screen.getByLabel('Username').fill(username);
  await pause();
  await screen.getByLabel('Password').fill(password);
  await pause();
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/\/master(?:\/index\.php)?\/dashboard(?:$|\?)/, { timeout: 10000 });
  await pause();
}

async function submitConfirmed(browser: any, formSelector: string) {
  await browser.locator(`${formSelector} button[type="submit"]`).click();
  await pause();
  await browser.locator('[data-confirm-save]').click();
  await pause();
}

async function openCreate(browser: any) {
  await browser.locator('[data-open-modal="#ops-create-modal"]').click();
  await expect(browser.locator('#ops-create-modal.is-open')).toBeVisible();
  await pause();
}

async function fill(browser: any, selector: string, value: string) {
  await browser.locator(selector).fill(value);
  await pause();
}

async function selectFirstOption(browser: any, selector: string) {
  const value = await browser.evaluate((selectSelector) => {
    const select = document.querySelector(selectSelector) as HTMLSelectElement | null;
    const option = select ? Array.from(select.options).find((item) => item.value && !item.disabled) : null;
    return option ? option.value : '';
  }, selector);
  expect(value, `${selector} has selectable option`).not.toBe('');
  await browser.locator(selector).selectOption({ value: String(value) });
  await pause();
}

async function setFirstSelectOption(browser: any, selector: string) {
  const value = await browser.evaluate((selectSelector) => {
    const select = document.querySelector(selectSelector) as HTMLSelectElement | null;
    const option = select ? Array.from(select.options).find((item) => item.value && !item.disabled) : null;
    if (!select || !option) return '';
    select.value = option.value;
    select.dispatchEvent(new Event('input', { bubbles: true }));
    select.dispatchEvent(new Event('change', { bubbles: true }));
    return option.value;
  }, selector);
  expect(value, `${selector} has selectable option`).not.toBe('');
  await pause();
}

async function selectOptionByText(browser: any, selector: string, text: string) {
  const value = await browser.evaluate(({ selectSelector, optionText }) => {
    const select = document.querySelector(selectSelector) as HTMLSelectElement | null;
    const option = select
      ? Array.from(select.options).find((item) => item.textContent?.includes(optionText))
      : null;
    return option ? option.value : '';
  }, { selectSelector: selector, optionText: text });
  expect(value, `${selector} option containing ${text}`).not.toBe('');
  await browser.locator(selector).selectOption({ value: String(value) });
  await pause();
}

async function bodyShouldContain(browser: any, text: string) {
  await expect.poll(async () => (await browser.locator('body').textContent()) ?? '').toContain(text);
  await pause();
}

async function pause() {
  if (slowMs > 0) {
    await new Promise((resolve) => setTimeout(resolve, slowMs));
  }
}

test('landing booking to master operations journey', async ({ app, screen, browser }) => {
  await app.open(`${landingPrefix}/#booking`);
  await pause();
  const viewportWidth = await browser.evaluate(() => window.innerWidth);
  roomId = viewportWidth >= 1900 ? (process.env.GULHOUSE_E2E_ROOM_ID_JOURNEY_WIDE ?? roomId) : (process.env.GULHOUSE_E2E_ROOM_ID_JOURNEY ?? roomId);
  if (viewportWidth < 600) roomId = process.env.GULHOUSE_E2E_ROOM_ID_JOURNEY_MOBILE ?? roomId;
  const stayEndDate = viewportWidth >= 1900 ? '2099-12-31' : '2099-12-30';
  await browser.locator('.booking-modal-trigger [data-booking-open]').click();
  await expect(browser.locator('[data-booking-modal].is-open')).toBeVisible();
  await pause();
  await fill(browser, '.booking-form [name="fullname"]', guestName);
  await fill(browser, '.booking-form [name="phone"]', phone);
  await setFirstSelectOption(browser, '.booking-form [name="room_interest"]');
  await fill(browser, '.booking-form [name="move_in_plan"]', 'Minggu depan');
  await fill(browser, '.booking-form [name="message"]', 'E2E Journey booking dari browser test.');
  await screen.getByRole('button', 'Kirim Booking').click();
  await bodyShouldContain(browser, 'Booking');

  await login(app, screen, browser);

  await app.open(`${masterPrefix}/booking?q=${encodeURIComponent(phone)}`);
  await bodyShouldContain(browser, guestName);
  await browser.locator('[data-open-modal^="#booking-convert-"]').first().click();
  await expect(browser.locator('.modal.is-open')).toBeVisible();
  await pause();
  if (roomId) {
    await browser.locator('.modal.is-open [name="room_id"]').selectOption({ value: roomId });
  } else {
    await selectFirstOption(browser, '.modal.is-open [name="room_id"]');
  }
  await browser.locator('.modal.is-open [name="stay_status"]').selectOption({ value: 'reserved' });
  await browser.locator('.modal.is-open button[type="submit"]').click();
  expect(await browser.evaluate(() => {
    const form = document.querySelector('.modal.is-open form') as HTMLFormElement;
    return ['start_date', 'end_date'].every(name => {
      const input = form.querySelector(`[name="${name}"]`) as HTMLInputElement;
      return input.required && input.validity.valueMissing;
    });
  })).toBe(true);
  await expect(browser.locator('[data-save-confirm-modal].is-open')).not.toBeVisible();
  await fill(browser, '.modal.is-open [name="start_date"]', '2099-01-01');
  await fill(browser, '.modal.is-open [name="end_date"]', stayEndDate);
  await submitConfirmed(browser, '.modal.is-open form');
  await bodyShouldContain(browser, 'berhasil');

  await app.open(`${masterPrefix}/contracts`);
  await bodyShouldContain(browser, guestName);

  await app.open(`${masterPrefix}/documents`);
  await bodyShouldContain(browser, 'KTP');

  await app.open(`${masterPrefix}/deposits`);
  await openCreate(browser);
  await selectOptionByText(browser, '#ops-create-modal [name="tenant_id"]', guestName);
  if (roomId) await browser.locator('#ops-create-modal [name="room_id"]').selectOption({ value: roomId });
  await fill(browser, '#ops-create-modal [name="transaction_date"]', '2099-01-02');
  await browser.locator('#ops-create-modal [name="transaction_type"]').selectOption({ value: 'received' });
  await fill(browser, '#ops-create-modal [name="amount"]', '500000');
  await fill(browser, '#ops-create-modal [name="method"]', 'Transfer E2E');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, 'Deposit Masuk');

  await app.open(`${masterPrefix}/maintenance`);
  await openCreate(browser);
  if (roomId) await browser.locator('#ops-create-modal [name="room_id"]').selectOption({ value: roomId });
  await fill(browser, '#ops-create-modal [name="issue_title"]', `E2E Journey AC ${stamp}`);
  await fill(browser, '#ops-create-modal [name="issue_detail"]', 'Test ketik detail maintenance.');
  await fill(browser, '#ops-create-modal [name="pic_name"]', 'QA E2E');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey AC ${stamp}`);

  await app.open(`${masterPrefix}/inventory`);
  await openCreate(browser);
  if (roomId) await browser.locator('#ops-create-modal [name="room_id"]').selectOption({ value: roomId });
  await fill(browser, '#ops-create-modal [name="item_name_snapshot"]', `E2E Journey Meja ${stamp}`);
  await fill(browser, '#ops-create-modal [name="standard_qty"]', '1');
  await fill(browser, '#ops-create-modal [name="actual_qty"]', '1');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Meja ${stamp}`);

  await app.open(`${masterPrefix}/complaints`);
  await openCreate(browser);
  await selectOptionByText(browser, '#ops-create-modal [name="tenant_id"]', guestName);
  if (roomId) await browser.locator('#ops-create-modal [name="room_id"]').selectOption({ value: roomId });
  await fill(browser, '#ops-create-modal [name="complaint_title"]', `E2E Journey Komplain ${stamp}`);
  await fill(browser, '#ops-create-modal [name="complaint_detail"]', 'Test ketik komplain penghuni.');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Komplain ${stamp}`);

  await app.open(`${masterPrefix}/facilities`);
  await openCreate(browser);
  await fill(browser, '#ops-create-modal [name="facility_name"]', `E2E Journey Dispenser ${stamp}`);
  await fill(browser, '#ops-create-modal [name="location_label"]', 'Area Pantry E2E');
  await fill(browser, '#ops-create-modal [name="quantity"]', '1');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Dispenser ${stamp}`);

  await app.open(`${masterPrefix}/housekeeping`);
  await openCreate(browser);
  await fill(browser, '#ops-create-modal [name="location_label"]', `E2E Journey Area ${stamp}`);
  await fill(browser, '#ops-create-modal [name="check_item"]', 'Sapu dan pel lantai');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Area ${stamp}`);

  await app.open(`${masterPrefix}/audits`);
  await openCreate(browser);
  await fill(browser, '#ops-create-modal [name="period_label"]', '2099-01');
  await fill(browser, '#ops-create-modal [name="location_label"]', `E2E Journey Audit Area ${stamp}`);
  await fill(browser, '#ops-create-modal [name="audit_item"]', `E2E Journey Audit ${stamp}`);
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Audit ${stamp}`);

  await app.open(`${masterPrefix}/finance`);
  await openCreate(browser);
  await fill(browser, '#ops-create-modal [name="expense_date"]', '2099-01-03');
  await fill(browser, '#ops-create-modal [name="description"]', `E2E Journey Biaya ${stamp}`);
  await fill(browser, '#ops-create-modal [name="amount"]', '125000');
  await browser.locator('#ops-create-modal [name="payment_status"]').selectOption({ value: 'paid' });
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Biaya ${stamp}`);

  await app.open(`${masterPrefix}/notifications`);
  await openCreate(browser);
  await fill(browser, '#ops-create-modal [name="recipient_name"]', guestName);
  await fill(browser, '#ops-create-modal [name="recipient_phone"]', phone);
  await fill(browser, '#ops-create-modal [name="subject"]', `E2E Journey Reminder ${stamp}`);
  await fill(browser, '#ops-create-modal [name="message_body"]', 'Halo, ini draft reminder dari E2E.');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Reminder ${stamp}`);

  await app.open(`${masterPrefix}/templates`);
  await openCreate(browser);
  await fill(browser, '#ops-create-modal [name="template_key"]', `e2e_journey_${stamp}`);
  await fill(browser, '#ops-create-modal [name="title"]', `E2E Journey Template ${stamp}`);
  await fill(browser, '#ops-create-modal [name="body_template"]', 'Template dokumen dibuat oleh E2E.');
  await submitConfirmed(browser, '#ops-create-modal form');
  await bodyShouldContain(browser, `E2E Journey Template ${stamp}`);

  await app.open(`${masterPrefix}/reports`);
  await bodyShouldContain(browser, 'Maintenance open');
});
