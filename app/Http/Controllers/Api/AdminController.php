<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Session\Store;

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
use Carbon\Carbon;
use Validator;
use Session;


class AdminController extends BaseController
{
    //
    public function addStoreToMarket(Request $request){
        if(Auth::user()->role_id = 2){
            $validator = Validator::make($request->all(), [
                'market'    => 'required',
                'store_name'=> 'required',
                'image'     => 'required',
                'whatsapp'  => 'string',
                'phone'     => 'string',
                'status'    => 'required'
            ]);
       
            if($validator->fails()){
                return $this->sendError('Validation Error.', $validator->errors());       
            }
            $image = $request->file('image');
            if(isset($image)){
                $imageName =  rand(1000000000, 9999999999).'.jpeg';
                $image->move(public_path('store'),$imageName);
                $image_path = "/store/" . $imageName; 
             }
             else {
                $image_path = "";
             }


            $store = new Vendor;
            $store->market_id   = $request->market_id;
            $store->name        = $request->store_name;
            $store->img         = $image_path;
            $store->whatsapp_link = $request->whatsapp;
            $store->phone         = $request->phone;
            $store->status        = $request->status;
            $store->save();

            if($store){
                $get_store = Vendor::where('id', $request->market_id)->get(); 
                $pluck = Arr::pluck($get_store, 'name');
                $pluck_store = implode(" ",$pluck);
            $data['store'] = $pluck_store;

            return $this->sendResponse($data, 'Store added successfully to'.$pluck_store);
            }
            else { 
                return $this->sendError('opps!.', ['error'=>'something went wrong']);    
                }
            
        }  
        else { 
            return $this->sendError('Unauthorised.', ['error'=>'You do not have permission to vist this page']);    
            } 
          
    }
}
