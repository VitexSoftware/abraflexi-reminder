<?php

declare(strict_types=1);
/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * 
 *
 * @author     Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2023 Vitex Software
 */

namespace AbraFlexi\Reminder\Notifier;

/**
 * Description of ByDatovka
 *
 * @author vitex
 */
class ByDatovka extends \Defr\CzechDataBox\DataBox implements \AbraFlexi\Reminder\notifier
{

    /**
     * Compile Reminder message with its contents
     *
     * @param int                         $score        Weeks after due date
     * @param Customer $customer
     * @param array                       $clientDebts
     * 
     * @return boolean
     */
    public function compile($score, $customer, $clientDebts)
    {
        
    }
}