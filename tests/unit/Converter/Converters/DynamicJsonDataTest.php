<?php

declare(strict_types=1);

namespace Smile\GdprDump\Tests\Unit\Converter\Proxy;

use PHPUnit\Framework\Attributes\DataProvider;
use Smile\GdprDump\Converter\Proxy\DynamicJsonData;
use Smile\GdprDump\Tests\Framework\Mock\Converter\ConverterMock;
use Smile\GdprDump\Tests\Unit\Converter\TestCase;

final class DynamicJsonDataTest extends TestCase
{
    /**
     * Test the converter.
     */
    #[DataProvider('getConverterTestDataProvider')]
    public function testConverter(string $input, string $expected): void
    {
        $converter = $this->createConverter(DynamicJsonData::class, [
            'converters' => [
                'customer.<>.firstname' => $this->createConverter(ConverterMock::class),
                'customer.<>.lastname' => $this->createConverter(ConverterMock::class),
                'customer.not_exists' => $this->createConverter(ConverterMock::class),
            ],
        ]);

        $value = $converter->convert($input);
        $this->assertIsString($value);
        $this->assertJsonStringEqualsJsonString($expected, $value);
    }

    public function testKeepsScalarDataUnchanged(): void
    {
        $converter = $this->createConverter(DynamicJsonData::class, [
            'converters' => [
                'customer.<>.firstname' => $this->createConverter(ConverterMock::class),
                'customer.<>.lastname' => $this->createConverter(ConverterMock::class),
            ],
        ]);

        $value = $converter->convert(\json_encode('simple string', \JSON_THROW_ON_ERROR));

        $this->assertIsString($value);
        $this->assertJson($value);
    }

    public static function getConverterTestDataProvider(): \Generator
    {
        $input = \json_encode([
            'customer' => [
                'id-1' => [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'description' => 'This does not change.',
                ],
            ],
        ], \JSON_THROW_ON_ERROR);
        $expected = \json_encode([
            'customer' => [
                'id-1' => [
                    'firstname' => 'test_John',
                    'lastname' => 'test_Doe',
                    'description' => 'This does not change.',
                ],
            ],
        ], \JSON_THROW_ON_ERROR);

        yield 'associative array' => [
            'input' => $input,
            'expected' => $expected,
        ];

        $input = \json_encode([
            'customer' => [
                [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'description' => 'This does not change.',
                ],
                [
                    'firstname' => 'Jane',
                    'lastname' => 'Doe',
                    'description' => 'This does not change.',
                ],
            ],
        ], \JSON_THROW_ON_ERROR);
        $expected = \json_encode([
            'customer' => [
                [
                    'firstname' => 'test_John',
                    'lastname' => 'test_Doe',
                    'description' => 'This does not change.',
                ],
                [
                    'firstname' => 'test_Jane',
                    'lastname' => 'test_Doe',
                    'description' => 'This does not change.',
                ],
            ],
        ], \JSON_THROW_ON_ERROR);

        yield 'numeric array' => [
            'input' => $input,
            'expected' => $expected,
        ];
    }
}
