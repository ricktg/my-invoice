<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\BusinessDayCalculator;
use PHPUnit\Framework\TestCase;

final class BusinessDayCalculatorTest extends TestCase
{
    public function testCountWeekdaysInMonthForMarch2026(): void
    {
        $calculator = new BusinessDayCalculator();

        self::assertSame(22, $calculator->countWeekdaysInMonth('2026-03'));
    }

    public function testCountWeekdaysInMonthForFebruary2024LeapYear(): void
    {
        $calculator = new BusinessDayCalculator();

        self::assertSame(21, $calculator->countWeekdaysInMonth('2024-02'));
    }

    public function testCountWeekdaysInMonthReturnsZeroForInvalidInput(): void
    {
        $calculator = new BusinessDayCalculator();

        self::assertSame(0, $calculator->countWeekdaysInMonth('2026-13'));
        self::assertSame(0, $calculator->countWeekdaysInMonth('abc'));
        self::assertSame(0, $calculator->countWeekdaysInMonth('2026/03'));
    }
}
