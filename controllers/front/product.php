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

/**
 * Controlador para trabajar con los productos de PrestaShop
 * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
 * @version 2016-01-30
 */
class LibredteProductModuleFrontController extends ModuleFrontController
{

    /**
     * Acción que permite obtener los datos de un item (producto) para poder
     * consumir desde la aplicación web de LibreDTE
     * @author Esteban De La Fuente Rubio, DeLaF (esteban[at]sasco.cl)
     * @version 2016-01-30
     */
    public function init()
    {
        $item = [];
        // inicializar controlador
        parent::init();
        // columna que se usará para identificar al producto
        if (!($column = Tools::getValue('column'))) {
            $column = 'product_id';
        }
        // recuperar ID del producto
        if ($product_id = Tools::getValue('product_id')) {
            if ($column != 'product_id') {
                $db = Db::getInstance();
                $sql = new DbQuery();
                $sql->select('id_product');
                $sql->from('product');
                $sql->where($db->_escape($column).' = \''.$db->_escape($product_id).'\'');
                $r = Db::getInstance()->executeS($sql);
                $product_id = isset($r[0]['id_product']) ? (int)$r[0]['id_product'] : 0;
            } else {
                $product_id = (int)$product_id;
            }
        } else {
            $product_id = 0;
        }
        // crear objeto con el producto
        if ($product_id) {
            $this->product = new Product($product_id, true, $this->context->language->id, $this->context->shop->id);
        }
        // si el objeto no existe error
        if (!Validate::isLoadedObject($this->product) or !$this->product->active) {
            header('HTTP/1.1 404 Not Found');
            header('Status: 404 Not Found');
            $item = 'Item solicitado fue encontrado o no está activo';
        }
        // crear datos del item
        else {
            $item = [
                'TpoCodigo' => 'INT1',
                'VlrCodigo' => substr(Tools::getValue('product_id'), 0, 35),
                'NmbItem' => substr($this->product->name, 0, 80),
                'DscItem' => substr($this->product->meta_description, 0, 1000),
                'IndExe' => $this->product->tax_rate ? 0 : 1,
                'UnmdItem' => substr('', 0, 4),
                'PrcItem' => round($this->product->price),
                'ValorDR' => 0,
                'TpoValor' => '$',
            ];
        }
        // enviar item como objeto json
        header('Content-Type: application/json');
        die(json_encode($item, JSON_PRETTY_PRINT));
    }

}
