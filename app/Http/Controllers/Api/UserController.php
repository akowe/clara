<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\BaseController as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Session\Store;

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

class UserController extends BaseController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);
        $success['token'] =  $user->createToken('apiAccess')->accessToken;
        $success['name'] =  $user->name;

        return $this->sendResponse($success, 'User register successfully.');
    }

    public function login(Request $request)
    {
            $email = $request->email;
            //check if it's a valid email
            $result = filter_var($email, FILTER_VALIDATE_EMAIL )&& preg_match('/@.+\./', $email);
            if($result){
                //return $this->sendResponse($email, 'Your email address is valid.'); 
            }
            else{
                return $this->sendError('oops!.', ['error'=>'Invalid email address']);  
            }
            $user = User::where('email', $email)->first();
           if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $profile = Profile::where('user_id', $user->id)->first();
                
                //create authentication. 
                //$request->user()->createToken('apiAccess')
                $objToken =  $user->createToken($user->email); 
                $strToken = $objToken->plainTextToken;
                $expiration = $objToken->accessToken->expires_at;
                //print_r($expiration);
                // die();

                //display details
                $success['token'] =   $strToken; 
                $success['token_type'] = 'Bearer';
                $success['expires_at']  =  date($expiration);
                $success['user_id']= $user->id;
                $success['email'] =  $user->email;
                $success['first_name'] = $profile->fname;
                $success['last_name'] = $profile->lname;
                $success['phone_number'] = $profile->phone;

                return $this->sendResponse($success, 'User login successfully.');
            } else {
                return $this->sendError('Unauthorised.', ['error'=>'Wrong password']);
            }
    
        } else {
            return $this->sendError('Unauthorised.', ['error'=>'User does not exist']);
        }  
    }
    
    public function checkout(Request $request){
        if( Auth::user()){
        // get user id for the login member
            $id = Auth::user()->id;

            $cart = $request->session()->get('cart');
            if(empty($cart)){
                $checkout['user_id'] = $id;
                $checkout['email']= Auth::user()->email;
                return $this->sendResponse($checkout, 'Your Cart is empty');
            } 
            else{
                $cartItem[$request->id]["quantity"] = $cart['quantity'];
                $cartItem[$request->id]["c_price"] = $cart["c_price"];
                $cartItem[$request->id]["store_id"] = $cart["store_id"];

                $totalAmount = 0;
                foreach ($cartItem as $item) {
                $totalAmount += $item['c_price'] * $item['quantity'];
                }
                // get user billing details from profile tab
                $user = Profile::Join('users', 'users.id', '=', 'profile.user_id')
                    ->Join('country', 'country.id', '=', 'profile.country_id')
                    ->Join('location', 'location.id', '=', 'profile.location_id')
                    ->Join('states', 'states.id', '=', 'profile.state_id')
                    ->where('users.id', $id)->get([
                    'users.id', 
                    'users.email',
                    'profile.fname',
                    'profile.lname',
                    'profile.address',
                    'profile.phone', 
                    'states.state', 
                    'location.location', 
                    'country.country']); 
                $processing_fee = 3 * $totalAmount / 100;
                $vat = 7.5 * $totalAmount / 100;
                $delivery = '';
                $grand_total = $totalAmount + $processing_fee + $vat;
                $less_processing = $grand_total - $processing_fee;

               $checkout['profile_details'] = $user;
               $checkout['cart']= $cart;
               $checkout['total_amount'] = $totalAmount;
               $checkout['processing_fee'] =round($processing_fee);
               $checkout['vat'] = $vat ;
               $checkout['delivery_fee'] = $delivery;
               $checkout['grand_total'] = number_format(round($grand_total), 2);
               $checkout = $request->session()->all();
                return $this->sendResponse($checkout, 'Your shopping cart');
            }
        }
        else { 
            return $this->sendError('Unauthorised.', ['error'=>'kindly login to checkout']);    
            }
       
    }
}//class

 



