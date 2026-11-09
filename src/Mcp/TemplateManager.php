<?php

namespace Alnv\ContaoCatalogManagerBundle\Mcp;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Liest, schreibt und entfernt Catalog-Manager-Templates im Ordner /templates.
 *
 * Erlaubt sind nur .html5-Templates mit den Präfixen des Catalog-Managers.
 * Vor dem Speichern wird die PHP-Syntax geprüft, überschriebene oder
 * gelöschte Dateien landen als Sicherung in var/catalog-mcp/templates.
 */
final class TemplateManager
{
    /** Präfix => wofür das Template verwendet wird */
    public const PREFIXES = [
        'cm_listing_' => 'Eintrag in der Listenansicht (Modul "listing-table" / Element "listview", Feld cmTemplate)',
        'cm_master_' => 'Detailansicht eines Eintrags (Modul "master", Feld cmTemplate)',
        'cm_map_view_' => 'Kartenansicht (Modul "listing-map", Feld cmTemplate)',
        'mod_listing' => 'Hülle des Listen-Moduls (customTpl, z. B. mod_listing_table_*, mod_listing_map_*)',
        'mod_master' => 'Hülle des Detail-Moduls (customTpl)',
        'ce_listview' => 'Hülle des Inhaltselements "listview" (customTpl)',
        'ce_watchlist' => 'Merkliste (Formular/Tabelle)',
    ];

    private readonly Filesystem $fs;

    public function __construct(
        private readonly string $projectDir,
        private readonly string $phpBinary = \PHP_BINARY,
    ) {
        $this->fs = new Filesystem();
    }

    public function list(): array
    {
        $custom = [];
        $templatesDir = $this->templatesDir();

        if (is_dir($templatesDir)) {
            $finder = (new Finder())->files()->in($templatesDir)->depth('< 3')->name('*.html5')->sortByName();

            foreach ($finder as $file) {
                $name = $file->getBasename('.html5');
                if (null === $this->prefixOf($name)) {
                    continue;
                }
                $custom[] = [
                    'name' => $name,
                    'folder' => str_replace('\\', '/', $file->getRelativePath()),
                    'path' => 'templates/' . str_replace('\\', '/', $file->getRelativePathname()),
                    'modified' => date('c', $file->getMTime()),
                ];
            }
        }

        $defaults = [];
        $bundleDir = $this->bundleTemplatesDir();

        if (null !== $bundleDir) {
            $finder = (new Finder())->files()->in($bundleDir)->name('*.html5')->sortByName();
            foreach ($finder as $file) {
                $name = $file->getBasename('.html5');
                if (null !== $this->prefixOf($name)) {
                    $defaults[] = $name;
                }
            }
        }

        return [
            'customTemplates' => $custom,
            'bundleDefaults' => $defaults,
            'prefixes' => self::PREFIXES,
        ];
    }

    public function read(string $name, string $folder = ''): array
    {
        $name = $this->assertName($name);
        $folder = $this->assertFolder($folder);

        $custom = $this->customPath($name, $folder);
        if (is_file($custom)) {
            return ['name' => $name, 'source' => 'templates/' . ltrim($folder . '/' . $name . '.html5', '/'), 'content' => file_get_contents($custom)];
        }

        $bundleDir = $this->bundleTemplatesDir();
        if (null !== $bundleDir) {
            $finder = (new Finder())->files()->in($bundleDir)->name($name . '.html5');
            foreach ($finder as $file) {
                return ['name' => $name, 'source' => 'catalog-manager (Standard)', 'content' => $file->getContents()];
            }
        }

        throw new \InvalidArgumentException(sprintf('Template "%s" nicht gefunden.', $name));
    }

    public function write(string $name, string $content, string $folder = '', bool $overwrite = false): array
    {
        $name = $this->assertName($name);
        $folder = $this->assertFolder($folder);
        $path = $this->customPath($name, $folder);

        if (is_file($path) && !$overwrite) {
            throw new \RuntimeException(sprintf('Template "%s" existiert bereits. Zum Ersetzen overwrite=true setzen.', $name));
        }

        if (\strlen($content) > 200_000) {
            throw new \InvalidArgumentException('Template ist zu groß (max. 200 KB).');
        }

        $this->lint($content);

        $backup = null;
        if (is_file($path)) {
            $backup = $this->backup($path, $name);
        }

        $this->fs->dumpFile($path, $content);

        return [
            'written' => true,
            'path' => substr($path, \strlen($this->projectDir) + 1),
            'usage' => self::PREFIXES[$this->prefixOf($name)],
            'backupOfPrevious' => $backup,
            'hint' => 'Falls das Template im Backend nicht auswählbar ist: "vendor/bin/contao-console cache:clear --env=prod" ausführen. Templates in Unterordnern müssen dem Theme als Template-Ordner zugewiesen sein.',
        ];
    }

    public function delete(string $name, string $folder = ''): array
    {
        $name = $this->assertName($name);
        $folder = $this->assertFolder($folder);
        $path = $this->customPath($name, $folder);

        if (!is_file($path)) {
            throw new \InvalidArgumentException(sprintf('Eigenes Template "%s" nicht gefunden (Standard-Templates des Bundles werden nie gelöscht).', $name));
        }

        $backup = $this->backup($path, $name);
        $this->fs->remove($path);

        return ['deleted' => true, 'backup' => $backup];
    }

    public function prefixOf(string $name): ?string
    {
        foreach (array_keys(self::PREFIXES) as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $prefix;
            }
        }

        return null;
    }

    public function assertName(string $name): string
    {
        $name = preg_replace('/\.html5$/', '', trim($name)) ?? '';

        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new \InvalidArgumentException('Template-Namen dürfen nur a-z, 0-9 und _ enthalten (ohne Endung).');
        }

        if (null === $this->prefixOf($name)) {
            throw new \InvalidArgumentException('Erlaubte Präfixe: ' . implode(', ', array_keys(self::PREFIXES)));
        }

        return $name;
    }

    public function assertFolder(string $folder): string
    {
        $folder = trim(str_replace('\\', '/', $folder), '/');

        if ('' !== $folder && !preg_match('/^[a-zA-Z0-9_-]+(\/[a-zA-Z0-9_-]+)?$/', $folder)) {
            throw new \InvalidArgumentException('Ungültiger Unterordner. Erlaubt: "meintheme" oder "meintheme/catalog".');
        }

        return $folder;
    }

    private function lint(string $content): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'cmtpl');
        file_put_contents($tmp, $content);

        try {
            $process = new Process([$this->phpBinary, '-l', $tmp]);
            $process->setTimeout(20);
            $process->run();

            if (!$process->isSuccessful()) {
                $message = trim($process->getOutput() . ' ' . $process->getErrorOutput());
                $message = str_replace($tmp, 'template', $message);

                throw new \InvalidArgumentException('PHP-Syntaxfehler im Template: ' . $message);
            }
        } finally {
            @unlink($tmp);
        }
    }

    private function backup(string $path, string $name): string
    {
        $dir = $this->projectDir . '/var/catalog-mcp/templates';
        $target = sprintf('%s/%s-%s.html5', $dir, $name, date('Ymd-His'));
        $this->fs->copy($path, $target, true);

        return substr($target, \strlen($this->projectDir) + 1);
    }

    private function customPath(string $name, string $folder): string
    {
        return $this->templatesDir() . ('' !== $folder ? '/' . $folder : '') . '/' . $name . '.html5';
    }

    private function templatesDir(): string
    {
        return $this->projectDir . '/templates';
    }

    private function bundleTemplatesDir(): ?string
    {
        foreach (['catalog-manager-bundle', 'contao-catalog-manager-bundle'] as $package) {
            $dir = $this->projectDir . '/vendor/alnv/' . $package . '/src/Resources/contao/templates';
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return null;
    }
}
