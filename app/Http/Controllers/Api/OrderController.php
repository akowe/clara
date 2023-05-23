<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Session\Store;
use Illuminate\Support\Arr;

use App\Models\Market;
use App\Models\Vendor;
use App\Models\MarketWishList;
use App\Models\MarketType;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shipping;
use App\Models\City;
use App\Models\Location;
use App\Models\States;
use App\Models\Country;
use App\Models\User;
use App\Models\Profile;
use Carbon\Carbon;
use Validator;
use Session;


class OrderController extends BaseController
{
    //
    public function confirmOrder(Request $request){
        if(Auth::user()){
            $id = Auth::user()->id;
            $cart = $request->session()->get('cart', []);
          //get others form input
            $ship_fname    = $request->fname;
            $ship_lname    = $request->lname;
            $ship_state    = $request->state;
            $ship_address  = $request->ship_address;
            $ship_city     = $request->ship_city;
            $note          = $request->note;
           
             if(empty($cart)){
                $checkout['user_id'] = $id;
                $checkout['email']= Auth::user()->email;
                return $this->sendResponse($checkout, 'Your Cart is empty');
            } 
            else{
                $cartItem[$request->id]["quantity"] = $cart['quantity'];
                $cartItem[$request->id]["c_price"] = $cart["c_price"];
                $cartItem[$request->id]["store_id"] = $cart["store_id"];
                $cartItem[$request->id]["product_id"] = $cart["product_id"];

                $totalAmount = 0;
                foreach ($cartItem as $item) {
                    $totalAmount += $item['c_price'] * $item['quantity'];
                }
        
                $order_number  = random_int(1000000000, 9999999999); //random unique 10 figure str_random(6)
                $order = new Order();
                $order->user_id         = Auth::user()->id;
                $order->total           = $totalAmount;
                $order->order_number    = $order_number;
                $order->order_status    = 'pending';
                $order->save();
                
            }
            if($order){
                $data = [];
                foreach ($cartItem as $item) {
                    $data['items'] = [
                        [
                            'c_price' => $item['c_price'],
                            'quantity' => $item['quantity'],
                            $cartItem[$request->id]["product_id"] = $cart["product_id"],
                            'store_id'=> $item['store_id'],  
                        ]
                    ];

                $orderItem = new OrderItem();
                    $orderItem->order_id   = $order->id;
                    $orderItem->prod_id    = $item['product_id'];
                    $orderItem->store_id   = $item['store_id'];
                    $orderItem->order_quantity   = $item['quantity'];
                    $orderItem->amount     = $item['c_price'];
                    $orderItem->save();
                }
                $shipping = new Shipping();
                $shipping->order_id     = $order->id;
                $shipping->fname        = $ship_fname;
                $shipping->lname        = $ship_lname;
                $shipping->ship_state   = $ship_state;
                $shipping->ship_address = $ship_address;
                $shipping->ship_city    = $ship_city;
                $shipping->note         = $note;
                $shipping->save();
                // $data['confirm_order'] = [
                //     $order_datails =  $order,
                //     $order_items = $orderItem,
                //     $shipment = $shipping
                // ];

                $profileDetails = Order::Join('order_items', 'order_items.order_id', '=', 'order.id')
                ->Join('users', 'users.id', '=', 'order.user_id')
                ->join('shipping', 'shipping.order_id', '=', 'order.id')
                ->join('product', 'product.id', '=', 'order_items.prod_id')
                ->Join('profile', 'profile.user_id', '=', 'users.id')
                 ->where('users.id', $id)
                 ->where('order.order_number', $order_number)
                ->get([ 
                    'users.email', 
                    'profile.fname', 
                    'profile.lname', 
                    'profile.address', 
                    'profile.phone',
                ]);  

                $shipDetails = Order::Join('order_items', 'order_items.order_id', '=', 'order.id')
                ->Join('users', 'users.id', '=', 'order.user_id')
                ->join('shipping', 'shipping.order_id', '=', 'order.id')
                ->join('product', 'product.id', '=', 'order_items.prod_id')
                ->Join('profile', 'profile.user_id', '=', 'users.id')
                 ->where('users.id', $id)
                 ->where('order.order_number', $order_number)
                ->get([ 
                    'shipping.*'
                ]);  

                $orderDetails = Order::Join('order_items', 'order_items.order_id', '=', 'order.id')
                ->Join('users', 'users.id', '=', 'order.user_id')
                ->join('shipping', 'shipping.order_id', '=', 'order.id')
                ->join('product', 'product.id', '=', 'order_items.prod_id')
                ->Join('profile', 'profile.user_id', '=', 'users.id')
                 ->where('users.id', $id)
                 ->where('order.order_number', $order_number)
                ->get([ 
                    'order.id', 
                    'order.order_number', 
                    'product.p_name', 
                    'product.img', 
                    'order.order_date', 
                    'order_items.order_quantity',
                    'order_items.amount',  
                    'order.total',
                    'order.order_status' 
                ]);  

                $detail['user_profile'] = $profileDetails;
                $detail['shipping_details'] = $shipDetails;
                $detail['order_details'] = $orderDetails;
                
                 //remove item from cart
                $request->session()->forget('cart');
                return $this->sendResponse($detail, 'Your order'); 
            }   
        }
        else{
            return $this->sendError('Unauthorised.', ['error'=>'kindly login']);  
        }
    }


    public function invoice(Request $request, $order_number ){
     if( Auth::user()){
         $id = Auth::user()->id; //
           $item = Order::join('users', 'users.id', '=', 'order.user_id')
                          ->leftjoin('order_items', 'order_items.order_id', '=', 'order.id')
                           ->join('shipping', 'shipping.id', '=', 'order.id')
                             ->join('products', 'products.id', '=', 'order_items.prod_id')
        
                        ->where('users.id', $id)
                        ->where('order.order_number', $order_number)
                        ->get(['order.*', 'users.*', 'order_items.*', 'shipping.*', 'products.*']);

         $orders = Order::join('users', 'users.id', '=', 'order.user_id')
                          ->leftjoin('order_items', 'order_items.order_id', '=', 'order.id')
                           ->join('shipping', 'shipping.id', '=', 'order.id')
                           ->join('products', 'products.id', '=', 'order_items.prod_id')
                           // ->where('order_items.status', 'confirmed')
                           // ->orwhere('order_items.status', 'paid')

                          ->where('users.id', $id)
                          ->where('order.order_number', $order_number)
                          ->get(['orders.*', 'users.*', 'order_items.*', 'shipping.*', 'products.*']);              

    return view('invoice', compact('item', 'orders'));
           }

    else { return Redirect::to('/login');
    
        }
                     
    }
}
