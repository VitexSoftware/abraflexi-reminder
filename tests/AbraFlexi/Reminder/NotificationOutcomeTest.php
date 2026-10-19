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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Per-customer outcome used to colour the job log (green / orange / red).
 *
 * @no-named-arguments
 */
class NotificationOutcomeTest extends TestCase
{
    public static function reports(): array
    {
        return [
            'delivered' => ['notified', ['notifiedVia' => ['ByEmail']]],
            'nothing attempted' => ['skipped', ['message' => 'Remind 1 already sent']],
            'no e-mail, other modules idle' => ['noContact', ['remindsSent' => [
                'ByEmail' => ['sent' => false, 'noContact' => true],
                'ByDatovka' => false,
                'ByServiceToggle' => ['action' => 'disconnect'],
            ]]],
            'contact exists, send failed' => ['failed', ['remindsSent' => [
                'ByEmail' => ['sent' => false, 'noContact' => false],
            ]]],
            'one channel without contact, other failed' => ['failed', ['remindsSent' => [
                'ByEmail' => ['sent' => false, 'noContact' => true],
                'BySms' => ['sent' => false, 'noContact' => false],
            ]]],
            'no channel available' => ['failed', ['remindsSent' => []]],
        ];
    }

    #[DataProvider('reports')]
    public function testOutcome(string $expected, array $report): void
    {
        $method = new \ReflectionMethod(Upominac::class, 'notificationOutcome');
        $this->assertSame($expected, $method->invoke(null, $report));
    }
}
