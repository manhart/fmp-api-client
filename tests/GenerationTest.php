<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GenerationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/fmp-generation-test-'.bin2hex(random_bytes(8));
        foreach (['tools', 'resources', 'src/Response/Stable', 'src/Client'] as $path) {
            mkdir($this->directory.'/'.$path, 0700, true);
        }
        copy(__DIR__.'/../tools/generate.php', $this->directory.'/tools/generate.php');
        $manifest = ['endpoints' => [
            'profile' => [
                'class' => 'Profile',
                'description' => 'Company identity and listing details.',
                'format' => 'json',
                'fields' => ['symbol'],
                'parameters' => [
                    'symbol' => ['phpType' => 'string', 'required' => true],
                    'active' => ['phpType' => 'bool', 'required' => false, 'description' => 'Restrict fixture records to active listings.'],
                ],
            ],
            'stock-list' => ['class' => 'StockList', 'format' => 'json', 'fields' => [], 'parameters' => []],
        ]];
        file_put_contents($this->directory.'/resources/endpoints.json', json_encode($manifest, JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($this->directory);
    }

    private function runPhp(array $arguments): array
    {
        $process = proc_open(
            [PHP_BINARY, ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $output];
    }

    public function testGenerationIsValidAndReproducible(): void
    {
        file_put_contents($this->directory.'/resources/client-shortcuts.json', '{"getProfile":"profile","getStockList":"stock-list"}');
        [$status, $output] = $this->runPhp([$this->directory.'/tools/generate.php']);
        $this->assertSame(0, $status, $output);
        $files = [
            $this->directory.'/src/Response/Stable/Profile.php',
            $this->directory.'/src/Response/Stable/StockList.php',
            $this->directory.'/src/Response/Stable/EndpointRegistry.php',
            $this->directory.'/src/Client/ClientMethods.php',
        ];
        $hashes = [];
        foreach ($files as $file) {
            $hashes[$file] = hash_file('sha256', $file);
            [$status, $output] = $this->runPhp(['-l', $file]);
            $this->assertSame(0, $status, $output);
        }
        [$status, $output] = $this->runPhp([$this->directory.'/tools/generate.php']);
        $this->assertSame(0, $status, $output);
        foreach ($hashes as $file => $expected) {
            $this->assertSame($expected, hash_file('sha256', $file));
        }
        $trait = file_get_contents($files[3]);
        $this->assertStringContainsString('bool|null $active = null', $trait);
        $this->assertStringContainsString('public function getStockList(): Stable\\StockList', $trait);
        $this->assertStringContainsString("): Stable\\Profile {\n", $trait);
        $this->assertStringContainsString("return Stable\\Profile::fetch(\n            \$this,\n", $trait);
        $this->assertStringContainsString("public function getStockList(): Stable\\StockList\n    {\n        return Stable\\StockList::fetch(\$this);", $trait);
        $profile = file_get_contents($files[0]);
        foreach ([$profile, $trait] as $code) {
            $this->assertStringContainsString('Company identity and listing details.', $code);
            $this->assertStringContainsString('@param bool|null $active Restrict fixture records to active listings.', $code);
            $this->assertStringNotContainsString('@return', $code);
            $this->assertStringNotContainsString('@param string $symbol', $code);
        }
        $registry = file_get_contents($files[2]);
        $this->assertStringContainsString('@return class-string<\\Manhart\\Fmp\\Response\\Response|\\Manhart\\Fmp\\Response\\FileResponse>', $registry);
        $this->assertStringContainsString('@var array<string, class-string<', $registry);
    }

    public function testImportsFollowEndpointAndSelectedShortcutParameterTypes(): void
    {
        $manifest = json_decode(file_get_contents($this->directory.'/resources/endpoints.json'), true, 512, JSON_THROW_ON_ERROR);
        $manifest['endpoints']['statements'] = [
            'class' => 'Statements',
            'format' => 'json',
            'fields' => [],
            'parameters' => [
                'period' => ['phpType' => 'string|Period|ReportPeriod', 'required' => false],
                'from' => ['phpType' => 'string|DateTimeInterface', 'required' => false],
                'to' => ['phpType' => 'string|DateTimeInterface', 'required' => false],
            ],
        ];
        $manifest['endpoints']['indicator'] = [
            'class' => 'Indicator',
            'format' => 'json',
            'fields' => [],
            'parameters' => [
                'timeframe' => ['phpType' => 'string|Timeframe', 'required' => true],
                'sort' => ['phpType' => 'string|SortDirection', 'required' => false],
            ],
        ];
        $manifest['endpoints']['workbook'] = [
            'class' => 'Workbook',
            'format' => 'xlsx',
            'fields' => [],
            'parameters' => [],
        ];
        file_put_contents($this->directory.'/resources/endpoints.json', json_encode($manifest, JSON_THROW_ON_ERROR));
        file_put_contents($this->directory.'/resources/client-shortcuts.json', '{"getStatements":"statements"}');

        [$status, $output] = $this->runPhp([$this->directory.'/tools/generate.php']);
        $this->assertSame(0, $status, $output);
        foreach (['Profile', 'StockList'] as $class) {
            $code = file_get_contents($this->directory."/src/Response/Stable/$class.php");
            $this->assertSame([
                'Manhart\\Fmp\\Client\\FmpApiClient',
                'Manhart\\Fmp\\Response\\Response',
            ], $this->imports($code));
        }
        $statements = file_get_contents($this->directory.'/src/Response/Stable/Statements.php');
        $this->assertEqualsCanonicalizing([
            'Manhart\\Fmp\\Client\\FmpApiClient',
            'Manhart\\Fmp\\Response\\Response',
            'DateTimeInterface',
            'Manhart\\Fmp\\Enum\\Period',
            'Manhart\\Fmp\\Enum\\ReportPeriod',
        ], $this->imports($statements));
        $indicator = file_get_contents($this->directory.'/src/Response/Stable/Indicator.php');
        $this->assertEqualsCanonicalizing([
            'Manhart\\Fmp\\Client\\FmpApiClient',
            'Manhart\\Fmp\\Response\\Response',
            'Manhart\\Fmp\\Enum\\SortDirection',
            'Manhart\\Fmp\\Enum\\Timeframe',
        ], $this->imports($indicator));
        $workbook = file_get_contents($this->directory.'/src/Response/Stable/Workbook.php');
        $this->assertStringContainsString('@throws \\JsonException', $workbook);
        $this->assertStringContainsString('@throws \\JsonException', $statements);
        $this->assertSame([
            'Manhart\\Fmp\\Client\\FmpApiClient',
            'Manhart\\Fmp\\Response\\FileResponse',
        ], $this->imports($workbook));
        $trait = file_get_contents($this->directory.'/src/Client/ClientMethods.php');
        $this->assertStringContainsString('@throws \\JsonException', $trait);
        $this->assertEqualsCanonicalizing([
            'Manhart\\Fmp\\Response\\Stable',
            'DateTimeInterface',
            'Manhart\\Fmp\\Enum\\Period',
            'Manhart\\Fmp\\Enum\\ReportPeriod',
        ], $this->imports($trait));
        foreach (['Statements', 'Indicator', 'Workbook'] as $class) {
            [$status, $output] = $this->runPhp(['-l', $this->directory."/src/Response/Stable/$class.php"]);
            $this->assertSame(0, $status, $output);
        }
    }

    private function imports(string $code): array
    {
        preg_match_all('/^use ([^;]+);$/m', $code, $matches);
        return $matches[1];
    }

    public static function invalidShortcuts(): iterable
    {
        yield 'missing endpoint' => [['getMissing' => 'removed-endpoint'], 'Unknown Stable endpoint'];
        yield 'case-insensitive method collision' => [['getProfile' => 'profile', 'getprofile' => 'stock-list'], 'conflicting shortcut method'];
        yield 'transport method collision' => [['request' => 'profile'], 'conflicting shortcut method'];
        yield 'invalid PHP method name' => [['get-profile' => 'profile'], 'conflicting shortcut method'];
    }

    #[DataProvider('invalidShortcuts')]
    public function testInvalidMappingsFailBeforeWritingGeneratedFiles(array $shortcuts, string $expectedError): void
    {
        file_put_contents($this->directory.'/resources/client-shortcuts.json', json_encode($shortcuts, JSON_THROW_ON_ERROR));
        [$status, $output] = $this->runPhp([$this->directory.'/tools/generate.php']);
        $this->assertNotSame(0, $status);
        $this->assertStringContainsString($expectedError, $output);
        $this->assertSame([], glob($this->directory.'/src/Response/Stable/*.php'));
        $this->assertFileDoesNotExist($this->directory.'/src/Client/ClientMethods.php');
    }
}
