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

class PaymentController extends BaseController
{
    //
    public function Pay(Request $request){
      
        // validation
        $validator =Validator ::make($request->all(), [
         'request_id' => 'required'
         ]);      
         if($validator->fails()){
         $status = false;
         $message ="";
         $error = $validator->errors()->first();
         $data = "";
         $code = 401;                
         return ResponseBuilder::result($status, $message, $error, $data, $code);   
         }else{ 
 
            $request_id = $request->request_id;
 
             $requestResult = OrderRequest::where('id',$request_id)->first();
 
             // amount = rate x farm_size
            $amount =  $requestResult->hectare_rate * $requestResult->farm_size;
            if(!$amount){
             $status = false;
             $message ="";
             $error = "";
             $data = "Kindly  measure your farm size before making payment";
             $code = 401;                
             return ResponseBuilder::result($status, $message, $error, $data, $code); 
            }
             
             $curl = curl_init();
 
             $user_id = Auth::user()->id;
             $phone = Auth::user()->phone;
             $profileResult = UserProfile::where('user_id',$user_id)->first();
             $email = $profileResult->email;
             // $ref = random_int(100000, 999999);
 
             //UPDATE THE REFERENCE CODE TO REQUEST TABLE
             // $post_ref = OrderRequest::where('id',$request_id)
             // ->update([
             //  'reference' => $ref
             // ]);
 
               // url to go to after payment
             $callback_url = 'http://localhost:8000/api/payment';  
 
             $data = array(
               'callback_url' => $callback_url,
              
               'email'=>$email,
               "amount" => $amount,
               'metadata' =>array('phone' => $phone)
 
             );
 
             //$post_data = json_encode($data);
             
               curl_setopt_array($curl, array(
               CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
               CURLOPT_RETURNTRANSFER => true,
               CURLOPT_CUSTOMREQUEST => "POST",
               CURLOPT_POSTFIELDS => json_encode($data),
               CURLOPT_HTTPHEADER => [
                 "authorization: Bearer sk_test_8fabc18c29f908e5b7540b54d38a4b097250c39b", //replace this with your own test key
                 "content-type: application/json",
                 "cache-control: no-cache"
               ],
             ));
             
             $response = curl_exec($curl);
             $err = curl_error($curl);
             
             if($err){
               // there was an error contacting the Paystack API
               die('Curl returned error: ' . $err);
             }
             
             $tranx = json_decode($response, true);
             
             if(!$tranx['status']){
               // there was an error from the API
               print_r('API returned error: ' . $tranx['message']);
             }
                        
             if($tranx){
              $requestResult  = OrderRequest::where('id',$request_id)
             
             ->update([
              'agent_id' => Auth::user()->id,
             'pay_status' => "Payment pending"
             ]);
 
             $status = true;
             $message ="Transaction successful";
             $error = "";
             $data = $data;
             $code = 200;                
             return ResponseBuilder::result($status, $message, $error, $data, $code); 
           }
         }
    }
    
    public function payment(Request $request){

        $request_id = $request['request_id'];
         $reference = $request['reference'];
  
        $crl = curl_init('https://api.paystack.co/transaction/verify/'.$reference);
          curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($crl, CURLINFO_HEADER_OUT, true);
      
          curl_setopt($crl, CURLOPT_HTTPHEADER, array(
            "authorization: Bearer sk_test_8fabc18c29f908e5b7540b54d38a4b097250c39b", //replace this with your own test key
            "content-type: application/json",
            "cache-control: no-cache")
        );
   
      $response = curl_exec($crl);
      curl_close($crl);
  
      $better_response = json_decode($response);
  
      // get payment details
      $amount       = $better_response->data->amount;
      $paid_date    = $better_response->data->created_at;
      $pay_status   = $better_response->data->gateway_response;
      $gateway_ref  = $better_response->data->reference;
  
  //insert payment details to table
     $pay =  new Payment();
     $pay->request_id     = $request_id;
     $pay->ref            = $reference;
     $pay->pay_status     = $pay_status;
     $pay->gateway_ref    = $gateway_ref;
     $pay->pay_date       = $paid_date;
     $pay->amount         = $amount;
  
     $pay->save();
   
      //update request status to PAID
      if($pay_status == 'Successful'){
        OrderRequest::where('id',$request_id)
              ->update([
                'agent_id' => Auth::user()->id,
               'pay_status' => 'Paid'
              ]);
      }
  
      if($pay_status != 'Successful'){
         OrderRequest::where('id',$request_id)
              ->update([
              'agent_id' => Auth::user()->id,
               'pay_status' => 'Unpaid'
              ]);
      }
      if($pay){
        $status = true;
        $message ="";
        $error = "";
        $data = $better_response;
        $code = 200;                
        return ResponseBuilder::result($status, $message, $error, $data, $code); 
      }
      else{
         $status = false;
      $message ="Opps! Something went wrong.";
      $error = "";
      $data = "";
      $code = 401;                
      return ResponseBuilder::result($status, $message, $error, $data, $code);
      } 
  }
  
}
