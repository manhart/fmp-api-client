<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\HttpResponse;
use Manhart\Fmp\Recording\CaptureCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CaptureCacheTest extends TestCase
{
    private string $directory;
    private string $key;
    private CaptureCache $cache;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/fmp-cache-test-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        $this->cache = new CaptureCache($this->directory.'/cache');
        $this->key = CaptureCache::key('https://example.test/stable/profile?symbol=DEMO');
    }

    protected function tearDown(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($this->directory);
    }

    private function saveCapture(): void
    {
        $this->cache->begin($this->key, 'profile', 'https://example.test/stable/profile?symbol=DEMO');
        $this->cache->save($this->key, new HttpResponse('[]', 200, 'application/json', 2), 'empty');
    }

    public static function unsafePermissions(): iterable
    {
        yield 'world writable' => [0777];
        yield 'group access' => [0750];
        yield 'other access' => [0701];
    }

    #[DataProvider('unsafePermissions')]
    public function testUnsafeExistingPermissionsAreRejectedWithoutChangingThem(int $mode): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Unix directory modes are not Windows ACLs.');
        }
        chmod($this->directory.'/cache', $mode);
        try {
            new CaptureCache($this->directory.'/cache');
            $this->fail('Unsafe existing directories must be rejected.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('private (0700)', $error->getMessage());
            $this->assertSame($mode, fileperms($this->directory.'/cache') & 0777);
        }
    }

    public function testCacheDirectorySymlinkIsRejectedWithOrWithoutTrailingSlash(): void
    {
        symlink($this->directory.'/cache', $this->directory.'/linked');
        foreach (['', DIRECTORY_SEPARATOR] as $suffix) {
            try {
                new CaptureCache($this->directory.'/linked'.$suffix);
                $this->fail('A symlink at the cache directory must be rejected.');
            } catch (\RuntimeException $error) {
                $this->assertStringContainsString('not a symlink', $error->getMessage());
            }
        }
    }

    public function testSymlinkInAnAncestorPathRemainsUsable(): void
    {
        symlink($this->directory, $this->directory.'/ancestor');
        $cache = new CaptureCache($this->directory.'/ancestor/cache');
        $cache->begin($this->key, 'profile', 'https://example.test/stable/profile');
        $this->assertTrue($cache->exists($this->key));
    }

    public static function symlinkOperations(): iterable
    {
        foreach ([
            'metadata existence' => ['.json', 'exists'],
            'metadata read' => ['.json', 'metadata'],
            'metadata write' => ['.json', 'begin'],
            'body read' => ['.body', 'response'],
            'body write' => ['.body', 'save'],
            'lock' => ['.lock', 'acquireLock'],
            'report' => ['report.json', 'writeReport'],
        ] as $name => [$filename, $operation]) {
            yield $name => [$filename, $operation, false];
            yield $name.' dangling' => [$filename, $operation, true];
        }
    }

    #[DataProvider('symlinkOperations')]
    public function testFileSymlinksAreRejectedWithoutTouchingTheirTargets(string $filename, string $operation, bool $dangling): void
    {
        $this->saveCapture();
        $target = $this->directory.'/outside.txt';
        if (!$dangling) {
            file_put_contents($target, 'unchanged');
        }
        if ($filename === '.json' || $filename === '.body') {
            $filename = $this->key.$filename;
        }
        $path = $this->directory.'/cache/'.$filename;
        if (is_file($path)) {
            unlink($path);
        }
        symlink($target, $path);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not symlinks or special files');
        try {
            match ($operation) {
                'begin' => $this->cache->begin($this->key, 'profile', 'https://example.test/stable/profile'),
                'save' => $this->cache->save($this->key, new HttpResponse('new body', 200, 'text/csv', 8), 'recorded'),
                'writeReport' => $this->cache->writeReport(['cases' => []]),
                'acquireLock' => $this->cache->acquireLock(),
                default => $this->cache->$operation($this->key),
            };
        } finally {
            $this->assertTrue(is_link($path));
            if ($dangling) {
                $this->assertFileDoesNotExist($target);
            } else {
                $this->assertSame('unchanged', file_get_contents($target));
            }
        }
    }

    public function testDirectoryAtAFilePathIsRejected(): void
    {
        mkdir($this->directory.'/cache/report.json', 0700);
        $this->expectException(\RuntimeException::class);
        $this->cache->writeReport([]);
    }

    public function testPredictableTemporarySymlinksAreNeverUsed(): void
    {
        $target = $this->directory.'/outside.txt';
        file_put_contents($target, 'unchanged');
        foreach ([$this->key.'.json.tmp', $this->key.'.body.tmp', 'report.json.tmp'] as $filename) {
            symlink($target, $this->directory.'/cache/'.$filename);
        }
        $this->saveCapture();
        $this->cache->writeReport(['cases' => []]);
        $this->assertSame('unchanged', file_get_contents($target));
        $this->assertSame('[]', $this->cache->response($this->key)->body);
        $this->assertSame([], glob($this->directory.'/cache/.capture-*'));
    }

    public function testAtomicReplacementDoesNotWriteThroughAHardLink(): void
    {
        $target = $this->directory.'/outside.txt';
        file_put_contents($target, 'unchanged');
        link($target, $this->directory.'/cache/report.json');
        $this->cache->writeReport(['cases' => []]);
        $this->assertSame('unchanged', file_get_contents($target));
        $this->assertSame(['cases' => []], json_decode(file_get_contents($this->directory.'/cache/report.json'), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testExistingCapturesAndReportsRemainUsable(): void
    {
        $this->saveCapture();
        $cache = new CaptureCache($this->directory.'/cache');
        $this->assertSame('[]', $cache->response($this->key)->body);
        $cache->writeReport(['cases' => ['old']]);
        $cache->writeReport(['cases' => ['new']]);
        $report = $this->directory.'/cache/report.json';
        $this->assertSame(['cases' => ['new']], json_decode(file_get_contents($report), true, 512, JSON_THROW_ON_ERROR));
        if (PHP_OS_FAMILY !== 'Windows') {
            foreach ([$report, $this->directory.'/cache/'.$this->key.'.json', $this->directory.'/cache/'.$this->key.'.body'] as $file) {
                $this->assertSame(0600, fileperms($file) & 0777);
            }
        }
    }

    public function testLockExcludesConcurrentRecorderAndCanBeReacquired(): void
    {
        $first = $this->cache->acquireLock();
        try {
            $this->cache->acquireLock();
            $this->fail('A second lock must be rejected.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Another recorder is already running.', $error->getMessage());
        } finally {
            flock($first, LOCK_UN);
            fclose($first);
        }
        $second = $this->cache->acquireLock();
        $this->assertIsResource($second);
        fclose($second);
    }
}
