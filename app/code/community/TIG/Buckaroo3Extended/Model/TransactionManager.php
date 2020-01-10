<?php
/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

class TIG_Buckaroo3Extended_Model_TransactionManager extends Mage_Core_Model_Abstract
{

    /**
     * @var array
     */
    protected $transactionArray = [
        'transaction'     => [],
        'history'         => [],
        'total_debit'     => 0.00,
        'total_credit'    => 0.00
    ];

    /**
     * TransactionManager constructor.
     * @param null $array
     */
    public function _construct($array = null)
    {
        $this->_init('buckaroo3extended/transactionManager');

        if (isset($array) && is_array($array) && count($array) > 0) {
            $this->setTransactionArray($array);
        }
    }

    /**
     * @param $array
     */
    public function setTransactionArray($array)
    {
        if (isset($array) && is_array($array) && count($array) > 0) {
            $this->transactionArray = $array;
        }
    }

    /**
     * @return array
     */
    public function getTransactionArray()
    {
        return $this->transactionArray;
    }

    /**
     * @return bool|mixed
     */
    public function getPossibleRefundAmount()
    {
        if (isset($this->transactionArray['total_debit']) &&
            $this->transactionArray['total_debit'] > 0
        ) {
            return $this->transactionArray['total_debit'] - $this->transactionArray['total_credit'];
        }

        return false;
    }

    /**
     * @param float $amount
     * @return array|bool
     */
    public function refundTransaction($amount = 0.00)
    {
        //possible
        $possibleRefundAmount = $this->getPossibleRefundAmount();

        if ($possibleRefundAmount == false ||
            round($amount,4) > round($possibleRefundAmount, 4)
        ) {
            return false;
        }

        //suggested
        $calculatedTransactions = $this->calculateRefundTransaction($amount);

        return $calculatedTransactions;
    }

    /**
     * @param $transactionKey
     * @param $amount
     * @param $type
     * @param $status
     */
    public function addHistory($transactionKey, $amount, $type, $status)
    {
        $this->transactionArray['history'][] = ['transaction_key' => $transactionKey,
            'refund_amount' => $amount,
            'type' => $type,
            'status' => $status];
    }

    /**
     *  in = add amount to transaction (debit)
     *  out = refund amount from transaction (credit)
     *
     * @param $inOut
     * @param $transactionkey
     * @param $amount
     * @param null $type
     * @return array
     */
    public function addTransaction($inOut, $transactionkey, $amount, $type = null)
    {
        $amount = round($amount,2);

        if ($type) {
            $this->transactionArray['transaction'][$transactionkey]['type'] = $type;
        }

        if ($inOut == 'in') {
            $this->transactionArray['transaction'][$transactionkey]['amount'] = $amount;
        } else {
            //add refund
            if (!isset($this->transactionArray['transaction'][$transactionkey]['refunded'])) {
                $this->transactionArray['transaction'][$transactionkey]['refunded'] = $amount;
            }
            else {
                $this->transactionArray['transaction'][$transactionkey]['refunded'] += $amount;
            }
        }

        $this->transactionArray['total_'. $inOut] += $amount;

        return $this->transactionArray;
    }

    /**
     * @param $transactionKey
     * @param $amount
     * @param null $type
     * @return array
     */
    public function addDebitTransaction($transactionKey, $amount, $type = null)
    {
        $amount = round($amount,2);

        if ($type) {
            $this->transactionArray['transaction'][$transactionKey]['type'] = $type;
        }

        $this->transactionArray['transaction'][$transactionKey]['amount'] = $amount;

        $this->transactionArray['total_debit'] += $amount;

        return $this->transactionArray;
    }

    /**
     * @param $transactionKey
     * @param $amount
     * @param null $type
     * @return array
     */
    public function addCreditTransaction($transactionKey, $amount, $type = null)
    {
        $amount = round($amount,2);

        if ($type) {
            $this->transactionArray['transaction'][$transactionKey]['type'] = $type;
        }

        if (!isset($this->transactionArray['transaction'][$transactionKey]['refunded'])) {
            $this->transactionArray['transaction'][$transactionKey]['refunded'] = $amount;
        }
        else {
            $this->transactionArray['transaction'][$transactionKey]['refunded'] += $amount;
        }

        $this->transactionArray['total_credit'] += $amount;

        return $this->transactionArray;
    }

    /**
     * @param float $refundRequestAmount
     * @return array
     */
    protected function calculateRefundTransaction($refundRequestAmount = 0.00)
    {
        $calculatedRefundTransactions = [];

        // loop through transactions, most recent first
        foreach (array_reverse($this->transactionArray['transaction']) as $transactionKey => $transactionValue ) {

            //no amount, should not happen
            if (!isset($transactionValue['amount']) ||
                $transactionValue['amount'] == 0
            ) {
                Mage::log('TransactionManager Transaction had no amount or amount of 0.00 for transaction: ' . $transactionValue['transactionkey']);
                continue;
            }

            //already fully refundend
            if (isset($transactionValue['refunded']) &&
                round($transactionValue['refunded'], 4) >= round($transactionValue['amount'], 4)
               ) {
                continue;
            }

            //not refunded amount = amount - already refunded
            $notRefundedAmount = $transactionValue['amount'];
            if (isset($transactionValue['refunded'])) {
                $notRefundedAmount = $transactionValue['amount'] - $transactionValue['refunded'];
            }

            //fits within this refund total which is left on the transaction
            if ($refundRequestAmount <= $notRefundedAmount) {
                $calculatedRefundTransactions[$transactionKey] = $refundRequestAmount;
                break;
            }

            //decrease wanted amount and do next loop
            if ($refundRequestAmount > $notRefundedAmount) {
                $calculatedRefundTransactions[$transactionKey] = $notRefundedAmount;
                $refundRequestAmount -= $notRefundedAmount;
            }
        }

        return $calculatedRefundTransactions;
    }
}
