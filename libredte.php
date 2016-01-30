<?php

/**
 * LibreDTE
 * Copyright (C) SASCO SpA (https://sasco.cl)
 *
 * Este programa es software libre: usted puede redistribuirlo y/o
 * modificarlo bajo los términos de la Licencia Pública General GNU
 * publicada por la Fundación para el Software Libre, ya sea la versión
 * 3 de la Licencia, o (a su elección) cualquier versión posterior de la
 * misma.
 *
 * Este programa se distribuye con la esperanza de que sea útil, pero
 * SIN GARANTÍA ALGUNA; ni siquiera la garantía implícita
 * MERCANTIL o de APTITUD PARA UN PROPÓSITO DETERMINADO.
 * Consulte los detalles de la Licencia Pública General GNU para obtener
 * una información más detallada.
 *
 * Debería haber recibido una copia de la Licencia Pública General GNU
 * junto a este programa.
 * En caso contrario, consulte <http://www.gnu.org/licenses/gpl.html>.
 */

if (!defined('_PS_VERSION_'))
    exit;

// bibliotecas
require_once(_PS_MODULE_DIR_.'libredte/lib/Rut.php');

/**
 * Clase principal del módulo Libredte para Prestashop
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-01-30
 */
class Libredte extends Module
{

    private $defaultConfig = [
        'LIBREDTE_URL' => 'https://libredte.sasco.cl',
        'LIBREDTE_CONTRIBUYENTE' => '',
        'LIBREDTE_PREAUTH_HASH' => '',
    ]; ///< Configuración inicial del módulo

    /**
     * Constructor del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    public function __construct()
    {
        $this->name = 'libredte';
        $this->tab = 'billing_invoicing';
        $this->version = 'dev-master';
        $this->author = 'SASCO SpA';
        $this->need_instance = 0;
        //$this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION];
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('LibreDTE');
        $this->description = $this->l('¡Facturación electrónica libre para Chile!');
        $this->confirmUninstall = $this->l('¿Está seguro de querer desinstalar LibreDTE?');
        if (!Configuration::get('LIBREDTE_URL'))
            $this->warning = $this->l('Falta la URL de LibreDTE');
    }

    /**
     * Método para instalar el módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    public function install()
    {
        if (!parent::install())
            return false;
        foreach ($this->defaultConfig as $key => $value) {
            if (!Configuration::updateValue($key, $value))
                return false;
        }
        return true;
    }

    /**
     * Método para desinstalar el módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    public function uninstall()
    {
        if (!parent::uninstall())
            return false;
        foreach ($this->defaultConfig as $key => $value) {
            if (!Configuration::deleteByName($key))
                return false;
        }
        return true;
    }

    /**
     * Método para generar página de configuración del módulo y procesar el
     * formulario al guardar los cambios
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
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
            if ($config['LIBREDTE_CONTRIBUYENTE']) {
                $rut = \sowerphp\app\Utility_Rut::check($config['LIBREDTE_CONTRIBUYENTE']);
                if (!$rut) {
                    $output .= $this->displayError($this->l('RUT del contribuyente es incorrecto'));
                    $error = true;
                } else {
                    $config['LIBREDTE_CONTRIBUYENTE'] = $rut;
                }
            }
            if (!$error) {
                foreach ($config as $key => $value) {
                    Configuration::updateValue($key, $value);
                }
                $output .= $this->displayConfirmation($this->l('Configuración actualizada'));
            }
        }
        return $output.$this->displayForm().$this->footer();
    }

    /**
     * Método que genera el footer de la página de configuración del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    private function footer()
    {
        return '<p><a href="http://libredte.cl" target="_blank">LibreDTE</a> es un proyecto de <a href="https://sasco.cl" target="_blank">SASCO SpA</a> que tiene como misión proveer de facturación electrónica libre para Chile.</p>';
    }

    /**
     * Método que genera el formulario de la configuración del módulo
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    private function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        // campos del formulario
        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Configuración básica'),
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('URL LibreDTE'),
                    'name' => 'LIBREDTE_URL',
                    'size' => 255,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('RUT contribuyente'),
                    'name' => 'LIBREDTE_CONTRIBUYENTE',
                    'size' => 12,
                    'required' => true,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Hash usuario'),
                    'name' => 'LIBREDTE_PREAUTH_HASH',
                    'size' => 32,
                    'required' => true,
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
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
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ],
        ];
        // asignar valores para el formulario y entregar vista
        foreach ($this->defaultConfig as $key => $value) {
            $helper->fields_value[$key] = Configuration::get($key);
        }
        if ($helper->fields_value['LIBREDTE_CONTRIBUYENTE']) {
            $helper->fields_value['LIBREDTE_CONTRIBUYENTE'] = \sowerphp\app\Utility_Rut::addDV($helper->fields_value['LIBREDTE_CONTRIBUYENTE']);
        }
        return $helper->generateForm($fields_form);
    }

}
