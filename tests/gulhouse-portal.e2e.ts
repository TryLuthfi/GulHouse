import { test } from '@e2e-dev/web';
import { expect } from 'e2e';
import { mkdirSync, writeFileSync, unlinkSync } from 'node:fs';

const landing='/landing/index.php';
async function textContains(browser:any,text:string) {
  await expect.poll(async()=>await browser.locator('body').textContent()).toContain(text);
}
async function lookup(browser:any,phone:string,last4:string) {
  await browser.locator('.payment-check-form [name="phone"]').fill(phone);
  await browser.locator('.payment-check-form [name="nik_last4"]').fill(last4);
  await browser.locator('.payment-check-form button[type="submit"]').click();
}
test('landing modals, accordion, map and room gallery work through browser actions',async({app,browser})=>{
  await app.open(landing+'/');
  await expect.poll(()=>browser.evaluate(()=>document.documentElement.getAttribute('data-landing-ready')),{timeout:30000}).toBe('true');
  await browser.locator('.booking-modal-trigger [data-booking-open]').click();
  await expect(browser.locator('[data-booking-modal].is-open')).toBeVisible({ timeout: 20000 });
  await browser.locator('.booking-form button[type="submit"]').click();
  expect(await browser.evaluate(()=>Array.from(document.querySelectorAll('.booking-form input[required],.booking-form select[required]')).some((node:any)=>!node.validity.valid))).toBe(true);
  await browser.keyboard.press('Escape');
  await expect(browser.locator('[data-booking-modal].is-open')).not.toBeVisible({ timeout: 20000 });
  await browser.locator('.header-action[data-booking-open]').click();
  await expect(browser.locator('[data-booking-modal].is-open')).toBeVisible({ timeout: 20000 });
  await browser.locator('button.booking-modal-close').click();
  await expect(browser.locator('[data-booking-modal].is-open')).not.toBeVisible({ timeout: 20000 });
  await browser.locator('.accordion-trigger').last().click();
  expect(await browser.evaluate(()=>document.querySelector('.accordion-trigger:last-of-type')?.getAttribute('aria-expanded')==='true'||Array.from(document.querySelectorAll('.accordion-trigger')).at(-1)?.getAttribute('aria-expanded')==='true')).toBe(true);
  await browser.locator('[data-map-tab="GH 2"]').click();
  await expect(browser.locator('[data-map-panel="GH 2"].is-active')).toBeVisible({ timeout: 20000 });
  await browser.locator('a[href*="/rooms/"]').first().click();
  await expect(browser).toHaveURL(/\/rooms\//);
  await expect.poll(()=>browser.evaluate(()=>Number((window as any).GH_ROOM_GALLERY?.length??0)),{timeout:20000}).toBeGreaterThan(0);
  await expect.poll(()=>browser.evaluate(()=>document.querySelector('[data-gallery-open]')?.getAttribute('data-gallery-ready')),{timeout:30000}).toBe('true');
  await browser.locator('[data-gallery-open]').first().click();
  await expect(browser.locator('[data-photo-modal].is-open')).toBeVisible({ timeout: 20000 });
  await browser.locator('[data-photo-next]').click();
  await expect(browser.locator('[data-photo-main]')).toBeVisible({ timeout: 20000 });
  await browser.locator('[data-photo-prev]').click();
  await browser.locator('button[data-photo-close]').click();
  await expect(browser.locator('[data-photo-modal].is-open')).not.toBeVisible({ timeout: 20000 });
});
test('resident lookup, period, complaint, logout and isolation work through browser',async({app,browser})=>{
  await app.open(landing+'/');
  await expect.poll(()=>browser.evaluate(()=>document.documentElement.getAttribute('data-landing-ready')),{timeout:30000}).toBe('true');
  if (await browser.evaluate(()=>window.innerWidth) < 980) {
    await browser.locator('[data-menu-toggle]').click();
    await expect(browser.locator('.site-header.is-menu-open')).toBeVisible();
  }
  await browser.locator('[data-payment-open]').first().click();
  await expect(browser.locator('[data-payment-modal].is-open')).toBeVisible({ timeout: 20000 });
  await browser.keyboard.press('Escape');
  await expect(browser.locator('[data-payment-modal].is-open')).not.toBeVisible({ timeout: 20000 });
  await app.open(landing+'/payment');
  await lookup(browser,'089991000001','9999');
  await textContains(browser,'Data belum cocok');
  await lookup(browser,'089991000001','1001');
  await expect(browser).toHaveURL(/payment\/status/);
  await textContains(browser,'DEMO-CONTRACT-1');
  const body=await browser.locator('body').textContent();
  expect(body).not.toContain('DEMO-CONTRACT-2');
  await browser.locator('[name="period"]').fill('2026-11');
  await browser.locator('form[method="get"] button[type="submit"]').click();
  await textContains(browser,'Lunas');
  const complaint='E2E Resident '+Date.now()+'-'+await browser.evaluate(()=>window.innerWidth);
  await browser.locator('[name="category"]').selectOption({value:'facility'});
  await browser.locator('[name="complaint_title"]').fill(complaint);
  await browser.locator('[name="complaint_detail"]').fill('Periksa AC melalui alur browser demo.');
  await browser.locator('form[action*="resident/complaints"] button[type="submit"]').click();
  await textContains(browser,'Komplain diterima');
  await textContains(browser,complaint);
  await browser.locator('form[action*="payment/logout"] button[type="submit"]').click();
  await expect(browser).toHaveURL(/\/payment$/);
  await lookup(browser,'6289991000002','1002');
  await expect(browser).toHaveURL(/payment\/status/);
  await textContains(browser,'DEMO-CONTRACT-2');
  expect(await browser.locator('body').textContent()).not.toContain(complaint);
  expect(await browser.locator('body').textContent()).not.toContain('DEMO-CONTRACT-1');
});
test('resident period without invoice is explicit and contact links do not point to placeholders',async({app,browser})=>{
  await app.open(landing+'/payment');
  await lookup(browser,'089991000003','1003');
  await expect(browser).toHaveURL(/payment\/status/);
  await textContains(browser,'DEMO-CONTRACT-3');
  await browser.locator('[name="period"]').fill('2026-12');
  await browser.locator('form[method="get"] button[type="submit"]').click();
  await textContains(browser,'belum dibuat');
  expect(await browser.evaluate(()=>Array.from(document.querySelectorAll('.payment-summary-grid .payment-metric strong')).slice(0,3).map(n=>n.textContent?.trim()))).toEqual(['Rp 0','Rp 0','Rp 0']);
  expect(await browser.evaluate(()=>Array.from(document.querySelectorAll('a[href]')).some((a:any)=>a.href.includes('6280000000000')))).toBe(false);
});
test('private documents real file input, download, invalid MIME and delete work through browser',async({app,browser})=>{
  const stamp=Date.now()+'-'+Math.random().toString(16).slice(2,8);
  mkdirSync('tests/fixtures',{recursive:true});
  const good=`tests/fixtures/portal-doc-${stamp}.png`, bad=`tests/fixtures/portal-bad-${stamp}.pdf`;
  writeFileSync(good,Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=','base64'));
  writeFileSync(bad,'Invalid synthetic PDF content');
  try {
    await app.open('/master/index.php/login');
    await browser.locator('[name="username"]').fill(process.env.GULHOUSE_E2E_USER??'codex_trial');
    await browser.locator('[name="password"]').fill(process.env.GULHOUSE_E2E_PASS??'LocalDemo2026!');
    await browser.locator('button[type="submit"]').click();
    await expect(browser).toHaveURL(/dashboard/);
    const title='E2E Private Doc '+stamp;
    async function create(file:string,label:string) {
      await app.open('/master/index.php/documents');
      await browser.locator('[data-open-modal="#ops-create-modal"]').click();
      const tenantId=await browser.evaluate(()=>Array.from((document.querySelector('#ops-create-modal [name="tenant_id"]') as HTMLSelectElement).options).find(o=>(o.textContent??'').includes('DEMO Penghuni 1'))?.value??'');
      const contractId=await browser.evaluate(()=>Array.from((document.querySelector('#ops-create-modal [name="contract_id"]') as HTMLSelectElement).options).find(o=>(o.textContent??'').includes('DEMO-CONTRACT-1'))?.value??'');
      expect(tenantId).not.toBe(''); expect(contractId).not.toBe('');
      await browser.locator('#ops-create-modal [name="tenant_id"]').selectOption({value:tenantId});
      await browser.locator('#ops-create-modal [name="contract_id"]').selectOption({value:contractId});
      await browser.locator('#ops-create-modal [name="document_type"]').selectOption({value:'other'});
      await browser.locator('#ops-create-modal [name="document_title"]').fill(label);
      await browser.locator('#ops-create-modal [name="status"]').selectOption({value:'received'});
      await browser.locator('#ops-create-modal [name="received_at"]').fill('2026-11-01');
      await browser.locator('#ops-create-modal input[type="file"]').setInputFiles(file);
      await browser.locator('#ops-create-modal button[type="submit"]').click();
      await browser.locator('[data-confirm-save]').click();
    }
    await create(good,title); await textContains(browser,'Dokumen tersimpan');
    const goodRow=await browser.evaluate(label=>Array.from(document.querySelectorAll('tbody tr')).findIndex(n=>(n.textContent??'').includes(label)),title);
    expect(goodRow).toBeGreaterThanOrEqual(0);
    const download=await browser.waitForDownload(()=>browser.locator(`tbody tr:nth-child(${goodRow+1}) a[href*="/download_document/"]`).click());
    expect(download.suggestedFilename).toMatch(/document_.*\.png$/);
    await create(bad,title+' Invalid');
    await textContains(browser,'file ditolak');
    await browser.locator('.filter-form [name="q"]').fill(title);
    await browser.locator('.filter-form button[type="submit"]').click();
    const index=await browser.evaluate(label=>Array.from(document.querySelectorAll('tbody tr')).findIndex(n=>(n.textContent??'').includes(label+' Invalid')),title);
    expect(index).toBeGreaterThanOrEqual(0);
    await browser.locator(`tbody tr:nth-child(${index+1}) [data-delete-url],tbody tr:nth-child(${index+1}) [data-confirm-delete-url]`).click();
    await browser.locator('[data-confirm-delete]').click();
    expect(await browser.locator('body').textContent()).not.toContain(title+' Invalid');
  } finally { unlinkSync(good); unlinkSync(bad); }
});
