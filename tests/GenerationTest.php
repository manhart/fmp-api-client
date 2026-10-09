<?php
declare(strict_types=1);

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
                'format' => 'json',
                'fields' => ['symbol'],
                'parameters' => [
                    'symbol' => ['phpType' => 'string', 'required' => true],
                    'active' => ['phpType' => 'bool', 'required' => false],
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
            $this->directory.'/src/Response/Stable/StableEndpointRegistry.php',
            $this->directory.'/src/Client/StableClientMethods.php',
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
        $this->assertStringContainsString('return Stable\\Profile::fetch($this,', $trait);
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
        $this->assertFileDoesNotExist($this->directory.'/src/Client/StableClientMethods.php');
    }
}
