import { test } from '@e2e-dev/web';
import { expect } from 'e2e';
import { readFile, readdir } from 'node:fs/promises';
import { join, isAbsolute } from 'node:path';

const prefix = process.env.GULHOUSE_MASTER_PREFIX ?? '/master/index.php';
const username = process.env.GULHOUSE_E2E_USER ?? 'codex_trial';
const password = process.env.GULHOUSE_E2E_PASS ?? 'CodexTrial123!';
const room = process.env.GULHOUSE_E2E_ROOM_ID ?? '';
const stamp = `${Date.now()}`;
const money = (value: number) => `Rp ${value.toLocaleString('id-ID')}`;

async function login(app: any, screen: any, browser: any, loginUser = username) {
  await app.open(`${prefix}/login`);
  await screen.getByLabel('Username').fill(loginUser);
  await screen.getByLabel('Password').fill(password);
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/\/dashboard(?:\?|$)/, { timeout: 20000 });
}
async function fields(browser: any, modal: string, values: Record<string, string>) {
  for (const [name, value] of Object.entries(values)) {
    const selector = `${modal} [name="${name}"]`;
    const tag = await browser.evaluate((s: string) => document.querySelector(s)?.tagName ?? '', selector);
    expect(tag, `Visible form field ${name}`).not.toBe('');
    if (tag === 'SELECT') await browser.locator(selector).selectOption({ value });
    else await browser.locator(selector).fill(value);
  }
}
async function confirm(browser: any, modal: string) {
  await browser.locator(`${modal} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible({ timeout: 20000 });
  await browser.locator('[data-confirm-save]').click();
  await expect(browser.locator('.notice.success')).toBeVisible({ timeout: 20000 });
}
async function create(browser: any, values: Record<string, string>) {
  await browser.locator('[data-open-modal="#ops-create-modal"]').click();
  await expect(browser.locator('#ops-create-modal.is-open')).toBeVisible({ timeout: 20000 });
  await fields(browser, '#ops-create-modal', values);
  await confirm(browser, '#ops-create-modal');
}
async function confirmRejected(browser: any, modal: string) {
  await browser.locator(`${modal} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible({ timeout: 20000 });
  await browser.locator('[data-confirm-save]').click();
  await expect(browser.locator('.notice.error')).toBeVisible({ timeout: 20000 });
}
async function filter(browser: any, query: string, status?: string) {
  await browser.locator('.filter-form [name="q"]').fill(query);
  if (status !== undefined) await browser.locator('.filter-form [name="status"]').selectOption({ value: status });
  await browser.locator('.filter-form button[type="submit"]').click();
}
async function rowSelector(browser: any, text: string) {
  const id = await browser.evaluate((label: string) => {
    const row = Array.from(document.querySelectorAll('tbody tr')).find(r => r.textContent?.includes(label));
    return row?.querySelector('[data-open-modal^="#ops-edit-"]')?.getAttribute('data-open-modal') ?? '';
  }, text);
  expect(id, `Persisted row for ${text}`).not.toBe('');
  return { modal: id, row: `tbody tr:has([data-open-modal="${id}"])` };
}
async function edit(browser: any, text: string, values: Record<string, string>) {
  const target = await rowSelector(browser, text);
  await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
  await expect(browser.locator(`${target.modal}.is-open`)).toBeVisible({ timeout: 20000 });
  await fields(browser, target.modal, values);
  await confirm(browser, target.modal);
}
async function exportCsv(browser: any, marker: string) {
  const download = await browser.waitForDownload(() => browser.locator('a[href*="/export?"]').click());
  expect(download.suggestedFilename).toContain('.csv');
  const outputRoot = process.env.GULHOUSE_E2E_OUTPUT ?? '.e2e-poco-operations';
  let savedPath = download.path;
  if (!isAbsolute(savedPath)) {
    // The SDK returns an attempt-relative artifact path, not a project-relative path.
    const resultRoot = join(outputRoot, 'results');
    const artifacts = await readdir(resultRoot, { recursive: true });
    const suffix = savedPath.replace(/\\/g, '/');
    const candidates = artifacts.filter(path => String(path).replace(/\\/g, '/').endsWith(suffix));
    expect(candidates.length, 'Downloaded CSV artifact resolved exactly once').toBe(1);
    savedPath = join(resultRoot, String(candidates[0]));
  }
  const csv = await readFile(savedPath, 'utf8');
  expect(csv, 'Export contains the filtered saved data').toContain(marker);
  return csv;
}
async function remove(browser: any, text: string) {
  const target = await rowSelector(browser, text);
  await browser.locator(`${target.row} [data-delete-url]`).click();
  await expect(browser.locator('[data-delete-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-delete]').click();
  await expect(browser.locator('.notice.success')).toBeVisible();
  await filter(browser, text);
  expect(await browser.locator('tbody').textContent()).not.toContain(text);
}

const modules = [
  { key: 'maintenance', name: 'issue_title', status: 'status', initial: 'open', final: 'cancelled', data: { room_id: room, reported_at: '2026-11-02', deadline_at: '2026-11-05', pic_name: 'DEMO Technician', category: 'ac', cost_amount: '0' }, changes: { notes: 'Cancelled duplicate inspection' } },
  { key: 'inventory', name: 'item_name_snapshot', status: 'condition_status', initial: 'good', final: 'damaged', data: { room_id: room, standard_qty: '2', actual_qty: '2', check_date: '2026-11-02', check_stage: 'routine', repair_cost: '0' }, changes: { actual_qty: '1', notes: 'One item damaged' } },
  { key: 'complaints', name: 'complaint_title', status: 'status', initial: 'open', final: 'cancelled', data: { room_id: room, reported_at: '2026-11-02', deadline_at: '2026-11-05', pic_name: 'DEMO Handler', category: 'noise' }, changes: { resolution_notes: 'Duplicate complaint withdrawn' } },
  { key: 'facilities', name: 'facility_name', status: 'followup_status', initial: 'open', final: 'done', data: { room_id: room, location_label: 'DEMO Lobby', quantity: '2', condition_status: 'needs_maintenance', check_date: '2026-11-02', deadline_at: '2026-11-05', pic_name: 'DEMO Technician' }, changes: { condition_status: 'good', issue_notes: 'Repair verified' } },
  { key: 'housekeeping', name: 'check_item', status: 'status', initial: 'open', final: 'done', data: { location_label: 'DEMO Lobby', area_name: 'Lobby', check_date: '2026-11-02', condition_status: 'dirty', officer_name: 'DEMO Cleaner' }, changes: { condition_status: 'good', notes: 'Cleaned and verified' } },
  { key: 'audits', name: 'audit_item', status: 'status', initial: 'open', final: 'done', data: { location_label: 'DEMO Lobby', period_label: 'NOVEMBER 2026', audit_date: '2026-11-02', result_status: 'finding', pic_name: 'DEMO Auditor', deadline_at: '2026-11-05' }, changes: { result_status: 'ok', action_plan: 'Finding closed' } },
  { key: 'finance', name: 'description', status: 'payment_status', initial: 'unpaid', final: 'paid', data: { expense_date: '2026-11-02', category: 'supplies', amount: '25000', vendor_name: 'DEMO Vendor', reference_no: `POCO-FIN-${stamp}` }, changes: { amount: '30000', notes: 'Expense verified' } },
  { key: 'notifications', name: 'subject', status: 'status', initial: 'draft', final: 'cancelled', data: { recipient_name: 'DEMO Reminder', recipient_phone: '089990000000', message_body: 'DEMO reminder only; no external delivery.', scheduled_at: '2026-11-02T09:00' }, changes: { message_body: 'DEMO reminder updated and cancelled.' } },
  { key: 'templates', name: 'title', status: 'document_type', initial: 'other', final: 'statement', data: { template_key: `poco_${stamp}`, body_template: 'DEMO statement for {tenant_name} room {room_label}.' }, changes: { body_template: 'Updated DEMO statement for {tenant_name}.' } },
] as const;

for (const spec of modules) {
  test(`operations ${spec.key}: browser create edit filter CSV delete`, async ({ app, screen, browser }) => {
    await login(app, screen, browser);
    await app.open(`${prefix}/${spec.key}`);
    const marker = `DEMO POCO ${spec.key} ${stamp}`;
    await create(browser, { ...spec.data, [spec.name]: marker, [spec.status]: spec.initial });
    await filter(browser, marker, spec.initial);
    expect(await browser.locator('tbody tr').count()).toBe(1);
    expect(await browser.locator('tbody').textContent()).toContain(marker);
    expect(((await browser.locator('tbody').textContent()) ?? '').toLowerCase()).toContain(spec.initial);
    if (spec.key === 'maintenance') {
      let target = await rowSelector(browser, marker);
      await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
      await fields(browser, target.modal, { issue_title: `${marker} UNSAVED` });
      await browser.locator(`${target.modal} button[type="submit"]`).click();
      await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible({ timeout: 20000 });
      await browser.locator('[data-save-confirm-modal] .modal-actions [data-close-modal]').click();
      await browser.locator('[data-save-confirm-modal]').waitFor({ state: 'hidden' });
      await browser.locator(`${target.modal} .modal-head [data-close-modal]`).click();
      await filter(browser, marker, spec.initial);
      expect(await browser.locator('tbody').textContent()).not.toContain('UNSAVED');
      target = await rowSelector(browser, marker);
      await browser.locator(`${target.row} [data-delete-url]`).click();
      await expect(browser.locator('[data-delete-confirm-modal].is-open')).toBeVisible({ timeout: 20000 });
      await browser.locator('[data-delete-confirm-modal] .modal-actions [data-close-modal]').click();
      await browser.locator('[data-delete-confirm-modal]').waitFor({ state: 'hidden' });
      await filter(browser, marker, spec.initial);
      expect(await browser.locator('tbody').textContent()).toContain(marker);
    }
    await edit(browser, marker, { ...spec.changes, [spec.status]: spec.final });
    await filter(browser, marker, spec.final);
    expect(await browser.locator('tbody').textContent()).toContain(marker);
    expect(((await browser.locator('tbody').textContent()) ?? '').toLowerCase()).toContain(spec.final);
    if (spec.key === 'templates') {
      let target = await rowSelector(browser, marker);
      await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
      await browser.locator(`${target.modal} [name="is_active"]`).uncheck();
      await confirm(browser, target.modal);
      await filter(browser, marker, spec.final);
      target = await rowSelector(browser, marker);
      expect((await browser.locator(`${target.row} td`).nth(3).textContent())?.trim()).toBe('0');
      expect(await browser.locator(`${target.row} a[href*="/templates/print/"]`).count()).toBe(0);
      await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
      await browser.locator(`${target.modal} [name="is_active"]`).check();
      await confirm(browser, target.modal);
      await filter(browser, marker, spec.final);
      target = await rowSelector(browser, marker);
      expect((await browser.locator(`${target.row} td`).nth(3).textContent())?.trim()).toBe('1');
      await browser.locator(`${target.row} a[href*="/templates/print/"]`).click();
      await expect(browser).toHaveURL(/\/templates\/print\//, { timeout: 20000 });
      expect(await browser.locator('main').textContent()).toContain('Updated DEMO statement');
      await browser.locator('.actions a').click();
      await expect(browser).toHaveURL(/\/templates\?/, { timeout: 20000 });
    }
    if (spec.key === 'finance') {
      await browser.locator('.filter-form [name="period"]').fill('2026-11');
      await browser.locator('.filter-form button[type="submit"]').click();
      expect(await browser.locator('tbody').textContent()).toContain(marker);
      await browser.locator('.filter-form [name="period"]').fill('2026-12');
      await browser.locator('.filter-form button[type="submit"]').click();
      expect(await browser.locator('tbody').textContent()).not.toContain(marker);
      await browser.locator('.filter-form [name="period"]').fill('2026-11');
      await browser.locator('.filter-form button[type="submit"]').click();
    }
    const csv = await exportCsv(browser, marker);
    expect(csv).toContain(spec.final);
    expect(csv.trim().split(/\r?\n/).length, 'Filtered CSV has header and exactly one data row').toBe(2);
    await filter(browser, `NO-SUCH-${stamp}`, spec.final);
    expect(await browser.locator('tbody').textContent()).not.toContain(marker);
    await browser.locator('.filter-form a.mini-link').click();
    expect(await browser.locator('tbody').textContent()).toContain(marker);
    await filter(browser, marker, spec.final);
    await remove(browser, marker);
  });
}

test('complaint escalation and maintenance cost expense reconcile through browser', async ({ app, screen, browser }) => {
  await login(app, screen, browser);
  const width = await browser.evaluate(() => window.innerWidth);
  // Isolate amount assertions from the 2026 portal demo and the 2099 rental lifecycle.
  // Separate target years also prevent expenses from previous viewport runs leaking in.
  const year = width < 600 ? '2088' : width >= 1900 ? '2087' : '2086';
  const reportPeriod = `${year}-11`;
  const emptyPeriod = `${year}-12`;
  const completionDate = `${year}-11-15`;
  const marker = `DEMO POCO Linked AC ${stamp}`;
  await app.open(`${prefix}/complaints`);
  await create(browser, { complaint_title: marker, room_id: room, category: 'facility', status: 'open', reported_at: `${year}-11-10`, deadline_at: completionDate, pic_name: 'DEMO Technician' });
  await filter(browser, marker);
  let target = await rowSelector(browser, marker);
  await browser.locator(`${target.row} form button[type="submit"]`).click();
  await expect(browser.locator('.notice.success')).toBeVisible();
  await filter(browser, marker, 'in_progress');
  target = await rowSelector(browser, marker);
  await browser.locator(`${target.row} form button[type="submit"]`).click();
  await app.open(`${prefix}/maintenance`);
  await filter(browser, marker);
  expect(await browser.locator('tbody tr').count()).toBe(1);
  await edit(browser, marker, { status: 'in_progress', pic_name: 'DEMO Technician' });
  await filter(browser, marker);
  await edit(browser, marker, { status: 'done', finished_at: completionDate, cost_amount: '75000', notes: 'DEMO repaired and verified' });
  await app.open(`${prefix}/complaints`);
  await filter(browser, marker, 'resolved');
  expect(await browser.locator('tbody').textContent()).toContain(marker);
  expect(((await browser.locator('tbody').textContent()) ?? '').toLowerCase()).toContain('resolved');
  await app.open(`${prefix}/finance`);
  await filter(browser, marker);
  expect(await browser.locator('tbody tr').count()).toBe(1);
  expect(await browser.locator('tbody').textContent()).toContain(money(75000));
  await edit(browser, marker, { payment_status: 'paid', reference_no: `POCO-COST-${stamp}` });
  await filter(browser, marker, 'paid');
  expect(await browser.locator('tbody tr').count()).toBe(1);
  await exportCsv(browser, marker);
  await app.open(`${prefix}/maintenance`);
  await filter(browser, marker);
  await edit(browser, marker, { status: 'done', cost_amount: '75000', finished_at: completionDate, notes: 'DEMO repeated close is safe' });
  await app.open(`${prefix}/finance`);
  await filter(browser, marker);
  expect(await browser.locator('tbody tr').count()).toBe(1);
  // Validate amounts from rendered reports, not a database or HTTP substitute.
  await app.open(`${prefix}/reports`);
  await browser.locator('.filter-form [name="period"]').fill(reportPeriod);
  await browser.locator('.filter-form button[type="submit"]').click();
  const reportRows = await browser.evaluate(() => Array.from(document.querySelectorAll('tbody tr')).map(r => Array.from(r.querySelectorAll('td')).map(c => c.textContent?.trim() ?? '')));
  const value = (label: string) => reportRows.find((r: string[]) => r[0] === label)?.[1];
  expect(value('Tagihan sewa periode')).toBe(money(0));
  expect(value('Penerimaan sewa periode')).toBe(money(0));
  expect(value('Biaya operasional dibayar periode')).toBe(money(75000));
  expect(value('Selisih sewa dan biaya periode')).toBe(money(-75000));
  await exportCsv(browser, money(75000));
  await filter(browser, 'Biaya operasional dibayar periode');
  expect(await browser.locator('tbody tr').count()).toBe(1);
  expect(await browser.locator('tbody').textContent()).toContain(money(75000));
  const reportCsv = await exportCsv(browser, money(75000));
  expect(reportCsv.trim().split(/\r?\n/).length).toBe(2);
  await filter(browser, `NO-SUCH-${stamp}`);
  expect(await browser.locator('tbody').textContent()).not.toContain('Biaya operasional dibayar periode');
  await browser.locator('.filter-form [name="q"]').fill('');
  await browser.locator('.filter-form [name="period"]').fill(emptyPeriod);
  await browser.locator('.filter-form button[type="submit"]').click();
  expect(await browser.locator('tbody').textContent()).not.toContain(money(75000));
  await app.open(`${prefix}/dashboard`);
  await browser.locator('.dashboard-filterbar [name="period"]').fill(reportPeriod);
  await browser.locator('.dashboard-filterbar button[type="submit"]').click();
  const expenseCard = '.dashboard-kpi-grid article:has(span:text-is("Biaya operasional"))';
  expect(await browser.locator(expenseCard).textContent()).toContain(money(75000));
  await browser.locator('.dashboard-filterbar [name="period"]').fill(emptyPeriod);
  await browser.locator('.dashboard-filterbar button[type="submit"]').click();
  expect(await browser.locator(expenseCard).textContent()).toContain(money(0));
});

test('dashboard shortcuts hide unauthorized destinations by role', async ({ app, screen, browser }) => {
  await login(app, screen, browser, 'demo_finance');
  expect(await browser.locator('.dashboard-search').count()).toBe(0);
  expect(await browser.locator('.dashboard-actions a[href$="/complaints"]').count()).toBe(0);
  expect(await browser.locator('.dashboard-actions a[href$="/notifications"]').count()).toBe(0);
  await browser.locator('[data-logout-form] button[data-logout]').click();
  await expect(browser).toHaveURL(/\/login$/);
  await login(app, screen, browser, 'demo_maintenance');
  expect(await browser.locator('.dashboard-search').count()).toBe(0);
  expect(await browser.locator('.dashboard-actions a[href$="/notifications"]').count()).toBe(0);
  await browser.locator('.dashboard-actions a[href$="/complaints"]').click();
  await expect(browser).toHaveURL(/\/complaints$/);
  expect(await browser.locator('main').textContent()).toContain('Komplain');
});

test('operational browser validation rejects invalid quantity cost and completion', async ({ app, screen, browser }) => {
  await login(app, screen, browser);
  const marker = `DEMO POCO Validation ${stamp}`;
  await app.open(`${prefix}/inventory`);
  await create(browser, { item_name_snapshot: marker, room_id: room, standard_qty: '2', actual_qty: '2', condition_status: 'good', check_stage: 'routine', check_date: '2026-11-15', repair_cost: '0' });
  await filter(browser, marker);
  let target = await rowSelector(browser, marker);
  await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
  await fields(browser, target.modal, { actual_qty: '-1' });
  await confirmRejected(browser, target.modal);
  await filter(browser, marker);
  target = await rowSelector(browser, marker);
  expect((await browser.locator(`${target.row} td`).nth(4).textContent())?.trim()).toBe('2');
  await remove(browser, marker);
  await app.open(`${prefix}/maintenance`);
  await create(browser, { issue_title: marker, room_id: room, status: 'open', reported_at: '2026-11-15', cost_amount: '0' });
  await filter(browser, marker);
  target = await rowSelector(browser, marker);
  await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
  await fields(browser, target.modal, { status: 'done', finished_at: '' });
  await confirmRejected(browser, target.modal);
  await filter(browser, marker, 'open');
  expect(((await browser.locator('tbody').textContent()) ?? '').toLowerCase()).toContain('open');
  await remove(browser, marker);
  await app.open(`${prefix}/finance`);
  await browser.locator('[data-open-modal="#ops-create-modal"]').click();
  await fields(browser, '#ops-create-modal', { description: marker, expense_date: '2026-11-15', amount: '0', payment_status: 'unpaid' });
  await confirmRejected(browser, '#ops-create-modal');
  await filter(browser, marker);
  expect(await browser.locator('tbody').textContent()).not.toContain(marker);
});

test('dashboard visible search and shortcuts have real destinations', async ({ app, screen, browser }) => {
  await login(app, screen, browser);
  expect(await browser.locator('.dashboard-actions button:not([data-logout])').count()).toBe(0);
  await browser.locator('.dashboard-search [name="q"]').fill('Ephesus');
  await browser.locator('.dashboard-search button[type="submit"]').click();
  await expect(browser).toHaveURL(/\/rooms\?q=Ephesus/);
  expect(await browser.locator('main').textContent()).toContain('Kamar');
  await app.open(`${prefix}/dashboard`);
  await browser.locator('.dashboard-actions a[href$="/complaints"]').click();
  await expect(browser).toHaveURL(/\/complaints$/);
  await app.open(`${prefix}/dashboard`);
  await browser.locator('.dashboard-actions a[href$="/notifications"]').click();
  await expect(browser).toHaveURL(/\/notifications$/);
});

test('manual reminder queued failed and sent metadata has no provider delivery action', async ({ app, screen, browser }) => {
  await login(app, screen, browser);
  const width = await browser.evaluate(() => window.innerWidth);
  const year = width < 600 ? '2088' : width >= 1900 ? '2087' : '2086';
  const marker = `DEMO POCO Manual Reminder ${stamp}`;
  await app.open(`${prefix}/notifications`);
  await create(browser, {
    subject: marker, recipient_name: 'DEMO Manual Status', recipient_phone: '089990000000',
    message_body: 'Synthetic administrative status simulation; no actual external message delivery.',
    status: 'queued', scheduled_at: `${year}-11-15T09:00`,
  });
  await filter(browser, marker, 'queued');
  expect(((await browser.locator('tbody').textContent()) ?? '').toLowerCase()).toContain('queued');
  await edit(browser, marker, { status: 'failed', error_message: 'Synthetic provider unavailable; no message sent.' });
  await filter(browser, marker, 'failed');
  const failedCsv = await exportCsv(browser, marker);
  expect(failedCsv).toContain('failed');
  await edit(browser, marker, { status: 'sent', sent_at: `${year}-11-15T10:00`, error_message: '' });
  await filter(browser, marker, 'sent');
  expect(((await browser.locator('tbody').textContent()) ?? '').toLowerCase()).toContain('sent');
  const target = await rowSelector(browser, marker);
  await browser.locator(`${target.row} [data-open-modal="${target.modal}"]`).click();
  await expect(browser.locator(`${target.modal} [name="sent_at"]`)).toHaveValue(`${year}-11-15T10:00`);
  await expect(browser.locator(`${target.modal} [name="message_body"]`)).toHaveValue('Synthetic administrative status simulation; no actual external message delivery.');
  await browser.locator(`${target.modal} .modal-head [data-close-modal]`).click();
  expect(await browser.locator('button').filter({ hasText: /^(Kirim|Send)(?:\s|$)/i }).count()).toBe(0);
  const providerRequests = await browser.evaluate(() => performance.getEntriesByType('resource')
    .map(entry => entry.name).filter(url => /https?:\/\/(?:graph\.facebook\.com|api\.twilio\.com|api\.whatsapp\.com)\//i.test(url)));
  expect(providerRequests.length).toBe(0);
  await exportCsv(browser, marker);
  await remove(browser, marker);
});
