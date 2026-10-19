<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Client\AbstractApiClient;
use Manhart\Fmp\Client\FmpApiClient;
use Manhart\Fmp\Client\LegacyApiClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ClientDiagnosticsTest extends TestCase
{
    public static function constructorFailures(): iterable
    {
        $cases = [
            'empty key' => [''],
            'CR key' => ["synthetic-key\r"],
            'LF key' => ["synthetic-key\n"],
            'CR/LF key' => ["synthetic-key\r\n"],
            'invalid timeout' => ['synthetic-key', 0],
            'invalid connect timeout' => ['synthetic-key', 60, 0],
            'invalid byte limit' => ['synthetic-key', 60, 10, 0],
        ];
        foreach (['Stable' => FmpApiClient::class, 'Legacy' => LegacyApiClient::class] as $name => $class) {
            foreach ($cases as $case => $arguments) {
                yield "$name $case" => [$class, $arguments];
            }
        }
    }

    /** @param class-string<FmpApiClient|LegacyApiClient> $class */
    #[DataProvider('constructorFailures')]
    public function testConstructorTraceHidesTheKeyWhenArgumentsAreRecorded(string $class, array $arguments): void
    {
        $original = ini_get('zend.exception_ignore_args');
        $this->assertIsString($original);
        ini_set('zend.exception_ignore_args', '0');
        try {
            $this->assertSame('0', ini_get('zend.exception_ignore_args'));
            try {
                new $class(...$arguments);
                $this->fail('The invalid constructor arguments must still be rejected.');
            } catch (\InvalidArgumentException $error) {
                // Caller-owned trace arguments are outside the SDK constructor's contract.
                $frame = $error->getTrace()[0];
                $this->assertSame(AbstractApiClient::class, $frame['class']);
                $this->assertSame('__construct', $frame['function']);
                $this->assertInstanceOf(\SensitiveParameterValue::class, $frame['args'][0]);
                $this->assertSame($arguments[0], $frame['args'][0]->getValue());
            }
        } finally {
            ini_set('zend.exception_ignore_args', $original);
        }
    }

    public static function clients(): iterable
    {
        yield 'Stable' => [FmpApiClient::class];
        yield 'Legacy' => [LegacyApiClient::class];
    }

    /**
     * @param class-string<FmpApiClient|LegacyApiClient> $class
     * @throws \JsonException If synthetic trace arguments cannot be encoded.
     */
    #[DataProvider('clients')]
    public function testNullByteEndpointsFailWithoutExposingTheKeyOrStartingATransfer(string $class): void
    {
        $original = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');
        try {
            $key = 'synthetic-request-key+/';
            $client = new $class($key);
            try {
                $client->request("stable/profile\0");
                $this->fail('Null bytes must be rejected before calling cURL.');
            } catch (\InvalidArgumentException $error) {
                $this->assertSame('Endpoint URL must not contain null bytes.', $error->getMessage());
                $trace = json_encode($error->getTrace(), JSON_THROW_ON_ERROR);
                $this->assertStringNotContainsString($key, $trace);
                $this->assertStringNotContainsString(rawurlencode($key), $trace);
                $this->assertNull($error->getPrevious());
                $this->assertSame(0, $client->getRequests());
            }
        } finally {
            ini_set('zend.exception_ignore_args', $original);
        }
    }

    /** @param class-string<FmpApiClient|LegacyApiClient> $class */
    #[DataProvider('clients')]
    public function testStandardDebugOutputHidesTheKeyWithoutChangingClientState(string $class): void
    {
        $key = 'synthetic debug-key+/';
        $client = new $class($key, timeoutSeconds: 17, connectTimeoutSeconds: 5, maxResponseBytes: 4096);
        $info = $client->__debugInfo();
        $private = static fn(string $name): string => "\0".AbstractApiClient::class."\0$name";
        $this->assertSame('[REDACTED]', $info[$private('apiKey')]);
        $this->assertSame(17, $info[$private('timeoutSeconds')]);
        $this->assertSame(5, $info[$private('connectTimeoutSeconds')]);
        $this->assertSame(4096, $info[$private('maxResponseBytes')]);
        $this->assertSame($client->getRequests(), $info[$private('requests')]);
        $this->assertSame($client->getLastEndpointURL(), $info[$private('lastEndpointURL')]);
        // Dumpers such as Symfony VarDumper replace real entries only when mangled names match.
        $this->assertSame(array_keys((array)$client), array_keys($info));
        $this->assertStringContainsString(
            '[apiKey:'.AbstractApiClient::class.':private] => [REDACTED]',
            print_r($client, true),
        );

        ob_start();
        try {
            var_dump($client);
            $dump = ob_get_contents();
        } finally {
            ob_end_clean();
        }
        foreach ([$dump, print_r($client, true)] as $output) {
            $this->assertStringNotContainsString($key, $output);
            $this->assertStringContainsString('[REDACTED]', $output);
        }
        $this->assertSame('[REDACTED]', $client->redact($key));
        $this->assertSame('[REDACTED]', $client->redact(rawurlencode($key)));
        $this->assertSame(0, $client->getRequests());
    }
}
