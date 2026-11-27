<?php

namespace SimpleJwtLoginTests\Unit\Repositories;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Repositories\PaginationTrait;

class PaginationTraitTest extends TestCase
{
    /**
     * @var object
     */
    private object $subject;

    public function setUp(): void
    {
        $this->subject = new class {
            use PaginationTrait;

            /**
             * @param mixed $page
             * @param int $perPage
             * @return int
             */
            public function offset($page, $perPage)
            {
                return $this->pageOffset($page, $perPage);
            }
        };
    }

    #[DataProvider('offsetProvider')]
    public function testPageOffset(mixed $page, int $perPage, int $expected): void
    {
        $this->assertSame($expected, $this->subject->offset($page, $perPage));
    }

    public static function offsetProvider(): array
    {
        return [
            'first page'                 => [1, 20, 0],
            'third page'                 => [3, 20, 40],
            'page below 1 is page 1'     => [-5, 20, 0],
            'huge page does not overflow' => [PHP_INT_MAX, 20, PHP_INT_MAX],
            'huge page as a string'      => ['9133999273169708152', 20, PHP_INT_MAX],
        ];
    }
}
