<?php

/**
 * Lädt den Autoloader des Bundles (eigenes vendor/) oder – in der Entwicklungsumgebung unter
 * _packages/ – den des Projekts.
 */
$dir = __DIR__;

for ($i = 0; $i < 6; ++$i) {
    $dir = \dirname($dir);

    if (is_file($dir . '/vendor/autoload.php')) {
        $loader = require $dir . '/vendor/autoload.php';
        $loader->addPsr4('Alnv\\ContaoCatalogManagerBundle\\', \dirname(__DIR__) . '/src/', true);
        $loader->addPsr4('Alnv\\ContaoCatalogManagerBundle\\Tests\\', __DIR__ . '/');

        return;
    }
}

throw new RuntimeException('vendor/autoload.php not found');
