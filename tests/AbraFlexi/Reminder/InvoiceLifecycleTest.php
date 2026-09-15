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
 * Integration tests for the invoice reminder lifecycle against a live AbraFlexi instance.
 *
 * Requires ABRAFLEXI_URL/LOGIN/PASSWORD/COMPANY in the project .env pointing to the
 * test company (flexibee-dev.spoje.net, spoje_net_s_r_o_).
 *
 * Tests use customer TEST-CI-KLIENT created/cleaned by IntegrationTestCase.
 * MUTE=true is set by IntegrationTestCase so no real emails are sent.
 *
 * @no-named-arguments
 */
class InvoiceLifecycleTest extends IntegrationTestCase
{
    private static Upominac $upominac;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$upominac = new Upominac();
    }

    protected function setUp(): void
    {
        self::cleanupAllInvoices();
        self::setCustomerLabels([]);
    }

    /**
     * Customer with no overdue invoices → score 0, no reminder triggered.
     */
    public function testNoInvoicesScoreIsZero(): void
    {
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(0, $score, 'Customer with no invoices should have score 0');
    }

    /**
     * Invoice overdue 1 day, fresh customer (no labels) → score 1.
     */
    public function testFreshCustomerOneDayOverdueScoreOne(): void
    {
        self::createOverdueInvoice(daysOverdue: 1);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(1, $score, 'Invoice 1 day overdue without any UPOMINKA labels → score 1');
    }

    /**
     * Invoice overdue 15 days, no labels → still score 1 (must escalate through level 1 first).
     */
    public function testFreshCustomerFifteenDaysOverdueScoreOneNotThree(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(1, $score, 'Without UPOMINKA1 label, score cannot jump to 3');
    }

    /**
     * Invoice overdue 10 days, customer already has UPOMINKA1 → score 2.
     */
    public function testWithUpominka1TenDaysOverdueScoreTwo(): void
    {
        self::createOverdueInvoice(daysOverdue: 10);
        self::setCustomerLabels(['UPOMINKA1']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(2, $score, 'UPOMINKA1 set + 10 days overdue → score 2');
    }

    /**
     * Invoice overdue 15 days, customer has UPOMINKA1 but no UPOMINKA2 → score 2, not 3.
     */
    public function testWithUpominka1OnlyFifteenDaysOverdueScoreTwo(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        self::setCustomerLabels(['UPOMINKA1']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(2, $score, 'Without UPOMINKA2 label, score cannot reach 3');
    }

    /**
     * Invoice overdue 15 days, customer has both UPOMINKA1 and UPOMINKA2 → score 3.
     */
    public function testWithUpominka1And2FifteenDaysOverdueScoreThree(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        self::setCustomerLabels(['UPOMINKA1', 'UPOMINKA2']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(3, $score, 'UPOMINKA1+2 set + 15 days overdue → score 3');
    }

    /**
     * Customer with UPOMINKA1+2+3 and overdue invoice → score stays at 3 (already reminded).
     */
    public function testAllLabelsSetScoreThree(): void
    {
        self::createOverdueInvoice(daysOverdue: 15);
        self::setCustomerLabels(['UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(3, $score);
    }

    /**
     * All debts paid → enableCustomer() removes reminder labels, score drops to 0.
     */
    public function testPaidOffLabelsCleared(): void
    {
        self::setCustomerLabels(['UPOMINKA1', 'UPOMINKA2']);
        self::$upominac->enableCustomer('UPOMINKA1,UPOMINKA2', self::$customerId);

        $labels = self::getCustomerLabels();
        $this->assertArrayNotHasKey('UPOMINKA1', $labels);
        $this->assertArrayNotHasKey('UPOMINKA2', $labels);
    }

    /**
     * Customer with NEUPOMINAT label → score calculated but reminder suppressed in processUserDebts.
     */
    public function testNeupominatLabelScoreStillCalculated(): void
    {
        self::createOverdueInvoice(daysOverdue: 5);
        self::setCustomerLabels(['NEUPOMINAT']);
        $score = self::$upominac->getCustomerScore(self::$customerId);
        $this->assertSame(1, $score, 'Score is calculated independently of NEUPOMINAT label');
    }

    /**
     * A reminder actually sent (at least one notifier succeeded, MUTE=true routes the mail to
     * EASE_EMAILTO instead of skipping delivery) must be recorded back on the invoice as
     * datUp1.
     */
    public function testReminderSentWritesDatUp1(): void
    {
        $invoiceId = self::createOverdueInvoice(daysOverdue: 1);

        $clientDebts = self::$upominac->getEvidenceDebts('faktura-vydana', ['firma = '.self::$customerId]);
        $this->assertNotEmpty($clientDebts, 'Precondition: the freshly created overdue invoice must be found as a debt');

        $clientInfo = [
            'id' => self::$customerId,
            'kod' => self::$customerCode,
            'nazev' => 'CI Test Customer (auto-generated)',
            'stitky' => [],
        ];

        $report = self::$upominac->processUserDebts($clientInfo, $clientDebts);

        $this->assertSame(1, $report['reminderLevel'], 'Fresh customer, 1 day overdue → reminder level 1');
        $this->assertArrayHasKey('changed', $report, 'processUserDebts must report which date column it changed');
        $this->assertSame('datUp1', $report['changed']);
        $this->assertContains(
            'ByEmail',
            $report['notifiedVia'] ?? [],
            'Report must list which notifier(s) actually notified this customer',
        );

        self::$invoicer->dataReset();
        $refetched = self::$invoicer->getColumnsFromAbraFlexi(['datUp1'], ['id' => $invoiceId, 'limit' => 1]);
        $this->assertNotEmpty(
            $refetched[0]['datUp1'] ?? '',
            'datUp1 must be persisted on the invoice in AbraFlexi after a reminder was sent',
        );
    }

    /**
     * Regression test: when no notifier module actually delivers anything (here: customer has
     * no e-mail address, so ByEmail::compile() fails and every other module either skips or
     * only performs an unrelated action such as ByServiceToggle's disconnect), datUp1 must
     * NOT be written.
     *
     * Before the fix, processUserDebts() gated the date-write on
     * `if ($report['remindsSent'])`, which only checked that the *array* of per-notifier
     * results was non-empty — true as soon as any Notifier class is registered, regardless of
     * whether it actually reported success. That meant an invoice could be falsely marked as
     * "reminder sent" on a given date even though the customer never received anything. Live
     * verification against the dev instance confirmed this: with the old code the same
     * scenario below did write datUp1.
     */
    public function testNoNotifierSucceededDoesNotWriteDate(): void
    {
        $noEmailCode = 'TEST-CI-NOEMAIL';
        $adresar = new \AbraFlexi\Adresar();
        $existing = $adresar->getColumnsFromAbraFlexi(['id'], ['kod' => $noEmailCode, 'limit' => 1]);

        if (!empty($existing)) {
            $noEmailId = (int) $existing[0]['id'];
        } else {
            $adresar->dataReset();
            $adresar->insertToAbraFlexi([
                'kod' => $noEmailCode,
                'nazev' => 'CI Test Customer WITHOUT email',
            ]);
            $noEmailId = (int) $adresar->getLastInsertedId();
        }

        self::$invoicer->dataReset();
        self::$invoicer->insertToAbraFlexi([
            'firma' => \AbraFlexi\Code::ensure($noEmailCode),
            'rada' => 'code:FAKTURA-STANDARD',
            'typDokl' => 'code:FAKTURA',
            'datVyst' => (new \DateTime('-8 days'))->format('Y-m-d'),
            'datSplat' => (new \DateTime('-1 days'))->format('Y-m-d'),
            'bezPolozek' => true,
            'sumZklZakl' => 500.0,
            'popis' => 'CI integration test invoice (no-email customer)',
        ]);
        $invoiceId = (int) self::$invoicer->getLastInsertedId();

        try {
            $clientDebts = self::$upominac->getEvidenceDebts('faktura-vydana', ["firma = {$noEmailId}"]);
            $this->assertNotEmpty($clientDebts, 'Precondition: the freshly created overdue invoice must be found as a debt');

            $clientInfo = [
                'id' => $noEmailId,
                'kod' => $noEmailCode,
                'nazev' => 'CI Test Customer WITHOUT email',
                'stitky' => [],
            ];

            $report = self::$upominac->processUserDebts($clientInfo, $clientDebts);

            $this->assertArrayNotHasKey('changed', $report, 'No date column should be reported as changed when nothing was actually sent');
            $this->assertEmpty($report['notifiedVia'] ?? [], 'No notifier succeeded, so notifiedVia must be empty');

            self::$invoicer->dataReset();
            $refetched = self::$invoicer->getColumnsFromAbraFlexi(['datUp1'], ['id' => $invoiceId, 'limit' => 1]);
            $this->assertSame(
                '',
                (string) ($refetched[0]['datUp1'] ?? ''),
                'datUp1 must stay empty: no notifier actually delivered a reminder',
            );
        } finally {
            self::$invoicer->dataReset();
            self::$invoicer->setMyKey($invoiceId);

            try {
                self::$invoicer->deleteFromAbraFlexi();
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Cleanup all invoices created by individual test methods.
     * Called in setUp() to ensure a clean slate between tests.
     */
    private static function cleanupAllInvoices(): void
    {
        foreach (self::$invoiceIds as $id) {
            self::$invoicer->dataReset();
            self::$invoicer->setMyKey($id);

            try {
                self::$invoicer->deleteFromAbraFlexi();
            } catch (\Throwable) {
            }
        }

        self::$invoiceIds = [];
    }
}
