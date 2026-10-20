import { test } from '@e2e-dev/web';
import { expect } from 'e2e';

const username = process.env.GULHOUSE_E2E_USER ?? 'codex_trial';
const password = process.env.GULHOUSE_E2E_PASS ?? 'CodexTrial123!';
const masterPrefix = process.env.GULHOUSE_MASTER_PREFIX ?? '/master/index.php';
const slowMs = Number(process.env.GULHOUSE_E2E_SLOW_MS ?? '0');

const pages = [
  ['Dashboard', '/dashboard', 'Dashboard Operasional GulHouse'],
  ['Booking', '/booking', 'Booking'],
  ['Properti', '/properties', 'Properti'],
  ['Tipe Kamar', '/room-types', 'Tipe Kamar'],
  ['Kamar', '/rooms', 'Kamar'],
  ['Penghuni', '/tenants', 'Penghuni'],
  ['Kontrak', '/contracts', 'Kontrak'],
  ['Dokumen', '/documents', 'Dokumen'],
  ['Billing', '/billing', 'Billing'],
  ['Pembayaran', '/payments', 'Pembayaran'],
  ['Piutang', '/receivables', 'Piutang'],
  ['Deposit', '/deposits', 'Deposit'],
  ['Maintenance', '/maintenance', 'Maintenance'],
  ['Inventaris', '/inventory', 'Inventaris'],
  ['Komplain', '/complaints', 'Komplain'],
  ['Fasilitas', '/facilities', 'Fasilitas'],
  ['Housekeeping', '/housekeeping', 'Housekeeping'],
  ['Audit Bulanan', '/audits', 'Audit Bulanan'],
  ['Keuangan', '/finance', 'Keuangan'],
  ['Laporan', '/reports', 'Laporan'],
  ['WhatsApp', '/notifications', 'WhatsApp'],
  ['Template Dokumen', '/templates', 'Template Dokumen'],
  ['Admin & Role', '/users', 'Admin'],
] as const;

async function login(app: { open(path?: string): Promise<void> }, screen: any, browser: any) {
  await app.open(`${masterPrefix}/login`);
  await pause();
  await expect(screen.getByRole('heading', 'Masuk Dashboard')).toBeVisible();
  await screen.getByLabel('Username').fill(username);
  await pause();
  await screen.getByLabel('Password').fill(password);
  await pause();
  await screen.getByRole('button', 'Login').click();
  await expect(browser).toHaveURL(/\/master(?:\/index\.php)?\/dashboard(?:$|\?)/, { timeout: 10000 });
  await expect(screen.getByText('Dashboard Operasional GulHouse')).toBeVisible();
  await pause();
}

async function expectNoHorizontalOverflow(browser: any, label: string) {
  const result = await browser.evaluate(() => {
    const doc = document.documentElement;
    const offenders = Array.from(document.querySelectorAll('body *'))
      .map((node) => {
        const rect = node.getBoundingClientRect();
        return {
          tag: node.tagName.toLowerCase(),
          className: String((node as HTMLElement).className || ''),
          text: String((node.textContent || '').trim()).slice(0, 60),
          left: Math.floor(rect.left),
          right: Math.ceil(rect.right),
        };
      })
      .filter((node) => node.right > window.innerWidth + 2 || node.left < -2)
      .slice(0, 8);

    return {
      clientWidth: doc.clientWidth,
      scrollWidth: doc.scrollWidth,
      viewportWidth: window.innerWidth,
      offenders,
    };
  });

  expect(
    result.scrollWidth,
    `${label} horizontal overflow: ${JSON.stringify(result.offenders)}`,
  ).toBeLessThanOrEqual(result.clientWidth + 2);
  await pause();
}

async function pause() {
  if (slowMs > 0) {
    await new Promise((resolve) => setTimeout(resolve, slowMs));
  }
}

test('master login opens dashboard', async ({ app, screen, browser }) => {
  await login(app, screen, browser);
  await expectNoHorizontalOverflow(browser, 'Dashboard');
});

test('master pages open and keep horizontal layout inside viewport', async ({ app, screen, browser }) => {
  await login(app, screen, browser);

  for (const [label, path, visibleText] of pages) {
    await app.open(`${masterPrefix}${path}`);
    await pause();
    await expect(browser.locator('body')).toBeVisible({ timeout: 10000 });
    const bodyText = await browser.locator('body').textContent();
    expect(bodyText ?? '', `${label} content`).toContain(visibleText);
    await expectNoHorizontalOverflow(browser, label);
  }
});
