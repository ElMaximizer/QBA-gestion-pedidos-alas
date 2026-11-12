const { spawn } = require('node:child_process');
const { mkdirSync } = require('node:fs');
const path = require('node:path');

const raiz = path.resolve(__dirname, '../..');
const sesiones = path.join(__dirname, '.php-sessions');
mkdirSync(sesiones, { recursive: true });

const servidor = spawn(process.env.QBA_PHP_BINARY || 'php', [
    '-d', `session.save_path=${sesiones}`,
    '-S', '127.0.0.1:8787',
    '-t', 'public',
    'public/router.php',
], {
    cwd: raiz,
    windowsHide: true,
    stdio: 'inherit',
    env: {
        ...process.env,
        // Esta suite solo abre páginas anónimas sin base de datos.
        QBA_DB_HOST: '127.0.0.1',
        QBA_DB_PORT: '1',
        QBA_DB_NAME: 'qba_e2e_sin_base',
        QBA_DB_USER: 'qba_e2e_sin_usuario',
        QBA_DB_PASSWORD: '',
        QBA_MAIL_TRANSPORT: 'local',
    },
});

servidor.on('error', (error) => {
    console.error(`No se pudo iniciar PHP. Configurá QBA_PHP_BINARY: ${error.message}`);
    process.exitCode = 1;
});
servidor.on('exit', (codigo) => { process.exitCode = codigo ?? 1; });
for (const senal of ['SIGINT', 'SIGTERM']) {
    process.on(senal, () => { servidor.kill(); });
}
process.on('exit', () => { servidor.kill(); });
