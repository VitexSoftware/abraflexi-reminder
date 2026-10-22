<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexi Reminder package
 *
 * https://github.com/VitexSoftware/abraflexi-reminder
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AbraFlexi\Reminder;

use AbraFlexi\Reminder\Upominac;

/**
 * Reminder level selection must rely on dates stored on invoices, not only on labels.
 *
 * @no-named-arguments
 */
class ReminderLevelTest extends \PHPUnit\Framework\TestCase
{
    private static function day(int $daysAgo): string
    {
        return (new \DateTimeImmutable("-{$daysAgo} days"))->format('Y-m-d').'+02:00';
    }

    private static function debt(int $overdueDays, array $sent = []): array
    {
        return ['datSplat' => self::day($overdueDays)] + $sent;
    }

    public function testFirstReminderWhenNothingSentYet(): void
    {
        self::assertSame(1, Upominac::calculateReminderLevel([self::debt(3)], []));
    }

    public function testNothingDueBeforeMaturity(): void
    {
        self::assertSame(0, Upominac::calculateReminderLevel([self::debt(-2)], []));
    }

    public function testFirstReminderIsNotRepeatedEvenWhenLabelWasCleared(): void
    {
        $debts = [self::debt(3, ['datUp1' => self::day(2)])];
        self::assertSame(0, Upominac::calculateReminderLevel($debts, []));
        self::assertSame(0, Upominac::calculateReminderLevel([self::debt(5, ['datUp1' => self::day(0)])], []));
    }

    public function testSecondReminderAfterWeek(): void
    {
        $debts = [self::debt(9, ['datUp1' => self::day(7)])];
        self::assertSame(2, Upominac::calculateReminderLevel($debts, []));
    }

    public function testSecondReminderWaitsForWeekSincePrevious(): void
    {
        $debts = [self::debt(9, ['datUp1' => self::day(6)])];
        self::assertSame(0, Upominac::calculateReminderLevel($debts, []));
    }

    public function testThirdReminderAfterSecond(): void
    {
        $debts = [self::debt(20, ['datUp1' => self::day(14), 'datUp2' => self::day(7)])];
        self::assertSame(3, Upominac::calculateReminderLevel($debts, []));
    }

    public function testNothingAfterThirdReminder(): void
    {
        $debts = [self::debt(40, ['datUp1' => self::day(30), 'datUp2' => self::day(23), 'datSmir' => self::day(16)])];
        self::assertSame(0, Upominac::calculateReminderLevel($debts, []));
    }

    public function testLevelsAreNotSkipped(): void
    {
        self::assertSame(1, Upominac::calculateReminderLevel([self::debt(30)], []));
    }

    public function testLabelIsStillRespected(): void
    {
        self::assertSame(0, Upominac::calculateReminderLevel([self::debt(3)], ['UPOMINKA1' => 'UPOMINKA1']));
    }

    public function testEmptyDatesAreIgnored(): void
    {
        $debts = [self::debt(3, ['datUp1' => '', 'datUp2' => null, 'datSmir' => ''])];
        self::assertSame(1, Upominac::calculateReminderLevel($debts, []));
    }

    public function testEmptyAbraFlexiDateObjectsAreIgnored(): void
    {
        $debts = [self::debt(3, ['datUp1' => new \AbraFlexi\Date(''), 'datUp2' => new \AbraFlexi\Date(''), 'datSmir' => new \AbraFlexi\Date('')])];
        self::assertSame(1, Upominac::calculateReminderLevel($debts, []));
    }

    public function testAbraFlexiDateObjectCountsAsSent(): void
    {
        $debts = [self::debt(3, ['datUp1' => new \AbraFlexi\Date((new \DateTimeImmutable('-2 days'))->format('Y-m-d')), 'datUp2' => new \AbraFlexi\Date('')])];
        self::assertSame(0, Upominac::calculateReminderLevel($debts, []));
    }
}
