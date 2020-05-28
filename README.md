Plugin LibreDTE para PrestaShop
===============================

Este plugin permite integrar PrestaShop con la
[Aplicación Web de LibreDTE](https://libredte.cl).

Funcionalidades implementadas:

- Emisión de Boleta o Factura desde un pedido pagado de PrestaShop.
- Se ingresa el enlace al PDF de la boleta o factura como nota en el documento del pedido en PrestaShop.
- Permite activar un registro para poder hacer depuración de las llamadas a LibreDTE.
- API para obtener datos de los items desde la página de emisión de LibreDTE.

![Configuración del Plugin en PrestaShop](https://i.imgur.com/nHfN8bS.png "Configuración del Plugin en PrestaShop")

El plugin fue probado con PrestaShop 1.7.6.5

Para que este plugin pueda ser usado se requiere una cuenta con
[Servicio Plus](https://libredte.cl/ventas/dte-plus) activo en la
[Versión Oficial de LibreDTE](https://libredte.cl).

Licencia
--------

Este código está liberado bajo la licencia de software libre [AGPL](http://www.gnu.org/licenses/agpl-3.0.en.html).
Para detalles sobre cómo se puede utilizar, modificar y/o distribuir este plugin revisar los términos de la licencia.
También tiene detalles, en español, sobre esto en los [términos y condiciones](https://legal.libredte.cl) de LibreDTE.

API
---

URL items:

    https://example.com/index.php?fc=module&module=libredte&controller=product&column=reference&product_id=CODIGO
