<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

// Import the official Markdown reference without making data API calls.
if ($argc !== 2) {
    fwrite(STDERR, "Usage: php tools/import-docs.php /path/to/api-docs.md\n");
    exit(1);
}
$source = file_get_contents($argv[1]);
if ($source === false) {
    throw new RuntimeException('Cannot read documentation.');
}
if (($offset = strpos($source, "Markdown Content:\n")) !== false) {
    $source = substr($source, $offset + strlen("Markdown Content:\n"));
}
$root = dirname(__DIR__);
$existing = json_decode(file_get_contents($root.'/resources/endpoints.json'), true, 512, JSON_THROW_ON_ERROR)['endpoints'];
$acronyms = ['cik'=>'CIK', 'cusip'=>'CUSIP', 'sec'=>'SEC', 'sic'=>'SIC', 'esg'=>'ESG', 'etf'=>'Etf', 'dcf'=>'DCF', 'ttm'=>'TTM', 'ipo'=>'IPO', 'sma'=>'SMA', 'ema'=>'EMA', 'wma'=>'WMA', 'dema'=>'DEMA', 'tema'=>'TEMA', 'rsi'=>'RSI', 'adx'=>'ADX', 'eod'=>'Eod', 'id'=>'ID', 'tipranks'=>'TipRanks'];
$integerNames = ['limit', 'page', 'year', 'quarter', 'part', 'periodLength'];
$endpoints = [];
foreach (array_slice(preg_split('/^### /m', $source), 1) as $block) {
    if (!preg_match('/\*\*Endpoint:\*\* `https:\/\/financialmodelingprep\.com\/stable\/([^`?]+)(?:\?([^`]*))?`/', $block, $match)) {
        continue;
    }
    $endpoint = $match[1];
    parse_str($match[2] ?? '', $query);
    preg_match_all('/^\| (.*?) \| (.*?) \| (.*?) \|$/m', $block, $rows, PREG_SET_ORDER);
    $parameters = [];
    foreach ($rows as [, $name, $type, $example]) {
        if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*\*?$/', $name)) {
            continue;
        }
        $required = str_ends_with($name, '*');
        $name = rtrim($name, '*');
        $example = str_replace('`', '', $query[$name] ?? $example);
        $phpType = match ($type) {
            'number' => in_array($name, $integerNames, true) ? 'int' : 'int|float',
            'boolean' => 'bool',
            'date' => 'string|DateTimeInterface',
            'string' => 'string',
            default => throw new RuntimeException("Unknown documented type: $type"),
        };
        $phpType = match ($name) {
            'symbols' => 'string|array',
            'cik' => 'string|int',
            'period' => 'string|Period|ReportPeriod',
            'timeframe' => 'string|Timeframe',
            'sort' => 'string|SortDirection',
            default => $phpType,
        };
        $example = match ($type) {
            'number' => str_contains($example, '.') ? (float)$example : (int)$example,
            'boolean' => strtolower($example) === 'true',
            default => in_array($name, ['period', 'timeframe', 'sort'], true) ? explode(',', $example)[0] : $example,
        };
        $parameters[$name] = ['type' => $type, 'phpType' => $phpType, 'required' => $required, 'example' => $example];
    }
    $fields = [];
    if (preg_match('/\*\*Example Response\*\*\s*```json\s*([\s\S]*?)\s*```/', $block, $response)) {
        $data = json_decode($response[1], true, 512, JSON_THROW_ON_ERROR);
        $row = array_is_list($data) && $data ? $data[0] : $data;
        if (is_array($row)) {
            $fields = array_keys($row);
        }
    }
    if (isset($endpoints[$endpoint])) {
        foreach ($parameters as $name => $parameter) {
            $previous = $endpoints[$endpoint]['parameters'][$name] ?? null;
            if ($previous && ($previous['type'] !== $parameter['type'] || $previous['required'] !== $parameter['required'])) {
                throw new RuntimeException("Conflicting documentation for $endpoint / $name");
            }
            $endpoints[$endpoint]['parameters'][$name] ??= $parameter;
        }
        $endpoints[$endpoint]['fields'] = array_values(array_unique(array_merge($endpoints[$endpoint]['fields'], $fields)));
        continue;
    }
    $class = $existing[$endpoint]['class'] ?? implode('', array_map(static fn(string $part): string => $acronyms[$part] ?? ucfirst($part), preg_split('~[-/_]~', $endpoint)));
    $capture = [];
    foreach ($parameters as $name => $parameter) {
        if ($parameter['required'] || in_array($name, ['limit', 'page', 'from', 'to'], true)) {
            $capture[$name] = match ($name) {
                'limit' => 1,
                'page' => 0,
                default => $parameter['example'],
            };
        }
    }
    $endpoints[$endpoint] = ['class' => $class, 'format' => str_ends_with($endpoint, 'xlsx') ? 'xlsx' : 'json-csv', 'large' => str_contains($endpoint, 'bulk') || str_starts_with($endpoint, 'financial-reports-') && $endpoint !== 'financial-reports-dates', 'parameters' => $parameters, 'fields' => $fields, 'capture' => $capture];
}
if (!$endpoints) {
    throw new RuntimeException('Documentation contains no Stable endpoints; nothing written.');
}
foreach ($endpoints as $endpoint => $contract) {
    $savedCapture = array_intersect_key($existing[$endpoint]['capture'] ?? [], $contract['parameters']);
    $endpoints[$endpoint]['capture'] = array_replace($contract['capture'], $savedCapture);
    // Keep curated explanations only while their endpoint/parameter still exists.
    if (isset($existing[$endpoint]['description'])) {
        $endpoints[$endpoint]['description'] = $existing[$endpoint]['description'];
    }
    foreach ($contract['parameters'] as $name => $parameter) {
        if (isset($existing[$endpoint]['parameters'][$name]['description'])) {
            $endpoints[$endpoint]['parameters'][$name]['description'] = $existing[$endpoint]['parameters'][$name]['description'];
        }
    }
}
ksort($endpoints);
$manifest = ['source' => 'https://site.financialmodelingprep.com/api-docs.md', 'capturedAt' => gmdate('Y-m-d'), 'sourceSha256' => hash('sha256', $source), 'endpoints' => $endpoints];
file_put_contents($root.'/resources/endpoints.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
echo count($endpoints)." documentation contracts imported. Review changes and independent request fixtures before regenerating.\n";
