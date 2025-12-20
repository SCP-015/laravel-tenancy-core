<?php

namespace Deployer;

require_once 'recipe/laravel.php';

// Config

set('repository', 'git@bitbucket.org:nusanet/nusahire.git');
set('keep_releases', 5);

add('shared_files', []);
add('shared_dirs', []);
add('writable_dirs', []);

// Hosts

host('prod')
    ->set('branch', 'master')
    ->set('hostname', 'nusahire') // nama di .ssh/config
    ->set('remote_user', 'ade')
    //->set('deploy_path', '~/nusahire');
    ->set('deploy_path', '/home/{{remote_user}}/nusahire');
host('dev')
    ->set('branch', 'develop')
    ->set('hostname', 'lab-meet') // nama di .ssh/config
    ->set('remote_user', 'ade')
    //->set('deploy_path', '~/nusahire');
    ->set('deploy_path', '/home/{{remote_user}}/nusahire');
//task

task('deploy:env-sync', function () {
    // backup file .env
    run('cp {{deploy_path}}/shared/.env {{deploy_path}}/shared/.env.bak');
    // Cek apakah file .env.example ada
    $envExamplePath = '{{release_path}}/.env.example';
    $envPath = '{{deploy_path}}/shared/.env';

    // Periksa keberadaan file .env.example di server
    if ('exists' === run("[ -f $envExamplePath ] && echo 'exists' || echo 'not exists'")) {
        // Ambil konten dari .env.example
        $envExampleContent = run("cat $envExamplePath");

        // Loop melalui setiap baris .env.example
        $lines = explode("\n", $envExampleContent);
        foreach ($lines as $line) {
            // Cek jika baris bukan komentar dan tidak kosong
            if (! empty($line) && 0 !== strpos($line, '#')) {
                // Ambil nama variabel dari baris
                $varName = explode('=', $line)[0];

                // Cek jika variabel belum ada di .env
                $envContent = run("cat $envPath");
                if (false === strpos($envContent, "$varName=")) {
                    // Jika variabel belum ada, tambahkan ke dalam .env
                    run("echo '$line' >> $envPath");
                    writeln("Added new property: $line");
                }
            }
        }
    } else {
        writeln('.env.example not found, skipping sync.');
    }
});

task('npm:build', function () {
    run('export NVM_DIR="$HOME/.nvm" && [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" && cd {{release_path}} && npm install && npm run build');
});

task('php-fpm:restart', function () {
    $phpVersion = run('php -r "echo PHP_MAJOR_VERSION.\'.\'.PHP_MINOR_VERSION;"');
    $phpFpmService = "php{$phpVersion}-fpm";

    writeln(">>> Restarting PHP-FPM service: {$phpFpmService}");
    run("sudo systemctl restart {$phpFpmService}");
});

task('deploy:generate-cron-config', function () {
    // config in bash

    //run('echo "* * * * * cd {{deploy_path}}/current && php artisan schedule:run >> /dev/null 2>&1" | sudo tee -a /etc/crontab');
    run('echo "* * * * * www-data cd {{deploy_path}}/current && php artisan schedule:run >> /dev/null 2>&1" | sudo tee -a /tmp/cron.generate');
    // pindahkan ke /etc/crod.d/nusahire
    run('sudo mv /tmp/cron.generate /etc/cron.d/nusahire');
    run('sudo chown root:root /etc/cron.d/nusahire');
    run('sudo chmod 644 /etc/cron.d/nusahire');
    //run('sudo crontab -u {{remote_user}} /tmp/cron.generate');
});

task('artisan:tenant-migrate', function () {
    run('cd {{release_path}} && php artisan tenants:migrate --force');
});

desc('Restart Supervisor queue workers');
task('supervisor:restart', function () {
    run('sudo supervisorctl reread');
    run('sudo supervisorctl update');
    run('sudo supervisorctl restart nusahire-worker:*');
});
// Hooks
after('deploy:vendors', 'npm:build');
before('artisan:migrate', 'deploy:env-sync');
after('artisan:migrate', 'artisan:tenant-migrate');
after('deploy:cleanup', 'php-fpm:restart');
after('deploy:failed', 'deploy:unlock');
after('deploy:cleanup', 'deploy:generate-cron-config');
after('deploy:success', 'supervisor:restart');
