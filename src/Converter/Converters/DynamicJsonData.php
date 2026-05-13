<?php

declare(strict_types=1);

namespace Smile\GdprDump\Converter\Proxy;

use Smile\GdprDump\Converter\ConverterInterface;
use Smile\GdprDump\Converter\Parameters\Parameter;
use Smile\GdprDump\Converter\Parameters\ParameterProcessor;
use Smile\GdprDump\Util\ArrayHelper;

final class DynamicJsonData implements ConverterInterface
{
    /**
     * @var ConverterInterface[]
     */
    private array $converters;

    public function setParameters(array $parameters): void
    {
        $input = (new ParameterProcessor())
            ->addParameter('converters', Parameter::TYPE_ARRAY, true)
            ->process($parameters);

        $this->converters = $input->get('converters');
    }

    public function convert(mixed $value, array $context = []): mixed
    {
        try {
            $decoded = \json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $value;
        }
        if (!\is_array($decoded)) {
            return $value;
        }

        $flattenedArray = $this->flattenArray($decoded);
        foreach ($this->converters as $path => $converter) {
            $regexPath = \preg_replace('/\<\>/', '(.+)', $path);
            foreach ($flattenedArray as $key => $nestedValue) {
                if (preg_match('/^' . $regexPath . '$/', $key, $matches)) {
                    // Format the value
                    $convertedValue = $converter->convert($nestedValue, $context);

                    ArrayHelper::setPath($decoded, $key, $convertedValue);
                }
            }
        }
        return \json_encode($decoded, JSON_THROW_ON_ERROR);
    }

    private function flattenArray(array $decoded, string $prefix = ''): array
    {
        $flattenedArray = [];
        foreach ($decoded as $key => $value) {
            $path = $prefix === '' ? $key : $prefix . '.' . $key;
            if (\is_array($value)) {
                $flattenedArray += $this->flattenArray($value, $path);
            } else {
                $flattenedArray[$path] = $value;
            }
        }

        return $flattenedArray;
    }
}
