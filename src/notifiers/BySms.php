<?php

/**
 * FlexiPeeHP - Remind by SMS class 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018 Spoje.Net, Vitex Software
 */
class BySms extends \Ease\Sand
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
        $result = false;
        parent::__construct();

        if (defined('SMS_ENGINE')) {
            if ($reminder->customer->adresar->getAnyPhoneNumber()) {
                $message = $this->compile($score, $reminder->customer, $debts);

                switch (constant('SMS_ENGINE')) {
                    case 'axfone':
                        $smsEngine = new \FlexiPeeHP\Reminder\SmsByAxfone($reminder->customer->adresar,
                            $message);
                        break;
                    case 'gnokii':
                        $smsEngine = new FlexiPeeHP\Reminder\SmsByGnokii($reminder->customer->adresar,
                            $message);
                        break;
                    case 'sshgnokii':
                        $smsEngine = new FlexiPeeHP\Reminder\SmsBySshGnokii($reminder->customer->adresar,
                            $message);
                        break;
                    default:
                        $smsEngine = null;
                        break;
                }

                if ($smsEngine) {



                    if ($smsEngine->sendMessage()) {
//            file_put_contents('/var/tmp/upominka.txt',$message);
                        if ($score && $result) {
                            setData(['id' => $reminder->customer->adresar->getRecordID(),
                                'stitky' => 'UPOMINKA'.$score], true);
                            $reminder->addStatusMessage(sprintf(_('Set Label %s '),
                                    'UPOMINKA'.$score),
                                $reminder->customer->adresar->sync() ? 'success'
                                        : 'error' );
                        }
                    } else {
                        $this->addStatusMessage(_('Remind was not sent'),
                            'warning');
                    }
                    $this->result = $result;
                }
            } else {
                $this->addStatusMessage(sprintf(_('Client %s without phone neumber %s !!!'),
                        $reminder->customer->adresar->getDataValue('nazev'),
                        $reminder->customer->adresar->getApiURL()), 'warning');
            }
        }
    }

    public function compile($score, $customer, $clientDebts)
    {
        $result = false;
        $nazev  = $customer->adresar->getDataValue('nazev');


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
                $upominka->loadTemplate('inventarizace');
        }



        $dnes    = new \DateTime();
        $subject = $upominka->getDataValue('hlavicka').' ke dni '.$dnes->format('d.m.Y');
        $heading = $upominka->getDataValue('uvod').' '.$nazev."\n".$upominka->getDataValue('textNad')."\n".\FlexiPeeHP\Reminder\Upominac::formatTotals(\FlexiPeeHP\Reminder\Upominka::getSums($clientDebts));
        $result  = $subject.':'.$heading;


        return $result;
    }
}
