<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Redirect;

class BigComAuthController extends Controller
{

    public function __construct()
    {
      $this->client_id = config('auth.bc_client_id');
      $this->client_secret = config('auth.bc_client_secret');
      $this->redirect_uri = config('auth.bc_redirect_url');
    }
    
    // This function will be invoked upon successful authentication.
    public function authCallback(Request $request) {
        // We will only process it if all required parameters are passed.
        if ($request->has('code') && $request->has('scope') && $request->has('context')) {

            try {
                $auth_code = $request->input('code');
                $auth_scope = $request->input('scope');
                $auth_context = $request->input('context');

                // Call API to retrieve the access token.
                $client = new Client();
                $result = $client->request('POST', 'https://login.bigcommerce.com/oauth2/token', [
                    'json' => [
                        'client_id' => $this->client_id,
                        'client_secret' => $this->client_secret,
                        'redirect_uri' => $this->redirect_uri,
                        'grant_type' => 'authorization_code',
                        'code' => $auth_code,
                        'scope' => $auth_scope,
                        'context' => $auth_context,
                    ]
                ]);
                
                // Verify whether the response is valid or not.
                $statusCode = $result->getStatusCode();
                
                if ($statusCode == 200) {
                    $data = json_decode($result->getBody(), true);
                    $request->session()->put('store_hash', $data['context']);
                    $request->session()->put('access_token', $data['access_token']);
                    $request->session()->put('user_id', $data['user']['id']);
                    $request->session()->put('user_email', $data['user']['email']);
                    // Upon successful authentication and storage of necessary data, redirect to the home page.
                    return Redirect::to('/');
                }else {
                    return 'Something went wrong... [' . $result->getStatusCode() . '] ' . $result->getBody();
                }
                
            } catch (RequestException $e) {
                return "Error:".$e->getResponse();
            }
            
        } else {
            return "Ensure that all necessary parameters have been provided. Please review and try again.";
        }
    }

    // This function will be called whenever a user opens the app in BigCommerce.
    public function authLoad (Request $request) {

        $signedPayload = $request->input('signed_payload');
        // Verify whether the signed_payload we received here is valid or not.
        if (!empty($signedPayload)) {
            $signedPayloadArr = explode("." , $signedPayload);
            $encodedData =  $signedPayloadArr[0];
            $encodedSignature =  $signedPayloadArr[1];

            // Decode the data 
            $decodedSignature = base64_decode($encodedSignature);
            $decodedData = base64_decode($encodedData); //JSON Encoded Data
            $data = json_decode($decodedData);

            // Create the expected signature hash 
            $expectedSignature = hash_hmac('sha256', $decodedData, $this->client_secret, $raw = false);

            if (hash_equals($expectedSignature , $decodedSignature)) {
                /*
                    We have user and owner data available.
                    We can store the required data.
                */

                $request->session()->put('user_id', $data->user->id);
                $request->session()->put('user_email', $data->user->email);
                $request->session()->put('store_hash', $data->context);

                // After storing the required data, redirect the user to the home page.
                return Redirect::to('/');
            }else{
                return "Bad signed request from BigCommerce!"; 
            }
        }else {
            return "Ensure that all necessary parameters have been provided. Please review and try again.";
        }
    }
}
