<?php

namespace SimpleJwtLoginTests\Unit\Repositories;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\DateFilterTrait;

class DateFilterTraitTest extends TestCase
{
    /**
     * @var object
     */
    private object $subject;

    public function setUp(): void
    {
        $this->subject = new class {
            use DateFilterTrait;

            public function validate(string $date): bool
            {
                return $this->isValidDate($date);
            }
        };
    }

    #[DataProvider('validDateProvider')]
    public function testIsValidDateAcceptsValidDates(string $date): void
    {
        $this->assertTrue($this->subject->validate($date));
    }

    #[DataProvider('invalidDateProvider')]
    public function testIsValidDateRejectsInvalidDates(string $date): void
    {
        $this->assertFalse($this->subject->validate($date));
    }

    public static function validDateProvider(): array
    {
        return [
            'typical date'    => ['2024-01-15'],
            'start of year'   => ['2024-01-01'],
            'end of year'     => ['2024-12-31'],
            'leap day'        => ['2024-02-29'],
        ];
    }

    public static function invalidDateProvider(): array
    {
        return [
            'empty string'         => [''],
            'missing dashes'       => ['20240115'],
            'wrong separator'      => ['2024/01/15'],
            'extra chars'          => ['2024-01-15 00:00:00'],
            'partial date'         => ['2024-01'],
            'non-numeric'          => ['abcd-ef-gh'],
            'sql injection attempt' => ["2024-01-01' OR '1'='1"],
        ];
    }
}
