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

class Pickup extends ObjectModel
{
    /**
     * load all stores
     * @return array
     */
    public static function loadAllStores()
    {
        return Db::getInstance()->executeS('
            SELECT s.*, cl.name country, st.iso_code state
            FROM ' . _DB_PREFIX_ . 'store s
            ' . Shop::addSqlAssociation('store', 's') . '
            LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
            LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
            WHERE s.active = 1
            AND cl.id_lang = ' . (int) Context::getContext()->language->id);
    }

    /**
     * get pick up stores list
     * @param integer $id_lang
     * @return array
     */
    public static function getPickupStores($id_lang = 0)
    {
        if (!$id_lang) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        $sql = new DbQuery();
        $sql->select('s.*, st.iso_code');
        $sql->from('store', 's');
        $sql->leftJoin('state', 'st', 's.id_state = st.id_state');
        $sql->where('s.`active` = 1');
        $sql->orderBy('s.id_store');

        if (true === Tools::version_compare(_PS_VERSION_, '1.7.3.0', '>=')) {
            $sql->select('sl.*');
            $sql->leftJoin('store_lang', 'sl', 'sl.id_store = s.id_store AND sl.id_lang = ' . (int) $id_lang);
        }
        return Db::getInstance()->executeS($sql);
    }

    /**
     * getting store data
     * @param int   $id_cart
     * @return array
     */
    public static function getStoreByCart($id_cart)
    {
        if (!$id_cart) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('storepickup_cart');
        $sql->where('id_cart = ' . (int) $id_cart);
        return Db::getInstance()->getRow($sql);
    }

    /**
     * add store address
     * @param array $data
     * @return bool
     */
    public static function addStoreAddress($data)
    {
        if (!isset($data) && !isset($data['id_store']) && !isset($data['id_address'])) {
            return false;
        }

        return (bool) Db::getInstance()->insert(
            'storepickup_address',
            $data,
            false,
            false,
            Db::ON_DUPLICATE_KEY
        );
    }

    /**
     * push store data to cart
     * @param array $data
     * @return bool
     */
    public static function pushStore($data)
    {
        if (!isset($data) && !isset($data['id_store']) && !isset($data['id_cart'])) {
            return false;
        }

        return (bool) Db::getInstance()->insert(
            'storepickup_cart',
            $data,
            false,
            false,
            Db::ON_DUPLICATE_KEY
        );
    }

    /**
     * getting store id_address
     * @param int   $id_store
     * @return bool|int
     */
    public static function getStoreAddressId($id_store)
    {
        if (!$id_store) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('id_address');
        $sql->from('storepickup_address');
        $sql->where('id_store = ' . (int) $id_store);
        return (int) Db::getInstance()->getValue($sql);
    }

    /**
     * update cart store
     * @param int $id_cart
     * @param array $data
     * @return bool
     */
    public static function updateStoreByCart($id_cart, $data)
    {
        if (!$id_cart) {
            return false;
        }

        return (bool) Db::getInstance()->update(
            'storepickup_cart',
            $data,
            'id_cart = ' . (int) $id_cart
        );
    }

    /**
     * remove pickup store from cart association
     * @param int $id_cart
     * @return bool
     */
    public static function popStore($id_cart)
    {
        if (!$id_cart) {
            return false;
        }

        return (bool) Db::getInstance()->delete(
            'storepickup_cart',
            'id_cart = ' . (int) $id_cart
        );
    }

    /**
     * getting store id
     * @param int   $id_order
     * @return bool|int
     */
    public static function getIdStoreByOrder($id_order)
    {
        if (!$id_order) {
            return false;
        }

        $sql = new DbQuery();
        $sql->select('id_store');
        $sql->from('storepickup_cart');
        $sql->where('id_order = ' . (int) $id_order);
        return (int) Db::getInstance()->getValue($sql);
    }

    public static function getStores($distance = 50, $all = true)
    {
        $distanceUnit = Configuration::get('PS_DISTANCE_UNIT');
        if (!in_array($distanceUnit, array('km', 'mi'))) {
            $distanceUnit = 'km';
        }

        if ($all && true === Tools::version_compare(_PS_VERSION_, '1.7.3.0', '<')) {
            return Db::getInstance()->executeS('
                SELECT s.*,
                cl.name country,
                st.iso_code state
                FROM ' . _DB_PREFIX_ . 'store s
                ' . Shop::addSqlAssociation('store', 's') . '
                LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
                LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
                WHERE s.active = 1 AND cl.id_lang = ' . (int) Context::getContext()->language->id);
        } elseif ($all && true === Tools::version_compare(_PS_VERSION_, '1.7.3.0', '>=')) {
            return Db::getInstance()->executeS('
                SELECT s.*,
                cl.name country,
                st.iso_code state,
                sl.name, sl.address1, sl.address2, sl.hours, sl.note
                FROM ' . _DB_PREFIX_ . 'store s
                ' . Shop::addSqlAssociation('store', 's') . '
                LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
                LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
                LEFT JOIN ' . _DB_PREFIX_ . 'store_lang sl ON (s.id_store = sl.id_store)
                WHERE s.active = 1 AND cl.id_lang = ' . (int) Context::getContext()->language->id . '
                AND sl.id_lang = ' . (int) Context::getContext()->language->id);
        } elseif (!$all && true === Tools::version_compare(_PS_VERSION_, '1.7.3.0', '<')) {
            $multiplicator = ($distanceUnit == 'km' ? 6371 : 3959);
            return Db::getInstance()->executeS('
                SELECT s.*, cl.name country, st.iso_code state,
                (' . (int) ($multiplicator) . '
                    * acos(
                        cos(radians(' . (float) (Tools::getValue('latitude')) . '))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(' . (float) (Tools::getValue('longitude')) . '))
                        + sin(radians(' . (float) (Tools::getValue('latitude')) . '))
                        * sin(radians(latitude))
                    )
                ) distance,
                cl.id_country id_country
                FROM ' . _DB_PREFIX_ . 'store s
                ' . Shop::addSqlAssociation('store', 's') . '
                LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
                LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
                WHERE s.active = 1 AND cl.id_lang = ' . (int) Context::getContext()->language->id . '
                HAVING distance < ' . (int) ($distance) . '
                ORDER BY distance ASC
                LIMIT 0, 20');
        } elseif (!$all && true === Tools::version_compare(_PS_VERSION_, '1.7.3.0', '>=')) {
            $multiplicator = ($distanceUnit == 'km' ? 6371 : 3959);
            return Db::getInstance()->executeS('
                SELECT s.*, sl.*, cl.name country, st.iso_code state,
                (' . (int) ($multiplicator) . '
                    * acos(
                        cos(radians(' . (float) (Tools::getValue('latitude')) . '))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(' . (float) (Tools::getValue('longitude')) . '))
                        + sin(radians(' . (float) (Tools::getValue('latitude')) . '))
                        * sin(radians(latitude))
                    )
                ) distance,
                cl.id_country id_country
                FROM ' . _DB_PREFIX_ . 'store s
                ' . Shop::addSqlAssociation('store', 's') . '
                LEFT JOIN ' . _DB_PREFIX_ . 'country_lang cl ON (cl.id_country = s.id_country)
                LEFT JOIN ' . _DB_PREFIX_ . 'state st ON (st.id_state = s.id_state)
                LEFT JOIN ' . _DB_PREFIX_ . 'store_lang sl ON (sl.id_store = s.id_store AND sl.id_lang = ' . (int) Context::getContext()->language->id . ')
                WHERE s.active = 1 AND
                cl.id_lang = ' . (int) Context::getContext()->language->id . '
                HAVING distance < ' . (int) ($distance) . '
                ORDER BY distance ASC
                LIMIT 0,20');
        }
    }

    public static function processStoreAddress($store)
    {
        $ignoreField = array(
            'firstname',
            'lastname',
        );

        $outDatas = array();

        $addressDatas = AddressFormat::getOrderedAddressFields($store['id_country'], false, true);
        $state = (isset($store['id_state'])) ? new State($store['id_state']) : null;

        foreach ($addressDatas as $dataLine) {
            $dataFields = explode(' ', $dataLine);
            $addrOut = array();

            $dataFieldsMod = false;
            foreach ($dataFields as $fieldItem) {
                $fieldItem = trim($fieldItem);
                if (!in_array($fieldItem, $ignoreField) && !empty($store[$fieldItem])) {
                    $addrOut[] = ('city' == $fieldItem && $state && isset($state->iso_code) && Tools::strlen($state->iso_code)) ?
                    $store[$fieldItem] . ', ' . $state->iso_code : $store[$fieldItem];
                    $dataFieldsMod = true;
                }
            }
            if ($dataFieldsMod) {
                $outDatas[] = implode(' ', $addrOut);
            }
        }

        $out = implode('<br />', $outDatas);
        return $out;
    }

    public static function updateCustomizationAddress($id_cart, $data)
    {
        if (!$id_cart) {
            return false;
        }

        return (bool) Db::getInstance()->update(
            'customization',
            $data,
            'id_cart = ' . (int) $id_cart
        );
    }

    /**
     * getting store data
     * @param int   $id_order
     * @return array
     */
    public static function getStoreByOrder($id_order, $id_lang = null)
    {
        if (!$id_order) {
            return false;
        }

        if (!$id_lang) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('storepickup_cart');
        $sql->where('id_order = ' . (int) $id_order);

        $pickup = Db::getInstance()->getRow($sql);
        if (isset($pickup) &&
            $pickup &&
            isset($pickup['id_store']) &&
            $pickup['id_store'] &&
            Validate::isLoadedObject($store = new Store($pickup['id_store'], $id_lang))) {
            $id_address = self::getStoreAddressId($store->id);
            if ($id_address && Validate::isLoadedObject($address = new Address($id_address))) {
                $pickup['store_name'] = $store->name;
                $st_address = AddressFormat::generateAddress($address, array(), ' ', ' ');
                $st_address = str_replace($store->name, '', $st_address);
                $st_address = str_replace($address->firstname . ' ' . $address->lastname, '', $st_address);
                $pickup['store_address'] = ltrim($st_address);
            }
        }
        return $pickup;
    }

    public static function getAllStores($id_lang = 0)
    {
        if (!$id_lang) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        $sql = new DbQuery();
        $sql->select('t.*,s.iso_code');
        $sql->from('store', 't');
        $sql->leftJoin('state', 's', 's.id_state = t.id_state');
        $sql->where('t.`active` = 1');
        $sql->orderBy('t.id_store');

        if (true === Tools::version_compare(_PS_VERSION_, '1.7.3.0', '>=')) {
            $sql->select('tl.*');
            $sql->leftJoin('store_lang', 'tl', 'tl.id_store = t.id_store AND tl.id_lang = ' . (int) $id_lang);
        }
        return Db::getInstance()->executeS($sql);
    }

    public static function getAllStoreAddresses()
    {
        $sql = new DbQuery();
        $sql->select('id_address');
        $sql->from('storepickup_address');

        $result = Db::getInstance()->executeS($sql);

        $stores = array();
        if (isset($result) && $result) {
            foreach ($result as $st) {
                $stores[] = (int)$st['id_address'];
            }
        }
        return $stores;
    }

    public static function getStoreOrders($id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = (int) Context::getContext()->language->id;
        }

        $sql = 'SELECT sc.`id_store`, sc.`pickup_date`, sc.`store_alert`, sc.`customer_alert`,
        o.*, o.`id_order` AS id_pdf,
        CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
        osl.`name` AS `osname`,
        os.`color`,
        IF((
            SELECT so.`id_order` FROM `' . _DB_PREFIX_ . 'orders` so
            WHERE so.`id_customer` = o.`id_customer`
            AND so.`id_order` < o.`id_order` LIMIT 1) > 0, 0, 1
        ) as new,
        IF(o.valid, 1, 0) badge_success';

        $sql .= '
        FROM `' . _DB_PREFIX_ . 'storepickup_cart` sc
        LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (sc.id_order = o.id_order)
        LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = o.`id_customer`)
        LEFT JOIN `' . _DB_PREFIX_ . 'address` address ON address.id_address = o.id_address_delivery
        LEFT JOIN `' . _DB_PREFIX_ . 'order_state` os ON (os.`id_order_state` = o.`current_state`)
        LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl
            ON (os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int) $id_lang . ')';
        $sql .= '
        WHERE sc.id_order > 0';
        $sql .= '
        ORDER BY o.id_order DESC';

        $orders = Db::getInstance()->executeS($sql);

        if (isset($orders) && $orders) {
            foreach ($orders as &$order) {
                $order['store_name'] = null;
                $order['store_email'] = null;
                $order['store_address'] = null;

                if ($order['id_store'] && Validate::isLoadedObject($store = new Store($order['id_store'], $id_lang))) {
                    $order['store_name'] = $store->name;
                    $order['store_email'] = $store->email;
                    $id_address = self::getStoreAddressId($store->id);

                    if ($id_address && Validate::isLoadedObject($address = new Address($id_address))) {
                        $st_address = AddressFormat::generateAddress($address, array(), ' ', ' ');
                        $st_address = str_replace($store->name, '', $st_address);
                        $st_address = str_replace($address->firstname . ' ' . $address->lastname, '', $st_address);
                        $order['store_address'] = ltrim($st_address);
                    }
                }
            }
        }

        return $orders;
    }
}
