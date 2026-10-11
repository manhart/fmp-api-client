<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

$root = dirname(__DIR__);
$manifest = json_decode(file_get_contents($root.'/resources/endpoints.json'), true, 512, JSON_THROW_ON_ERROR);
$shortcuts = json_decode(file_get_contents($root.'/resources/client-shortcuts.json'), true, 512, JSON_THROW_ON_ERROR);
$methodNames = array_fill_keys(['__construct', 'fetch', 'request', 'executecurl', 'redact', 'getrequests', 'getlastendpointurl', 'buildendpointurl', 'formatparameter'], true);
foreach ($shortcuts as $method => $endpoint) {
    if (!is_string($method) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/D', $method) || isset($methodNames[strtolower($method)])) {
        throw new RuntimeException("Invalid or conflicting shortcut method: $method");
    }
    if (!is_string($endpoint) || !isset($manifest['endpoints'][$endpoint])) {
        throw new RuntimeException("Unknown Stable endpoint for shortcut $method");
    }
    $methodNames[strtolower($method)] = true;
}
$namespace = 'Manhart\\Fmp\\Response\\Stable';
$licenseHeader = <<<'HEADER'
// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT
HEADER;
$typeImports = [
    'DateTimeInterface' => 'DateTimeInterface',
    'Period' => 'Manhart\\Fmp\\Enum\\Period',
    'ReportPeriod' => 'Manhart\\Fmp\\Enum\\ReportPeriod',
    'SortDirection' => 'Manhart\\Fmp\\Enum\\SortDirection',
    'Timeframe' => 'Manhart\\Fmp\\Enum\\Timeframe',
];
$importsForTypes = static function (array $types) use ($typeImports): string {
    $imports = [];
    foreach ($types as $type) {
        foreach (explode('|', $type) as $name) {
            if (isset($typeImports[$name])) {
                $imports[$name] = $typeImports[$name];
            }
        }
    }
    ksort($imports);
    return implode("\n", array_map(static fn(string $name): string => "use $name;", $imports));
};
$registry = [];
$methodSignatures = [];
$methodArguments = [];
$methodTypes = [];
$methodDocs = [];
$parameterNotes = [
    'cik' => 'SEC identifier; pass a string to preserve leading zeroes.',
    'companyCik' => 'Company SEC identifier, including any leading zeroes.',
    'reportingCik' => 'SEC identifier of the reporting person, including any leading zeroes.',
    'symbols' => 'Comma-separated FMP symbols or an array, sent in one request.',
    'from' => 'Start calendar date as YYYY-MM-DD; a DateTime contributes only its date.',
    'to' => 'End calendar date as YYYY-MM-DD; a DateTime contributes only its date.',
    'date' => 'Calendar date in YYYY-MM-DD format.',
    'period' => 'Reporting frequency (annual/quarter) or report label (FY/Q1-Q4), as required by this endpoint.',
    'part' => 'Bulk-file partition identifier; fetch other partitions explicitly when needed.',
    'page' => 'Provider page index; pagination requires another explicit request.',
    'quarter' => 'Reporting quarter number (1-4), in the provider\'s reporting calendar.',
    'periodLength' => 'Number of price bars used by the technical indicator.',
    'timeframe' => 'Price-bar interval, e.g. 1day; enum backing values are sent unchanged.',
    'timestamp' => 'Unix timestamp in seconds, passed to FMP as a string.',
    'nonadjusted' => 'Request data without the provider\'s adjustments.',
    'structure' => 'Provider response layout, e.g. flat; does not change the PHP response type.',
    'short' => 'Request the provider\'s reduced quote fields.',
    'senateID' => 'Provider identifier of the legislator, also used by the House endpoints.',
    'expertUID' => 'TipRanks analyst identifier resolved through its directory/search endpoints.',
    'sicCode' => 'Standard Industrial Classification code; keep leading zeroes if present.',
];
$docBlock = static function (string $summary, array $parameters = [], array $exceptionTags = []) use ($parameterNotes): string {
    $lines = $summary ? [$summary] : [];
    $tags = [];
    foreach ($parameters as $name => $parameter) {
        $description = $parameter['description'] ?? $parameterNotes[$name] ?? null;
        if ($description) {
            $type = $parameter['phpType'].($parameter['required'] ? '' : '|null');
            $tags[] = "@param $type \$$name $description";
        }
    }
    $tags = array_merge($tags, $exceptionTags);
    if ($lines && $tags) {
        $lines[] = '';
    }
    $lines = array_merge($lines, $tags);
    if (!$lines) {
        return '';
    }
    $formatted = [];
    foreach ($lines as $line) {
        foreach (explode("\n", wordwrap(str_replace('*/', '* /', $line), 105, "\n   ")) as $part) {
            $formatted[] = $part === '' ? ' *' : ' * '.$part;
        }
    }
    return "/**\n".implode("\n", $formatted)."\n */";
};
$transportExceptionTags = ['@throws \\JsonException If parameter encoding or cached transport metadata fails.'];
foreach ($manifest['endpoints'] as $endpoint => $contract) {
    $class = $contract['class'];
    $base = $contract['format'] === 'xlsx' ? 'FileResponse' : 'Response';
    $signature = ['FmpApiClient $client'];
    $arguments = [];
    $types = [];
    $parameters = $contract['parameters'];
    uasort($parameters, static fn(array $a, array $b): int => $b['required'] <=> $a['required']);
    foreach ($parameters as $name => $parameter) {
        $type = $parameter['phpType'];
        $types[$name] = $type;
        if (!$parameter['required']) {
            $type .= '|null';
        }
        $signature[] = "$type \$$name".($parameter['required'] ? '' : ' = null');
        $arguments[] = "            '$name' => \$$name,";
    }
    $methodSignatures[$endpoint] = array_slice($signature, 1);
    $methodTypes[$endpoint] = $types;
    $classDoc = $docBlock($contract['description'] ?? '');
    $fetchDoc = $docBlock('', $parameters, $transportExceptionTags);
    $fetchDoc = $fetchDoc ? implode("\n", array_map(static fn(string $line): string => '    '.$line, explode("\n", $fetchDoc)))."\n" : '';
    $methodDocs[$endpoint] = $docBlock($contract['description'] ?? '', $parameters, $transportExceptionTags);
    $methodArguments[$endpoint] = array_map(static fn(string $name): string => "            $name: \$$name", array_keys($parameters));
    $baseImport = 'Manhart\\Fmp\\Response\\'.$base;
    $fields = '['.($contract['format'] === 'xlsx' || !$contract['fields'] ? '' : "\n".implode("\n", array_map(static fn(string $field): string => '        '.var_export($field, true).',', $contract['fields']))."\n    ").']';
    $params = "[\n        'query' => [\n";
    foreach ($types as $name => $type) {
        $params .= "            '$name' => '$type',\n";
    }
    $params .= "        ],\n    ]";
    $argumentsText = implode("\n", $arguments);
    $signatureText = implode(",\n        ", $signature);
    $parameterImports = $importsForTypes($types);
    $importsText = "use Manhart\\Fmp\\Client\\FmpApiClient;\nuse $baseImport;";
    if ($parameterImports) {
        $importsText .= "\n$parameterImports";
    }
    $code = <<<PHPFILE
<?php
declare(strict_types=1);

$licenseHeader

// Generated by tools/generate.php from resources/endpoints.json.
namespace $namespace;

$importsText

$classDoc
class $class extends $base
{
    public const array FIELDS = $fields;
    public const array PARAMS = $params;
    protected static string \$url = 'stable/$endpoint';

{$fetchDoc}    public static function fetch(
        $signatureText
    ): static {
        return parent::create(\$client, ...[
$argumentsText
        ]);
    }
}

PHPFILE;
    file_put_contents("$root/src/Response/Stable/$class.php", $code);
    $registry[] = "        '$endpoint' => $class::class,";
}
$entries = implode("\n", $registry);
$code = <<<PHPFILE
<?php
declare(strict_types=1);

$licenseHeader

// Generated by tools/generate.php from resources/endpoints.json.
namespace $namespace;

/** Resolve Stable route names to their generated response factories without a request. */
final class EndpointRegistry
{
    /** @var array<string, class-string<\Manhart\Fmp\Response\Response|\Manhart\Fmp\Response\FileResponse>> */
    public const array ENDPOINTS = [
$entries
    ];

    /**
     * Accept a bare route or /stable/ prefix; unknown routes fail before any transfer.
     *
     * @return class-string<\Manhart\Fmp\Response\Response|\Manhart\Fmp\Response\FileResponse>
     */
    public static function resolve(string \$endpoint): string
    {
        \$endpoint = ltrim(\$endpoint, '/');
        if (str_starts_with(\$endpoint, 'stable/')) {
            \$endpoint = substr(\$endpoint, 7);
        }
        return self::ENDPOINTS[\$endpoint] ?? throw new \InvalidArgumentException('Unknown stable endpoint: '.\$endpoint);
    }

    /** @return array<string, class-string<\Manhart\Fmp\Response\Response|\Manhart\Fmp\Response\FileResponse>> */
    public static function endpoints(): array
    {
        return self::ENDPOINTS;
    }
}

PHPFILE;
file_put_contents($root.'/src/Response/Stable/EndpointRegistry.php', $code);
$methods = [];
$shortcutTypes = [];
foreach ($shortcuts as $method => $endpoint) {
    $class = $manifest['endpoints'][$endpoint]['class'];
    $signature = $methodSignatures[$endpoint];
    array_push($shortcutTypes, ...array_values($methodTypes[$endpoint]));
    $signatureText = $signature ? "\n        ".implode(",\n        ", $signature)."\n    " : '';
    $openingBrace = $signature ? ' {' : "\n    {";
    $arguments = $methodArguments[$endpoint];
    $argumentsText = $arguments ? "\n            \$this,\n".implode(",\n", $arguments).",\n        " : '$this';
    $methodDoc = $methodDocs[$endpoint];
    $methodDoc = $methodDoc ? implode("\n", array_map(static fn(string $line): string => '    '.$line, explode("\n", $methodDoc)))."\n" : '';
    $methods[] = <<<METHOD
{$methodDoc}    public function $method($signatureText): Stable\\$class$openingBrace
        return Stable\\$class::fetch($argumentsText);
    }
METHOD;
}
$methodsText = implode("\n\n", $methods);
$parameterImports = $importsForTypes($shortcutTypes);
$importsText = 'use Manhart\\Fmp\\Response\\Stable;';
if ($parameterImports) {
    $importsText .= "\n$parameterImports";
}
$code = <<<PHPFILE
<?php
declare(strict_types=1);

$licenseHeader

// Generated by tools/generate.php from resources/client-shortcuts.json and resources/endpoints.json.
namespace Manhart\Fmp\Client;

$importsText

/** Named Stable requests; each method performs one transfer and returns its typed endpoint response. */
trait ClientMethods
{
$methodsText
}

PHPFILE;
file_put_contents($root.'/src/Client/ClientMethods.php', $code);
echo count($manifest['endpoints'])." Stable wrappers and ".count($shortcuts)." client shortcuts generated.\n";
