<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Arr;
use Illuminate\Session\Store;
use Carbon\Carbon;
use App\Models\Market;
use App\Models\Vendor;
use App\Models\MarketWishList;
use App\Models\MarketType;
use App\Models\Product;
use Validator;
use Session;


class StoreController extends BaseController
{
    //
    public function getAllStoresInMarket(Request $request){
      $stores = Vendor::select(
        'store.id',
        'store.name', 
        'store.img', 
        'store.whatsapp_link',
        'store.phone',
        'store.status',
        'store.market_id',
        'market.market_name', 
        'market.type', 
        DB::raw('count(name) as number_of_product'))
        ->leftJoin('product', 'product.store_id', '=', 'store.id')
        ->Join('market', 'market.id', '=', 'store.market_id') 
        //->Join('city', 'city.id', '=', 'market.city_id') 
        //->Join('location', 'location.id', '=', 'market.location_id') 
        ->where('market.type', 'supermarket')
        ->groupBy('store.id')
        ->get();
  
        $data['store'] = $stores;  
        if($stores){
            return $this->sendResponse($data, 'All stores in supermarket.');
        }
        else{
            return $this->sendError('oops!.', ['error'=>'No store found']); 
        }
    }
    public function getAllProductsInAStore(Request $request){
        $products = Product::Join('store', 'store.id', '=', 'product.store_id')
                    ->Join('market', 'market.id', '=', 'store.market_id') 
                    ->where('market.type', 'supermarket')
                    ->where('product.p_status', 'approved')
                    ->get(['product.*', 'store.name  as store_name', 'market.market_name', 'market.type' ]);
        $data['product'] = $products;
        if($products){
            return $this->sendResponse($data, 'All products in store.');
        }   
        else{
            return $this->sendError('oops!.', ['error'=>'No product found']); 
        }         
    }

    public function getAllProductInLocalMarket(Request $request){
        $products = Product::Join('store', 'store.id', '=', 'product.store_id')
                    ->Join('market', 'market.id', '=', 'store.market_id') 
                    ->where('market.type', 'local')
                    ->where('product.p_status', 'approved')
                    ->get(['product.*', 'store.name  as vendor_name', 'market.market_name', 'market.type' ]);
        $data['product'] = $products;
        if($products){
            return $this->sendResponse($data, 'All products in local market.');
        }   
        else{
            return $this->sendError('oops!.', ['error'=>'No product found']); 
        } 

    }
}
