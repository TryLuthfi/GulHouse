import { test } from '@e2e-dev/web';
import { expect } from 'e2e';
import { mkdirSync, writeFileSync, unlinkSync, existsSync, readFileSync } from 'node:fs';
import { resolve, sep, basename } from 'node:path';
import { deflateSync } from 'node:zlib';

const prefix = '/master/index.php';
const user = process.env.GULHOUSE_E2E_USER ?? 'codex_trial';
const pass = process.env.GULHOUSE_E2E_PASS ?? 'LocalDemo2026!';
const stamp = Date.now().toString().slice(-8);
async function login(app:any, browser:any, username=user, password=pass) {
  await app.open(`${prefix}/login`);
  await browser.locator('[name="username"]').fill(username);
  await browser.locator('[name="password"]').fill(password);
  await browser.locator('button[type="submit"]').click();
  await expect(browser).toHaveURL(/\/dashboard/);
}
async function text(browser:any, expected:string) {
  await expect.poll(async () => await browser.locator('body').textContent()).toContain(expected);
}
async function save(browser:any, form:string) {
  await browser.locator(`${form} button[type="submit"]`).click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeVisible();
  await browser.locator('[data-confirm-save]').click();
}
async function idFor(browser:any, label:string, kind:string) {
  return browser.evaluate(({label,kind}) => {
    const row = Array.from(document.querySelectorAll('tbody tr')).find(r => Array.from(r.querySelectorAll('td')).some(td => td.textContent?.trim() === label));
    return row?.querySelector(`[data-open-modal^="#${kind}-edit-"]`)?.getAttribute('data-open-modal') ?? '';
  }, {label,kind});
}
async function choose(browser:any, selector:string, label?:string) {
  const value = await browser.evaluate(({selector,label}) => {
    const select = document.querySelector(selector) as HTMLSelectElement;
    return Array.from(select.options).find(o => o.value && !o.disabled && (!label || o.textContent?.includes(label)))?.value ?? '';
  }, {selector,label:label ?? null});
  expect(value).not.toBe('');
  await browser.locator(selector).selectOption({value});
}
async function remove(browser:any, selector:string, cancel=false) {
  await browser.locator(selector).click();
  await expect(browser.locator('[data-delete-confirm-modal].is-open')).toBeVisible();
  await browser.locator(cancel ? '[data-delete-confirm-modal] .modal-actions [data-close-modal]' : '[data-confirm-delete]').click();
}

test('admin property type room CRUD, filters and cancelled confirmations', {timeout:240_000}, async ({app,browser}) => {
  await login(app,browser);
  const code=`QA${stamp}`, name=`QA Property ${stamp}`, type=`QA Type ${stamp}`, room=`QA Room ${stamp}`;
  await app.open(`${prefix}/properties`);
  await browser.locator('[data-open-modal="#property-create-modal"]').click();
  await browser.locator('#property-create-modal [name="code"]').fill(code);
  await browser.locator('#property-create-modal [name="name"]').fill(name);
  await browser.locator('#property-create-modal [name="property_type"]').selectOption({value:'apartment'});
  await browser.locator('#property-create-modal [name="address"]').fill('Synthetic demo address');
  await browser.locator('#property-create-modal button[type="submit"]').click();
  await browser.locator('[data-save-confirm-modal] .modal-actions [data-close-modal]').click();
  await expect(browser.locator('#property-create-modal.is-open')).toBeVisible();
  await save(browser,'#property-create-modal form'); await text(browser,'Properti tersimpan.');
  await browser.locator('[data-open-modal="#property-create-modal"]').click();
  await browser.locator('#property-create-modal [name="code"]').fill(code);
  await browser.locator('#property-create-modal [name="name"]').fill('Duplicate property attempt');
  await save(browser,'#property-create-modal form'); await text(browser,'Kode properti harus unik');
  let modal=await idFor(browser,name,'property'); expect(modal).not.toBe('');
  await browser.locator(`[data-open-modal="${modal}"]`).click();
  await browser.locator(`${modal} [name="address"]`).fill('Updated synthetic address');
  await browser.locator(`${modal} [name="property_type"]`).selectOption({value:'other'});
  await save(browser,`${modal} form`); await text(browser,'Properti tersimpan.');
  await browser.locator(`[data-open-modal="${modal}"]`).click();
  expect(await browser.evaluate(selector=>(document.querySelector(selector) as HTMLTextAreaElement).value,`${modal} [name="address"]`)).toBe('Updated synthetic address');
  expect(await browser.evaluate(selector=>(document.querySelector(selector) as HTMLSelectElement).value,`${modal} [name="property_type"]`)).toBe('other');
  await browser.locator(`${modal} .modal-head [data-close-modal]`).click();
  await browser.locator(`[data-open-modal="${modal}"]`).click();
  await browser.evaluate(selector=>(document.querySelector(selector) as HTMLSelectElement).add(new Option('Invalid enum regression','invalid-enum')),`${modal} [name="property_type"]`);
  await browser.locator(`${modal} [name="property_type"]`).selectOption({value:'invalid-enum'});
  await save(browser,`${modal} form`); await text(browser,'Jenis properti tidak valid.');
  await browser.locator(`[data-open-modal="${modal}"]`).click();
  expect(await browser.evaluate(selector=>(document.querySelector(selector) as HTMLSelectElement).value,`${modal} [name="property_type"]`)).toBe('other');
  await browser.locator(`${modal} .modal-head [data-close-modal]`).click();
  await remove(browser,`[data-delete-label="${code}"]`,true);
  await app.open(`${prefix}/properties`); await text(browser,name);
  await browser.locator('.master-filter-form [name="q"]').fill(code);
  await browser.locator('.master-filter-form button[type="submit"]').click(); await text(browser,name);

  await app.open(`${prefix}/room-types`);
  await browser.locator('[data-open-modal="#type-create-modal"]').click();
  await browser.locator('#type-create-modal [name="name"]').fill(type);
  await browser.locator('#type-create-modal [name="description"]').fill('Synthetic type');
  await save(browser,'#type-create-modal form'); await text(browser,'Tipe kamar tersimpan.');
  await browser.locator('[data-open-modal="#type-create-modal"]').click();
  await browser.locator('#type-create-modal [name="name"]').fill(type);
  await save(browser,'#type-create-modal form'); await text(browser,'Nama tipe harus unik');
  modal=await idFor(browser,type,'type');
  await browser.locator(`[data-open-modal="${modal}"]`).click();
  await browser.locator(`${modal} [name="description"]`).fill('Updated synthetic type');
  await save(browser,`${modal} form`); await text(browser,'Updated synthetic type');
  await remove(browser,`[data-delete-label="${type}"]`,true); await text(browser,type);

  await app.open(`${prefix}/rooms`);
  await browser.locator('[data-open-modal="#room-create-modal"]').click();
  await choose(browser,'#room-create-modal [name="property_id"]',code);
  await choose(browser,'#room-create-modal [name="room_type_id"]',type);
  await browser.locator('#room-create-modal [name="room_code"]').fill(`Q${stamp}`);
  await browser.locator('#room-create-modal [name="room_name"]').fill(room);
  await browser.locator('#room-create-modal [name="room_label"]').fill(room);
  await browser.locator('#room-create-modal [name="floor_no"]').fill('2');
  await browser.locator('#room-create-modal [name="deposit"]').fill('500000');
  await browser.locator('#room-create-modal [name="monthly_price"]').fill('-100');
  await browser.locator('#room-create-modal button[type="submit"]').click();
  await expect(browser.locator('[data-save-confirm-modal].is-open')).toBeHidden();
  expect(await browser.evaluate(()=>{const i=document.querySelector('#room-create-modal [name="monthly_price"]') as HTMLInputElement;return {raw:i.value,valid:i.validity.valid};})).toEqual({raw:'-100',valid:false});
  await browser.locator('#room-create-modal [name="monthly_price"]').fill('1e3');
  expect(await browser.evaluate(()=>{const i=document.querySelector('#room-create-modal [name="monthly_price"]') as HTMLInputElement;return {raw:i.value,valid:i.validity.valid};})).toEqual({raw:'1e3',valid:false});
  await browser.locator('#room-create-modal [name="monthly_price"]').fill('Rp 2.500.000,00');
  await browser.locator('#room-create-modal [name="is_public"]').check();
  await save(browser,'#room-create-modal form'); await text(browser,'Kamar tersimpan.');
  modal=await idFor(browser,room,'room'); const id=modal.split('-').pop();
  await browser.locator(`[data-open-modal="${modal}"]`).click();
  await browser.locator(`${modal} [name="monthly_price"]`).fill('2600000');
  await save(browser,`${modal} form`); await text(browser,'2.600.000');
  await remove(browser,`[data-delete-url$="rooms/toggle-public/${id}"]`,true);
  await expect.poll(() => browser.evaluate(selector => document.querySelector(selector)?.closest('tr')?.querySelectorAll('td')[6]?.textContent?.trim(),`[data-open-modal="${modal}"]`)).toBe('Public');
  await remove(browser,`[data-delete-url$="rooms/toggle-public/${id}"]`);
  await expect.poll(() => browser.evaluate(selector => document.querySelector(selector)?.closest('tr')?.querySelectorAll('td')[6]?.textContent?.trim(),`[data-open-modal="${modal}"]`)).toBe('Hidden');
  await app.open(`${prefix}/properties`); await remove(browser,`[data-delete-label="${code}"]`); await text(browser,'Arsipkan kamar terlebih dahulu');
  await app.open(`${prefix}/room-types`); await remove(browser,`[data-delete-label="${type}"]`); await text(browser,'Arsipkan kamar terlebih dahulu');
  await app.open(`${prefix}/rooms`);
  await remove(browser,`[data-delete-url$="rooms/delete/${id}"]`); await text(browser,'Kamar diarsipkan.');
  await app.open(`${prefix}/room-types`); await remove(browser,`[data-delete-label="${type}"]`); await text(browser,'Tipe kamar dinonaktifkan.');
  await app.open(`${prefix}/properties`); await remove(browser,`[data-delete-label="${code}"]`); await text(browser,'Properti dinonaktifkan.');
});

function png():Buffer {
  const crc=(buf:Buffer)=>{let c=0xffffffff;for(const b of buf){c^=b;for(let i=0;i<8;i++)c=(c>>>1)^((c&1)?0xedb88320:0);}return (c^0xffffffff)>>>0;};
  const chunk=(kind:string,data:Buffer)=>{const tag=Buffer.from(kind),size=Buffer.alloc(4),checksum=Buffer.alloc(4);size.writeUInt32BE(data.length);checksum.writeUInt32BE(crc(Buffer.concat([tag,data])));return Buffer.concat([size,tag,data,checksum]);};
  const hdr=Buffer.alloc(13);hdr.writeUInt32BE(4,0);hdr.writeUInt32BE(2,4);hdr[8]=8;hdr[9]=2;
  const pixels=Buffer.alloc(26,160);pixels[0]=0;pixels[13]=0;
  return Buffer.concat([Buffer.from('89504e470d0a1a0a','hex'),chunk('IHDR',hdr),chunk('IDAT',deflateSync(pixels)),chunk('IEND',Buffer.alloc(0))]);
}
function cleanSyntheticUpload(relative:string) {
  const path=resolve(relative), root=resolve('uploads') + sep;
  if (!path.startsWith(root) || !/^(?:image|kamar)_\d{14}_\d{3}_[a-f0-9]{6}\.png$/.test(basename(path)) || !existsSync(path)) return;
  const bytes=readFileSync(path);
  if (bytes.length < 24 || bytes.length > 2048 || bytes.subarray(0,8).toString('hex') !== '89504e470d0a1a0a') return;
  const dimensions=`${bytes.readUInt32BE(16)}x${bytes.readUInt32BE(20)}`;
  if (dimensions === '4x2' || dimensions === '2x4') unlinkSync(path);
}
test('admin real file inputs upload, preview, rotate, cancel and delete photos', {timeout:240_000}, async ({app,browser}) => {
  const path=`tests/fixtures/admin-${stamp}.png`, invalid=`tests/fixtures/admin-invalid-${stamp}.png`; mkdirSync('tests/fixtures',{recursive:true}); writeFileSync(path,png()); writeFileSync(invalid,'Invalid synthetic PNG file');
  const generated:string[]=[];
  try {
    await login(app,browser); await app.open(`${prefix}/photos`);
    const before=await browser.evaluate(()=>Array.from(document.querySelectorAll('[data-photo-file]')).map(n=>n.getAttribute('data-photo-file')));
    await browser.locator('[data-open-modal="#slider-upload-modal"]').click();
    await browser.locator('#slider-upload-modal input[type="file"]').setInputFiles(invalid);
    await save(browser,'#slider-upload-modal form'); await text(browser,'Foto belum tersimpan.');
    await browser.locator('[data-open-modal="#slider-upload-modal"]').click();
    await browser.locator('#slider-upload-modal input[type="file"]').setInputFiles(path);
    await save(browser,'#slider-upload-modal form'); await text(browser,'foto berhasil');
    const filename=await browser.evaluate(previous=>Array.from(document.querySelectorAll('[data-photo-file]')).map(n=>n.getAttribute('data-photo-file')).find(f=>!previous.includes(f))??'',before);
    expect(filename).not.toBe('');
    generated.push(`uploads/SLIDER/${filename}`);
    await browser.locator(`[data-photo-file="${filename}"]`).click();
    await expect(browser.locator('[data-master-photo-modal].is-open')).toBeVisible();
    await browser.locator('[data-master-photo-zoom-in]').click();
    await browser.locator('[data-master-photo-reset]').click();
    await browser.locator('[data-master-photo-rotate-right]').click();
    await browser.locator('[data-master-photo-save]').click();
    await expect.poll(()=>browser.evaluate(()=>{const i=document.querySelector('[data-master-photo-image]') as HTMLImageElement;return `${i.naturalWidth}x${i.naturalHeight}`;})).toBe('2x4');
    await browser.locator('button[data-master-photo-close]').click();
    await remove(browser,`[data-delete-label="${filename}"]`,true);
    await expect(browser.locator(`[data-photo-file="${filename}"]`)).toBeVisible();
    await remove(browser,`[data-delete-label="${filename}"]`); await text(browser,'Foto berhasil dihapus.');
    await expect(browser.locator(`[data-photo-file="${filename}"]`)).toBeHidden();

    // Load the selected gallery before recording its files; only delete our new upload.
    await choose(browser,'form[data-photo-cascade-form] [name="property"]');
    await choose(browser,'form[data-photo-cascade-form] [name="type"]');
    await browser.locator('form[data-photo-cascade-form] button[type="submit"]').click();
    await text(browser,'Menampilkan foto untuk');
    await browser.locator('[data-open-modal="#room-upload-modal"]').click();
    await browser.locator('[data-room-upload-add]').click();
    expect(await browser.evaluate(()=>document.querySelectorAll('[data-room-upload-row]').length)).toBe(2);
    await browser.locator('[data-room-upload-row]:nth-child(2) [data-room-upload-remove]').click();
    expect(await browser.evaluate(()=>document.querySelectorAll('[data-room-upload-row]').length)).toBe(1);
    await choose(browser,'#room-upload-modal [name="upload_rows[0][property_code]"]');
    await choose(browser,'#room-upload-modal [name="upload_rows[0][room_type]"]');
    await choose(browser,'#room-upload-modal [name="upload_rows[0][category]"]');
    const folder=await browser.evaluate(()=>{
      const p=(document.querySelector('#room-upload-modal [name="upload_rows[0][property_code]"]') as HTMLSelectElement).value;
      const t=(document.querySelector('#room-upload-modal [name="upload_rows[0][room_type]"]') as HTMLSelectElement).value;
      return `uploads/${p.toUpperCase().replace(/[^A-Z0-9]/g,'')}/${t.toUpperCase().replace(/[^A-Z0-9]/g,'')}`;
    });
    const roomBefore = await browser.evaluate(() => Array.from(document.querySelectorAll('[data-photo-file]')).map(n => n.getAttribute('data-photo-file')));
    await browser.locator('#room-upload-modal input[type="file"]').setInputFiles(path);
    await save(browser,'#room-upload-modal form'); await text(browser,'1 foto berhasil disimpan dari batch upload.');
    const roomfile=await browser.evaluate(previous=>Array.from(document.querySelectorAll('[data-photo-scope="room"][data-photo-file]')).map(n=>n.getAttribute('data-photo-file')).find(f=>!previous.includes(f))??'',roomBefore);
    expect(roomfile).not.toBe('');
    generated.push(`${folder}/${roomfile}`);
    await remove(browser,`[data-delete-label="${roomfile}"]`); await text(browser,'Foto berhasil dihapus.');
  } finally {
    // UI deletion remains the assertion. This only removes our recorded tiny PNGs on failure.
    for (const uploaded of generated) cleanSyntheticUpload(uploaded);
    if (existsSync(path)) unlinkSync(path);
    if (existsSync(invalid)) unlinkSync(invalid);
  }
});

test('admin users create edit disable and self-disable protection through forms', async ({app,browser}) => {
  await login(app,browser); await app.open(`${prefix}/users`);
  const username=`qa_${stamp}`;
  const create='main .panel:nth-of-type(1) form';
  await browser.locator(`${create} [name="fullname"]`).fill('QA Browser User');
  await browser.locator(`${create} [name="username"]`).fill(username);
  await browser.locator(`${create} [name="password"]`).fill(pass);
  await browser.locator(`${create} [name="role"]`).selectOption({value:'viewer'});
  await browser.locator(`${create} button[type="submit"]`).click(); await text(browser,'Akun tersimpan.');
  let detail=`details:has(input[name="username"][value="${username}"])`;
  await browser.locator(`${detail} summary`).click();
  await browser.locator(`${detail} [name="fullname"]`).fill('QA Browser User Updated');
  await browser.locator(`${detail} [name="role"]`).selectOption({value:'manager'});
  await browser.locator(`${detail} button[type="submit"]`).click(); await text(browser,'QA Browser User Updated');
  await browser.locator(`${detail} summary`).click();
  await browser.locator(`${detail} [name="is_active"]`).selectOption({value:'0'});
  await browser.locator(`${detail} button[type="submit"]`).click(); await text(browser,'Nonaktif');
  detail=`details:has(input[name="username"][value="${user}"])`;
  await browser.locator(`${detail} summary`).click();
  await browser.locator(`${detail} [name="is_active"]`).selectOption({value:'0'});
  await browser.locator(`${detail} button[type="submit"]`).click(); await text(browser,'Akun sendiri tidak boleh');
  await browser.locator('[data-logout-form] button[data-logout]').click(); await expect(browser).toHaveURL(/\/login/);
});

test('viewer controls are read only and invalid CSRF is rejected by browser submission', async ({app,browser}) => {
  await login(app,browser,'demo_viewer'); await app.open(`${prefix}/rooms`);
  await expect(browser.locator('[data-open-modal="#room-create-modal"]')).toBeHidden();
  await app.open(`${prefix}/users`); await text(browser,'Akses ditolak');
  await app.open(`${prefix}/rooms/delete/99999999`); await text(browser,'Metode tidak diizinkan');
  await app.open(`${prefix}/logout`); await text(browser,'Metode tidak diizinkan');
  await app.open(`${prefix}/dashboard`); await browser.locator('[data-logout-form] button[data-logout]').click();
  await app.open(`${prefix}/login`);
  await browser.locator('[name="username"]').fill(user); await browser.locator('[name="password"]').fill(pass);
  await browser.evaluate(()=>{const t=document.querySelector('input[name="csrf_gh_master"]') as HTMLInputElement;t.value='invalid-token';});
  await browser.locator('button[type="submit"]').click(); await text(browser,'The action you have requested is not allowed.');
});

test('password reset rejects old password and disabled account cannot sign in', {timeout:240_000}, async ({app,browser}) => {
  const account=`pw_${stamp}`, newPassword=`ResetDemo2026-${stamp}`;
  await login(app,browser); await app.open(`${prefix}/users`);
  const create='main .panel:nth-of-type(1) form';
  await browser.locator(`${create} [name="fullname"]`).fill('Penguji ' + '安全'.repeat(25));
  await browser.locator(`${create} [name="username"]`).fill(account);
  await browser.locator(`${create} [name="password"]`).fill(pass);
  await browser.locator(`${create} [name="role"]`).selectOption({value:'viewer'});
  await browser.locator(`${create} button[type="submit"]`).click(); await text(browser,'Akun tersimpan.');
  await browser.locator('[data-logout-form] button[data-logout]').click();
  await login(app,browser,account);
  await browser.locator('[data-logout-form] button[data-logout]').click();
  await login(app,browser); await app.open(`${prefix}/users`);
  const detail=`details:has(input[name="username"][value="${account}"])`;
  await browser.locator(`${detail} summary`).click();
  await browser.locator(`${detail} [name="password"]`).fill(newPassword);
  await browser.locator(`${detail} button[type="submit"]`).click(); await text(browser,'Akun tersimpan.');
  await browser.locator('[data-logout-form] button[data-logout]').click();
  await browser.locator('[name="username"]').fill(account);
  await browser.locator('[name="password"]').fill(pass);
  await browser.locator('button[type="submit"]').click();
  await text(browser,'Username atau password belum sesuai.'); await expect(browser).toHaveURL(/\/login/);
  await login(app,browser,account,newPassword);
  await browser.locator('[data-logout-form] button[data-logout]').click();
  await login(app,browser); await app.open(`${prefix}/users`);
  await browser.locator(`${detail} summary`).click();
  await browser.locator(`${detail} [name="is_active"]`).selectOption({value:'0'});
  await browser.locator(`${detail} button[type="submit"]`).click(); await text(browser,'Nonaktif');
  await browser.locator('[data-logout-form] button[data-logout]').click();
  await browser.locator('[name="username"]').fill(account);
  await browser.locator('[name="password"]').fill(newPassword);
  await browser.locator('button[type="submit"]').click();
  await text(browser,'Username atau password belum sesuai.'); await expect(browser).toHaveURL(/\/login/);
});
