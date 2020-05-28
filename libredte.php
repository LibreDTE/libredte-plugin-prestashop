<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General Affero de GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General Affero de GNU para
 * obtener una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General Affero de GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/agpl.html>.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Módulo de LibreDTE para PrestaShop
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2020-05-24
 */
class Libredte extends Module
{

    private $defaultConfig = [
        'LIBREDTE_GENERAL_ACTIVO' => false,
        'LIBREDTE_GENERAL_LOG' => false,
        'LIBREDTE_EMPRESA_RUT' => '',
        'LIBREDTE_API_URL' => 'https://libredte.cl',
        'LIBREDTE_API_KEY' => '',
    ]; ///< Configuración inicial del módulo

    protected $config_form = false;

    public function __construct()
    {

        // configuración base del módulo
        $this->name = 'libredte';
        $this->tab = 'billing_invoicing';
        $this->version = '1.0.0';
        $this->author = 'SASCO SpA';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        // módulo es compatible con bootstrap (PrestaShop 1.6)
        $this->bootstrap = true;

        // crear módulo llamando al constructor
        parent::__construct();

        // textos para el usuario que ve el módulo
        $this->displayName = $this->l('LibreDTE');
        $this->description = $this->l('Facturación y Boletas Electrónicas en Chile usando LibreDTE Versión Oficial en www.libredte.cl');
        $this->confirmUninstall = $this->l('Al quitar el módulo no podrás facturar con LibreDTE.');

        // advertencias de configuración
        $this->warning = [];
        if (!Configuration::get('LIBREDTE_GENERAL_ACTIVO')) {
            $this->warning[] = $this->l('Generación de DTE desactivada');
        }
        if (!Configuration::get('LIBREDTE_EMPRESA_RUT')) {
            $this->warning[] = $this->l('RUT no está asignado');
        }
        if (!Configuration::get('LIBREDTE_API_KEY')) {
            $this->warning[] = $this->l('API key no está asignada');
        }
        $this->warning = implode(' / ', $this->warning);

    }

    /**
     * Método que instala el plugin de LibreDTE
     */
    public function install()
    {

        // valores por defecto de la configuración
        foreach ($this->defaultConfig as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        // base de datos
        include(dirname(__FILE__).'/sql/install.php');

        // instalar módulo
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionOrderStatusPostUpdate');

    }

    /**
     * Método que desinstala el plugin de LibreDTE
     */
    public function uninstall()
    {

        // valores por defecto de la configuración
        foreach ($this->defaultConfig as $key => $value) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        // base de datos
        include(dirname(__FILE__).'/sql/uninstall.php');

        // desinstalar módulo
        return parent::uninstall();

    }

    /**
     * Método que carga el formulario de configuración
     */
    public function getContent()
    {
        // procesar valores pasados en el formulario
        if (((bool)Tools::isSubmit('submitLibredteModule')) == true) {
            $this->postProcess();
        }
        // armar página del formulario
        $this->context->smarty->assign('module_dir', $this->_path);
        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        return $output.$this->renderForm().$this->footer();
    }

    /**
     * Método que genera el footer de la página de configuración del módulo
     */
    private function footer()
    {
        return '<p class="text-center"><a href="https://libredte.cl" target="_blank">LibreDTE</a> es un proyecto de <a href="https://sasco.cl" target="_blank">SASCO SpA</a> que tiene como misión proveer de facturación electrónica libre para Chile.</p>';
    }

    /**
     * Método que crea el formulario que se debe mostrar en la página de configuración
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
        $helper->submit_action = 'submitLibredteModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Estructura del formulario de configuración
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Opciones de Integración'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Módulo activo'),
                        'name' => 'LIBREDTE_GENERAL_ACTIVO',
                        'is_bool' => true,
                        'desc' => $this->l('Recuerda que además la integración debe estar disponible en LibreDTE para que el módulo funcione'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'LIBREDTE_EMPRESA_RUT',
                        'label' => $this->l('RUT'),
                        'prefix' => '<i class="icon icon-building"></i>',
                        'desc' => $this->l('RUT de la empresa registrada en LibreDTE'),
                        'required' => true,
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'LIBREDTE_API_KEY',
                        'label' => $this->l('API key'),
                        'prefix' => '<i class="icon icon-key"></i>',
                        'desc' => $this->l('Se obtiene en el perfil de usuario de LibreDTE'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Registro de notificaciones'),
                        'name' => 'LIBREDTE_GENERAL_LOG',
                        'is_bool' => true,
                        'desc' => $this->l('Se recomienda activar esta opción sólo si es necesario hacer una depuración de la integración'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Métood que asigna valores a los campos del formulario
     */
    protected function getConfigFormValues()
    {
        $config = [];
        foreach ($this->defaultConfig as $key => $value) {
            $config[$key] = Configuration::get($key, $value);
        }
        return $config;
    }

    /**
     * Método que guarda los datos pasados al formulario
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Archivos CSS y JavaScript para el backoffice
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Archivos CSS y JavaScript para la tienda
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    /**
     * Método que se ejecuta mediante un hook cuando el estado de la orden es actualizado
     * Este método es el necesario para detectar el método pagado y poder crear el DTE
     */
    public function hookActionOrderStatusPostUpdate(array $params = [])
    {
        // no hay nuevo estado de la orden
        if (empty($params['newOrderStatus']) || empty($params['id_order'])) {
            return;
        }
        // el nuevo estado no es pagado
        if (!in_array($params['newOrderStatus']->id, [Configuration::get('PS_OS_WS_PAYMENT'), Configuration::get('PS_OS_PAYMENT')])) {
            return;
        }
        // crear objetos que se usarán para extraer datos
        $Order = new Order((int)$params['id_order']);
        $Customer = new Customer((int)$Order->id_customer);
        $Cart = new Cart($Order->id_cart);
        $Address = new Address($Cart->id_address_delivery);
        $Carrier = new Carrier((int)($Order->id_carrier));
        $Shop = new Shop((int)($Order->id_shop));
        // construir arreglo con los datos que se usarán
        $order = array_merge($params, get_object_vars($Order));
        $order['customer'] = get_object_vars($Customer);
        unset($order['customer']['passwd']);
        $order['cart'] = get_object_vars($Cart);
        $order['cart']['rules'] = $Cart->getCartRules();
        $order['address'] = get_object_vars($Address);
        $order['carrier'] = get_object_vars($Carrier);
        $order['products'] = $Order->getProducts();
        $order['detail'] = $Order->getOrderDetailList();
        $order['shop'] = get_object_vars($Shop);
        unset($order['shop']['theme']);
        // llamar al método que procesa la orden pagada
        return $this->processOrderPaid($order);
    }

    /**
     * Método que procesa la orden con todos sus datos enviando la solicitud a LibreDTE
     * El resultado de la llamada a LibreDTE es guardado como nota del documento asociado al pedido
     */
    private function processOrderPaid($order)
    {
        // log para la orden que se enviará
        if (Configuration::get('LIBREDTE_GENERAL_LOG')) {
            PrestaShopLogger::addLog('LibreDTE Data Order #'.$order['id_order'].': '.base64_encode(json_encode($order)));
        }
        // notificar al servicio web de LibreDTE
        $rut = str_replace('.', '', Configuration::get('LIBREDTE_EMPRESA_RUT'));
        $resource = '/webhooks/prestashop/pedido_actualizado/'.$rut;
        $response = $this->api_post($resource, $order);
        // log para la respuesta del servicio web
        if (Configuration::get('LIBREDTE_GENERAL_LOG')) {
            $msg = is_string($response) ? $response : base64_encode(json_encode($response));
            PrestaShopLogger::addLog('LibreDTE Response Order #'.$order['id_order'].': '.$msg);
        }
        // guardar nota en la factura de la orden con el resultado
        if (!$response) {
            $note = 'No fue posible generar el DTE en LibreDTE (error desconocido)';
        } else if (is_string($response)) {
            $note = $response;
        } else {
            $note = $response['links']['pdf'];
        }
        $sql = sprintf(
            'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET note="%s" WHERE id_order_invoice="%d" and id_order="%d"',
            pSQL($note),
            (int)$order['invoice_number'],
            (int)$order['id_order']
        );
        Db::getInstance()->Execute($sql);
        // todo ok
        return true;
    }

    /**
     * Método que hace un llamado por POST a LibreDTE
     */
    private function api_post($resource, $data)
    {
        $url = $this->defaultConfig['LIBREDTE_API_URL'].'/api'.$resource;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic '.base64_decode(Configuration::get('LIBREDTE_API_KEY')),
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

}
