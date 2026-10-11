<?php
declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Alexander Manhart IT
// SPDX-License-Identifier: MIT

namespace Manhart\Fmp\Tests;

use Manhart\Fmp\Response\Legacy\Profile as LegacyProfile;
use Manhart\Fmp\Response\PayloadDecoder;
use Manhart\Fmp\Response\Stable\Profile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StringGettersTest extends TestCase
{
    public static function scalars(): iterable
    {
        yield 'text' => ['usd', 'usd', 'USD'];
        yield 'padded text' => [' usd ', ' usd ', ' USD '];
        yield 'whitespace' => ['  ', '  ', '  '];
        yield 'zero string' => ['0', '0', '0'];
        yield 'zero integer' => [0, '0', '0'];
        yield 'integer' => [42, '42', '42'];
        yield 'float' => [1.5, '1.5', '1.5'];
        yield 'true' => [true, '1', '1'];
        yield 'false' => [false, '', 'DEFAULT'];
    }

    #[DataProvider('scalars')]
    public function testBothGettersNormalizeEveryScalarAsAString(mixed $value, string $input, string $expected): void
    {
        $response = new Profile([['value' => $value]]);
        $seen = [];
        $normalizer = static function (string $text) use (&$seen): string {
            $seen[] = $text;
            return strtoupper($text);
        };

        $this->assertSame($expected, $response->getResponseValueAsString('value', 'DEFAULT', $normalizer));
        $this->assertSame($expected, $response->getResponseValueAsStringByKeys(['value'], 'DEFAULT', $normalizer));
        $this->assertSame([$input, $input], $seen);
        $this->assertSame($value, $response->getResponseValue('value'));
    }

    public static function normalizedResults(): iterable
    {
        yield 'string' => ['USD', 'USD'];
        yield 'integer zero' => [0, '0'];
        yield 'string zero' => ['0', '0'];
        yield 'float' => [1.5, '1.5'];
        yield 'true' => [true, '1'];
        yield 'false' => [false, 'DEFAULT'];
        yield 'null' => [null, 'DEFAULT'];
        yield 'empty' => ['', 'DEFAULT'];
        yield 'array' => [[], 'DEFAULT'];
        yield 'object' => [new \stdClass(), 'DEFAULT'];
    }

    #[DataProvider('normalizedResults')]
    public function testCallbackResultsUseTheSameConversionAndFallback(mixed $result, string $expected): void
    {
        $response = new Profile([['currency' => 'usd']]);
        $normalizer = static fn(string $text): mixed => $result;

        $this->assertSame($expected, $response->getResponseValueAsString('currency', 'DEFAULT', $normalizer));
        $this->assertSame($expected, $response->getResponseValueAsStringByKeys(['currency'], 'DEFAULT', $normalizer));
    }

    public function testAliasesSkipOnlyMissingNullAndEmptyStringValues(): void
    {
        $response = new Profile([['null' => null, 'empty' => '', 'currency' => 'usd', 'alternative' => 'eur', 'zero' => 0, 'false' => false]]);

        $this->assertSame('USD', $response->getResponseValueAsStringByKeys(['missing', 'null', 'empty', 'currency', 'alternative'], normalizer: strtoupper(...)));
        $this->assertSame('0', $response->getResponseValueAsStringByKeys(['zero', 'currency']));
        $this->assertSame('DEFAULT', $response->getResponseValueAsStringByKeys(['false', 'currency'], 'DEFAULT'));
    }

    public function testWhitespaceIsPreservedAndCanBeTrimmedExplicitly(): void
    {
        $response = new Profile([['blank' => '  ', 'padded' => ' usd ', 'currency' => 'eur']]);

        $this->assertSame('  ', $response->getResponseValueAsStringByKeys(['blank', 'currency'], 'DEFAULT'));
        $this->assertSame(' usd ', $response->getResponseValueAsStringByKeys(['padded']));
        $this->assertSame(' usd ', $response->getResponseValueAsString('padded'));
        $this->assertSame('usd', $response->getResponseValueAsStringByKeys(['padded'], normalizer: trim(...)));
        $this->assertSame('DEFAULT', $response->getResponseValueAsStringByKeys(['blank', 'currency'], 'DEFAULT', trim(...)));
    }

    public function testRejectedSelectedAliasDoesNotAdvanceToAnotherAlias(): void
    {
        $response = new Profile([['first' => 'usd', 'second' => 'eur', 'invalid' => []]]);
        $seen = [];
        $normalizer = static function (string $text) use (&$seen): ?string {
            $seen[] = $text;
            return null;
        };

        $this->assertSame('DEFAULT', $response->getResponseValueAsStringByKeys(['first', 'second'], 'DEFAULT', $normalizer));
        $this->assertSame(['usd'], $seen);
        $this->assertSame('DEFAULT', $response->getResponseValueAsStringByKeys(['invalid', 'second'], 'DEFAULT', $normalizer));
        $this->assertSame(['usd'], $seen, 'Nonscalar values do not invoke the normalizer');
    }

    public function testMissingFieldsAndEmptyKeyListsNormalizeNonNullDefaults(): void
    {
        $response = new Profile([['null' => null, 'empty' => '']]);

        $this->assertSame('USD', $response->getResponseValueAsString('missing', 'usd', strtoupper(...)));
        $this->assertSame('USD', $response->getResponseValueAsString('null', 'usd', strtoupper(...)));
        $this->assertSame('USD', $response->getResponseValueAsStringByKeys(['missing', 'null', 'empty'], 'usd', strtoupper(...)));
        $this->assertSame('USD', $response->getResponseValueAsStringByKeys([], 'usd', strtoupper(...)));
    }

    public function testNullDefaultsDoNotInvokeTheNormalizer(): void
    {
        $response = new Profile([]);
        $normalizer = static fn(string $text) => throw new \LogicException('Null must not be normalized.');

        $this->assertNull($response->getResponseValueAsString('missing', normalizer: $normalizer));
        $this->assertNull($response->getResponseValueAsStringByKeys(['missing'], normalizer: $normalizer));
        $this->assertNull($response->getResponseValueAsStringByKeys([], normalizer: $normalizer));
    }

    public function testUnsupportedValuesReturnTheOriginalDefaultWithoutNormalization(): void
    {
        $response = new Profile([['array' => [], 'object' => new \stdClass()]]);
        $normalizer = static fn(string $text) => throw new \LogicException('Nonscalar values must not be normalized.');

        foreach (['array', 'object'] as $key) {
            $this->assertSame('usd', $response->getResponseValueAsString($key, 'usd', $normalizer));
            $this->assertSame('usd', $response->getResponseValueAsStringByKeys([$key], 'usd', $normalizer));
        }
    }

    public function testExhaustedIteratorsNormalizeDefaultsConsistently(): void
    {
        $response = new Profile([['currency' => 'eur']]);
        $response->next();

        $this->assertSame('USD', $response->getResponseValueAsString('currency', 'usd', strtoupper(...)));
        $this->assertSame('USD', $response->getResponseValueAsStringByKeys(['currency'], 'usd', strtoupper(...)));
        $response->rewind();
        $this->assertSame('EUR', $response->getResponseValueAsStringByKeys(['currency'], 'usd', strtoupper(...)));
    }

    public function testReferenceNormalizersReceiveAVariableWithoutChangingRowData(): void
    {
        $response = new Profile([['currency' => 'usd']]);
        $normalizer = static function (string &$text): string {
            return $text = strtoupper($text);
        };

        $this->assertSame('USD', $response->getResponseValueAsString('currency', normalizer: $normalizer));
        $this->assertSame('USD', $response->getResponseValueAsStringByKeys(['currency'], normalizer: $normalizer));
        $this->assertSame('usd', $response->getResponseValue('currency'));
    }

    public static function payloads(): iterable
    {
        yield 'JSON' => ['[{"currency":"usd","name":"Example"}]'];
        yield 'CSV' => ["currency,name\nusd,Example\n"];
    }

    /** @throws \JsonException If the synthetic payload cannot be decoded. */
    #[DataProvider('payloads')]
    public function testStableAndLegacyRowsNormalizeJsonAndCsvStrings(string $payload): void
    {
        $rows = PayloadDecoder::decode($payload);
        foreach ([new Profile($rows), new LegacyProfile($rows)] as $response) {
            $this->assertSame('USD', $response->getResponseValueAsString('currency', normalizer: strtoupper(...)));
            $this->assertSame('USD', $response->getResponseValueAsStringByKeys(['currency'], normalizer: strtoupper(...)));
            $this->assertSame('usd', $response->getResponseValue('currency'));
        }
    }

    public static function methods(): iterable
    {
        yield 'single' => ['getResponseValueAsString', 'currency'];
        yield 'aliases' => ['getResponseValueAsStringByKeys', ['currency']];
    }

    #[DataProvider('methods')]
    public function testNormalizerExceptionsArePassedThroughUnchanged(string $method, string|array $key): void
    {
        $response = new Profile([['currency' => 'usd']]);
        $failure = new \LogicException('Normalizer failure.');
        $normalizer = static fn(string $text) => throw $failure;

        try {
            $response->$method($key, normalizer: $normalizer);
            $this->fail('Expected a normalizer failure.');
        } catch (\LogicException $exception) {
            $this->assertSame($failure, $exception);
        }
    }
}
