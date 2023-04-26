<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');
$routes->get('/api/mobile/v1/test_redis', 'App::checkRedis');
$routes->get('/api/mobile/v1', 'App::index');
$routes->get('/api/mobile/v1/generate_reference_no', 'App::generateReferenceNo');
$routes->get('/api/mobile/v1/get_shop_categories', 'App::getShopCategories');
$routes->get('/api/mobile/v1/get_all_products_by_shop_category/(:any)', 'App::getAllProductByBusinessCategoryId/$1');
$routes->get('/api/mobile/v1/get_similar_products/(:any)', 'App::getSimilarProducts/$1');
$routes->post('/api/mobile/v1/save_shipping_address', 'App::saveShippingAddress');
$routes->get('/api/mobile/v1/get_shipping_address/(:any)', 'App::getClientShippingAddress/$1');
$routes->get('/api/mobile/v1/get_product_category/(:any)', 'App::getAllCategories/$1');
$routes->post('/api/mobile/v1/client_request_order', 'App::clientRequestOrder');
$routes->get('/api/mobile/v1/tracking_order/(:any)', 'App::trackingOrderByReference/$1');
$routes->get('/api/mobile/v1/get_nearby_client_request/(:any)', 'App::getProductToDelivery/$1');
$routes->post('/api/mobile/v1/create_client_account', 'App::createClientAccount');

$routes->get('/api/web/v1/get_customer_orders', 'Home::getAllOrders');

$routes->add('/(:any)', 'Home::$1');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
