<?php
namespace Webstatus;

$webstatus = new Webstatus($webstatus_file, $sources_file);
$available_locales = $webstatus->getAvailableLocales();
$available_products =  $webstatus->getAvailableProducts();
$webstatus_data = $webstatus->getWebstatusData();
$webstatus_metadata = $webstatus->getWebstatusMetadata();

$requested_locale = Utils::getQueryParam('locale', Utils::detectLocale($available_locales));
$requested_product = Utils::getQueryParam('product', 'all');

// Check if the requested product is supported
$supported_product = (in_array($requested_product, array_keys($available_products))) ? true : false;
if ($supported_product) {
    $product_name = $available_products[$requested_product]['name'];
} else {
    $product_name = 'N/A';
}

// Default: don't display note for XLIFF files
$xliff_note = false;

// Calculate some common data for products
$calculate_row_style = function ($product_data) {
    $result = [];

    // Determine percentage of completeness. For .properties files
    // I consider also the number of identical strings
    $percentage = $product_data['percentage'];
    if ($product_data['source_type'] == 'properties') {
        $perc_identical = $product_data['identical'] / $product_data['total'] * 100;
        if ($perc_identical > 20) {
            $percentage = $percentage - $perc_identical;
        }
    }

    if ($product_data['error_status']) {
        $result['class'] = 'error';
        $result['style'] = '';
    } else {
        $result['class'] = '';
        $result['style'] = Utils::getRowStyle($percentage);
    }

    return $result;
};

$template_meta = [];
$table_rows = [];
if ($requested_product != 'all') {
    // Requested view: A single product for all locales
    $requested_locale = 'All locales';
    $page_title = "Web Status – {$product_name}";
    $supported = $supported_product;
    $template_name = 'main_single_product.twig';

    if ($supported) {
        $template_meta['complete_locales'] = 0;
        $template_meta['total_locales'] = 0;

        // Check if we need to display the note for this specific product
        $xliff_note = $webstatus->getsoUrceType($requested_product) == 'xliff' ? true : false;

        foreach ($available_locales as $locale_code) {
            if (isset($webstatus_data[$locale_code][$requested_product])) {
                $current_product = $webstatus_data[$locale_code][$requested_product];

                // Count number of locales (overall, completely localized)
                if ($current_product['percentage'] == 100) {
                    $template_meta['complete_locales'] = $template_meta['complete_locales'] + 1;
                }
                $template_meta['total_locales'] = $template_meta['total_locales'] + 1;

                // Calculate inline CSS and class for this row
                $row_style = $calculate_row_style($current_product);

                $row = [
                    'class'           => $row_style['class'],
                    'locale'          => $locale_code,
                    'product_data'    => $current_product,
                    'product_id'      => $requested_product,
                    'style'           => $row_style['style'],
                ];
                array_push($table_rows, $row);
            }
        }
    }
} else {
    // Requested view: All products for a single locales
    $page_title = "Web Status – {$requested_locale}";
    $supported = in_array($requested_locale, $available_locales);
    $template_name = 'main_single_locale.twig';

    if ($supported) {
        foreach ($available_products as $product_id => $product) {
            if (array_key_exists($product_id, $webstatus_data[$requested_locale])) {
                $current_product = $webstatus_data[$requested_locale][$product_id];

                // I display the note even if there's only one XLIFF based product
                if ($webstatus->getsoUrceType($product_id) == 'xliff') {
                    $xliff_note = true;
                }
                // Calculate inline CSS and class for this row
                $row_style = $calculate_row_style($current_product);

                $row = [
                    'class'           => $row_style['class'],
                    'product_data'    => $current_product,
                    'product_id'      => $product_id,
                    'repository_url'  => $product['repository_url'],
                    'repository_type' => $webstatus->getsoUrceType($product_id),
                    'style'           => $row_style['style'],
                ];
                array_push($table_rows, $row);
            }
        }
    }
}

// Determine proper URL for history page
$url_history = "https://l10n.mozilla-community.org/~flod/webstatus_history/?product={$requested_product}&";
if ($requested_locale == 'All locales') {
    $url_history .= "locale=all";
} else {
    $url_history .= "locale={$requested_locale}";
}

$last_update_local = date('Y-m-d H:i e (O)', strtotime($webstatus_metadata['creation_date']));

// Add specific CSS and JS files
array_push($default_css, 'main.css');
array_push($default_js, 'main.js');

print $twig->render(
    $template_name,
    [
        'assets_folder'      => $assets_folder,
        'available_locales'  => $available_locales,
        'available_products' => $available_products,
        'default_css'        => $default_css,
        'default_js'         => $default_js,
        'last_update'        => $last_update_local,
        'page_title'         => $page_title,
        'product_name'       => $product_name,
        'requested_locale'   => $requested_locale,
        'supported'          => $supported,
        'table_rows'         => $table_rows,
        'template_meta'      => $template_meta,
        'url_history'        => $url_history,
        'xliff_note'         => isset($xliff_note) && $xliff_note,
    ]
);
