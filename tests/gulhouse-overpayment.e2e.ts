import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

test('overpaid unsigned invoice keeps dashboard charts payments and receivables usable', { timeout: 180000 }, async ({ app, screen, browser }) => {
  const reference = `OVERPAID-${Date.now()}`;
  await app.open('/master/index.php/login');
  await screen.getByLabel('Username').fill(process.env.GULHOUSE_E2E_USER ?? 'codex_trial');
  await screen.getByLabel('Password').fill(process.env.GULHOUSE_E2E_PASS ?? '');
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/dashboard/);
  await app.open('/master/index.php/payments?q=DEMO%20Penghuni%201');
  const button = browser.locator('[data-open-modal^="#payment-add-"]').first();
  const modal = await button.getAttribute('data-open-modal');
  await button.click();
  await browser.locator(`${modal} [name="amount"]`).fill('1000');
  await browser.locator(`${modal} [name="payment_date"]`).fill('2026-11-02');
  await browser.locator(`${modal} [name="payment_type"]`).selectOption({ value: 'rent' });
  await browser.locator(`${modal} [name="method"]`).fill('Synthetic overpayment');
  await browser.locator(`${modal} [name="reference_no"]`).fill(reference);
  await browser.locator(`${modal} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-save]').click();
  await expect(browser.locator('.notice.success')).toBeVisible();
  await expect(browser.locator('tbody tr:first-child .badge').first()).toHaveText(/Overpaid/i);
  for (const path of ['/dashboard?period=2026-11', '/payments?q=DEMO%20Penghuni%201', '/receivables?period=2026-11']) {
    await app.open(`/master/index.php${path}`);
    await expect(browser.locator('body')).toBeVisible();
    const text = await browser.locator('body').textContent();
    expect(text).not.toMatch(/out of range|Database Error|SQLSTATE|A PHP Error/i);
  }
  await app.open('/master/index.php/receivables?q=DEMO%20Penghuni%201');
  expect(await browser.locator('tbody').textContent()).not.toContain('DEMO Penghuni 1');
  // Restore this shared synthetic demo invoice for subsequent portal tests/viewports.
  await app.open('/master/index.php/payments?q=DEMO%20Penghuni%201');
  const deleteUrl = await browser.evaluate((ownReference) => {
    const field = Array.from(document.querySelectorAll('.modal [name="reference_no"]')).find(input => (input as HTMLInputElement).value === ownReference);
    const ownModal = field?.closest('.modal');
    const row = Array.from(document.querySelectorAll('tbody tr')).find(tr => Array.from(tr.querySelectorAll('[data-open-modal]')).some(button => button.getAttribute('data-open-modal') === `#${ownModal?.id}`));
    return row?.querySelector('[data-delete-url]')?.getAttribute('data-delete-url');
  }, reference);
  expect(deleteUrl, 'only the synthetic overpayment is selected for cleanup').not.toBe(null);
  await browser.locator(`button[data-delete-url="${deleteUrl}"]`).click();
  await browser.locator('[data-confirm-delete]').click();
  await expect(browser.locator('.notice.success')).toBeVisible();
  await expect(browser.locator('tbody tr:first-child .badge').first()).toHaveText(/^Paid$/i);
});
