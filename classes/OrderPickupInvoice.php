<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file.
 * You are not authorized to modify, copy or redistribute this file.
 * Permissions are reserved by FME Modules.
 *
 *  @author    FMM Modules
 *  @copyright FME Modules 2020
 *  @license   Single domain
 */

class OrderPickupInvoice extends OrderInvoiceCore
{
    /**
     *
     *
     * @param $date_from
     * @param $date_to
     *
     * @return array collection of invoice
     */
    public static function getByDeliveryDateInterval($date_from, $date_to, $id_store = 0)
    {
        $order_invoice_list = Db::getInstance()->executeS('
            SELECT oi.*, sl.id_store
            FROM `' . _DB_PREFIX_ . 'order_invoice` oi
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = oi.`id_order`)
            LEFT JOIN `' . _DB_PREFIX_ . 'storepickup_cart` sl ON (sl.`id_order` = oi.`id_order`)
            WHERE DATE_ADD(oi.delivery_date, INTERVAL -1 DAY) <= \'' . pSQL($date_to) . '\'
            ' . (($id_store) ? ' AND sl.`id_store` = ' . (int) $id_store : '') . '
            AND oi.delivery_date >= \'' . pSQL($date_from) . '\'
            ' . Shop::addSqlRestriction(Shop::SHARE_ORDER, 'o') . '
            ORDER BY oi.delivery_date ASC
        ');

        return ObjectModel::hydrateCollection('OrderInvoice', $order_invoice_list);
    }
}
