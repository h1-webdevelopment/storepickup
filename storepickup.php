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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'storepickup/classes/Pickup.php';

require_once _PS_MODULE_DIR_ . 'storepickup/classes/OrderPickupInvoice.php';

require_once _PS_MODULE_DIR_ . 'storepickup/classes/AdminPcikupSlips.php';

class Storepickup extends Module
{
    public $weekdays = array();

    public $map_themes = array();

    public $translations = array();

    protected $id_shop = null;

    protected $id_shop_group = null;

    protected $pickupTabs = array(
        //'AdminPickupStore',
        'AdminPickupSettings',
        'AdminPickupStoreParent',
        'AdminPickupSlip',
    );

    protected $pickupHooks = array(
        'ModuleRoutes',
        'displayHeader',
        'displayAdminOrder',
        'displayOrderDetail',
        'actionValidateOrder',
        'displayPDFDeliverySlip',
        'displayBackOfficeHeader',
        'actionOrderStatusPostUpdate',
    );

    public function __construct()
    {
        $this->name = 'storepickup';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'FMM Modules';
        $this->need_instance = 0;

        $this->module_key = 'a1f140f08222d229c436d9de77b3cf6e';
        $this->author_address = '0xcC5e76A6182fa47eD831E43d80Cd0985a14BB095';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pick up from Store');
        $this->description = $this->l('Allow your customers to pick up from your store.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall my module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->weekdays = $this->getWeekDays();

        $this->translations = $this->getTranslateableFields() + $this->storeTranslations();

        if ($this->id_shop === null || !Shop::isFeatureActive()) {
            $this->id_shop = Shop::getContextShopID();
        } else {
            $this->id_shop = $this->context->shop->id;
        }
        if ($this->id_shop_group === null || !Shop::isFeatureActive()) {
            $this->id_shop_group = Shop::getContextShopGroupID();
        } else {
            $this->id_shop_group = $this->context->shop->id_shop_group;
        }
    }

    /**
     * install module settings
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        include dirname(__FILE__) . '/sql/install.php';

        if (parent::install() &&
            $this->setConfigurations() &&
            $this->registerHook($this->pickupHooks) &&
            $this->installTab('AdminPickupStoreParent', 'Store Pickup', 'person_pin_circle') &&
            //$this->installTab('AdminPickupStore', 'Stores', 'store', 'AdminPickupStoreParent') &&
            $this->installTab('AdminPickupSlip', 'Pickup Slip', 'receipt', 'AdminPickupStoreParent') &&
            $this->installTab('AdminPickupSettings', 'Settings', 'settings', 'AdminPickupStoreParent')) {
            return true;
        }
        return false;
    }

    public function uninstall()
    {
        if (parent::uninstall() &&
            $this->uninstallTab() &&
            $this->unsetConfigurations()) {
            if ($this->removeStoreAddresses()) {
                include dirname(__FILE__) . '/sql/uninstall.php';
                return true;
            }
        }
        return false;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitStorepickupModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
    }

    /**
     * render config form.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStorepickupModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * configuration form.
     */
    protected function getConfigForm()
    {
        $stores = array();
        $carriers = array();
        $stores = Pickup::getPickupStores();
        $carriers = Carrier::getCarriers($this->context->language->id);

        if (!isset($stores) || !$stores) {
            $stores = array(
                array(
                    'id_store' => 0,
                    'name' => $this->l('None'),
                ),
            );
        }

        if (!isset($carriers) || !$carriers) {
            $carriers = array(
                array(
                    'id_carrier' => 0,
                    'name' => $this->l('No carrier found'),
                ),
            );
        }

        $radio = (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '>=') == true) ? 'switch' : 'radio';
        if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '<') == true) {
            $image_url = _PS_IMG_DIR_ . 'logo_stores.gif';
            $image_url = ImageManager::thumbnail($image_url, 'logo_stores.gif', 30, 'gif', true, false);
        } else {
            $image_url = _PS_IMG_DIR_ . 'logo_stores.png';
            $image_url = ImageManager::thumbnail($image_url, 'logo_stores.png', 30, 'png', true, false);
        }

        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'tabs' => array(
                    'general' => $this->l('General'),
                    'map_settings' => $this->l('Map Settings'),
                    'store_settings' => $this->l('Store Settings'),
                    'time_settings' => $this->l('Pick Up Settings'),
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->l('Default Store'),
                        'name' => 'STOREPICKUP_DEFAULT_STORE',
                        'options' => array(
                            'query' => $stores,
                            'id' => 'id_store',
                            'name' => 'name',
                        ),
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Carrier'),
                        'name' => 'STOREPICKUP_DEFAULT_CARRIER',
                        'options' => array(
                            'query' => $carriers,
                            'id' => 'id_carrier',
                            'name' => 'name',
                        ),
                        'tab' => 'general',
                    ),
                    array(
                        'type' => 'text',
                        'lang' => false,
                        'label' => $this->l('Google API Key'),
                        'name' => 'STOREPICKUP_KEY',
                        'required' => true,
                        'hint' => $this->l('See instructions in description to find key'),
                        'desc' => $this->l('You can get Google API key from ') . '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . $this->l('HERE') . '</a>',
                        'tab' => 'map_settings',
                    ),
                    array(
                        'type' => 'text',
                        'lang' => false,
                        'label' => $this->l('Default Zoom Level'),
                        'name' => 'STOREPICKUP_ZOOM_VALUE',
                        'col' => 2,
                        'required' => false,
                        'desc' => $this->l('Zoom range is from 0 which is lowest and 21 is the highest.'),
                        'hint' => $this->l('Default zoom level view of map. Use from 0 to 20.'),
                        'placeholder' => 10,
                        'tab' => 'map_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Fixed map View'),
                        'desc' => $this->l('Disable map movement.'),
                        'name' => 'STOREPICKUP_FIXED_MAP_VIEW',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_FIXED_MAP_VIEW_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_FIXED_MAP_VIEW_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'tab' => 'map_settings',
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Map Theme'),
                        'name' => 'STOREPICKUP_MAP_THEME',
                        'options' => array(
                            'query' => $this->getMapThemes(),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                        'tab' => 'map_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Show store email on store popup'),
                        'name' => 'STOREPICKUP_STORE_EMAIL',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_STORE_EMAIL_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_STORE_EMAIL_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'tab' => 'store_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Show store fax number on store popup'),
                        'name' => 'STOREPICKUP_STORE_FAX',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_STORE_FAX_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_STORE_FAX_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'tab' => 'store_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Show store note on store popup'),
                        'name' => 'STOREPICKUP_STORE_NOTE',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_STORE_NOTE_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_STORE_NOTE_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'tab' => 'store_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Autolocate User Location?'),
                        'name' => 'STOREPICKUP_USER',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_USER_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_USER_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'tab' => 'store_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Use below icon for all stores?'),
                        'name' => 'STOREPICKUP_GLOBAL_ICON',
                        'is_bool' => true,
                        'class' => 't',
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_GLOBAL_ICON_on',
                                'value' => 1,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_GLOBAL_ICON_off',
                                'value' => 0,
                                'label' => $this->l('Disabled'),
                            ),
                        ),
                        'tab' => 'store_settings',
                    ),
                    array(
                        'type' => 'file',
                        'label' => $this->l('Store Map Icon'),
                        'name' => 'STOREPICKUP_STORE_ICON',
                        'display_image' => true,
                        'image' => $image_url ? $image_url : false,
                        'size' => 300,
                        'hint' => $this->l('Upload an Image type PNG from your computer.'),
                        'tab' => 'store_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Allow Pickup Date Selection'),
                        'name' => 'STOREPICKUP_PICKUP_DATE',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'desc' => $this->l('Allow customers to select pickup date.'),
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_PICKUP_DATE_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_PICKUP_DATE_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                        'tab' => 'time_settings',
                    ),
                    array(
                        'type' => $radio,
                        'label' => $this->l('Allow Pickup Time Selection'),
                        'name' => 'STOREPICKUP_PICKUP_TIME',
                        'required' => false,
                        'class' => 't',
                        'is_bool' => true,
                        'desc' => $this->l('Allow customers to select pickup time. Your store hours will be used for selection.'),
                        'values' => array(
                            array(
                                'id' => 'STOREPICKUP_PICKUP_TIME_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ),
                            array(
                                'id' => 'STOREPICKUP_PICKUP_TIME_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ),
                        ),
                        'tab' => 'time_settings',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $fields = array();
        $fields['STOREPICKUP_DEFAULT_STORE'] = (int) Configuration::get('STOREPICKUP_DEFAULT_STORE', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_DEFAULT_CARRIER'] = (int) Configuration::get('STOREPICKUP_DEFAULT_CARRIER', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_KEY'] = Configuration::get('STOREPICKUP_KEY', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_STORE_EMAIL'] = (int) Configuration::get('STOREPICKUP_STORE_EMAIL', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_STORE_FAX'] = (int) Configuration::get('STOREPICKUP_STORE_FAX', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_STORE_NOTE'] = (int) Configuration::get('STOREPICKUP_STORE_NOTE', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_ZOOM_VALUE'] = (int) Configuration::get('STOREPICKUP_ZOOM_VALUE', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_FIXED_MAP_VIEW'] = (int) Configuration::get('STOREPICKUP_FIXED_MAP_VIEW', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_MAP_THEME'] = Configuration::get('STOREPICKUP_MAP_THEME', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_GLOBAL_ICON'] = (int) Configuration::get('STOREPICKUP_GLOBAL_ICON', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_PICKUP_TIME'] = (int) Configuration::get('STOREPICKUP_PICKUP_TIME', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_PICKUP_DATE'] = (int) Configuration::get('STOREPICKUP_PICKUP_DATE', null, $this->id_shop_group, $this->id_shop);
        $fields['STOREPICKUP_USER'] = (int) Configuration::get('STOREPICKUP_USER', null, $this->id_shop_group, $this->id_shop);
        return $fields;
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            $value = Tools::getValue($key);
            if ('STOREPICKUP_KEY' == $key && empty($value)) {
                return $this->context->controller->errors[] = $this->l('Google API KEY is required.');
            } elseif ('STOREPICKUP_ZOOM_VALUE' == $key && isset($value) && (!Validate::isUnsignedInt($value) || $value > 21)) {
                return $this->context->controller->errors[] = $this->l('Invalid value for "Zefault zoom level"');
            } else {
                Configuration::updateValue($key, $value, null, $this->id_shop_group, $this->id_shop);
            }
        }

        $storeIcon = Tools::fileAttachment('STOREPICKUP_STORE_ICON');
        if (!empty($storeIcon['name'])) {
            $storeIcon['type'] = explode('/', $storeIcon['mime']);
            $storeIcon['type'] = end($storeIcon['type']);
            if (ImageManager::validateUpload($storeIcon, Tools::convertBytes(ini_get('upload_max_filesize')))) {
                $this->context->controller->errors[] = $this->l('Image size exceeds limit in your PrestaShop settings');
            } else {
                ImageManager::resize($storeIcon['tmp_name'], _PS_IMG_DIR_ . 'logo_stores.png', 30, 30, 'png', false);
                ImageManager::resize($storeIcon['tmp_name'], _PS_IMG_DIR_ . 'tmp/logo_stores.png', 30, 30, 'png', false);
                if (Tools::version_compare(_PS_VERSION_, '1.6.0.0', '<') == true) {
                    ImageManager::resize($storeIcon['tmp_name'], _PS_IMG_DIR_ . 'logo_stores.gif', 30, 30, 'gif', false);
                    ImageManager::resize($storeIcon['tmp_name'], _PS_IMG_DIR_ . 'tmp/logo_stores.gif', 30, 30, 'gif', false);
                }
            }
        }

        if (!count($this->context->controller->errors)) {
            $this->context->controller->confirmations[] = $this->l('Settings updated successfully.');
        }
    }

    public function installTab($controllerClassName, $tabName, $icon = null, $tabParentControllerName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $controllerClassName;
        $tab->name = array();

        if (true === Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=') && !empty($icon)) {
            $tab->icon = $icon;
        }

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        $tab->id_parent = $tabParentControllerName ? (int) Tab::getIdFromClassName($tabParentControllerName) : 0;
        $tab->module = $this->name;
        return (bool) $tab->add();
    }

    public function uninstallTab()
    {
        $res = true;
        foreach ($this->pickupTabs as $tabClass) {
            $tab = Tab::getInstanceFromClassName($tabClass);
            $res &= $tab->delete();
        }
        return $res;
    }

    protected function setConfigurations()
    {
        Configuration::updateValue(
            'STOREPICKUP_DEFAULT_STORE',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_DEFAULT_CARRIER',
            (int) Configuration::get('PS_CARRIER_DEFAULT'),
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_KEY',
            null,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_STORE_EMAIL',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_STORE_FAX',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_STORE_NOTE',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_ZOOM_VALUE',
            10,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_FIXED_MAP_VIEW',
            0,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_MAP_THEME',
            'STOREPICKUP_MAP_STYLE_DEFAULT',
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_GLOBAL_ICON',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_PICKUP_TIME',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_PICKUP_DATE',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        Configuration::updateValue(
            'STOREPICKUP_USER',
            1,
            false,
            $this->id_shop_group,
            $this->id_shop
        );
        return true;
    }

    protected function unsetConfigurations()
    {
        Configuration::deleteByName('STOREPICKUP_DEFAULT_STORE');
        Configuration::deleteByName('STOREPICKUP_DEFAULT_CARRIER');
        Configuration::deleteByName('STOREPICKUP_KEY');
        Configuration::deleteByName('STOREPICKUP_STORE_EMAIL');
        Configuration::deleteByName('STOREPICKUP_STORE_FAX');
        Configuration::deleteByName('STOREPICKUP_STORE_NOTE');
        Configuration::deleteByName('STOREPICKUP_ZOOM_VALUE');
        Configuration::deleteByName('STOREPICKUP_FIXED_MAP_VIEW');
        Configuration::deleteByName('STOREPICKUP_MAP_THEME');
        Configuration::deleteByName('STOREPICKUP_GLOBAL_ICON');
        Configuration::deleteByName('STOREPICKUP_PICKUP_TIME');
        Configuration::deleteByName('STOREPICKUP_PICKUP_DATE');
        Configuration::deleteByName('STOREPICKUP_USER');
        return true;
    }

    /**
     * Themes for google map
     * @return array
     */
    protected function getMapThemes()
    {
        return array(
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_DEFAULT',
                'name' => $this->l('Default'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_BROWNS',
                'name' => $this->l('Browns'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_COBALT',
                'name' => $this->l('Cobalt'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_GREYSCALE',
                'name' => $this->l('Greyscale'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_MIDNIGHT',
                'name' => $this->l('Midnight'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_NIGHTMODE',
                'name' => $this->l('Nightmode'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_SKETCH',
                'name' => $this->l('Sketch'),
            ),
            array(
                'id' => 'STOREPICKUP_MAP_STYLE_YELLOW',
                'name' => $this->l('Yellow'),
            ),
        );
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/admin.css');
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $storePage = Dispatcher::getInstance()->getController();
        if (in_array($storePage, array('order', 'orderopc', 'order-opc'))) {
            $this->getMediaJsDef();
        }
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $order = new Order($params['id_order']);
        $rederenceOrders = Order::getByReference($order->reference);
        $defaultCarrier = (int) Configuration::get(
            'STOREPICKUP_DEFAULT_CARRIER',
            null,
            $order->id_shop_group,
            $order->id_shop
        );

        if (isset($rederenceOrders) && $rederenceOrders) {
            foreach ($rederenceOrders->getResults() as $order) {
                if ($defaultCarrier == $order->id_carrier && ($id_store = Pickup::getIdStoreByOrder($order->id))) {
                    if (($id_address = Pickup::getStoreAddressId($id_store))) {
                        $order->id_address_delivery = $id_address;
                        $order->save();
                        Pickup::updateCustomizationAddress(
                            $order->id_cart,
                            array('id_address_delivery' => (int) $id_address)
                        );
                    }
                }
            }
        }
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $defaultCarrier = (int) Configuration::get(
            'STOREPICKUP_DEFAULT_CARRIER',
            null,
            $order->id_shop_group,
            $order->id_shop
        );

        if ($defaultCarrier == $order->id_carrier) {
            Pickup::updateStoreByCart(
                $order->id_cart,
                array('id_order' => (int) $order->id)
            );
        }
    }

    public function hookDisplayOrderDetail($params)
    {
        $order = $params['order'];
        if (Validate::isLoadedObject($order)) {
            $pickupData = Pickup::getStoreByOrder((int) $order->id, $order->id_lang);
            if (isset($pickupData) && $pickupData) {
                $linkPdf = $this->context->link->getModuleLink(
                    $this->name,
                    'pickupslip',
                    array(
                        'submitAction' => 'generatePickupSlipPDF',
                        'id_order' => (int) $order->id,
                    )
                );
                $this->context->smarty->assign(array(
                    'storeOrder' => $order,
                    'linkPdf' => $linkPdf,
                    'pickup_data' => $pickupData,
                ));
                return $this->display(dirname(__FILE__), 'views/templates/admin/store-pickup-details.tpl');
            }
        }
    }

    public function hookDisplayAdminOrder($params)
    {
        $id_order = $params['id_order'];
        if ($id_order && Validate::isLoadedObject($order = new Order($id_order))) {
            $pickupData = Pickup::getStoreByOrder((int) $order->id, $order->id_lang);
            if (isset($pickupData) && $pickupData) {
                $linkPdf = $this->context->link->getAdminLink('AdminPdf') . '&' . http_build_query(array(
                    'submitAction' => 'generateDeliverySlipPDF',
                    'id_order' => (int) $order->id,
                ));
                $this->context->smarty->assign(array(
                    'storeOrder' => $order,
                    'linkPdf' => $linkPdf,
                    'pickup_data' => $pickupData,
                ));
                return $this->display(dirname(__FILE__), 'views/templates/admin/store-pickup-details.tpl');
            }
        }
    }

    public function hookDisplayPDFDeliverySlip($params)
    {
        if ($params['object']->id_order && Validate::isLoadedObject($order = new Order($params['object']->id_order))) {
            $pickupData = Pickup::getStoreByOrder((int) $order->id, $order->id_lang);
            if (isset($pickupData) && $pickupData) {
                $this->context->smarty->assign(array(
                    'pickup_data' => $pickupData,
                ));
                return $this->display(dirname(__FILE__), 'views/templates/admin/pdf/store-pickup.tpl');
            }
        }
    }

    public function hookModuleRoutes()
    {
        return array(
            'module-' . $this->name . '-pickup' => array(
                'controller' => 'pickup',
                'rule' => 'store-pickup',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                ),
            ),
            'module-' . $this->name . '-pickupslip' => array(
                'controller' => 'pickupslip',
                'rule' => 'pickup-slip',
                'keywords' => array(),
                'params' => array(
                    'fc' => 'module',
                    'module' => $this->name,
                ),
            ),
        );
    }

    public function getMapPickupStores()
    {
        $context = Context::getContext();
        if (!extension_loaded('Dom')) {
            return $context->controller->errors[] = $this->l('PHP "Dom" extension has not been loaded.');
        }

        $context->smarty->assign(array(
            'stores' => Pickup::getPickupStores(),
            'selectedpickupTime' => $this->getPickupTime(),
            'selectedpickupDate' => $this->getPickupDate(),
            'default_store' => (int) $this->getDefaultStore(),
            'pickupTime' => (int) Configuration::get(
                'STOREPICKUP_PICKUP_TIME',
                null,
                $context->shop->id_shop_group,
                $context->shop->id
            ),
            'pickupDate' => (int) Configuration::get(
                'STOREPICKUP_PICKUP_DATE',
                null,
                $context->shop->id_shop_group,
                $context->shop->id
            ),
            'default_carrier' => (int) Configuration::get(
                'STOREPICKUP_DEFAULT_CARRIER',
                null,
                $context->shop->id_shop_group,
                $context->shop->id
            ),
        ));
        return $this->display($this->_path, 'views/templates/hook/pickup-stores.tpl');
    }

    protected function getMediaJsDef()
    {
        $def_zoom = (int) Configuration::get(
            'STOREPICKUP_ZOOM_VALUE',
            null,
            Context::getContext()->shop->id_shop_group,
            Context::getContext()->shop->id
        );

        $def_zoom = ($def_zoom <= 0) ? 10 : $def_zoom;
        $protocol_link = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        $apiKey = Configuration::get(
            'STOREPICKUP_KEY',
            null,
            Context::getContext()->shop->id_shop_group,
            Context::getContext()->shop->id
        );
        $pickupDate = Configuration::get(
            'STOREPICKUP_PICKUP_DATE',
            null,
            Context::getContext()->shop->id_shop_group,
            Context::getContext()->shop->id
        );
        $defaultCountry = new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));

        $currentZone = Configuration::get('PS_TIMEZONE');
        $zoneTime = new DateTime(date('Y-m-d'), new DateTimeZone($currentZone));
        $year = $zoneTime->format('Y');
        // current month
        $month = $zoneTime->format('m');

        // next month
        $month += 1;

        // get last day of current month
        $lastday = (int) (strftime('%d', mktime(0, 0, 0, ($month >= 12 ? 1 : $month + 1), 0, ($month >= 12 ? $year + 1 : $year))));
        // generate date for last day of next month
        $lastDate = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($lastday, 2, '0', STR_PAD_LEFT);

        Media::addJsDef(array(
            'calYear' => date('Y'),
            'maxDate' => $lastDate,
            'STORE_KEY' => $apiKey,
            'pickp_zoom' => (int) $def_zoom,
            'protocol_link' => $protocol_link,
            'img_store_dir' => _THEME_STORE_DIR_,
            'default_store' => $this->getDefaultStore(),
            'STOREPICKUP_PICKUP_DATE' => (int) $pickupDate,
            'preselectedPickupTime' => $this->getPickupTime(),
            'preselectedPickupDate' => $this->getPickupDate(),
            'store_translations' => $this->storeTranslations(),
            'iso_lang' => $this->context->language->iso_code,
            'region' => Tools::substr($defaultCountry->iso_code, 0, 2),
            'pickup_page' => Dispatcher::getInstance()->getController(),
            'img_ps_dir' => $protocol_link . Tools::getMediaServer(_PS_IMG_) . _PS_IMG_,
            'psNew' => (true === (bool) Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=')) ? true : false,
            'pickupURL' => $this->context->link->getModuleLink($this->name, 'pickup', array('ajax' => true), true),
            'logo_store' => Configuration::get('PS_STORES_ICON', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'default_carrier' => (int) Configuration::get('STOREPICKUP_DEFAULT_CARRIER', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'STOREPICKUP_STORE_EMAIL' => (int) Configuration::get('STOREPICKUP_STORE_EMAIL', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'STOREPICKUP_STORE_FAX' => (int) Configuration::get('STOREPICKUP_STORE_FAX', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'STOREPICKUP_STORE_NOTE' => (int) Configuration::get('STOREPICKUP_STORE_NOTE', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'STOREPICKUP_PICKUP_TIME' => (int) Configuration::get('STOREPICKUP_PICKUP_TIME', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'STOREPICKUP_USER' => (int) Configuration::get('STOREPICKUP_USER', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'STOREPICKUP_GLOBAL_ICON' => (int) Configuration::get('STOREPICKUP_GLOBAL_ICON', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'defaultLat' => (float) Configuration::get('PS_STORES_CENTER_LAT', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'defaultLong' => (float) Configuration::get('PS_STORES_CENTER_LONG', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
            'pickup_map_theme' => $this->getMapStyles(Configuration::get('STOREPICKUP_MAP_THEME', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id)),
            'fixed_view' => (int) Configuration::get('STOREPICKUP_FIXED_MAP_VIEW', null, Context::getContext()->shop->id_shop_group, Context::getContext()->shop->id),
        ));

        $this->context->controller->addCss(array(
            $this->_path . 'views/css/sweetalert2.min.css',
            $this->_path . 'views/css/storepickup.css',
        ));

        $this->context->controller->addJs(array(
            $this->_path . 'views/js/sweetalert2.min.js',
            $this->_path . 'views/js/storepickup.js',
        ));

        if (true === (bool) $pickupDate) {
            $this->context->controller->addCSS(array(
                $this->_path . 'views/css/flatpickr/flatpickr.css',
                $this->_path . 'views/css/flatpickr/material_blue.css',
            ));
            $this->context->controller->addJs(array(
                $this->_path . 'views/js/moment.min.js',
                $this->_path . 'views/js/flatpickr/flatpickr.js',
                $this->_path . 'views/js/flatpickr//l10n/' . $this->context->language->iso_code . '.js',
            ));
        }
    }

    public function getDefaultStore()
    {
        $context = Context::getContext();
        $id_store = (int) Configuration::get(
            'STOREPICKUP_DEFAULT_STORE',
            false,
            $context->shop->id_shop_group,
            $context->shop->id
        );
        if (isset($context->cart)) {
            $preselectedStore = Pickup::getStoreByCart($context->cart->id);
            if (isset($preselectedStore) && $preselectedStore) {
                $id_store = (int) $preselectedStore['id_store'];
            }
        }
        return $id_store;
    }

    public function getPickupDate()
    {
        $pickupDate = null;
        if (isset(Context::getContext()->cart)) {
            $preselectedStore = Pickup::getStoreByCart(Context::getContext()->cart->id);
            if (isset($preselectedStore) && $preselectedStore) {
                if (isset($preselectedStore['pickup_date']) && false !== strtotime($preselectedStore['pickup_date'])) {
                    $pickupDate = date('Y-m-d', strtotime($preselectedStore['pickup_date']));
                }
            }
        }
        return $pickupDate;
    }

    public function getPickupTime()
    {
        $pickupTime = null;
        if (isset(Context::getContext()->cart)) {
            $preselectedStore = Pickup::getStoreByCart(Context::getContext()->cart->id);
            if (isset($preselectedStore) && $preselectedStore) {
                if (isset($preselectedStore['pickup_date']) && false !== strtotime($preselectedStore['pickup_date'])) {
                    $pickupTime = date('H:i', strtotime($preselectedStore['pickup_date']));
                }
            }
        }
        return $pickupTime;
    }

    public function renderStoreWorkingHours($store)
    {
        $days = array();
        $days[1] = $this->l('Monday');
        $days[2] = $this->l('Tuesday');
        $days[3] = $this->l('Wednesday');
        $days[4] = $this->l('Thursday');
        $days[5] = $this->l('Friday');
        $days[6] = $this->l('Saturday');
        $days[7] = $this->l('Sunday');

        $hours = array();
        $daysDatas = array();

        if ($store['hours'] && Tools::version_compare(_PS_VERSION_, '1.7.0.0', '<') == true) {
            $hours = Tools::unSerialize($store['hours']);
            if (is_array($hours)) {
                $hours = array_filter($hours);
            }
        } elseif ($store['hours'] && Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=') == true) {
            $hours = $store['hours'];
            $hours = preg_replace('~[\\]\[\/*?"<>|]~', '', $hours);
            $hours = explode(',', $hours);
            if (is_array($hours)) {
                $hours = array_filter($hours);
            }
        }

        if (!empty($hours)) {
            for ($i = 1; $i < 8; $i++) {
                if (isset($hours[(int) $i - 1])) {
                    $hoursDatas = array();
                    $hoursDatas['hours'] = $hours[(int) $i - 1];
                    $hoursDatas['day'] = $days[$i];
                    $daysDatas[] = $hoursDatas;
                }
            }
            $version_check = (Tools::version_compare(_PS_VERSION_, '1.7.0.0', '>=') == true) ? 1 : 0;
            $this->context->smarty->assign('days_datas', $daysDatas);
            $this->context->smarty->assign('id_country', $store['id_country']);
            $this->context->smarty->assign('ver_ps', (int) $version_check);
            return $this->context->smarty->fetch($this->local_path . 'views/templates/hook/store-infos.tpl');
        }
        return false;
    }

    public function removeStoreAddresses()
    {
        if (($addresses = Pickup::getAllStoreAddresses())) {
            foreach ($addresses as $id_address) {
                if ($id_address && Validate::isLoadedObject($address = new Address((int) $id_address))) {
                    $address->delete();
                }
            }
        }
        return true;
    }

    public function getPickupLink()
    {
        return $this->context->link->getAdminLink('AdminModules') . '&' . http_build_query(array(
            'configure' => $this->name,
            'tab_module' => $this->tab,
            'module_name' => $this->name,
        ));
    }

    protected function getWeekDays()
    {
        return array(
            'monday' => $this->l('Monday'),
            'tuesday' => $this->l('Tuesday'),
            'wednesday' => $this->l('Wednesday'),
            'thursday' => $this->l('Thursday'),
            'friday' => $this->l('Friday'),
            'saturday' => $this->l('Saturday'),
            'sunday' => $this->l('Sunday'),
        );
    }

    protected function getTranslateableFields()
    {
        return array(
            'monday' => $this->l('Monday'),
            'tuesday' => $this->l('Tuesday'),
            'wednesday' => $this->l('Wednesday'),
            'thursday' => $this->l('Thursday'),
            'friday' => $this->l('Friday'),
            'saturday' => $this->l('Saturday'),
            'sunday' => $this->l('Sunday'),
            'placeholder_label' => $this->l('Start typing here'),
            'invalid_request' => $this->l('Invalid Request.You cannot directly access this page.'),
            'store_selection_success' => $this->l('Store selection is saved successfully.'),
            'store_inactive' => $this->l('Your selected store is not available. Please select another store.'),
            'invalid_pickup_date' => $this->l('Pickup date is invalid.'),
            'saved_pickup_date' => $this->l('Pickup details saved successfully.'),
            'saved_pickup_date_error' => $this->l('Unfortunatley, we encountered an error while saving data.'),
        );
    }

    public function storeTranslations()
    {
        return array(
            'translation_1' => $this->l('No stores were found. Please try selecting a wider radius'),
            'translation_2' => $this->l('store found -- see details'),
            'translation_3' => $this->l('stores found -- view all results'),
            'translation_4' => $this->l('Phone'),
            'translation_5' => $this->l('Get directions'),
            'translation_6' => $this->l('Not found'),
            'translation_7' => $this->l('Email'),
            'translation_8' => $this->l('Fax'),
            'translation_9' => $this->l('Note'),
            'translation_10' => $this->l('Distance'),
            'translation_11' => $this->l('View'),
            'translation_01' => $this->l('Unable to find your location'),
            'translation_02' => $this->l('Permission denied'),
            'translation_03' => $this->l('Your location unknown'),
            'translation_04' => $this->l('Timeout error'),
            'translation_05' => $this->l('Location detection not supported in browser'),
            'translation_06' => $this->l('Your current Location'),
            'translation_07' => $this->l('You are near this location'),
            'translation_store_sel' => $this->l('Select Store'),
            'available_date_label' => $this->l('Available Dates'),
            'disabled_date_label' => $this->l('Unavailable Dates'),
            'invalid_pickupdate_label' => $this->l('Please pick up  a valid date'),
            'invalid_pickuptime_label' => $this->l('Please pick up a valid time'),
            'store_page_error_label' => $this->l('Please select a pickup store'),
        );
    }

    public function getMapStyles($theme = 'STOREPICKUP_MAP_STYLE_DEFAULT')
    {
        $mapThemes = array(
            'STOREPICKUP_MAP_STYLE_DEFAULT' => '[{"featureType":"administrative.country","elementType":"geometry.fill","stylers":[{"saturation":"-35"}]}]',
            'STOREPICKUP_MAP_STYLE_COBALT' => '[{"featureType":"all","elementType":"all","stylers":[{"invert_lightness":true},{"saturation":10},{"lightness":30},{"gamma":0.5},{"hue":"#435158"}]}]',
            'STOREPICKUP_MAP_STYLE_BROWNS' => '[{"elementType":"geometry","stylers":[{"hue":"#ff4400"},{"saturation":-68},{"lightness":-4},{"gamma":0.72}]},{"featureType":"road","elementType":"labels.icon"},{"featureType":"landscape.man_made","elementType":"geometry","stylers":[{"hue":"#0077ff"},{"gamma":3.1}]},{"featureType":"water","stylers":[{"hue":"#00ccff"},{"gamma":0.44},{"saturation":-33}]},{"featureType":"poi.park","stylers":[{"hue":"#44ff00"},{"saturation":-23}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"hue":"#007fff"},{"gamma":0.77},{"saturation":65},{"lightness":99}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"gamma":0.11},{"weight":5.6},{"saturation":99},{"hue":"#0091ff"},{"lightness":-86}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"lightness":-48},{"hue":"#ff5e00"},{"gamma":1.2},{"saturation":-23}]},{"featureType":"transit","elementType":"labels.text.stroke","stylers":[{"saturation":-64},{"hue":"#ff9100"},{"lightness":16},{"gamma":0.47},{"weight":2.7}]}]',
            'STOREPICKUP_MAP_STYLE_MIDNIGHT' => '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"color":"#000000"},{"lightness":13}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#144b53"},{"lightness":14},{"weight":1.4}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#08304b"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#0c4152"},{"lightness":5}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000000"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#0b434f"},{"lightness":25}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#000000"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"color":"#0b3d51"},{"lightness":16}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"}]},{"featureType":"transit","elementType":"all","stylers":[{"color":"#146474"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#021019"}]}]',
            'STOREPICKUP_MAP_STYLE_GREYSCALE' => '[{"featureType":"all","elementType":"geometry.fill","stylers":[{"weight":"2.00"}]},{"featureType":"all","elementType":"geometry.stroke","stylers":[{"color":"#9c9c9c"}]},{"featureType":"all","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#eeeeee"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"color":"#7b7b7b"}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#46bcec"},{"visibility":"on"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#c8d7d4"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#070707"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]}]',
            'STOREPICKUP_MAP_STYLE_NIGHTMODE' => '[{"elementType": "geometry", "stylers": [{"color": "#242f3e"}]},{"elementType": "labels.text.stroke", "stylers": [{"color": "#242f3e"}]},{"elementType": "labels.text.fill", "stylers": [{"color": "#746855"}]},{"featureType": "administrative.locality","elementType": "labels.text.fill","stylers": [{"color": "#d59563"}]},{"featureType": "poi","elementType": "labels.text.fill","stylers": [{"color": "#d59563"}]},{"featureType": "poi.park","elementType": "geometry","stylers": [{"color": "#263c3f"}]},{"featureType": "poi.park","elementType": "labels.text.fill","stylers": [{"color": "#6b9a76"}]},{"featureType": "road","elementType": "geometry","stylers": [{"color": "#38414e"}]},{"featureType": "road","elementType": "geometry.stroke","stylers": [{"color": "#212a37"}]},{"featureType": "road","elementType": "labels.text.fill","stylers": [{"color": "#9ca5b3"}]},{"featureType": "road.highway","elementType": "geometry","stylers": [{"color": "#746855"}]},{"featureType": "road.highway","elementType": "geometry.stroke","stylers": [{"color": "#1f2835"}]},{"featureType": "road.highway","elementType": "labels.text.fill","stylers": [{"color": "#f3d19c"}]},{"featureType": "transit","elementType": "geometry","stylers": [{"color": "#2f3948"}]},{"featureType": "transit.station","elementType": "labels.text.fill","stylers": [{"color": "#d59563"}]},{"featureType": "water","elementType": "geometry","stylers": [{"color": "#17263c"}]},{"featureType": "water","elementType": "labels.text.fill","stylers": [{"color": "#515c6d"}]},{"featureType": "water","elementType": "labels.text.stroke","stylers": [{"color": "#17263c"}]}]',
            'STOREPICKUP_MAP_STYLE_SKETCH' => '[{"featureType":"all","elementType":"geometry","stylers":[{"color":"#ffffff"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"gamma":0.01},{"lightness":20}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"saturation":-31},{"lightness":-33},{"weight":2},{"gamma":0.8}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"color":"#050505"}]},{"featureType":"administrative.locality","elementType":"labels.text.stroke","stylers":[{"color":"#fef3f3"},{"weight":"3.01"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"color":"#0a0a0a"},{"visibility":"off"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.stroke","stylers":[{"color":"#fffbfb"},{"weight":"3.01"},{"visibility":"off"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"lightness":30},{"saturation":30}]},{"featureType":"poi","elementType":"geometry","stylers":[{"saturation":20}]},{"featureType":"poi.attraction","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"lightness":20},{"saturation":-20}]},{"featureType":"road","elementType":"geometry","stylers":[{"lightness":10},{"saturation":-30}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"saturation":25},{"lightness":25}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#a1a1a1"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#292929"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#202020"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#ffffff"}]},{"featureType":"road.highway","elementType":"labels.icon","stylers":[{"visibility":"simplified"},{"hue":"#0006ff"},{"saturation":"-100"},{"lightness":"13"},{"gamma":"0.00"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#686868"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"visibility":"off"},{"color":"#8d8d8d"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#353535"},{"lightness":"6"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#ffffff"},{"weight":"3.45"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#d0d0d0"}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"lightness":"2"},{"visibility":"on"},{"color":"#999898"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#383838"}]},{"featureType":"road.local","elementType":"labels.text.stroke","stylers":[{"color":"#faf8f8"}]},{"featureType":"water","elementType":"all","stylers":[{"lightness":-20}]}]',
            'STOREPICKUP_MAP_STYLE_YELLOW' => '[{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#0096aa"},{"weight":"0.30"},{"saturation":"-75"},{"lightness":"5"},{"gamma":"1"}]},{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#0096aa"},{"saturation":"-75"},{"lightness":"5"}]},{"featureType":"administrative","elementType":"labels.text.stroke","stylers":[{"color":"#ffe146"},{"visibility":"on"},{"weight":"6"},{"saturation":"-28"},{"lightness":"0"}]},{"featureType":"administrative","elementType":"labels.icon","stylers":[{"visibility":"on"},{"color":"#e6007e"},{"weight":"1"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#ffe146"},{"saturation":"-28"},{"lightness":"0"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"color":"#0096aa"},{"visibility":"simplified"},{"saturation":"-75"},{"lightness":"5"},{"gamma":"1"}]},{"featureType":"road","elementType":"labels.text","stylers":[{"visibility":"on"},{"color":"#ffe146"},{"weight":8},{"saturation":"-28"},{"lightness":"0"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#0096aa"},{"weight":8},{"lightness":"5"},{"gamma":"1"},{"saturation":"-75"}]},{"featureType":"road","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"simplified"},{"color":"#0096aa"},{"saturation":"-75"},{"lightness":"5"},{"gamma":"1"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#0096aa"},{"saturation":"-75"},{"lightness":"5"},{"gamma":"1"}]},{"featureType":"water","elementType":"labels.text","stylers":[{"visibility":"simplified"},{"color":"#ffe146"},{"saturation":"-28"},{"lightness":"0"}]},{"featureType":"water","elementType":"labels.icon","stylers":[{"visibility":"off"}]}]',
        );
        return $mapThemes[$theme];
    }
}
