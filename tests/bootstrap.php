<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

$sassOutputDirectory = dirname(__DIR__).'/var/sass';
$sassOutputPattern = $sassOutputDirectory.'/app-*.output.css';

if (!glob($sassOutputPattern)) {
    $process = proc_open(
        PHP_BINARY.' '.escapeshellarg(dirname(__DIR__).'/bin/console').' sass:build --env=test --no-interaction',
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname(__DIR__)
    );

    if (!is_resource($process) || 0 !== proc_close($process)) {
        throw new RuntimeException('Unable to build Sass assets for the test suite.');
    }
}
