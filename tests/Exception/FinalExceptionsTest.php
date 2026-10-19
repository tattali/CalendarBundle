<?php

declare(strict_types=1);

namespace CalendarBundle\Tests\Exception;

use CalendarBundle\Exception\CalendarExceptionInterface;
use CalendarBundle\Exception\InvalidDateException;
use CalendarBundle\Exception\InvalidJsonException;
use CalendarBundle\Exception\InvalidTimeZoneException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FinalExceptionsTest extends TestCase
{
    /**
     * @param class-string<CalendarExceptionInterface> $class
     */
    #[DataProvider('provideExceptions')]
    public function testTheExceptionIsFinal(string $class): void
    {
        $reflection = new \ReflectionClass($class);

        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->implementsInterface(CalendarExceptionInterface::class));
        self::assertTrue($reflection->isSubclassOf(\InvalidArgumentException::class));
    }

    /**
     * @return iterable<string, array{class-string<CalendarExceptionInterface>}>
     */
    public static function provideExceptions(): iterable
    {
        yield 'invalid date' => [InvalidDateException::class];
        yield 'invalid JSON' => [InvalidJsonException::class];
        yield 'invalid time zone' => [InvalidTimeZoneException::class];
    }
}
