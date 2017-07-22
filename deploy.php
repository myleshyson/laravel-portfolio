<?php
namespace Deployer;

require 'recipe/laravel.php';
// require 'recipe/local.php';
require 'recipe/rsync.php';
require 'recipe/npm.php';

// Configuration

set('repository', 'git@github.com:myleshyson/laravel-portfolio.git');
set('ssh_type', 'native');
set('ssh_multiplexing', true);
set('git_tty', true); // [Optional] Allocate tty for git on first deployment
set('writable_mode', 'chmod');
set('default_stage', 'production');
set('local_deploy_path', '/tmp/deployer');

// // RSYNC files from /tmp/deployer
// set('rsync_src', function() {
//     $local_src = get('local_release_path');
//     if(is_callable($local_src)){
//         $local_src = $local_src();
//     }
//     return $local_src;
// });

add('shared_files', [
    '.env'
]);
add('shared_dirs', []);
add('writable_dirs', []);
add('rsync', [
    'exclude' => [
        '.git',
        'deploy.php',
        'node_modules',
    ],
]);


// Hosts

host('45.79.192.219')
    ->stage('production')
    ->user('forge')
    ->identityFile('~/.ssh/id_rsa')
    ->set('deploy_path', '/home/forge/docproject.myleshyson.com');

// Tasks
desc('Restart PHP-FPM service');
task('php-fpm:restart', function () {
    // The user must have rights for restart service
    // /etc/sudoers: username ALL=NOPASSWD:/bin/systemctl restart php-fpm.service
    run('sudo systemctl restart php-fpm.service');
});

task('test', function() {
    runLocally("echo {{local_release_path}}");
});

// Build assets locally
task('npm:local:build', function () {
  runLocally("cd {{local_release_path}} && {{local/bin/npm}} run production");
});

task('environment', function () {
    upload(__DIR__.'/.env.production', '{{release_path}}/.env');
});

// // Run tests
// task('local:phpunit', function () {
//   runLocally("cd {{local_release_path}} && phpunit");
// });

// Tasks
task('deploy', [
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'deploy:clear_paths',
    'artisan:view:clear',   // Optimze on server
    'artisan:cache:clear',  // Optimze on server
    'artisan:config:cache', // Optimze on server
    'artisan:optimize',     // Optimze on server
    'artisan:migrate',      // Migrate DB on server
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
])->desc('Deploy your project');


after('deploy:symlink', 'php-fpm:restart');

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

before('deploy:symlink', 'artisan:migrate');
