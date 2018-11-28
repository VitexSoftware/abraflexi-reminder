<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ByEmail
 *
 * @author vitex
 */
class ByEmail extends \Ease\Sand
{
    /**
     *
     * @var boolean status 
     */
    public $result = null;
    /**
     * 
     * @param FlexiPeeHP\Reminder\Upominac $reminder
     * @param int                          $score     weeks of due
     * @param array                        $debts     array of debts by current customer
     */
    public function __construct($reminder, $score, $debts)
    {
        $result = false;
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
                    'hlavicka' => _('Vážený zákazníku'),
                    'uvod' => 'zasíláme vám přehled vašich závazků vůči nám:',
                    'textNad' => '',
                    'textPod' => '',
                    'zapati' => 'S přátelským pozdravem'
                ]);
        }
        if ($upominka->compile($reminder->customer, $debts)) {
            $result = $upominka->send();
//            file_put_contents('/var/tmp/upominka.html', $upominka->mailer->htmlBody);
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
