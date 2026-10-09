<?php
declare(strict_types=1);

namespace Manhart\Fmp\Tests;

use PHPUnit\Framework\TestCase;

final class DocumentationImportTest extends TestCase
{
    public function testImportPreservesValidCaptureOverridesAndAddsRequiredDefaults(): void
    {
        $directory = sys_get_temp_dir().'/fmp-import-test-'.bin2hex(random_bytes(8));
        mkdir($directory.'/tools', 0700, true);
        mkdir($directory.'/resources', 0700);
        $manifest = ['endpoints' => [
            'industry-classification-search' => [
                'class' => 'IndustryClassificationSearch',
                'capture' => ['symbol' => 'AAPL', 'sicCode' => '7371', 'removedParameter' => 'obsolete'],
            ],
        ]];
        $markdown = <<<'MARKDOWN'
### Classification by symbol

**Endpoint:** `https://financialmodelingprep.com/stable/industry-classification-search`

| Query Parameter | Type | Example |
| --- | --- | --- |
| symbol | string | MSFT |
| year* | number | 2026 |

**Example Response**
```json
[{"symbol":"SYNTHETIC"}]
```

### Classification by SIC

**Endpoint:** `https://financialmodelingprep.com/stable/industry-classification-search`

| Query Parameter | Type | Example |
| --- | --- | --- |
| sicCode | string | 1234 |

MARKDOWN;
        try {
            copy(__DIR__.'/../tools/import-docs.php', $directory.'/tools/import-docs.php');
            file_put_contents($directory.'/resources/endpoints.json', json_encode($manifest, JSON_THROW_ON_ERROR));
            file_put_contents($directory.'/reference.md', $markdown);
            $process = proc_open(
                [PHP_BINARY, $directory.'/tools/import-docs.php', $directory.'/reference.md'],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $output.$error);
            $result = json_decode(file_get_contents($directory.'/resources/endpoints.json'), true, 512, JSON_THROW_ON_ERROR);
            $capture = $result['endpoints']['industry-classification-search']['capture'];
            $this->assertSame(['year' => 2026, 'symbol' => 'AAPL', 'sicCode' => '7371'], $capture);
        } finally {
            foreach ([$directory.'/tools/import-docs.php', $directory.'/resources/endpoints.json', $directory.'/reference.md'] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($directory.'/tools');
            rmdir($directory.'/resources');
            rmdir($directory);
        }
    }
}
