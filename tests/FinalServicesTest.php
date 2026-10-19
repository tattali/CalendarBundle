<?php

declare(strict_types=1);

namespace CalendarBundle\Tests;

use CalendarBundle\Request\RequestParser;
use CalendarBundle\Serializer\Serializer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FinalServicesTest extends TestCase
{
    /**
     * @param class-string $class
     */
    #[DataProvider('provideServices')]
    public function testTheServiceIsFinal(string $class): void
    {
        self::assertTrue(new \ReflectionClass($class)->isFinal(), $class.' must be customised through its interface or by service decoration');
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function provideServices(): iterable
    {
        yield 'request parser' => [RequestParser::class];
        yield 'serializer' => [Serializer::class];
    }
}
