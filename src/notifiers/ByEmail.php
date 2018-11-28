<?php

/**
 * FlexiPeeHP - Remind by eMail class 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net
 */
class ByEmail extends \Ease\Sand
{
    /**
     *
     * @var boolean status 
     */
    public $result = null;

    /**
     * eMail notification
     * 
     * @param FlexiPeeHP\Reminder\Upominac $reminder
     * @param int                          $score     weeks of due
     * @param array                        $debts     array of debts by current customer
     */
    public function __construct($reminder, $score, $debts)
    {
        $result   = false;
        parent::__construct();
        $upominka = new \FlexiPeeHP\Reminder\Upominka();
        switch ($score) {
            case 1:
                $upominka->loadTemplate('prvniUpominka');
                break;
            case 2:
                $upominka->loadTemplate('druhaUpominka');
                break;
            case 3:
                $upominka->loadTemplate('pokusOSmir');
                break;
            default :
// ⚠ No permission
//                $configurator = new \FlexiPeeHP\Nastaveni();
//                $ourCompanyInfo = $configurator->getFlexiData(1);
                $upominka->setData([
                    'hlavicka' => _('Dear customer').',',
                    'uvod' => _('we are sending you an overview of your commitments to us').':',
                    'textNad' => '',
                    'textPod' => '',
                    'zapati' => _('Sincerely')
                ]);
        }
        if ($upominka->compile($reminder->customer, $debts)) {
            $result = $upominka->send();
//            file_put_contents('/var/tmp/upominka.html',                $upominka->mailer->htmlDocument);
            if ($score && $result) {
                $reminder->customer->adresar->setData(['id' => $reminder->customer->adresar->getRecordID(),
                    'stitky' => 'UPOMINKA'.$score], true);
                $reminder->addStatusMessage(sprintf(_('Set Label %s '),
                        'UPOMINKA'.$score),
                    $reminder->customer->adresar->sync() ? 'success' : 'error' );
            }
        } else {
            $this->addStatusMessage(_('Remind was not sent'), 'warning');
        }
        $this->result = $result;
    }
}
