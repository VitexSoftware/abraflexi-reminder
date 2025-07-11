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

use AbraFlexi\Reminder\Upominka;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2018-12-10 at 23:41:09.
 *
 * @no-named-arguments
 */
class UpominkaTest extends \Tests\AbraFlexi\RWTest
{
    protected Upominka $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        $this->object = new Upominka();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
     * @covers \AbraFlexi\Reminder\Upominka::loadTemplate
     *
     * @todo   Implement testLoadTemplate().
     */
    public function testLoadTemplate(): void
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.',
        );
    }

    /**
     * @covers \AbraFlexi\Reminder\Upominka::getSums
     */
    public function testGetSums(): void
    {
        $debts = [
            ['mena' => 'code:EUR', 'zbyvaUhradit' => 23, 'zbyvaUhraditMen' => 1],
            ['mena' => 'code:EUR', 'zbyvaUhradit' => 23, 'zbyvaUhraditMen' => 1],
            ['mena' => 'code:CZK', 'zbyvaUhradit' => 10, 'zbyvaUhraditMen' => 0],
            ['mena' => 'code:CZK', 'zbyvaUhradit' => 10, 'zbyvaUhraditMen' => 0],
            ['mena' => 'code:CZK', 'zbyvaUhradit' => 10, 'zbyvaUhraditMen' => 0],
        ];
        //        ['code:CZK'=>10.5,'code:EUR'=>'23.6']
        $this->assertEquals(['EUR' => 2, 'CZK' => 30], Upominka::getSums($debts));
    }

    /**
     * @covers \AbraFlexi\Reminder\Upominka::qrPayments
     *
     * @todo   Implement testQrPayments().
     */
    public function testQrPayments(): void
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.',
        );
    }

    /**
     * @covers \AbraFlexi\Reminder\Upominka::formatCurrency
     *
     * @todo   Implement testFormatCurrency().
     */
    public function testFormatCurrency(): void
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.',
        );
    }
}
