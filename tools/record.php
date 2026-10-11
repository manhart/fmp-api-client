#!/usr/bin/env php
<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

require dirname(__DIR__).'/vendor/autoload.php';

use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Recording\CaptureCache;
use Manhart\Fmp\Recording\Recorder;

$options = getopt('', ['endpoint:', 'include-large', 'include-legacy', 'refresh', 'offline', 'plan', 'max-bytes:', 'rate:', 'cache-dir:', 'help']);
if (isset($options['help'])) {
    echo <<<'HELP'
Usage: php tools/record.php [options]
  --plan                    Show selected requests without API calls
  --offline                 Replay recordings without API calls
  --endpoint=PATH           Select one Stable path or legacy:methodName
  --include-legacy          Include supported Legacy method cases
  --include-large           Include Bulk/XLSX/full financial report cases
  --refresh                 Re-record a selected endpoint (requires --endpoint)
  --max-bytes=N             Received body budget per run (default 1073741824)
  --rate=N                  Requests per minute (default 60, maximum 3000)
  --cache-dir=PATH          Private recordings (default: user-specific temp directory)
  --help                    Show this help
Set FMP_API_KEY locally for live captures. Do not put keys in CLI arguments.

HELP;
    exit(0);
}
try {
    if (isset($options['refresh']) && !isset($options['endpoint'])) {
        throw new InvalidArgumentException('--refresh requires a specific --endpoint.');
    }
    $root = dirname(__DIR__);
    $stable = json_decode(file_get_contents($root.'/resources/endpoints.json'), true, 512, JSON_THROW_ON_ERROR)['endpoints'];
    $legacy = json_decode(file_get_contents($root.'/resources/legacy-cases.json'), true, 512, JSON_THROW_ON_ERROR);
    $cases = $stable;
    foreach ($legacy as $method => $case) {
        if (isset($options['include-legacy']) || ($options['endpoint'] ?? '') === 'legacy:'.$method) {
            $cases['legacy:'.$method] = $case;
        }
    }
    if (isset($options['endpoint'])) {
        $endpoint = ltrim($options['endpoint'], '/');
        if (str_starts_with($endpoint, 'stable/')) {
            $endpoint = substr($endpoint, 7);
        }
        if (!isset($cases[$endpoint])) {
            throw new InvalidArgumentException('Unknown endpoint: '.$endpoint);
        }
        $cases = [$endpoint => $cases[$endpoint]];
    }
    if (!isset($options['include-large'])) {
        $cases = array_filter($cases, static fn(array $case): bool => !$case['large']);
    }
    if (!$cases) {
        throw new InvalidArgumentException('Selection is empty. Large endpoints require --include-large.');
    }
    if (isset($options['plan'])) {
        $planner = new Manhart\Fmp\Recording\RequestPlanner();
        foreach ($cases as $id => $case) {
            echo $id.' '.$planner->plan($id, $case)."\n";
        }
        echo count($cases)." cases; 0 API calls.\n";
        exit(0);
    }
    $maxBytes = isset($options['max-bytes']) ? filter_var($options['max-bytes'], FILTER_VALIDATE_INT) : 1073741824;
    $rate = isset($options['rate']) ? filter_var($options['rate'], FILTER_VALIDATE_INT) : 60;
    if ($maxBytes === false || $maxBytes <= 0 || $rate === false || $rate <= 0 || $rate > 3000) {
        throw new InvalidArgumentException('Invalid byte budget or rate (maximum 3000/min).');
    }
    $userId = function_exists('posix_geteuid') ? (string)posix_geteuid() : substr(hash('sha256', getenv('USERNAME') ?: get_current_user()), 0, 16);
    $directory = $options['cache-dir'] ?? sys_get_temp_dir().'/manhart-fmp-recordings-'.$userId;
    $cache = new CaptureCache($directory);
    $lock = $cache->acquireLock();
    $factory = static function (int $remaining): FmpApiClient {
        $key = getenv('FMP_API_KEY');
        if ($key === false || $key === '') {
            throw new InvalidArgumentException('Set FMP_API_KEY before a live recording.');
        }
        return new FmpApiClient($key, timeoutSeconds: 120, maxResponseBytes: $remaining);
    };
    $recorder = new Recorder($cache, $factory, $maxBytes, $rate);
    $report = [];
    $failed = false;
    foreach ($cases as $id => $case) {
        $result = $recorder->capture($id, $case, isset($options['refresh']), isset($options['offline']));
        $report[] = $result;
        echo $id.' '.$result['outcome'].($result['cached'] ? ' (cached)' : '')."\n";
        $failed = $failed || $result['outcome'] !== 'verified';
        if ($result['outcome'] === 'budget-exhausted') {
            break;
        }
    }
    $summary = $recorder->statistics();
    $cache->writeReport(['statistics' => $summary, 'cases' => $report]);
    echo json_encode($summary, JSON_THROW_ON_ERROR)."\n";
    flock($lock, LOCK_UN);
    fclose($lock);
    exit($failed ? 1 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage()."\n");
    exit(1);
}
