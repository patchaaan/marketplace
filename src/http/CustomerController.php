<?php

namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Customer;
use Carbon\Carbon;
class CustomerController extends APIController
{

  public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
  public $emailClass = 'App\Http\Controllers\EmailController';

  function __construct(){
    $this->model = new Customer();
    $this->notRequired = array(
      'merchant_id', 'email'
    );
  }


  public function manageMerchant($data, $column, $value, $flag){
    $merchant = app($this->merchantClass)->getByParams($column, $value);
    if($merchant != null){
      if($this->checkIfExist($data['merchant'], 'merchant_id', $merchant['id']) == true){
        if($flag == true){
          return;
        }
        $this->response['data'] = null;
        $this->response['error'] = 'Merchant already existed to the list.';
        return $this->response();
      }
      $this->model = new Customer();
      $code = $this->generateCode();
      $params = array(
        'code'        => $code,
        'merchant'    => $data['merchant'],
        'merchant_id' => $merchant['id'],
        'status'      => 'pending'
      );
      $this->insertDB($params);
      if($this->response['data'] > 0){
          $account = app('Increment\Account\Http\AccountController')->retrieveById($merchant['account_id']);
          $template = array(
            'subject' => 'NEW MERCHANT LINK REQUEST',
            'view'    => 'email.customerinvitation'
          );
          $data['email'] = $account[0]['email'];
          $data['code'] = $code;
          $data['username'] = $account[0]['username'];
          app($this->emailClass)->sendCustomerInvitation($data, $template);
        }
        return $this->response();
    }else{
      if($flag == true){
        return;
      }
      $this->response['data'] = null;
      $this->response['error'] = 'Business code was not found!';
      return $this->response();
    }
  }
  

  public function create(Request $request){
    $data = $request->all();
    if(isset($data['business_code'])){
      return $this->manageMerchant($data, 'business_code', $data['business_code'], false);
    }else{
      if(!isset($data['email'])){
        $this->response['data'] = null;
        $this->response['error'] = 'Email address is required!';
        return $this->response();
      }
      if($this->checkIfExist($data['merchant'], 'email', $data['email']) == true){
        $this->response['data'] = null;
        $this->response['error'] = 'Email already existed to the list.';
        return $this->response();
      }
      $account = app('Increment\Account\Http\AccountController')->retrieveByEmail($data['email']);
      if($account != null){
        $this->manageMerchant($data, 'account_id', $account['id'], true);
      }

      $this->model = new Customer();
      $code = $this->generateCode();
      $params = array(
        'code'        => $code,
        'merchant'    => $data['merchant'],
        'email'       => $data['email'],
        'status'      => 'pending'
      );
      $this->insertDB($params);
      if($this->response['data'] > 0){
        $template = array(
          'subject' => 'YOUR INVITATION TO AGRICORD',
          'view'    => 'email.noncustomerinvitation'
        );
        $data['email'] = $data['email'];
        $data['code'] = $code;
        $data['username'] = $data['email'];
        app($this->emailClass)->sendCustomerInvitation($data, $template);
      }
      return $this->response();
    }
  }

  public function resend(Request $request){
    $data = $request->all();
    if($data['merchant_id'] != null){
      $getMerchant = app($this->merchantClass)->getByParams('id', $data['merchant_id']);
      if($getMerchant != null){
        $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
        $template = array(
          'subject' => 'NEW MERCHANT LINK REQUEST',
          'view'    => 'email.customerinvitation'
        );
        $data['email'] = $account[0]['email'];
        $data['username'] = $account[0]['username'];
        app($this->emailClass)->sendCustomerInvitation($data, $template);
      }
    }else{
      $template = array(
        'subject' => 'YOUR INVITATION TO AGRICORD',
        'view'    => 'email.noncustomerinvitation'
      );
      $data['username'] = $data['email'];
      app($this->emailClass)->sendCustomerInvitation($data, $template);
    }
    return $this->response();
  }

  public function update(Request $request){
    $data = $request->all();
    $this->updateDB($data);
    if($this->response['data']){

      if($data['status'] == 'approved'){
        // Send to receiver
        $getMerchant = app($this->merchantClass)->getByParams('id', $data['merchant_id']);
        $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
        $template = array(
          'subject' => 'BUSINESS LINK AGE SUCCESSFUL',
          'view'    => 'email.customerconfirmationreceiver'
        );
        $data['email'] = $account[0]['email'];
        $data['username'] = $account[0]['username'];
        $data['receiver_merchant_id'] = $data['merchant'];
        app($this->emailClass)->sendCustomerInvitation($data, $template);


        // Send to sender
        $getMerchant = app($this->merchantClass)->getByParams('id', $data['merchant']);
        $account = app('Increment\Account\Http\AccountController')->retrieveById($getMerchant['account_id']);
        $template = array(
          'subject' => 'BUSINESS LINK AGE SUCCESSFUL',
          'view'    => 'email.customerconfirmationsender'
        );
        $data['email'] = $account[0]['email'];
        $data['username'] = $account[0]['username'];
        $data['receiver_merchant_id'] = $data['merchant_id'];
        app($this->emailClass)->sendCustomerConfirmation($data, $template);
      }
    }
    return $this->response();
  }

  public function retrieve(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      foreach ($result as $key) {
        $this->response['data'][$i]['merchant_details'] = null;
        if($result[$i]['merchant_id'] != null){
          $this->response['data'][$i]['merchant_details'] = app($this->merchantClass)->getByParamsWithAccount('id', $result[$i]['merchant_id']);
        }
        $this->response['data'][$i]['merchant_sender_details'] = app($this->merchantClass)->getByParamsWithAccount('id', $result[$i]['merchant']);
        $i++;
      }
    }
    return $this->response();
  }

  public function retrieveAllowedOnly(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    
    if(sizeof($result) > 0){
      $i = 0;
      $array = array();
      foreach ($result as $key) {
        $name = null;
        $merchant = null;
       
        if($data['merchant_id'] == $key['merchant']){
          $merchant = app($this->merchantClass)->getByParamsWithAccount('id', $key['merchant_id']);
        }else{
          $merchant = app($this->merchantClass)->getByParamsWithAccount('id', $key['merchant']);
        }

        if($key['merchant_id'] == null){
          $name = $key['email'];
        }else{
          $name = $merchant ? $merchant['name'] : null;
        }

        $type = $merchant ? $merchant['account'][0]['account_type'] : null;
              
        $item = array(
          'name'    => $name,
          'type'    => $type,
          'status'  => $key['status'],
          'merchant'  => $key['merchant'],
          'merchant_id'  => $key['merchant_id'],
          'code'  => $key['code'],
          'id'      => $key['id']
        );
        $array[] = $item;
      }
      $this->response['data'] = $array;
    }

    if(sizeof($data['condition']) == 1){
      $this->response['size'] = Customer::where($data['condition'][0]['column'], '=', $data['condition'][0]['value'])->orWhere()->count();
    }else if(sizeof($data['condition']) == 2){
      $this->response['size'] = Customer::where($data['condition'][0]['column'], '=', $data['condition'][0]['value'])->orWhere($data['condition'][1]['column'], '=', $data['condition'][1]['value'])->count();
    }

    return $this->response();
  }

  public function retrieveList(Request $request){
    $data = $request->all();
    $this->retrieveDB($data);
    $result = $this->response['data'];
    if(sizeof($result) > 0){
      $i = 0;
      $this->response['data'] = [];
      foreach ($result as $key) {
        if($result[$i]['merchant_id'] != null){
          $this->response['data'][] = app($this->merchantClass)->getByParams('id', $result[$i]['merchant_id']);
        }
        $i++;
      }
    }
    return $this->response();
  }

  public function checkIfExist($merchant, $column, $value){
    $result = Customer::where('merchant', '=', $merchant)->where($column, '=', $value)->get();
    return sizeof($result) > 0 ? true : false;
  }

  public function generateCode(){
    $code = 'CUST-'.substr(str_shuffle($this->codeSource), 0, 59);
    $codeExist = Customer::where('code', '=', $code)->get();
    if(sizeof($codeExist) > 0){
      $this->generateCode();
    }else{
      return $code;
    }
  }
}
