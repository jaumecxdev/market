<?php

namespace App\Http\Controllers;

use App\Market;
use App\Shop;
use App\Traits\HelperTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;


class OauthController extends Controller
{
    use HelperTrait;

    public function index(Request $request)
    {
        return view('product.index', compact('products', 'statuses', 'suppliers', 'params', 'order_params'));
    }


    /* public function amazonAuth(Request $request)
    {
        try {
            Storage::append('mp/amazon/oauth/' . date('y-m-d_H-i'). '_auth.json', json_encode($request->all()));

            $selling_partner_id = $request->input('selling_partner_id');
            $countries = [
                'Australia',
                'Brazil',
                'Canada',
                'France',
                'Germany',
                'India',
                'Italy',
                'Japan',
                'Mexico',
                'Netherlands',
                'Poland',
                'Egypt',
                'Singapore',
                'Spain',
                'Sweden',
                'Turkey',
                'UAE',
                'UK',
                'US',
            ];

            $shop_types = [
                'Seller',
                'Vendor',
            ];

            return view('oauth.amazon', compact('selling_partner_id', 'countries', 'shop_types'));

        } catch (Throwable $th) {
        }
    } */


    public function amazon(Request $request)
    {
        try {
            Storage::append('mp/amazon/oauth/' . date('y-m-d_H-i'). '_amazon.json', json_encode($request->all()));

            // PRIMER: https://sellercentral-europe.amazon.com/apps/authorize/consent?application_id=amzn1.sp.solution.1800f969-3c9a-4507-b9ce-edb4f4cf293a&version=beta
            // Seller Partner autoritza: MPe Market Seller Sync
            // Amazon retorn aquests params:
            // "amazon_callback_uri" => "https://sellercentral-europe.amazon.com/apps/authorize/confirm/amzn1.sp.solution.1800f969-3c9a-4507-b9ce-edb4f4cf293a"
            // "amazon_state" => "MTYyMzIyNTA3NDE1OR1377-9bz7Wq--_vSAY77-9FwPKpW1RdzklD-mYvu-_vRDvv70WE1fvv712JEgBGVsJ77-9WQs_Me-_vV_vv73vv71v77-977-977-9JCle77-9Le-_vWTvv73mv77vv71b"
            // "version" => "beta"
            // "selling_partner_id" => "AOMPAKWU9QV0Z"

            // FORM TO AUTORIZE
            //Storage::append('mp/amazon/oauth/' . date('y-m-d_H-i'). '_Website_workflow.json', json_encode($request->all()));
            $selling_partner_id = $request->input('selling_partner_id');    // ONLY: Marketplace Appstore authorization workflow
            $amazon_callback_uri = $request->input('amazon_callback_uri');  // ONLY: Marketplace Appstore authorization workflow
            $amazon_state = $request->input('amazon_state');                // ONLY: Marketplace Appstore authorization workflow
            $countries = [
                'Australia',
                'Brazil',
                'Canada',
                'France',
                'Germany',
                'India',
                'Italy',
                'Japan',
                'Mexico',
                'Netherlands',
                'Poland',
                'Egypt',
                'Singapore',
                'Spain',
                'Sweden',
                'Turkey',
                'UAE',
                'UK',
                'US',
            ];

            $shop_types = [
                'Seller',
                'Vendor',
            ];

            return view('oauth.amazon', compact('selling_partner_id', 'amazon_callback_uri', 'amazon_state', 'countries', 'shop_types'));

            /* if (!$request->hasAny(['amazon_callback_uri', 'amazon_state'])) {
                return $this->backWithErrorMsg(__METHOD__, 'Amazon no ha devuelto los datos correctos, pongase en contacto con el Servicio Técnico de MPe.', $request->all())
            } */



            // FER LOGIN OR REGISTER A LA APP DEL NOU SELLER PARTNER

            // amazon_callback_uri + redirect_uri + amazon_state + state (Set the Referrer-Policy: no-referrer HTTP)
            // https://sellercentral-europe.amazon.com/apps/authorize/confirm/amzn1.sp.solution.1800f969-3c9a-4507-b9ce-edb4f4cf293a?
            // redirect_uri=https://app.mpespecialist.com/oauth/amazom/redirect&
            // amazon_state=MTYyMzIyNTA3NDE1OR1377-9bz7Wq--_vSAY77-9FwPKpW1RdzklD-mYvu-_vRDvv70WE1fvv712JEgBGVsJ77-9WQs_Me-_vV_vv73vv71v77-977-977-9JCle77-9Le-_vWTvv73mv77vv71b&
            // state=-37131022&version=beta

            /* $mpe_state = uniqid();
            if ($request->has('selling_partner_id')) {
                if ($shop = Shop::where('marketSellerId', $request->input('selling_partner_id'))->first()) {
                    
                }
                else {
                    
                    // FALTARAN AQUESTES DADES -> SI Marketplace Appstore authorization workflow
                    // Refresh Token: $shop->refresh
                    // marketplace_id
                    // endpoint
                    // region

                    // FALTARAN AQUESTES DADES -> SI Website authorization workflow
                    // Refresh Token: $shop->refresh
                }
            } */

            /* $client = new Client(); // ['base_uri' => ]
            $response = $client->get($request->input('amazon_callback_uri'), [
                'headers' => [
                    'Referrer-Policy' => 'no-referrer HTTP',
                ],
                'query' => [
                    'amazon_state'  => $request->input('amazon_state'),
                    'redirect_uri'  => 'https://app.mpespecialist.com/oauth/amazom/redirect',
                    'state'         => $mpe_state,
                    //'version'       => 'beta',
                ],
            ]);

            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append('mp/amazon/oauth/' .date('Y-m-d_H-i'). '_amazon-res.json', json_encode([$response->getStatusCode(), $response->getHeaders(), $response->getBody()]));
                Storage::append('mp/amazon/oauth/' .date('Y-m-d_H-i'). '_amazon.html', $contents);

                // Retorna Login Amazon
                return response($contents, 200)
                    ->header('Content-Type', $response->getHeader('Content-Type'));     //
            }



            return true; */

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, $request);
        }
    }



    public function amazonBuild(Request $request)
    {
        $validatedData = $request->validate([
            'country'               => 'required|max:64',       // Spain
            'shop_type'             => 'required|max:64',       // seller | vendor
            'selling_partner_id'    => 'required|max:64',       // Seller ID
        ]);

        try {
            Storage::append('mp/amazon/oauth/' . date('y-m-d_H-i'). '_build.json', json_encode($request->all()));

            /* if (!$shop = Shop::where('marketSellerId', $request->input('selling_partner_id'))->first())
                return $this->backWithErrorMsg(__METHOD__, 'No se ha encontrado la tienda, vuelva a intentarlo o pongase en contacto con el Soporte Técnico de MPe.', $request->all());
            */

            // Endpoint
            if (in_array($validatedData['country'], ['Singapore', 'Australia', 'Japan'])) {
                $endpoint = 'https://sellingpartnerapi-fe.amazon.com';
                $region = 'us-west-2';
            }
            elseif (in_array($validatedData['country'], ['Canada', 'US', 'Mexico', 'Brazil'])) {
                $endpoint = 'https://sellingpartnerapi-na.amazon.com';
                $region = 'us-east-1';
            }
            else {
                $endpoint = 'https://sellingpartnerapi-eu.amazon.com';
                $region = 'eu-west-1';
            }

            // MarketplaceId
            switch ($validatedData['country']) {
                case 'Canada':
                    $marketplaceId = 'A2EUQ1WTGCTBG2';
                    break;

                case 'US':
                    $marketplaceId = 'ATVPDKIKX0DER';
                    break;

                case 'Mexico':
                    $marketplaceId = 'ATVPDKIKX0DER';
                    break;

                case 'Brazil':
                    $marketplaceId = 'A2Q3Y263D00KWC';
                    break;

                case 'Spain':
                    $marketplaceId = 'A1RKKUPIHCS9HS';
                    break;

                case 'UK':
                    $marketplaceId = 'A1RKKUPIHCS9HS';
                    break;

                case 'France':
                    $marketplaceId = 'A13V1IB3VIYZZH';
                    break;

                case 'Netherlands':
                    $marketplaceId = 'A1805IZSGTT6HS';
                    break;

                case 'Germany':
                    $marketplaceId = 'A1PA6795UKMFR9';
                    break;

                case 'Italy':
                    $marketplaceId = 'APJ6JRA9NG5V4';
                    break;

                case 'Sweden':
                    $marketplaceId = 'A2NODRKZP88ZB9';
                    break;

                case 'Poland':
                    $marketplaceId = 'A1C3SOZRARQ6R3';
                    break;

                case 'Egypt':
                    $marketplaceId = 'ARBP9OOSHTCHU';
                    break;

                case 'Turkey':
                    $marketplaceId = 'A33AVAJ2PDY3EV';
                    break;

                case 'UAE':
                    $marketplaceId = 'A2VIGQ35RCS4UG';
                    break;

                case 'India':
                    $marketplaceId = 'A21TJRUUN4KGV';
                    break;

                case 'Singapore':
                    $marketplaceId = 'A19VAU5U5O7RUS';
                    break;

                case 'Australia':
                    $marketplaceId = 'A39IBJ37TRP1C6';
                    break;

                case 'Japan':
                    $marketplaceId = 'A1VC38T7YXB528';
                    break;
            }


            $mpe_state = uniqid();
            if ($shop = Shop::where('marketSellerId', $request->input('selling_partner_id'))->first()) {
                $shop->market_id = 19;  // Vultr Server
                $shop->app_version = $mpe_state;
                $shop->endpoint = $endpoint;
                $shop->site = $region;
                $shop->country = $marketplaceId;

                $shop->dev_id = '';
                $shop->dev_secret = '';
                $shop->header_url = '';
                $shop->client_id = '';         // MPe Market Seller Sync
                $shop->client_secret = '';  // MPe Market Seller Sync
                $shop->save();
            }
            else {
                $shop = Shop::create([
                    'market_id'         => 19,  // Vultr Server
                    'name'              => $request->input('selling_partner_id'),
                    'code'              => $request->input('selling_partner_id'),
                    'marketSellerId'    => $request->input('selling_partner_id'),
                    'app_version'       => $mpe_state,
                    'endpoint'          => $endpoint,
                    'site'              => $region,
                    'country'           => $marketplaceId,

                    'dev_id'            => '',
                    'dev_secret'        => '',
                    'header_url'        => '',
                    'client_id'         => '',     // MPe Market Seller Sync
                    'client_secret'     => '',   // MPe Market Seller Sync
                ]);
            }

            // FALTARA: Refresh Token: $shop->refresh

            // IF Website authorization workflow --> REDIRECT TO OAUTH AUTHORIZATION URI
            // IF Marketplace Appstore authorization workflow --> GET REQUEST amazon_callback_uri

            // Website authorization workflow
            if (null === $request->input('amazon_callback_uri') || null === $request->input('amazon_state')) {

                $OAuthAuthorizationURI = null;
                if ($validatedData['shop_type'] == 'Seller') {

                    switch ($validatedData['country']) {
                        case 'Canada':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.ca';
                            break;

                        case 'US':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.com';
                            break;

                        case 'Mexico':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.com.mx';
                            break;

                        case 'Brazil':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.com.br';
                            break;

                        case 'Spain':
                            $OAuthAuthorizationURI = 'https://sellercentral-europe.amazon.com';
                            break;

                        case 'UK':
                            $OAuthAuthorizationURI = 'https://sellercentral-europe.amazon.com';
                            break;

                        case 'France':
                            $OAuthAuthorizationURI = 'https://sellercentral-europe.amazon.com';
                            break;

                        case 'Netherlands':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.nl';
                            break;

                        case 'Germany':
                            $OAuthAuthorizationURI = 'https://sellercentral-europe.amazon.com';
                            break;

                        case 'Italy':
                            $OAuthAuthorizationURI = 'https://sellercentral-europe.amazon.com';
                            break;

                        case 'Sweden':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.se';
                            break;

                        case 'Poland':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.pl';
                            break;

                        case 'Egypt':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.me';
                            break;

                        case 'Turkey':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.com.tr';
                            break;

                        case 'UAE':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.ae';
                            $marketplaceId = 'A2EUQ1WTGCTBG2';
                            break;

                        case 'India':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.in';
                            break;

                        case 'Singapore':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.sg';
                            break;

                        case 'Australia':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.com.au';
                            break;

                        case 'Japan':
                            $OAuthAuthorizationURI = 'https://sellercentral.amazon.co.jp';
                            break;
                    }
                }
                elseif ($validatedData['shop_type'] == 'Vendor') {

                    switch ($validatedData['country']) {
                        case 'Canada':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.ca';
                            break;

                        case 'US':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.com';
                            break;

                        case 'Mexico':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.com.mx';
                            break;

                        case 'Brazil':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.com.br';
                            break;

                        case 'Spain':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.es';
                            break;

                        case 'UK':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.co.uk';
                            break;

                        case 'France':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.fr';
                            break;

                        case 'Netherlands':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.nl';
                            break;

                        case 'Germany':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.de';
                            break;

                        case 'Italy':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.it';
                            break;

                        case 'Sweden':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.se';
                            break;

                        case 'Poland':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.pl';
                            break;

                        case 'Egypt':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.me';
                            break;

                        case 'Turkey':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.com.tr';
                            break;

                        case 'UAE':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.me';
                            break;

                        case 'India':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.in';
                            break;

                        case 'Singapore':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.com.sg';
                            break;

                        case 'Australia':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.com.au';
                            break;

                        case 'Japan':
                            $OAuthAuthorizationURI = 'https://vendorcentral.amazon.co.jp';
                            break;
                    }

                }

                if (!isset($OAuthAuthorizationURI) || !$request->has('selling_partner_id'))
                    return $this->backWithErrorMsg(__METHOD__, 'Ha ocurrido un error con el formualrio, pongase en contacto con el Soporte Técnico de MPe.', $request->all());

                $OAuthAuthorizationURI .= '/apps/authorize/consent?application_id='.$shop->dev_id;      //.'&version=beta';
                $redirect_uri = 'https://app.mpespecialist.com/oauth/amazom/redirect';
                //$OAuthAuthorizationURI .= '&redirect_uri='.$redirect_uri;
                $OAuthAuthorizationURI .= '&state='.$mpe_state;
                //$OAuthAuthorizationURI .= '&version=beta';

                // https://sellercentral-europe.amazon.com/apps/authorize/consent?application_id=amzn1.sp.solution.1800f969-3c9a-4507-b9ce-edb4f4cf293a$state=12345&version=beta

                Storage::append('mp/amazon/oauth/' . date('y-m-d_H-i'). '_build-oauth.json', $OAuthAuthorizationURI);

                return redirect($OAuthAuthorizationURI);
            }
            // Marketplace Appstore authorization workflow
            else {
                $client = new Client(); // ['base_uri' => ]
                $response = $client->get($request->input('amazon_callback_uri'), [
                    'headers' => [
                        'Referrer-Policy' => 'no-referrer HTTP',
                    ],
                    'query' => [
                        'amazon_state'  => $request->input('amazon_state'),
                        'redirect_uri'  => 'https://app.mpespecialist.com/oauth/amazom/redirect',
                        'state'         => $mpe_state,
                        //'version'       => 'beta',
                    ],
                ]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::append('mp/amazon/oauth/' .date('Y-m-d_H-i'). '_amazon-res.json', json_encode([$response->getStatusCode(), $response->getHeaders(), $response->getBody()]));
                    Storage::append('mp/amazon/oauth/' .date('Y-m-d_H-i'). '_amazon.html', $contents);

                    // Retorna Login Amazon
                    return response($contents, 200)
                        ->header('Content-Type', $response->getHeader('Content-Type'));     //
                }

                return $this->msgAndStorage(__METHOD__, 'Ha ocurrido un error y no se ha recibido la Autorización. Ponsage en contacto con el Servicio Tecnico en info@mpespecialist.com', $response);
            }

        } catch (Throwable $th) {
            $this->nullWithErrors($th, __METHOD__, $request);
        }
    }




    public function amazonRedirect(Request $request)
    {
        try {
            Storage::append('mp/amazon/oauth/' . date('y-m-d_H-i'). '_redirect.json', json_encode($request->all()));

            // https://app.mpespecialist.com/oauth/amazon/redirect
            // AMB PARAMS: state, selling_partner_id, mws_auth_token, spapi_oauth_code
            // spapi_oauth_code -> demanar LWA refresh token
            // spapi_oauth_code EXPIRES in 5 minutes

            // https://github.com/amzn/selling-partner-api-docs/blob/main/guides/en-US/developer-guide/SellingPartnerApiDeveloperGuide.md#step-5-add-an-aws-security-token-service-policy-to-your-iam-user

            if (!$request->has('selling_partner_id'))
                return $this->msgAndStorage(__METHOD__, 'Ha ocurrido un error, no hay Selleing Partner ID', $request->all());

            if (!$shop = Shop::where('marketSellerId', $request->input('selling_partner_id'))->first())
                return $this->msgAndStorage(__METHOD__, 'Ha ocurrido un error, no se ha encontrado la Tienda, revisa la configuración', $request->all());

            if ($request->has('mws_auth_token')) {
                $shop->token = $request->input('mws_auth_token');
                $shop->save();
            }

            if ($request->has('spapi_oauth_code')) {
                $shop->app_version = $request->input('spapi_oauth_code');
                $shop->save();
            }

            // exchange an LWA authorization code for an LWA refresh token
            $client = new Client(); // ['base_uri' => ]
            $response = $client->post('https://api.amazon.com/auth/o2/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
                ],
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $request->input('spapi_oauth_code'),
                    'redirect_uri'  => 'https://app.mpespecialist.com/oauth/amazon/redirect',
                    'client_id'     => $shop->client_id,
                    'client_secret' => $shop->client_secret,
                ],
            ]);

            // The response is in JSON
            if ($response->getStatusCode() == '200') {
                $contents = $response->getBody()->getContents();
                Storage::append('mp/amazon/oauth/' .date('Y-m-d_H-i'). '_access-token.json', $contents);

                $json_res = json_decode($contents);

                $shop->token = $json_res->access_token;
                $shop->refresh = $json_res->refresh_token;
                $shop->save();

                return $this->msgAndStorage(__METHOD__, 'Tienda Seller sincronizada correctamente con MPE. Contacte con info@mpespecialist.com', [$contents, $request->all()]);
            }

            return $this->msgAndStorage(__METHOD__, 'Ha ocurrido un error sincronizando la Tienda con MPe.', $request->all());

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $request->all());
        }
    }


    public function google(Request $request)
    {
        // Google Redirect URI
        // https://app.mpespecialist.com/oauth/google?code={authorization_code}
        Storage::append('mp/google/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->getMethod()));

        // {"code":"4\/0AfDhmrhPtIPNktWF_m1sN0BJtLSB5S27uNULwE54I86Sf_8Zifnlh_CWb_wQ9n_qxgwkXg",
        //  "scope":"https:\/\/www.googleapis.com\/auth\/spreadsheets.readonly"}
        Storage::append('mp/google/oauth/' . date('y-m-d_H'). '_redirect.json', json_encode($request->all()));

        config(['google.token' => $request->all()]);

        return true;

        // Before Authorize -> GET authorization_code (5 minutes)
        $authorization_code = $request->input('code');
        // GET token FOR 30 DAYS
        if ($authorization_code) {

            $market = Market::whereCode('joom')->first();
            // Get Joom Shop with NULL Token
            $shop = Shop::whereMarketId($market->id)->whereNull('token')->first();
            try {
                $client = new Client(['base_uri' => $shop->endpoint]);
                $form_params = [
                        'client_id' => $shop->client_id,
                        'client_secret' => $shop->client_secret,
                        'code' => $authorization_code,
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => $shop->redirect_url,
                    ];

                $response = $client->post('v2/oauth/access_token', [
                    'headers'   => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => $form_params,
                ]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::append('mp/joom/oauth/' .date('Y-m-d'). '_token.json', $contents);
                    $json_res = json_decode($contents);
                    // success
                    if ($json_res->code == 0) {
                        $shop->token = $json_res->data->access_token;
                        $shop->refresh = $json_res->data->refresh_token;
                        $shop->marketSellerId = $json_res->data->merchant_user_id;
                        $shop->save();

                        return ['message' => 'Token obtenido correctamente'];
                    }
                }

                Storage::append('mp/joom/oauth/' .date('Y-m-d'). '_token_errors.json', $response->getBody()->getContents());
            }
            catch (ClientException $e) {
                Storage::append('mp/joom/oauth/' .date('Y-m-d'). '_token_exception.json', json_encode($e->getMessage()));
            }
        }

        return false;
    }



    public function wish(Request $request)
    {
        // Wish Redirect URI
        Storage::append('mp/wish/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->getMethod()));
        Storage::append('mp/wish/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->all()));

        // Before Authorize -> GET authorization_code (5 minutes)
        $authorization_code = $request->input('code');
        $shop = Shop::firstwhere('code', $request->input('shop'));

        // GET token FOR 30 DAYS
        if (isset($authorization_code) && $authorization_code && isset($shop)) {

            $market = $shop->market;
            //$market = Market::whereCode('wish')->first();
            //$shop = Shop::whereMarketId($market->id)->first();

            $client = new Client(['base_uri' => $shop->endpoint]);
            try {
                $response = $client->get('api/v3/oauth/access_token', ['query' => [
                    'client_id' => $shop->client_id,
                    'client_secret' => $shop->client_secret,
                    'code' => $authorization_code,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $shop->redirect_url,
                ]
                ]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_token.json', $contents);
                    $json_res = json_decode($contents);
                    // success
                    if ($json_res->code == 0) {
                        $shop->token = $json_res->data->access_token;
                        $shop->refresh = $json_res->data->refresh_token;
                        $shop->marketSellerId = $json_res->data->merchant_id;
                        $shop->save();

                        return ['message' => 'Token obtenido correctamente'];
                    }
                }

                Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_token_errors.json', $response->getBody()->getContents());
            }
            catch (ClientException $e) {
                Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_token_errors.json', $e->getMessage());
            }
        }
        else {
            Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_token_errors.json',
                json_encode(['No $authorization_code && $shop', $authorization_code ?? null, $shop ?? null]));
        }

        return ['message' => 'Error obteniendo token'];
    }


    /* public function wishAuthorize(Request $request)
    {
        // Authorize App & get Authorization Code via Redirect URI
        // RETURN TO REDIRECT URI: https://app.mpespecialist.com/oauth/wish?code=

        $market = Market::whereCode('wish')->first();
        $shop = Shop::whereMarketId($market->id)->first();

        return redirect()->to($shop->endpoint. 'v3/oauth/authorize?client_id=' .$shop->client_id);
    }
 */


    public function joom(Request $request)
    {
        // Wish Redirect URI
        // https://app.mpespecialist.com/oauth/joom?code={authorization_code}
        Storage::append('mp/joom/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->getMethod()));
        Storage::append('mp/joom/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->all()));

        // Before Authorize -> GET authorization_code (5 minutes)
        $authorization_code = $request->input('code');
        // GET token FOR 30 DAYS
        if ($authorization_code) {

            $market = Market::whereCode('joom')->first();
            // Get Joom Shop with NULL Token
            $shop = Shop::whereMarketId($market->id)->whereNull('token')->first();
            try {
                $client = new Client(['base_uri' => $shop->endpoint]);
                $form_params = [
                        'client_id' => $shop->client_id,
                        'client_secret' => $shop->client_secret,
                        'code' => $authorization_code,
                        'grant_type' => 'authorization_code',
                        'redirect_uri' => $shop->redirect_url,
                    ];

                $response = $client->post('v2/oauth/access_token', [
                    'headers'   => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => $form_params,
                ]);

                if ($response->getStatusCode() == '200') {
                    $contents = $response->getBody()->getContents();
                    Storage::append('mp/joom/oauth/' .date('Y-m-d'). '_token.json', $contents);
                    $json_res = json_decode($contents);
                    // success
                    if ($json_res->code == 0) {
                        $shop->token = $json_res->data->access_token;
                        $shop->refresh = $json_res->data->refresh_token;
                        $shop->marketSellerId = $json_res->data->merchant_user_id;
                        $shop->save();

                        return ['message' => 'Token obtenido correctamente'];
                    }
                }

                Storage::append('mp/joom/oauth/' .date('Y-m-d'). '_token_errors.json', $response->getBody()->getContents());
            }
            catch (ClientException $e) {
                Storage::append('mp/joom/oauth/' .date('Y-m-d'). '_token_exception.json', json_encode($e->getMessage()));
            }
        }

        return false;
    }


    /* public function joomAuthorize(Request $request)
    {
        // Authorize App & get Authorization Code via Redirect URI
        // https://app.mpespecialist.com/oauth/joom/authorize
        // RETURN TO REDIRECT URI: https://app.mpespecialist.com/oauth/joom?code=6430c1eb040f45f38e6a55e7f8fe2feb

        $market = Market::whereCode('joom')->first();
        $shop = Shop::whereMarketId($market->id)->first();

        return redirect()->to($shop->endpoint. 'v2/oauth/authorize?client_id=' .$shop->client_id);
    } */




    public function ae(Request $request)
    {
        try {
            // Aliexpress Redirect URI: https://app.mpespecialist.com/oauth/ae
            // To obtain an access token: Formal environment: https://oauth.aliexpress.com/token

            if (empty($request->all()))
                return 'Request Empty';

            // "GET"
            Storage::append('mp/ae/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->getMethod()));
            // {"code":"0_CKW95H5DZiy0gYkAhCXTDZ1L6404"}
            Storage::append('mp/ae/oauth/' . date('y-m-d'). '_redirect.json', json_encode($request->all()));

            // Before Authorize -> GET authorization_code (5 minutes)
            $authorization_code = $request->input('code');
            $shop = Shop::firstwhere('code', $request->input('shop'));

            // GET token
            if (isset($authorization_code) && $authorization_code && isset($shop)) {

                $market = $shop->market;
                //$market = Market::whereCode('ae')->first();
                // Get AE Shop with NULL Token
                //$shop = Shop::whereMarketId($market->id)->whereNull('token')->first();

                try {
                    $client = new Client(['base_uri' => $shop->endpoint]);
                    $response = $client->post('token', ['query' => [
                        'client_id'     => $shop->client_id,
                        'client_secret' => $shop->client_secret,
                        'code'          => $authorization_code,
                        'grant_type'    => 'authorization_code',
                        'redirect_uri'  => $shop->redirect_url,
                        'sp'            => 'ae',
                    ]
                    ]);

                    if ($response->getStatusCode() == '200') {
                        $contents = $response->getBody()->getContents();
                        Storage::append('mp/ae/oauth/' .date('Y-m-d'). '_token.json', $contents);
                        $json_res = json_decode($contents);
                        // success
                        if (isset($json_res->access_token)) {
                            $shop->token = $json_res->access_token;
                            $shop->refresh = $json_res->refresh_token;
                            $shop->marketSellerId = $json_res->user_id;
                            $shop->marketShopId = $json_res->user_nick;
                            $shop->save();

                            return ['message' => 'Token obtenido correctamente'];
                        }
                    }

                    Storage::append('mp/ae/oauth/' .date('Y-m-d'). '_token_errors.json', json_encode($response->getStatusCode()));
                }
                catch (ClientException $e) {
                    Storage::append('mp/ae/oauth/' .date('Y-m-d'). '_token_errors.json', json_encode([$e->getMessage(), $e->getTrace()]));
                }
            }
            else {
                Storage::append('mp/ae/oauth/' .date('Y-m-d'). '_token_errors.json',
                    json_encode(['No $authorization_code && $shop', $authorization_code ?? null, $shop ?? null]));
            }

            return ['message' => 'Error obteniendo token'];

        } catch (Throwable $th) {
            return $this->msgWithErrors($th, __METHOD__, $request);
        }
    }



    /*public function wishToken(Request $request)
    {
        // Get access_token
        // SANDBOX: https://app.mpespecialist.com/oauth/wish/token?sandbox=1
        $sandbox = $request->get('sandbox');
        if (!$sandbox || $sandbox == '1') $wish = $this->wish['sandbox'];
        else $wish = $this->wish['real'];

        $client = new Client(['base_uri' => $wish['base_uri']]);
        $response = $client->get('api/v3/oauth/access_token', ['query' => [
            'client_id' => $wish['client_id'],
            'client_secret' => $wish['client_secret'],
            'code' => $wish['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $wish['redirect_uri'],
            ]
        ]);

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            Storage::append($this->wish['storage_dir']. 'oauth/' .date('Y-m-d'). '_token.json', $contents);
            $json_res = json_decode($contents);

            // success
            if ($json_res->code == 0)
                return $json_res->data;     // $json_res->data->access_token, scopes[], merchant_id, expiry_time, refresh_token
        }

        Storage::append($this->wish['storage_dir']. 'oauth/' .date('Y-m-d'). '_token_errors.json', $response->getBody()->getContents());
        return null;
    }*/


    /*public function wishRefresh(Request $request)
    {
        // Refresh access_token every 30 days
        // https://app.mpespecialist.com/oauth/wish/refresh    route('oauth.wish.refresh')

        $sandbox = $request->get('sandbox');
        if (!$sandbox || $sandbox == '1') $wish = $this->wish['sandbox'];
        else $wish = $this->wish['real'];

        $shop = Shop::whereCode('wish')->get();
        $client = new Client(['base_uri' => $shop->endpoint]);
        $response = $client->get('api/v3/oauth/refresh_token', ['query' => [
            'client_id' => $shop->client_id,
            'client_secret' => $shop->client_secret,
            'refresh_token' => $shop->refresh,
            'grant_type' => 'refresh_token',
            ]
        ]);

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_refresh.json', $contents);
            $json_res = json_decode($contents);
            // success
            if ($json_res->code == 0) {
                $shop->token = $json_res->data->access_token;
                $shop->refresh = $json_res->data->refresh_token;
                $shop->marketSellerId = $json_res->data->merchant_id;
                $shop->save();

                return true;
            }
        }

        Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_refresh_errors.json', $response->getBody()->getContents());
        return false;
    }*/


    /*public function wishTest(Request $request)
    {
        // TEST token -> FAILS -> REFRESH
        // SANDBOX: https://app.mpespecialist.com/oauth/wish/test      route('oauth.wish.test')
        $shop = Shop::whereCode('wish')->get();
        $client = new Client(['base_uri' => $shop->endpoint]);

        // api/v3/oauth/test -> RESPONSE: $json_res->data->merchant_id
        $response = $client->get('api/v3/oauth/test', ['query' => [
            'access_token' => $shop->token,
            ]
        ]);

        if ($response->getStatusCode() == '200') {
            $contents = $response->getBody()->getContents();
            Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_test.json', $contents);
            $json_res = json_decode($contents);
            // success
            if ($json_res->code == 0)
                if (isset($json_res->data->merchant_id))
                    return true;
        }

        Storage::append('mp/wish/oauth/' .date('Y-m-d'). '_test_errors.json', $response->getBody()->getContents());
        return false;
    }*/



    public function wishRequest(Request $request)
    {
        // Test Request
        // SANDBOX: https://app.mpespecialist.com/oauth/wish/request?sandbox=1
        $sandbox = $request->get('sandbox');
        if (!$sandbox || $sandbox == '1') $wish = $this->wish['sandbox'];
        else $wish = $this->wish['real'];

        $client = new Client(['base_uri' => $wish['base_uri']]);

        ////////////// API V3 //////////////


        // TEST TOKEN
        // api/v3/oauth/test -> RESPONSE: $json_res->data->merchant_id
        /*$response = $client->get('api/v3/oauth/test', ['query' => [
            'access_token' => $wish['access_token'],
            ]
        ]);*/

        // GET BRANDS
        // RESPONSE: $json_res->data[0]->website: "www.adidas.com", "id": "545d5e666fa88c38cdfe04f8", "name": "Adidas"
        /*$response = $client->get('api/v3/brands', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                'limit' => 20,
                'sort_by' => 'id.asc',
            ]
        ]);*/

        // GET CURRENCIES
        // RESPONSE: $json_res->data[0]->code: code": "USD", "name": "US Dollar";
        // "code": "CNY", "name": "Chinese Yuan"; "code": "EUR", "name": "Euro"; "code": "GBP", "name": "Pound"
        /*$response = $client->get('api/v3/currencies', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
//                'limit' => 20,
//                'sort_by' => 'id.asc',
            ]
        ]);*/

        // GET list of product enrollment info in a particular region: "EAST" "SOUTH"
        // RESPONSE: $json_res->data[]
        /*$response = $client->get('api/v3/epc/enrollments/EAST', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                'limit' => 20,
                //'status' => 'ACTIVE',       // "ACTIVE" "QUEUED"
            ]
        ]);*/

        // GET inbound shipping recommendations
        // RESPONSE: $json_res->data[]
        /*$response = $client->get('api/v3/fbs/recommendations', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                'limit' => 20,
            ]
        ]);*/

        // GET merchant currency settings
        // RESPONSE: $json_res->data->product_boost_currency": "USD", "localized_currency": "USD"
        /*$response = $client->get('api/v3/merchant/currency_settings', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                //'limit' => 20,
            ]
        ]);*/

        // GET merchant early payment info
        // RESPONSE: $json_res->data->available_amount->amount: 0.0, available_amount->currency_code: "USD"
        /*$response = $client->get('api/v3/payments/early_payment', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                //'limit' => 20,
            ]
        ]);*/

        // GET Count number of penalties
        // RESPONSE: $json_res->data->count: 0
        /*$response = $client->get('api/v3/penalties/count', [
            'headers' => [
                'authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                //'limit' => 20,
            ]
        ]);*/


        ////////////// API V2 //////////////

        // GET get all products. 1rst CREATE job_id
        // RESPONSE: $json_res->data->job_id: "5ebbc612c757a00064c1210b"
        /*$response = $client->get('api/v2/product/create-download-job', [
            'headers' => [
                'Authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                //'limit' => 20,
            ]
        ]);*/

        // GET get all products. 2on CHECK JOB STATUS job_id
        // RESPONSE NO JOB FINISH: $json_res->data->
        // RESPONSE JOB FINISH: $json_res->data->status": "FINISHED", total_count": 1, "processed_count": 1
        //    +"download_link": "https://sweeper-sandbox-merchant-export.s3-us-west-1.amazonaws.com/5ebb98b72f2475004d1b15fe-5ebbc612c757a00064c1210a-2020-05-13-10%3A04%3A02.csv?Signature=JVfuW ▶"
        //    +"end_run_time": "2020-05-13 10:04:06.770000", "start_run_time": "2020-05-13 10:04:02.923000", "created_date": "2020-05-13 10:04:02+00:00"
        /*$response = $client->get('api/v2/product/get-download-job-status', [
            'headers' => [
                'Authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                'job_id' => '5ebbc612c757a00064c1210b',
            ]
        ]);*/

        /*
         * $res = $client->request('GET', $this->url_csv .$this->filename);
        // $res->getHeader('content-type')[0]; // 'text/csv'
        if ($res->getStatusCode() == '200')
            Storage::put($this->storage_dir. $this->filename, $res->getBody());
         */

        // GET all products RESPONSE CSV
        // RESPONSE: Storage CSV
        /*$download_link = 'https://sweeper-sandbox-merchant-export.s3-us-west-1.amazonaws.com/5ebb98b72f2475004d1b15fe-5ebbc612c757a00064c1210a-2020-05-13-10%3A04%3A02.csv?Signature=JVfuWE%2FAzJIV9DijREkJw%2BpTdxQ%3D&Expires=1589626229&AWSAccessKeyId=AKIAJFT6XO7RY2S4TSRQ';
        $csv_file_array = file($download_link);
        // remove first line (headers)
        $csv_file_array = array_slice($csv_file_array, 1);
        $row_arrays = null;
        foreach ($csv_file_array as $csv_line_array) {
            $row_arrays[] = str_getcsv($csv_line_array, ',');     // optional delimiter: ,
        }
        */



        // Retrieve a Product
        // RESPONSE: $json_res->data->Product->id
        // https://app.mpespecialist.com/oauth/wish/request?sandbox=1
        // data": {#1728 ▼
        //    +"Product": {#1703 ▼
        //      +"last_updated": "05-13-2020T06:50:47"
        //      +"description": "Product description"
        //      +"clean_image": ""
        //      +"tags": array:3 [▼
        //        0 => {#1710 ▼
        //          +"Tag": {#1717 ▼
        //            +"id": "tag1"
        //            +"name": "tag1"
        //          }
        //        }
        //        1 => {#1719 ▼
        //          +"Tag": {#1702 ▼
        //            +"id": "tag2"
        //            +"name": "tag2"
        //          }
        //        }
        //        2 => {#1721 ▼
        //          +"Tag": {#1720 ▼
        //            +"id": "tags3"
        //            +"name": "tags3"
        //          }
        //        }
        //      ]
        //      +"review_status": "pending"
        //      +"extra_images": ""
        //      +"fbw_inbound_approval_status": array:1 [▼
        //        0 => {#1723 ▼
        //          +"FBWInboundApprovalStatusDict": {#1722 ▼
        //            +"status": "NOT_SUBMIT"
        //            +"warehouse_code": "HZC"
        //          }
        //        }
        //      ]
        //      +"variants": array:1 [▼
        //        0 => {#1725 ▼
        //          +"Variant": {#1724 ▼
        //            +"sku": "sku 1"
        //            +"localized_shipping": "2.0"
        //            +"localized_currency_code": "USD"
        //            +"color": "mossyoakshadowgrassblades"
        //            +"price": "2.0"
        //            +"enabled": "True"
        //            +"shipping": "2.0"
        //            +"all_images": "https://s3-us-west-1.amazonaws.com/sweeper-sandbox-productimage/1fbe0165bd686a0aff8ab647ae255da6.jpg"
        //            +"color_name": "Mossy Oak Shadow Grass Blades"
        //            +"inventory": "20"
        //            +"shipping_time": "5-17"
        //            +"removed_by_wish": "False"
        //            +"localized_price": "2.0"
        //            +"size": "1"
        //            +"id": "5ebb98c33f1d3300220961c2"
        //            +"msrp": "6.0"
        //            +"product_id": "5ebb98c33f1d3300220961c1"
        //          }
        //        }
        //      ]
        //      +"parent_sku": "parent_sku 1"
        //      +"id": "5ebb98c33f1d3300220961c1"
        //      +"main_image": "https://s3-us-west-1.amazonaws.com/sweeper-sandbox-productimage/1fbe0165bd686a0aff8ab647ae255da6.jpg"
        //      +"is_promoted": "False"
        //      +"name": "Test Product"
        //      +"country_shippings": array:1 [▼
        //        0 => {#1727 ▼
        //          +"CountryShipping": {#1726 ▼
        //            +"price": "2.0"
        //            +"localized_currency_code": "USD"
        //            +"localized_price": "2.0"
        //            +"country_code": "US"
        //          }
        //        }
        //      ]
        //      +"removed_by_merchant": "False"
        //      +"upc": "1"
        //      +"original_image_url": "https://www.google.com/images/srpr/logo11w.png"
        //      +"landing_page_url": "http://www.lalala.com/1"
        //      +"default_shipping_price": "2.0"
        //      +"number_saves": "0"
        //      +"localized_default_shipping_price": "2.0"
        //      +"date_uploaded": "05-13-2020"
        //      +"number_sold": "0"
        /*$response = $client->get('api/v2/product', [
            'headers' => [
                'Authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'query' => [
                'id' => '5ebc25f829bace0022e0a33a',
            ]
        ]);*/




        // create product
        // RESPONSE: $json_res->data->data": {#1726 ▼
        //    +"Product": {#1703 ▼
        //      +"last_updated": "05-13-2020T16:53:13"
        //      +"product_brand_name": "Adidas"
        //      +"clean_image": ""
        //      +"tags": array:2 [▼
        //        0 => {#1710 ▼
        //          +"Tag": {#1717 ▼
        //            +"id": "sku1"
        //            +"name": "sku1"
        //          }
        //        }
        //        1 => {#1719 ▼
        //          +"Tag": {#1702 ▼
        //            +"id": "name1"
        //            +"name": "name1"
        //          }
        //        }
        //      ]
        //      +"review_status": "pending"
        //      +"extra_images": ""
        //      +"fbw_inbound_approval_status": array:1 [▼
        //        0 => {#1721 ▼
        //          +"FBWInboundApprovalStatusDict": {#1720 ▼
        //            +"status": "NOT_SUBMIT"
        //            +"warehouse_code": "HZC"
        //          }
        //        }
        //      ]
        //      +"requested_product_brand_id": "545d5e666fa88c38cdfe04f8"
        //      +"variants": array:1 [▼
        //        0 => {#1723 ▼
        //          +"Variant": {#1722 ▼
        //            +"sku": "sku1"
        //            +"localized_shipping": "10.0"
        //            +"localized_currency_code": "USD"
        //            +"all_images": "https://s3-us-west-1.amazonaws.com/sweeper-sandbox-productimage/None.jpg"
        //            +"price": "100.0"
        //            +"enabled": "True"
        //            +"shipping": "10.0"
        //            +"inventory": "10"
        //            +"removed_by_wish": "False"
        //            +"localized_price": "100.0"
        //            +"shipping_time": "7-21"
        //            +"id": "5ebc25f829bace0022e0a33b"
        //            +"msrp": "0.0"
        //            +"product_id": "5ebc25f829bace0022e0a33a"
        //          }
        //        }
        //      ]
        //      +"parent_sku": "sku1"
        //      +"id": "5ebc25f829bace0022e0a33a"
        //      +"description": "description1"
        //      +"main_image": "https://s3-us-west-1.amazonaws.com/sweeper-sandbox-productimage/None.jpg"
        //      +"is_promoted": "False"
        //      +"name": "name1"
        //      +"localized_default_shipping_price": "10.0"
        //      +"country_shippings": array:1 [▼
        //        0 => {#1725 ▼
        //          +"CountryShipping": {#1724 ▼
        //            +"price": "10.0"
        //            +"localized_currency_code": "USD"
        //            +"localized_price": "10.0"
        //            +"country_code": "US"
        //          }
        //        }
        //      ]
        //      +"removed_by_merchant": "False"
        //      +"original_image_url": "https://img.pccomponentes.com/articles/28/283750/msi-gf63-thin-10scxr-042xes-intel-core-i7-10750h-16gb-1tb-ssd-gtx-1650-156.jpg"
        //      +"default_shipping_price": "10.0"
        //      +"number_saves": "0"
        //      +"product_brand_status": "Pending"
        //      +"date_uploaded": "05-13-2020"
        //      +"number_sold": "0"
        //    }
        //  }
        /*$response = $client->post('api/v2/product/add', [
            'headers' => [
                'Authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'form_params' => [
                'name'                  => 'name4',
                'description'           => 'description4',
                'tags'                  => 'name4,sku4',
                'sku'                   => 'sku4',
                'requested_product_brand_id' => '545d5e666fa88c38cdfe04f8',
                'main_image'            => 'https://img.pccomponentes.com/articles/28/283750/msi-gf63-thin-10scxr-042xes-intel-core-i7-10750h-16gb-1tb-ssd-gtx-1650-156.jpg',
                'extra_images'          => 'https://app.mpespecialist.com/storage/img/829/v246hqlbi236vgahdmi5ms100m1250nitsblack.jpg_4.jpg|https://app.mpespecialist.com/storage/img/829/v246hqlbi236vgahdmi5ms100m1250nitsblack.jpg_1.jpg',
                'inventory'             => '10',
                'price'                 => '100',
                'shipping'              => '10',
            ]
        ]);*/


        // ADD VARIANT
        /*$response = $client->post('api/v2/variant/add', [
            'headers' => [
                'Authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'form_params' => [
                'parent_sku'            => 'sku3',
                'sku'                   => 'sku4',
                'main_image'            => 'https://app.mpespecialist.com/storage/img/829/v246hqlbi236vgahdmi5ms100m1250nitsblack.jpg_1.jpg',
                'inventory'             => '10',
                'price'                 => '100',
                'color'                 => 'Blue',
                'size'                  => 'XL',
            ]
        ]);*/


        // ENABLE / DISABLE product
        // POST: 'form_params' for application/x-www-form-urlencoded
        // POST: 'multipart' for multipart/form-data
        /*$response = $client->post('api/v2/product/disable', [
            'headers' => [
                'Authorization' => 'Bearer ' .$wish['access_token'],
            ],
            'form_params' => [
                'id' => '5ebc28bc26cf3d0037c5c798',
            ]
        ]);*/







        /*if ($response->getStatusCode() == '200') {
            $headers = $response->getHeaders();     // RESPONSE: $headers->Wish-Request-Id: ["f0f8b701-04bb-4b20-a6e3-8f26581ddd0f"],
            $contents = $response->getBody()->getContents();
            Storage::append($this->wish['storage_dir']. 'oauth/' .date('Y-m-d_H_i_s'). '_request_headers.json', json_encode($headers));
            Storage::append($this->wish['storage_dir']. 'oauth/' .date('Y-m-d_H_i_s'). '_request.json', $contents);
            $json_res = json_decode($contents);


            if ($json_res->code == 0)
                return $json_res->data;     // $json_res->data->merchant_id
        }

        Storage::append($this->wish['storage_dir']. 'oauth/' .date('Y-m-d'). '_request_errors.json', $response->getBody()->getContents());


        return null;*/
    }



}
