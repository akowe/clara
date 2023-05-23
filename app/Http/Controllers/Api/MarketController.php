<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Models\Market;
use App\Models\Vendor;
use App\Models\MarketWishList;
use App\Models\MarketType;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\City;
use App\Models\Location;
use App\Models\States;
use App\Models\Country;
use App\Models\User;
use App\Models\Profile;
use Validator;
use Session;

class MarketController extends BaseController
{
    //
    public function getAllLocalmarket(Request $request)
    {
        $market = Market::Join('city', 'city.id', '=', 'market.city_id') 
        ->Join('location', 'location.id', '=', 'city.location_id')  
        ->Join('states', 'states.id', '=', 'location.state_id') 
        ->Join('country', 'country.id', '=', 'market.country_id') 
        //->where('market.isActive', '1')
        ->where('market.type', 'local')
        ->get(['market.*', 'location.location', 'city.city', 'states.state', 'country.country']);
        $data['local_market'] = $market;
        
        if($market){ 
            return $this->sendResponse($data, 'All local markets.'); 
            //return ResponseBuilder::result($status, $message, $error, $data, $code); 
                  
        }
        else {
            # code...
            return $this->sendError('oops!.', ['error'=>'No market found']); 
        }    
    }

    public function getAllSupermarket(Request $request)
    {
        $market = Market::Join('city', 'city.id', '=', 'market.city_id') 
        ->Join('location', 'location.id', '=', 'city.location_id')  
        ->Join('states', 'states.id', '=', 'location.state_id') 
        ->Join('country', 'country.id', '=', 'market.country_id') 
        //->where('market.isActive', '1')
        ->where('market.type', 'supermarket')
        ->get(['market.*', 'location.location', 'city.city', 'states.state', 'country.country']);
        $data['supermarket'] = $market;
        if($market){
            return $this->sendResponse($data, 'All supermarkets.'); 
        }
        else {
            # code...
            return $this->sendError('oops!.', ['error'=>'No market found']); 
        }    
    }

    public function addMarketWishList(Request $request){
        // validation
        $validator =Validator::make($request->all(), [
            'market_name' => 'required|string',
            'type'=>'required',
            'contact_name' => 'required|string',
            'contact_phone' => 'required|numeric|digits_between:11,13',
            'contact_email' => 'required|email',
            'message'=>'string'

          ]);      

          if($validator->fails()){
           
            $error = $validator->errors();              
            return $this->sendError('Unauthorised.', ['error'=>$error]);  
            }  
            $email = $request->contact_email;
            //check if it's a valid email
            $result = filter_var($email, FILTER_VALIDATE_EMAIL )&& preg_match('/@.+\./', $email);
            if($result){
                //return $this->sendResponse($email, 'Your email address is valid.'); 
            }
            else{
                return $this->sendError('oops!.', ['error'=>'Invalid email address']);  
            }
            $market = new MarketWishList();
            $market->market_name    = $request->market_name;
            $market->type           = $request->type;
            $market->contact_name   = $request->contact_name;
            $market->contact_phone  = $request->contact_phone;
            $market->contact_email  = $email;
            $market->save();
            if($market){
                $data['wishlist'] = $market;
                return $this->sendResponse($data, 'Your market is wishlisted successfully. We will contact you.'); 
            }
            else{
                return $this->sendError('oops!.', ['error'=>'Something went wrong']); 
            }
    }

    public function getMarketType(Request $request){
        $market_type = MarketType::all();
        if($market_type){
            $data['market_type'] = $market_type;
                return $this->sendResponse($data, 'List of market type.'); 
        }
        else{
            return $this->sendError('oops!.', ['error'=>'Nothing found']); 
        }
    }
}
