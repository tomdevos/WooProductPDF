<?php
/**
 * Plugin name: WooProductPDF
 * Plugin URI: https://github.com/tomdevos/WooProductPDF
 * Description: A Wordpress Plugin for adding WooCommerce Product Sheets in PDF
 * Author: Tom Devos
 * Author URI: https://www.tomdevos.be
 * Version: 0.1
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: woocommerce-pdf-invoices-packing-slips
 * WC requires at least: 2.2.0
 * WC tested up to: 3.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( !class_exists( 'WooProductPdf' ) ) :

    class WooProductPdf
    {
        public function __construct() {
            add_action( 'woocommerce_product_meta_start', [$this,'show_button'] );
            add_action( 'init', [$this,'init'] );
            require "vendor/autoload.php";

        }

        public function show_button() {
            global $product;
            $id = $product->get_id();
            echo "<a href='?pdf=".$id."' target='_blank'>Download PDF</a>";
        }

        public function init() {
            if(isset($_GET['pdf'])) {
                //Generate and show the Product PDF
                $product = wc_get_product( $_GET['pdf'] );
                $dompdf = new \Dompdf\Dompdf;

                $name = $product->get_name();
                $logo = get_theme_mod('site_logo');
                $logo = str_replace('https://'.$_SERVER['SERVER_NAME'],'',$logo);
                $logo = getcwd().$logo;
                $description = $product->get_description();
                $short_description = $product->get_short_description();

                $price = $product->get_price();
                $categories = $product->get_categories();
                $image = wp_get_attachment_image_src($product->get_image_id(), 'full');
                $image = getcwd().str_replace('https://'.$_SERVER['SERVER_NAME'],'',$image[0]);
                $sku = $product->get_sku();

                $attributes = $product->get_attributes();
                $the_attributes = [];
                $specs = "";
                $additional_information = __('Additional information','woocommerce');
                foreach($attributes as $attribute) {
                    $attr_name = wc_attribute_label($attribute->get_name());
                    $attr_name = __($attr_name, 'wordpress');
                    $attr_value = "";
                    $sep = "";
                    foreach($attribute->get_terms() as $term) {
                        $attr_value.=$sep.$term->name;
                        $sep = ", ";
                    }

                    $specs .= <<<EOD
        <tr>
            <th>$attr_name</th>
            <td>$attr_value</td>
       </tr>
EOD;

                }

                $html = <<<EOD
<html>
<head>
    <style type="text/css">
        *, h2 {
        font-family: sans-serif;
        }
        .header {
            background-color:#000;
            padding:20px;
        }
        p {
            text-align: justify;
        }
        td {
           text-align:left;
        }
        
        .specs {
            width: 100%;
        }
        .specs td, .specs th {
            padding:10px;
            text-align:left;
        }
        
        .specs tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        .specs tr.header {
            background-color:#000;
            color:#FFF;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="$logo" height="50" />
    </div>
    
    <table width="100%">
        <tr>
            <td width="65%" style="vertical-align: top;">
                <h2>$name</h2>
                <p>$description $short_description</p>
                <h3>$price EUR</h3>
                <p>Artcode: $sku<br>$categories</p>
            </td>
            <td width="35%" style="text-align:center;">
                <img src="$image" style="height:300px;" />
            </td>
        </tr>
    </table>
    <p>&nbsp;</p>
    <table class="specs">
    <tr class="header">
    <td colspan="2">$additional_information</td>
</tr>
    $specs
    </table>
    
</body>
</html>
EOD;

                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                $dompdf->stream($product->get_sku()."-".$product->get_slug());
                die;
            }
        }

    }
endif;

new WooProductPdf;

?>