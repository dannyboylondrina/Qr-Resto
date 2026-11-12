<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

// ---------------------------------------------------------------- Public (guests)
$routes->get('/', 'Home::index');
$routes->get('lang/(:alpha)', 'Home::lang/$1');

$routes->get('menu', 'Menu::index');
$routes->get('api/table/(:segment)', 'Menu::tableInfo/$1');
$routes->post('promo/check', 'Menu::checkPromo');
$routes->post('service-request', 'Menu::serviceRequest');
$routes->post('order', 'Orders::place');

$routes->get('reserve', 'Reserve::index');
$routes->post('reserve', 'Reserve::submit');
$routes->get('reserve/tables', 'Reserve::availableTables');
$routes->get('reserve/done/(:segment)', 'Reserve::done/$1');

$routes->get('tables', 'Tables::index');
$routes->get('tables/live', 'Tables::live');

$routes->get('track', 'Track::index');
$routes->get('track/(:segment)/status', 'Track::status/$1');
$routes->get('track/(:segment)/receipt', 'Track::receipt/$1');
$routes->post('track/(:segment)/payment', 'Track::resubmit/$1');
$routes->post('track/(:segment)/review', 'Track::review/$1');
$routes->get('track/(:segment)', 'Track::show/$1');

// ---------------------------------------------------------------- Auth
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::store');
$routes->get('forgot-password', 'Auth::forgot');
$routes->post('forgot-password', 'Auth::sendReset');
$routes->get('reset-password/(:segment)', 'Auth::reset/$1');
$routes->post('reset-password', 'Auth::doReset');
$routes->get('mfa', 'Auth::mfa');
$routes->post('mfa', 'Auth::verifyMfa');
$routes->post('logout', 'Auth::logout');
$routes->get('auth/google', 'Auth::google');
$routes->get('auth/google/callback', 'Auth::googleCallback');

// ---------------------------------------------------------------- Kitchen display (kitchen + cashier + admin)
$routes->group('kitchen', ['filter' => 'auth:kitchen,cashier'], static function ($routes) {
    $routes->get('/', 'Staff\Kitchen::index');
    $routes->get('board', 'Staff\Kitchen::board');
    $routes->post('(:num)/(preparing|ready)', 'Staff\Kitchen::setStatus/$1/$2');
    $routes->post('item/(:num)/toggle', 'Staff\Kitchen::toggleItem/$1');
});

// ---------------------------------------------------------------- Staff (any signed-in staff)
$routes->group('staff', ['filter' => 'auth'], static function ($routes) {
    $routes->get('pulse', 'Staff\Dashboard::pulse');
    $routes->get('account', 'Staff\Account::index');
    $routes->post('account/password', 'Staff\Account::password');
    $routes->post('account/mfa/start', 'Staff\Account::mfaStart');
    $routes->post('account/mfa/enable', 'Staff\Account::mfaEnable');
    $routes->post('account/mfa/disable', 'Staff\Account::mfaDisable');
});

// ---------------------------------------------------------------- Cashier + admin
$routes->group('staff', ['filter' => 'auth:cashier'], static function ($routes) {
    $routes->get('/', 'Staff\Dashboard::index');

    $routes->get('orders', 'Staff\Orders::index');
    $routes->get('orders/board', 'Staff\Orders::board');
    $routes->get('orders/(:num)/receipt', 'Staff\Orders::receipt/$1');
    $routes->post('orders/(:num)/paid', 'Staff\Orders::markPaid/$1');
    $routes->post('orders/(:num)/approve', 'Staff\Orders::approve/$1');
    $routes->post('orders/(:num)/reject', 'Staff\Orders::reject/$1');
    $routes->post('orders/(:num)/status/(:alpha)', 'Staff\Orders::setStatus/$1/$2');
    $routes->post('orders/(:num)/discount', 'Staff\Orders::discount/$1');
    $routes->post('orders/(:num)/remove-discount', 'Staff\Orders::removeDiscount/$1');
    $routes->post('orders/(:num)/cancel', 'Staff\Orders::cancel/$1');

    $routes->get('service', 'Staff\Service::index');
    $routes->get('service/list', 'Staff\Service::list');
    $routes->post('service/(:num)/done', 'Staff\Service::markDone/$1');

    $routes->get('floor', 'Staff\Floor::index');
    $routes->get('floor/board', 'Staff\Floor::board');
    $routes->post('floor/(:num)/status', 'Staff\Floor::setStatus/$1');
    $routes->post('floor/visit/(:num)/settle', 'Staff\Floor::settle/$1');
    $routes->post('floor/visit/(:num)/close', 'Staff\Floor::close/$1');

    $routes->get('reservations', 'Staff\Reservations::index');
    $routes->post('reservations/(:num)/(confirm|cancel|noshow|approve|reject|assign|seat)', 'Staff\Reservations::action/$1/$2');
});

// ---------------------------------------------------------------- Admin only
$routes->group('staff', ['filter' => 'auth:admin'], static function ($routes) {
    $routes->get('menu', 'Staff\MenuAdmin::index');
    $routes->get('menu/new', 'Staff\MenuAdmin::form');
    $routes->get('menu/(:num)/edit', 'Staff\MenuAdmin::form/$1');
    $routes->post('menu/save', 'Staff\MenuAdmin::save');
    $routes->post('menu/(:num)/delete', 'Staff\MenuAdmin::delete/$1');
    $routes->post('menu/(:num)/toggle', 'Staff\MenuAdmin::toggle/$1');
    $routes->post('categories/save', 'Staff\MenuAdmin::saveCategory');
    $routes->post('categories/(:num)/delete', 'Staff\MenuAdmin::deleteCategory/$1');

    $routes->get('qr-codes', 'Staff\TablesAdmin::index');
    $routes->post('tables/save', 'Staff\TablesAdmin::save');
    $routes->post('tables/(:num)/delete', 'Staff\TablesAdmin::delete/$1');

    $routes->get('promos', 'Staff\Promos::index');
    $routes->post('promos/save', 'Staff\Promos::save');
    $routes->post('promos/(:num)/toggle', 'Staff\Promos::toggle/$1');
    $routes->post('promos/(:num)/delete', 'Staff\Promos::delete/$1');

    $routes->get('reviews', 'Staff\Reviews::index');
    $routes->post('reviews/(:num)/(approve|hide|delete)', 'Staff\Reviews::action/$1/$2');

    $routes->get('reports', 'Staff\Reports::index');
    $routes->get('reports/export/(pdf|xlsx)', 'Staff\Reports::export/$1');

    $routes->get('payment-settings', 'Staff\PaymentSettings::index');
    $routes->post('payment-settings', 'Staff\PaymentSettings::save');
    $routes->post('banks/save', 'Staff\PaymentSettings::saveBank');
    $routes->post('banks/(:num)/delete', 'Staff\PaymentSettings::deleteBank/$1');

    $routes->get('users', 'Staff\Users::index');
    $routes->post('users/save', 'Staff\Users::save');
    $routes->post('users/(:num)/reset-password', 'Staff\Users::resetPassword/$1');
    $routes->post('users/(:num)/reset-mfa', 'Staff\Users::resetMfa/$1');

    $routes->get('activity', 'Staff\Activity::index');
});
