import type { E2EConfig } from 'e2e';
import { web } from '@e2e-dev/web';

const app = {
  url: process.env.APP_URL ?? 'http://127.0.0.1:0',
  readyUrl: process.env.APP_READY_URL,
  identity: 'gulhouse-master-local',
  command: process.env.APP_URL
    ? undefined
    : {
        executable: process.env.PHP_BIN ?? 'D:\\XAMPP\\php\\php.exe',
        args: ['-S', '127.0.0.1:{port}', '-t', '.', 'scripts/dev-router.php'],
        startupTimeout: 60000,
        log: `${process.env.GULHOUSE_E2E_OUTPUT ?? '.e2e'}/logs/gulhouse-master.log`,
        env: {
          CI_ENV: 'development',
          GULHOUSE_DATABASE: process.env.GULHOUSE_DATABASE ?? '',
          ...(process.env.GH_DB_HOSTNAME ? { GH_DB_HOSTNAME: process.env.GH_DB_HOSTNAME } : {}),
          ...(process.env.GH_DB_USERNAME ? { GH_DB_USERNAME: process.env.GH_DB_USERNAME } : {}),
          ...(process.env.GH_DB_PASSWORD !== undefined ? { GH_DB_PASSWORD: process.env.GH_DB_PASSWORD } : {}),
        },
      },
};

export default {
  targets: [
    { name: 'desktop-1366', engine: web({ viewport: { width: 1366, height: 768 } }), app },
    { name: 'wide-1920', engine: web({ viewport: { width: 1920, height: 900 } }), app },
    { name: 'mobile-390', engine: web({ viewport: { width: 390, height: 844 } }), app },
  ],
  workers: 1,
  assertionTimeout: 15000,
} satisfies E2EConfig;
