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
use Illuminate\Http\Response;

use App\Models\Vendor;
use App\Models\Market;
use App\Models\MarketWishList;
use App\Models\MarketType;
use App\Models\Product;
use App\Models\User;
use App\Models\Profile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\City;
use App\Models\Location;
use App\Models\States;
use App\Models\Country;
use Carbon\Carbon;
use Validator;
use Session;

class ProductController extends BaseController
{
    //
    public function addToCart(Request $request, $id){
         $request->session()->regenerate();
        //print_r($sess_id);
        $product = Product::findOrFail($id);
        $cart = $request->session()->get('cart', []);
       
        if(isset($cart[$id])) {
            $cart[$id]['quantity']++;
        } else {
            $cart[$id] = [ 
                "p_name" => $product->p_name,
                "quantity" => 1,
                "c_price" => $product->c_price,
                "img" => $product->img,
                "product_id" => $product->id,
                "store_id" => $product->store_id,
            ];
        }
        $request->session()->put('cart', $cart[$id]);
        $request->session()->put('lang','en_EN');
        $request->session()->save();
        $data['cart'] = $cart[$id]; 
        if($cart[$id]){
            $data = $request->session()->all();
          
            return $this->sendResponse($data, 'Product added to cart successfully!.');
        }
        else{
            return $this->sendError('oops!.', ['error'=>'No product found']); 
        }
    }

    public function removeFromCartByID(Request $request, $id){
        $product = Product::findOrFail($id);
        $cart = $request->session()->get('cart', [$product]);
        if(isset($cart[$id])) {
            unset($cart[$id]);
        } 
        $request->session()->forget('cart', $cart);
        $request->session()->save();
        $data = $request->session()->all();
        return $this->sendResponse($data, 'Product remove successfully!'); 
    }

    public function removeFromCart(Request $request){
        if($request->id) {
            $cart = $request->session()->get('cart', []);
            if(isset($cart[$request->id])) {
                unset($cart[$request->id]);
            }
            $request->session()->forget('cart', $cart);
            $request->session()->save();
            $data = $request->session()->all();
            return $this->sendResponse($data, 'Product remove successfully!'); 
        }
        else{
            return $this->sendError('opps!.', ['error'=>'Enter product id']);
        }
    }

    public function removeAllCart(Request $request){
        if($request->id) {
            $cart = $request->session()->get('cart', []);
            if(isset($cart[$request->id])) {
                unset($cart[$request->id]);
            }
            $request->session()->flush('cart', $cart);
            $request->session()->save();
            $data = $request->session()->all();
            return $this->sendResponse($data, 'You successfully emptyed your cart'); 
        }
    }
   
    public function updateCartQuantity(Request $request){
        $validator =Validator::make($request->all(), [
            'id' => 'required',
            'quantity'=>'required'
          ]);      

          if($validator->fails()){
           
            $error = $validator->errors();              
            return $this->sendError('Unauthorised.', ['error'=>$error]);  
            }
        if($request->id && $request->quantity){
            $id = $request->id;
            $product = Product::findOrFail($id);
            $cart = $request->session()->get('cart', []);
            $cart[$id] = [
                "p_name" => $product->p_name,
                "quantity" => $request->quantity,
                "c_price" => $product->c_price,
                "img" => $product->img,
                "product_id" => $product->id,
                "store_id" => $product->store_id,
            ];
          
        }
        $request->session()->put('cart', $cart[$id]);
            $request->session()->save();
            $data['cart'] = $cart[$id]; 
            $data['amount']= $request->quantity * $product->c_price;
            $data = $request->session()->all();
            return $this->sendResponse($data, 'Cart quantity updated'); 
    }

    public function cart(Request $request){
        $cart = $request->session()->get('cart', []);
         $data['cart'] = $cart; 
       
         if(empty($cart)){
             return $this->sendResponse($cart, 'Your Cart is empty');
         }
         else{
            $data = $request->session()->all();
             return $this->sendResponse($data, 'Your cart.');
         }
     }
} 
