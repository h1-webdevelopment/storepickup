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

class StorepickupPickupModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();
        if (!extension_loaded('Dom')) {
            $this->errors[] = Tools::displayError('PHP "Dom" extension has not been loaded.');
            $this->context->smarty->assign('errors', $this->errors);
        }
        $this->context = Context::getContext();
    }

    protected function displayAjax()
    {
        $days = array();
        $stores = Pickup::getStores();
        $dom = new DOMDocument('1.0');
        $node = $dom->createElement('markers');
        $parentNode = $dom->appendChild($node);

        $days[1] = $this->module->translations['monday'];
        $days[2] = $this->module->translations['tuesday'];
        $days[3] = $this->module->translations['wednesday'];
        $days[4] = $this->module->translations['thursday'];
        $days[5] = $this->module->translations['friday'];
        $days[6] = $this->module->translations['saturday'];
        $days[7] = $this->module->translations['sunday'];

        if (isset($stores) && $stores) {
            foreach ($stores as $store) {
                $other = '';
                $node = $dom->createElement('marker');
                $newnode = $parentNode->appendChild($node);
                $newnode->setAttribute('name', $store['name']);
                $address = Pickup::processStoreAddress($store);

                $other .= $this->module->renderStoreWorkingHours($store);
                $newnode->setAttribute('addressNoHtml', strip_tags(str_replace('<br />', ' ', $address)));
                $newnode->setAttribute('address', $address);
                $newnode->setAttribute('other', $other);
                $newnode->setAttribute('phone', $store['phone']);
                $newnode->setAttribute('fax', $store['fax']);
                $newnode->setAttribute('email', $store['email']);
                $newnode->setAttribute('note', $store['note']);
                $newnode->setAttribute('id_store', (int) ($store['id_store']));
                $newnode->setAttribute('has_store_picture', file_exists(_PS_STORE_IMG_DIR_ . (int) ($store['id_store']) . '.jpg'));
                $newnode->setAttribute('lat', (float) ($store['latitude']));
                $newnode->setAttribute('lng', (float) ($store['longitude']));
                if (isset($store['distance'])) {
                    $newnode->setAttribute('distance', (int) ($store['distance']));
                }
            }
        }
        header('Content-type: text/xml');
        die($dom->saveXML());
    }

    /**
     * get available stores for map
     * @return json
     */
    public function displayAjaxGetMapStores()
    {
        die(Tools::jsonEncode(array(
            'success' => true,
            'html' => $this->module->getMapPickupStores(),
        )));
    }

    /**
     * save selected pickup store
     * @return json
     */
    public function displayAjaxSelectStore()
    {
        $response = array('success' => false, 'hasError' => false, 'msg' => '');
        if (isset($this->context->cart) && $this->context->cart) {
            $storeCarrier = (int) Configuration::get(
                'STOREPICKUP_DEFAULT_CARRIER',
                false,
                $this->context->shop->id_shop_group,
                $this->context->shop->id
            );

            if ($storeCarrier != $this->context->cart->id_carrier) {
                Pickup::popStore($this->context->cart->id);
            } else {
                // set default store as pickup if no selection has been made by user
                //$pickup_date = Tools::getValue('pickup_date', '');
                $id_store = (int) Tools::getValue('id_store');
                if (!$id_store) {
                    $id_store = (int) Configuration::get(
                        'STOREPICKUP_DEFAULT_STORE',
                        false,
                        $this->context->shop->id_shop_group,
                        $this->context->shop->id
                    );
                }

                if ($id_store && $store = new Store($id_store, $this->context->language->id)) {
                    $id_address = (int) Pickup::getStoreAddressId($id_store);

                    if (!$id_address || !Validate::isLoadedObject($address = new Address((int) $id_address))) {
                        $address = new Address();
                        $address->id_customer = null;
                        $address->id_supplier = null;
                        $address->id_warehouse = null;
                        $address->id_manufacturer = null;
                        $address->alias = sprintf('Store_Pickup_%s', $id_store);
                        $address->firstname = 'Store';
                        $address->lastname = 'Pickup';
                        $address->id_country = $store->id_country;
                        $address->id_state = $store->id_state;
                        $address->company = $store->name;
                        $address->address1 = $store->address1;
                        $address->address2 = $store->address2;
                        $address->postcode = $store->postcode;
                        $address->city = $store->city;
                        $address->phone = $store->phone;
                        $address->other = $store->note;

                        if ($address->save() &&
                            Pickup::addStoreAddress(array('id_store' => (int) $store->id, 'id_address' => (int) $address->id))) {
                            $id_address = (int) Pickup::getStoreAddressId($id_store);
                        }
                    }

                    $data = array(
                        'id_store' => (int) $id_store,
                        //'pickup_date' => pSQL($pickup_date),
                        'id_cart' => (int) $this->context->cart->id,
                        'id_carrier' => (int) $this->context->cart->id_carrier,
                    );

                    if ($id_address && Pickup::pushStore($data)) {
                        $response['success'] = true;
                        $response['msg'] = $this->module->translations['store_selection_success'];
                    }
                }
            }
        }
        die(Tools::jsonEncode($response));
    }

    public function displayAjaxGetPickupStoreDates()
    {
        $id_lang = (int) Context::getContext()->language->id;
        $id_store = (int) Tools::getValue('id_store', $this->module->getDefaultStore());
        if (!$id_store) {
            $id_store = (int) Configuration::get(
                'STOREPICKUP_DEFAULT_STORE',
                false,
                $this->context->shop->id_shop_group,
                $this->context->shop->id
            );
        }

        // current time zone
        $currentZone = Configuration::get('PS_TIMEZONE');
        $zoneTime = new DateTime(date('Y-m-d'), new DateTimeZone($currentZone));

        $year = $zoneTime->format('Y');
        // current month
        $month = $zoneTime->format('m');

        // next month
        $month += 1;

        // get last day of current month
        $lastday = (int)(strftime('%d', mktime(0, 0, 0, ($month >= 12 ? 1 : $month + 1), 0, ($month >= 12 ? $year + 1 : $year))));
        // generate date for last day of next month
        $lastDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($lastday, 2, '0', STR_PAD_LEFT);

        $wk = array();
        $dates = array('success' => true, 'disabled' => '');
        // gettings weekdays
        $weekdays = array_keys($this->module->weekdays);

        $disabled = array();
        if ($id_store && Validate::isLoadedObject($store = new Store($id_store, $id_lang))) {
            // set selected store
            Pickup::updateStoreByCart($this->context->cart->id, array('id_store' => (int) $id_store));

            // getting store hours
            $storeHours = (true === Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=')) ? Tools::jsonDecode($store->hours) : unserialize($store->hours);

            if (isset($storeHours) && $storeHours) {
                foreach ($storeHours as $key => $hour) {
                    if (isset($hour) && $hour) {
                        // separating opening and closong hours
                        $h = (true === Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=')) ? explode('-', trim($hour[0])) : explode('-', trim($hour));

                        // disable days of weeks, if no valid opening/closing time is set
                        if ((!isset($h[0]) || false === strtotime($h[0])) && (!isset($h[1]) || false === strtotime($h[1]))) {
                            $endDate = strtotime($lastDate);
                            $startDate = $zoneTime->format('Y-m-d');

                            $start = strtotime(Tools::ucfirst($weekdays[$key]), strtotime($startDate));
                            for (; $start <= $endDate; $start = strtotime('+1 week', $start)) {
                                $disabled[] = date('Y-m-d', $start);
                            }
                        }

                        $index = $key + 1;
                        // setting sunday as starting weekday for js calander (sunday = 0, monday = 1 and so on)
                        $wk[($index > 6) ? 0 : $index] = array(
                            'minTime' => (!isset($h[0]) || false === strtotime($h[0])) ? false : date("H:i", strtotime($h[0])),
                            'maxTime' => (!isset($h[1]) || false === strtotime($h[1])) ? false : date("H:i", strtotime($h[1])),
                            'defaultHour' => (int) (!isset($h[0]) || false === strtotime($h[0])) ? false : date("H", strtotime($h[0])),
                            'defaultMinute' => (int) (!isset($h[0]) || false === strtotime($h[0])) ? false : date("i", strtotime($h[0])),
                        );
                    }
                }
            }

            ksort($wk);
            $dates['timeslot'] = $wk;
            if (isset($disabled) && $disabled) {
                $dates['disabled'] = implode(',', $disabled);
            }
        }

        die(Tools::jsonEncode($dates));
    }

    /**
     * save pickup data
     * @return json
     */
    public function displayAjaxSavePickup()
    {
        $id_store = (int) Tools::getValue('id_store');
        $pickupTime = Tools::safeOutput(Tools::getValue('pickupTime'));
        $pickupDate = Tools::safeOutput(Tools::getValue('pickupDate'));

        $pickupDateTime = date('Y-m-d H:i:s', strtotime($pickupDate . (!empty($pickupTime) ? ' ' . $pickupTime : '')));

        $response = array('hasError' => true, 'msg' => $this->module->translations['store_page_error_label']);
        if ($id_store && Validate::isLoadedObject($store = new Store($id_store, $this->context->cart->id_lang))) {
            if (!$store->active) {
                $response['msg'] = $this->module->translations['store_inactive'];
            } elseif (empty($pickupDateTime) || !Validate::isDate($pickupDateTime)) {
                $response['msg'] = $this->module->translations['invalid_pickup_date'];
            } else {
                $data = array(
                    'id_store' => (int) $id_store,
                    'pickup_date' => pSQL($pickupDateTime),
                );

                if (false === Pickup::updateStoreByCart($this->context->cart->id, $data)) {
                    $response['msg'] = $this->module->translations['saved_pickup_date_error'];
                } else {
                    $response['hasError'] = false;
                    $response['msg'] = $this->module->translations['saved_pickup_date'];
                }
            }
        }
        die(Tools::jsonEncode($response));
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->module->setMediaFiles();
    }
}
