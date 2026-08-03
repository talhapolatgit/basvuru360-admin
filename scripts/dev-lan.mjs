import os from 'node:os';
import { spawn } from 'node:child_process';

function detectLanIp() {
    const preferred = process.env.VITE_DEV_SERVER_HOST?.trim();
    if (preferred) {
        return preferred;
    }

    const nets = os.networkInterfaces();
    for (const entries of Object.values(nets)) {
        for (const net of entries ?? []) {
            const family = typeof net.family === 'string' ? net.family : String(net.family);
            if (family === 'IPv4' && !net.internal) {
                return net.address;
            }
        }
    }

    return '127.0.0.1';
}

const ip = detectLanIp();
const appPort = process.env.APP_PORT || '8000';
const vitePort = process.env.VITE_DEV_SERVER_PORT || '5173';
const appUrl = `http://${ip}:${appPort}`;

const env = {
    ...process.env,
    APP_URL: appUrl,
    VITE_DEV_SERVER_HOST: ip,
    VITE_DEV_SERVER_PORT: vitePort,
};

console.log('');
console.log('  LAN geliştirme sunucusu');
console.log(`  Uygulama : ${appUrl}`);
console.log(`  Vite HMR : http://${ip}:${vitePort}`);
console.log('  Diğer cihazdan yukarıdaki uygulama adresini açın.');
console.log('');

const quote = (cmd) => (process.platform === 'win32' ? `"${cmd}"` : cmd);

// Pail requires pcntl (Unix-only); skip it on Windows so --kill-others does not tear down the stack.
const commands =
    process.platform === 'win32'
        ? [
              quote(`php artisan serve --host=0.0.0.0 --port=${appPort}`),
              quote('php artisan queue:listen --tries=1 --timeout=0'),
              quote('npm run dev'),
          ]
        : [
              quote(`php artisan serve --host=0.0.0.0 --port=${appPort}`),
              quote('php artisan queue:listen --tries=1 --timeout=0'),
              quote('php artisan pail --timeout=0'),
              quote('npm run dev'),
          ];

const names = process.platform === 'win32' ? 'server,queue,vite' : 'server,queue,logs,vite';
const colors =
    process.platform === 'win32'
        ? '#93c5fd,#c4b5fd,#fdba74'
        : '#93c5fd,#c4b5fd,#fb7185,#fdba74';

const child = spawn(
    'npx',
    ['concurrently', '-c', colors, '--names', names, '--kill-others', ...commands],
    {
        env,
        stdio: 'inherit',
        shell: true,
    },
);

child.on('exit', (code, signal) => {
    if (signal) {
        process.kill(process.pid, signal);
        return;
    }
    process.exit(code ?? 0);
});