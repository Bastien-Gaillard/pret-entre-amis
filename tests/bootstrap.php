<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// Recrée la base de test (prets_test) avec les migrations avant chaque lancement.
$console = sprintf('%s %s/bin/console --env=test --no-interaction --quiet ', PHP_BINARY, dirname(__DIR__));
foreach (['doctrine:database:drop --force --if-exists', 'doctrine:database:create', 'doctrine:migrations:migrate'] as $command) {
    passthru($console . $command, $code);
    if ($code !== 0) {
        exit("Impossible de préparer la base de test ($command).\n");
    }
}
