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

// bibliotecas
require_once(_PS_MODULE_DIR_.'libredte/lib/Rut.php');

/**
 * Clase principal del módulo Libredte para Prestashop
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2018-07-03
 */
class Libredte extends Module
{

    private $defaultConfig = [
        'LIBREDTE_URL' => 'https://libredte.cl',
        'LIBREDTE_HASH' => '',
        'LIBREDTE_RUT' => '',
    ]; ///< Configuración inicial del módulo

    /**
     * Constructor del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-07-03
     */
    public function __construct()
    {
        // configuración base del módulo
        $this->name = 'libredte';
        $this->tab = 'billing_invoicing';
        $this->version = '0.1';
        $this->author = 'SASCO SpA';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.5', 'max' => '1.6.9.9'];
        $this->dependencies = [];
        // crear módulo llamando al constructor
        parent::__construct();
        // textos para el usuario que ve el módulo
        $this->displayName = $this->l('LibreDTE');
        $this->description = $this->l('Permite emitir boletas o facturas electrónicas en Chile a partir de los pedidos de PrestaShop');
        $this->confirmUninstall = $this->l('¿Estás seguro de desinstalar? No podrás emitir boletas ni facturas');
        // advertencia de configuración
        $this->warning = [];
        if (!Configuration::get('LIBREDTE_HASH')) {
            $this->warning[] = $this->l('HASH del usuario no está asignado');
        }
        if (!Configuration::get('LIBREDTE_RUT')) {
            $this->warning[] = $this->l('RUT del emisor no está asignado');
        }
        $this->warning = implode(' / ', $this->warning);
    }

    /**
     * Método para instalar el módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        foreach ($this->defaultConfig as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Método para desinstalar el módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-07-03
     */
    public function uninstall()
    {
        foreach ($this->defaultConfig as $key => $value) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * Método para generar página de configuración del módulo y procesar el
     * formulario al guardar los cambios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-07-03
     */
    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit'.$this->name)) {
            // verificar que se hayan pasado los datos mínimos
            $error = false;
            $config = [];
            foreach ($this->defaultConfig as $key => $value) {
                $valor = Tools::getValue($key);
                if (!$valor || empty($valor)) {
                    $output .= $this->displayError($this->l('Debe especificar '.$key));
                    $error = true;
                    break;
                } else {
                    $config[$key] = $valor;
                }
            }
            if ($config['LIBREDTE_RUT']) {
                $config['LIBREDTE_RUT'] = str_replace('.','',$config['LIBREDTE_RUT']);
                $rut = \sowerphp\app\Utility_Rut::check($config['LIBREDTE_RUT']);
                if (!$rut) {
                    $output .= $this->displayError($this->l('RUT del emisor es incorrecto'));
                    $error = true;
                }
            }
            if (!$error) {
                foreach ($config as $key => $value) {
                    Configuration::updateValue($key, $value);
                }
                $output .= $this->displayConfirmation($this->l('Configuración del módulo LibreDTE ha sido actualizada'));
            }
        }
        return $output.$this->displayForm().$this->footer();
    }

    /**
     * Método que genera el formulario de la configuración del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-07-03
     */
    private function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        // campos del formulario
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Configuración conexión'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('URL servidor LibreDTE'),
                    'name' => 'LIBREDTE_URL',
                    'size' => 50,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Hash usuario autorizado'),
                    'name' => 'LIBREDTE_HASH',
                    'size' => 50,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('RUT emisor'),
                    'name' => 'LIBREDTE_RUT',
                    'size' => 50,
                    'required' => true,
                ],
            ],
            'submit' => [
                'title' => $this->l('Guardar'),
                'class' => 'button'
            ]
        ];
        $helper = new HelperForm();
        // módulo, token e índice actual
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        // idioma
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        // título y barra de herramientas
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Guardar'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Volver')
            ],
        ];
        // asignar valores para el formulario y entregar vista
        foreach ($this->defaultConfig as $key => $value) {
            $helper->fields_value[$key] = Configuration::get($key);
        }
        return $helper->generateForm($fields_form);
    }

    /**
     * Método que genera el footer de la página de configuración del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2018-07-03
     */
    private function footer()
    {
        return '<p><a href="https://libredte.cl" target="_blank">LibreDTE</a> es un proyecto de <a href="https://sasco.cl" target="_blank">SASCO SpA</a> que tiene como misión proveer de facturación electrónica libre para Chile.</p>';
    }

}
