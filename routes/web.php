<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

# Route::get('/test', function () { return view('test'); });
Route::get('/test', 'TestController@index')->name('test');
Route::get('/test2', 'TestController@index2')->name('test2');


Route::get('/', 'WelcomeController@index')->name('welcome');

// OAuth Redirect URIs
Route::get('/oauth', 'OauthController@index')->name('oauth');
Route::get('/oauth/wish', 'OauthController@wish')->name('oauth.wish');      // Redirect URI
Route::get('/oauth/joom', 'OauthController@joom')->name('oauth.wish');      // Redirect URI
Route::get('/oauth/ae', 'OauthController@ae')->name('oauth.ae');      // Redirect URI

// Amazon SP-API
//Route::get('/oauth/amazon/auth', 'OauthController@amazonAuth')->name('oauth.amazon.auth');
Route::get('/oauth/amazon', 'OauthController@amazon')->name('oauth.amazon');                            // URI de inicio de sesión de OAuth
Route::post('/oauth/amazon/auth', 'OauthController@amazonBuild')->name('oauth.amazon.build');           // POST del FORM
Route::get('/oauth/amazon/redirect', 'OauthController@amazonRedirect')->name('oauth.amazon.redirect');  // URI de redirección de OAut

Route::get('/oauth/google', 'OauthController@google')->name('oauth.google');      // Redirect URI

Route::get('/telegram', 'TelegramController@index')->name('telegram.index');
Route::get('/telegram/webhook', 'TelegramController@webhook')->name('telegram.webhook');
Route::get('/telegram/{bot}/invite/{invite_code}', 'TelegramController@invite')->name('telegram.invite');


// Route prefix: /guest/
// Roue names prefix: guest_service.
// Controllers Namespace: Controllers\Guest
Route::prefix('guest')->name('guest_service.')->namespace('Guest')->middleware(['GuestServiceAuth', 'throttle:60,1'])->group(function () {
    /* Route::get('/guest', 'GuestServiceController@index')->name('guest_service.index'); */

    //Route::get('/guest/order/{order}/track/{order_item}', 'GuestServiceController@orderTrack')->name('guest_service.order.track');
    Route::get('order/{order}/track', 'GuestServiceController@orderTrack')->name('order.track');
    Route::post('order/{order}/track', 'GuestServiceController@orderTrackStore')->name('order.track.store');
});





Auth::routes();


Route::group(['middleware' => ['role:admin']], function () {

    //Auth::routes();

    # PRODUCTS

    Route::get('/products/{product}/shops', 'ProductController@shops')->name('products.shops');
    Route::post('/products/{product}/relateds', 'ProductController@storeRelated')->name('products.storerelated');
    Route::delete('/products/{product}/relateds/{related}', 'ProductController@destroyRelated')->name('products.destroyrelated');
    Route::get('/products/{product}/scrape', 'ProductController@scrape')->name('products.scrape');
    Route::get('/products/{product}/scrape/{scrapeClass}', 'ProductController@scrapeBy')->name('products.scrapeby');

    Route::get('/products/{product}/attributes/create', 'ProductAttributeController@create')->name('products.attributes.create');
    Route::post('/products/{product}/attributes', 'ProductAttributeController@store')->name('products.attributes.store');
    Route::get('/products/{product}/attributes/{provider_product_attribute}/edit', 'ProductAttributeController@edit')->name('products.attributes.edit');
    Route::patch('/products/{product}/attributes/{provider_product_attribute}', 'ProductAttributeController@update')->name('products.attributes.update');
    Route::delete('/products/{product}/attributes/{provider_product_attribute}', 'ProductAttributeController@destroy')->name('products.attributes.destroy');

    # CATEGORIES

    Route::get('/categories/import', 'CategoryController@import')->name('categories.import');
    Route::get('/categories/canons', 'CategoryController@canons')->name('categories.canons');
    # Route::get('/categories/canons/sync', 'CategoryController@syncCanons')->name('categories.canons.sync');

    # ORDERS

    Route::get('/orders/create', 'OrderController@create')->name('orders.create');
    Route::post('/orders', 'OrderController@store')->name('orders.store');
    Route::delete('/orders/{order}', 'OrderController@destroy')->name('orders.destroy');

    /* Route::resource('suppliers.orsupplier_params', 'SupplierParamController')->only([
        'index', 'store', 'edit', 'update', 'destroy'
    ]); */

    Route::resource('order_payments', 'OrderPaymentController')->only([
        'index', 'edit', 'update'
    ]);
    Route::get('/order_payments/get', 'OrderPaymentController@get')->name('order_payments.get');
    Route::get('/order_payments/export', 'OrderPaymentController@export')->name('order_payments.export');


    # BUYERS

    Route::get('/buyers', 'BuyerController@index')->name('buyers.index');
    Route::get('/buyers/create', 'BuyerController@create')->name('buyers.create');
    Route::post('/buyers', 'BuyerController@store')->name('buyers.store');
    Route::delete('/buyers/{buyer}', 'BuyerController@destroy')->name('buyers.destroy');

    # PRICES

    Route::get('/prices/price', 'PriceController@price')->name('prices.price');

    # SUPPLIERS

    Route::get('/action/suppliers', 'ActionController@suppliers')->name('action.suppliers');

    Route::get('/suppliers/{supplier}/products/get', 'SupplierProductController@get')->name('suppliers.products.get');
    Route::get('/suppliers/{supplier}/products/getpricesstocks', 'SupplierProductController@getPricesStocks')->name('suppliers.products.getpricesstocks');
    Route::get('/suppliers/{supplier}/products/{product}/get', 'SupplierProductController@getProduct')->name('suppliers.products.get.product');

    Route::resource('suppliers.supplier_categories', 'SupplierCategoryController')->only([
        'index', 'edit', 'update'
    ]);

    Route::resource('suppliers.supplier_filters', 'SupplierFilterController')->only([
        'index', 'store', 'edit', 'update', 'destroy'
    ]);

    Route::get('/suppliers/{supplier}/supplier_params/sync', 'SupplierParamController@sync')->name('suppliers.supplier_params.sync');
    Route::resource('suppliers.supplier_params', 'SupplierParamController')->only([
        'index', 'store', 'edit', 'update', 'destroy'
    ]);

    # MARKETPLACES

    Route::get('/markets/{market}/market_params/sync', 'MarketParamController@sync')->name('markets.market_params.sync');
    Route::resource('markets.market_params', 'MarketParamController')->only([
        'index', 'store', 'edit', 'update', 'destroy'
    ]);

    # GENERAL RESOURCES

    Route::resources([
        'brands'            =>  'BrandController',
        'categories'        =>  'CategoryController',

        'prices'            =>  'PriceController',
        'promos'            =>  'PromoController',
        'receivers'         =>  'ReceiverController',

        'suppliers'         =>  'SupplierController',
        'markets'           =>  'MarketController',
        'shops'             =>  'ShopController',

        'log_notifications' =>  'LogNotificationController',
    ]);

    # LOG_SCHEDULES
    Route::resource('log_schedules', 'LogScheduleController')
        ->only(['index']);

    # DT1P

    Route::get('/logs', 'LogsController@index')->name('logs');
    Route::get('/logs/view', 'LogsController@view')->name('logs.view');
    Route::get('/logs/errors', 'LogsController@errors')->name('logs.errors');

    Route::get('/config', 'ConfigController@index')->name('config');
    Route::post('/config', 'ConfigController@getRequest')->name('config.get');

    Route::get('/utils', 'UtilsController@index')->name('utils');
    Route::get('/utils/match/eans', 'UtilsController@matchEans')->name('utils.match.eans');
    Route::get('/utils/match/images', 'UtilsController@matchImages')->name('utils.match.images');
    Route::get('/utils/file', 'UtilsController@selectFileType')->name('utils.file.select');
    Route::post('/utils/file', 'UtilsController@generateFile')->name('utils.file.generate');
    Route::get('/utils/file/get', 'UtilsController@getFile')->name('utils.file.get');
    Route::post('/utils/file/get', 'UtilsController@processFile')->name('utils.file.process');
    Route::get('/utils/product/backslash', 'UtilsController@removeProductsBackslashes')->name('utils.product.backslash');
    Route::get('/utils/update/vox', 'UtilsController@updateVox')->name('utils.update.vox');
    Route::post('/utils/import', 'UtilsController@import')->name('utils.import');
    Route::post('/utils/import/process', 'UtilsController@importProcess')->name('utils.import.process');
    Route::post('/utils/supplier_orders', 'UtilsController@getSupplierOrders')->name('utils.supplier_orders');
    Route::post('/utils/order_categories', 'UtilsController@getOrderCategories')->name('utils.order_categories');
    Route::post('/utils/test', 'UtilsController@test')->name('utils.test');

    Route::get('/utils/mailjet/sectors', 'UtilsController@mailjetSectors')->name('utils.mailjet.sectors');
    Route::get('/utils/mailjet/blockeds', 'UtilsController@mailjetBlockeds')->name('utils.mailjet.blockeds');
    Route::get('/utils/mailjet/deleteds', 'UtilsController@mailjetDeleteds')->name('utils.mailjet.deleteds');
    Route::post('/utils/mailjet', 'UtilsController@mailjet')->name('utils.mailjet');

    Route::get('/utils/own_suppliers/delete', 'UtilsController@getDeleteOwnSupplierFromShopFilters')->name('utils.own_suppliers.delete');



    Route::get('/requests', 'RequestsController@index')->name('requests');
    Route::post('/requests', 'RequestsController@getRequest')->name('requests.get');
});


Route::group(['middleware' => ['role:admin|owner|user|seller|saas']], function () {
    # HOME

    Route::get('/logout', 'WelcomeController@index')->name('welcome');
    Route::get('/home', 'HomeController@index')->name('home');

    # AUTOCOMPLETES

    Route::get('/autocomplete/attributes', 'AutocompleteController@attributes')->name('autocomplete.attributes');
    Route::get('/autocomplete/buyers', 'AutocompleteController@buyers')->name('autocomplete.buyers');
    Route::get('/autocomplete/categories', 'AutocompleteController@categories')->name('autocomplete.categories');

    Route::get('/autocomplete/supplierbrands', 'AutocompleteController@supplierBrands')->name('autocomplete.supplierbrands');
    Route::get('/autocomplete/suppliercategories', 'AutocompleteController@supplierCategories')->name('autocomplete.suppliercategories');

    Route::get('/autocomplete/rootcategories', 'AutocompleteController@rootCategories')->name('autocomplete.rootcategories');
    Route::get('/autocomplete/marketcategories', 'AutocompleteController@marketCategories')->name('autocomplete.marketcategories');
    Route::get('/autocomplete/brands', 'AutocompleteController@brands')->name('autocomplete.brands');
    Route::get('/autocomplete/marketbrands', 'AutocompleteController@marketBrands')->name('autocomplete.marketbrands');
    Route::get('/autocomplete/marketattributes', 'AutocompleteController@marketAttributes')->name('autocomplete.marketattributes');
    Route::get('/autocomplete/products', 'AutocompleteController@products')->name('autocomplete.products');
    Route::get('/autocomplete/propertyvalues', 'AutocompleteController@propertyValues')->name('autocomplete.propertyvalues');
    Route::get('/autocomplete/types/{type}', 'AutocompleteController@types')->name('autocomplete.types');
});


Route::group(['middleware' => ['role:admin|owner|user|seller']], function () {

    # HOME

    Route::get('/home/json', 'HomeController@json')->name('home.json');

    # PRODUCTS

    Route::get('/products/images', 'ProductController@indexImages')->name('products.index.images');
    Route::get('/products/create', 'ProductController@create')->name('products.create');
    Route::post('/products', 'ProductController@store')->name('products.store');
    Route::get('/products/{product}/edit', 'ProductController@edit')->name('products.edit');
    Route::patch('/products/{product}', 'ProductController@update')->name('products.update');
    Route::delete('/products/{product}', 'ProductController@destroy')->name('products.destroy');

    Route::get('/products/{product}/images', 'ProductController@images')->name('products.images');
    Route::post('/products/{product}/images', 'ProductController@storeImages')->name('products.storeimages');
    Route::get('/products/{product}/images/order', 'ProductController@orderImages')->name('products.orderimages');

    Route::get('/products/{product}/relateds', 'ProductController@relateds')->name('products.relateds');

    Route::get('/products/{product}/attributes', 'ProductAttributeController@index')->name('products.attributes');

    Route::get('/products', 'ProductController@index')->name('products.index');
    Route::get('/products/export', 'ProductController@export')->name('products.export');
    Route::get('/products/{product}', 'ProductController@show')->name('products.show');
    Route::get('/products/{product}/addtoshop', 'ProductController@addToShop')->name('products.addtoshop');
    Route::post('/products/{product}/addtoshop', 'ProductController@addToShopUpdate')->name('products.addtoshopupdate');

    # ORDERS

    Route::get('/orders', 'OrderController@index')->name('orders.index');
    Route::get('/orders/{order}', 'OrderController@show')->name('orders.show');
    Route::get('/orders/{order}/send', 'OrderController@send')->name('orders.send');    // Send Notification

    Route::get('/orders/{order}/carriers/get', 'OrderShipmentController@getCarriers')->name('orders.carriers.get');
    Route::get('/orders/{order}/shipments', 'OrderShipmentController@index')->name('orders.shipments');
    Route::post('/orders/{order}/shipments', 'OrderShipmentController@store')->name('orders.shipments.store');
    Route::get('/orders/{order}/shipments/{shipment}', 'OrderShipmentController@edit')->name('orders.shipments.edit');
    Route::patch('/orders/{order}/shipments/{shipment}/update', 'OrderShipmentController@update')->name('orders.shipments.update');

    Route::get('/orders/{order}/comments/get', 'OrderCommentController@get')->name('orders.comments.get');
    Route::get('/orders/{order}/comments', 'OrderCommentController@index')->name('orders.comments');
    Route::post('/orders/{order}/comments', 'OrderCommentController@store')->name('orders.comments.store');

    # BUYERS

    Route::get('/buyers/{buyer}', 'BuyerController@show')->name('buyers.show');
    Route::get('/buyers/{buyer}/edit', 'BuyerController@edit')->name('buyers.edit');
    Route::patch('/buyers/{buyer}', 'BuyerController@update')->name('buyers.update');

    # PROMOS

    Route::get('/promos/{promo}/copy', 'PromoController@copy')->name('promos.copy');

    # MARKETPLACES

    Route::get('/action/markets', 'ActionController@markets')->name('action.markets');

    Route::get('/markets/{market}/brands/get', 'MarketBrandController@get')->name('markets.brands.get');
    Route::get('/markets/{market}/brands/auto', 'MarketBrandController@auto')->name('markets.brands.auto');
    Route::get('/markets/{market}/brands/list', 'MarketBrandController@list')->name('markets.brands.list');
    Route::resource('markets.brands', 'MarketBrandController')->only([
        'index', 'edit', 'update'
    ]);

    Route::get('/markets/{market}/categories/get', 'MarketCategoryController@get')->name('markets.categories.get');
    Route::get('/markets/{market}/categories/get/root', 'MarketCategoryController@getRoot')->name('markets.categories.get.root');
    Route::get('/markets/{market}/categories/auto', 'MarketCategoryController@auto')->name('markets.categories.auto');
    Route::get('/markets/{market}/categories/list', 'MarketCategoryController@list')->name('markets.categories.list');
    Route::resource('markets.categories', 'MarketCategoryController')->only([
        'index', 'edit', 'update'
    ]);

    //Route::get('/markets/{market}/carriers/get', 'MarketCarrierController@get')->name('markets.carriers.get');

    Route::get('/markets/{market}/properties/get', 'MarketPropertyController@get')->name('markets.properties.get');
    Route::post('/markets/{market}/properties/get/root', 'MarketPropertyController@getRoot')->name('markets.properties.get.root');
    Route::get('/markets/{market}/properties/auto', 'MarketPropertyController@auto')->name('markets.properties.auto');
    Route::delete('/markets/{market}/properties/{property}/mapping/{attribute_market_attribute}/destroy', 'MarketPropertyController@destroyMapping')
        ->name('markets.properties.mapping.destroy');
    Route::resource('markets.properties', 'MarketPropertyController')->only([
        'index', 'edit', 'update', 'destroy'
    ]);

    # SHOPS & SHOP_PRODUCTS

    Route::get('/action/shops', 'ActionController@shops')->name('action.shops');

    Route::get('/shops/{shop}/shop_products/{shop_product}/get/feed', 'ShopProductController@getFeed')->name('shops.shop_products.get.feed');
    Route::get('/shops/{shop}/shop_products/{shop_product}/post/product', 'ShopProductController@postProduct')->name('shops.shop_products.post.product');

    Route::get('/shops/{shop}/shop_products/calculate', 'ShopProductController@calculatePrices')->name('shops.shop_products.calculate');
    Route::get('/shops/{shop}/shop_products/post/products', 'ShopProductController@postProducts')->name('shops.shop_products.post.products');
    Route::get('/shops/{shop}/shop_products/{shop_product}/post/updated', 'ShopProductController@postUpdated')->name('shops.shop_products.post.updated');
    Route::get('/shops/{shop}/shop_products/{shop_product}/post/price', 'ShopProductController@postPrice')->name('shops.shop_products.post.price');
    Route::get('/shops/{shop}/shop_products/post/updateds', 'ShopProductController@postUpdateds')->name('shops.shop_products.post.updateds');
    Route::get('/shops/{shop}/shop_products/post/prices', 'ShopProductController@postPrices')->name('shops.shop_products.post.prices');
    Route::get('/shops/{shop}/shop_products/synchronize', 'ShopProductController@synchronize')->name('shops.shop_products.synchronize');

    Route::get('/shops/{shop}/shop_products/promo', 'ShopProductController@promo')->name('shops.shop_products.promo');
    Route::get('/shops/{shop}/shop_products/export_product', 'ShopProductController@exportProduct')->name('shops.shop_products.export_product');
    Route::get('/shops/{shop}/shop_products/export_json/{field}', 'ShopProductController@exportJson')->name('shops.shop_products.export_json');
    Route::post('/shops/{shop}/shop_products/export_promo', 'ShopProductController@exportPromo')->name('shops.shop_products.export_promo');
    Route::post('/shops/{shop}/shop_products/repricing', 'ShopProductController@repricing')->name('shops.shop_products.repricing');

    Route::resource('shops.shop_products', 'ShopProductController')->only([
        'index', 'edit', 'update', 'destroy'
    ]);

    Route::get('/shops/{shop}/shop_products/{shop_product}/text', 'ShopProductController@text')->name('shops.shop_products.text');
    Route::post('/shops/{shop}/shop_products/{shop_product}/update_text', 'ShopProductController@update_text')->name('shops.shop_products.update_text');


    # SHOP_FILTERS

    Route::get('/shops/{shop}/shop_filters/filter', 'ShopFilterController@filter')->name('shops.shop_filters.filter');
    Route::get('/shops/{shop}/shop_filters/{shop_filter}/addtoshop', 'ShopFilterController@addToShop')->name('shops.shop_filters.addtoshop');
    Route::post('/shops/{shop}/shop_filters/import', 'ShopFilterController@import')->name('shops.shop_filters.import');
    Route::resource('shops.shop_filters', 'ShopFilterController')->only([
        'index', 'store', 'edit', 'update', 'destroy'
    ]);

    # SHOP_PARAMS

    Route::get('/shops/{shop}/shop_params/sync', 'ShopParamController@sync')->name('shops.shop_params.sync');
    Route::resource('shops.shop_params', 'ShopParamController')->only([
        'index', 'store', 'edit', 'update', 'destroy'
    ]);

    # SHOP GETTERS & POSTERS

    Route::get('/shops/{shop}/get/jobs', 'ShopController@getJobs')->name('shops.get.jobs');
    Route::get('/shops/{shop}/get/orders', 'ShopController@getOrders')->name('shops.get.orders');
    Route::get('/shops/{shop}/carriers/get', 'ShopController@getCarriers')->name('shops.carriers.get');
    Route::get('/shops/{shop}/get/payments', 'ShopController@getPayments')->name('shops.get.payments');

    Route::get('/shops/{shop}/messages', 'ShopMessageController@index')->name('shops.messages.index');
    //Route::get('/shops/{shop}/messages/{shop_message}', 'ShopMessageController@show')->name('shops.messages.show');
    Route::get('/shops/{shop}/messages/get', 'ShopMessageController@get')->name('shops.messages.get');

    Route::get('/shops/{shop}/shop_groups/get', 'ShopGroupController@get')->name('shops.shop_groups.get');
    Route::get('/shops/{shop}/shop_groups/post', 'ShopGroupController@post')->name('shops.shop_groups.post');
    Route::resource('shops.shop_groups', 'ShopGroupController')->only([
        'index', 'store', 'destroy'
    ]);
});


// App\Http\Controllers\Saas   namespace('Saas')->
Route::group(['middleware' => ['role:saas|admin']], function () {
    Route::namespace('Saas')->prefix('saas')->group(function () {

        Route::get('/', 'HomeController@index')->name('saas');

        Route::get('/products', 'ProductController@index')->name('saas.products');
        Route::get('/products/export', 'ProductController@export')->name('saas.products.export');
        Route::get('/products/create', 'ProductController@create')->name('saas.products.create');
        Route::post('/products', 'ProductController@store')->name('saas.products.store');
        Route::get('/products/{product}', 'ProductController@show')->name('saas.products.show');
        Route::get('/products/{product}/edit', 'ProductController@edit')->name('saas.products.edit');
        Route::patch('/products/{product}', 'ProductController@update')->name('saas.products.update');
        Route::delete('/products/{product}', 'ProductController@destroy')->name('saas.products.destroy');

        Route::get('/products/{product}/images', 'ProductController@images')->name('saas.products.images');
        Route::post('/products/{product}/images', 'ProductController@storeImages')->name('saas.products.storeimages');

        Route::get('/products/{product}/addtoshop', 'ProductController@addToShop')->name('saas.products.addtoshop');
        Route::post('/products/{product}/addtoshop', 'ProductController@addToShopUpdate')->name('saas.products.addtoshopupdate');
        Route::get('/products/{product}/shops', 'ProductController@shops')->name('saas.products.shops');

        Route::get('/orders', 'OrderController@index')->name('saas.orders');
        Route::get('/orders/{order}', 'OrderController@show')->name('saas.orders.show');

        Route::get('/buyers/{buyer}', 'BuyerController@show')->name('saas.buyers.show');
        Route::get('/buyers/{buyer}/edit', 'BuyerController@edit')->name('saas.buyers.edit');
        Route::patch('/buyers/{buyer}', 'BuyerController@update')->name('saas.buyers.update');

        //Route::get('/suppliers', 'SupplierController@index')->name('saas.suppliers');
        Route::get('/suppliers/{supplier}/products/get', 'SupplierProductController@get')->name('saas.suppliers.products.get');
        Route::get('/suppliers/{supplier}/products/getpricesstocks', 'SupplierProductController@getPricesStocks')->name('saas.suppliers.products.getpricesstocks');
        Route::resource('suppliers', 'SupplierController', ['names' => [
            'index' => 'saas.suppliers',
            'create' => 'saas.suppliers.create',
            'store' => 'saas.suppliers.store',
            'edit' => 'saas.suppliers.edit',
            'update' => 'saas.suppliers.update',
            'destroy' => 'saas.suppliers.destroy',
        ]])->only([
            'index', 'create', 'store', 'edit', 'update', 'destroy'
        ]);

        Route::get('/markets', 'MarketController@index')->name('saas.markets');
        Route::get('/markets/{market}/market_categories', 'MarketCategoryController@index')->name('saas.markets.market_categories');
        Route::resource('markets.supplier_categories', 'MarketSupplierCategoryController', ['names' => [
            'index' => 'saas.markets.supplier_categories',
            'edit' => 'saas.markets.supplier_categories.edit',
            'update' => 'saas.markets.supplier_categories.update',
        ]])->only([
            'index', 'edit', 'update'
        ]);

        Route::resource('shops', 'ShopController', ['names' => [
            'index' => 'saas.shops',
            'create' => 'saas.shops.create',
            'store' => 'saas.shops.store',
            'edit' => 'saas.shops.edit',
            'update' => 'saas.shops.update'
        ]])->only([
            'index', 'create', 'store', 'edit', 'update'
        ]);

        Route::get('/shops/{shop}/shop_params/sync', 'ShopParamController@sync')->name('saas.shops.shop_params.sync');
        Route::resource('shops.shop_params', 'ShopParamController', ['names' => [
            'index' => 'saas.shops.shop_params',
            'store' => 'saas.shops.shop_params.store',
            'edit' => 'saas.shops.shop_params.edit',
            'update' => 'saas.shops.shop_params.update',
            'destroy' => 'saas.shops.shop_params.destroy',
        ]])->only([
            'index', 'store', 'edit', 'update', 'destroy'
        ]);

        Route::get('/shops/{shop}/shop_filters/filter', 'ShopFilterController@filter')->name('saas.shops.shop_filters.filter');
        Route::post('/shops/{shop}/shop_filters/import', 'ShopFilterController@import')->name('saas.shops.shop_filters.import');
        Route::get('/shops/{shop}/shop_filters/{shop_filter}/addtoshop', 'ShopFilterController@addToShop')->name('saas.shops.shop_filters.addtoshop');
        Route::resource('shops.shop_filters', 'ShopFilterController', ['names' => [
            'index' => 'saas.shops.shop_filters',
            'store' => 'saas.shops.shop_filters.store',
            'edit' => 'saas.shops.shop_filters.edit',
            'update' => 'saas.shops.shop_filters.update',
            'destroy' => 'saas.shops.shop_filters.destroy',
        ]])->only([
            'index', 'store', 'edit', 'update', 'destroy'
        ]);

        Route::get('/shops/{shop}/shop_products/calculate', 'ShopProductController@calculatePrices')->name('saas.shops.shop_products.calculate');
        Route::get('/shops/{shop}/shop_products/post/products', 'ShopProductController@postProducts')->name('saas.shops.shop_products.post.products');
        Route::get('/shops/{shop}/shop_products/synchronize', 'ShopProductController@synchronize')->name('saas.shops.shop_products.synchronize');
        Route::get('/shops/{shop}/shop_products/{shop_product}/post/product', 'ShopProductController@postProduct')->name('saas.shops.shop_products.post.product');
        Route::resource('shops.shop_products', 'ShopProductController', ['names' => [
            'index' => 'saas.shops.shop_products',
            'edit' => 'saas.shops.shop_products.edit',
            'update' => 'saas.shops.shop_products.update',
            'destroy' => 'saas.shops.shop_products.destroy',
        ]])->only([
            'index', 'store', 'edit', 'update', 'destroy'
        ]);


    });
});
