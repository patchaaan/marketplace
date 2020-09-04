<?php


namespace Increment\Marketplace\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Models\Transfer;
use Increment\Marketplace\Models\TransferredProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class TransferController extends APIController
{
    public $transferredProductsClass = 'Increment\Marketplace\Http\TransferredProductController';
    public $merchantClass = 'Increment\Marketplace\Http\MerchantController';
    public $productClass = 'Increment\Marketplace\Http\ProductController';
    public $productTraceClass = 'Increment\Marketplace\Http\ProductTraceController';
    public $bundledProductController = 'Increment\Marketplace\Http\BundledProductController';
    public $landBlockProductClass = 'App\Http\Controllers\LandBlockProductController';
    function __construct(){
      $this->model = new Transfer();
      $this->localization();
    }

    public function create(Request $request){
      $data = $request->all();
      $data['code'] = $this->generateCode();
      $this->insertDB($data);
      return $this->response();
    }
    
    public function generateCode(){
      $code = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 32);
      $codeExist = Transfer::where('code', '=', $code)->get();
      if(sizeof($codeExist) > 0){
        $this->generateCode();
      }else{
        return $code;
      }
    }

    public function retrieve(Request $request){
      $data = $request->all();
      $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
      $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
      $size = null;
      $result = array();
      if($data['column'] == 'created_at'){
        $sort = array(
          $data['sort']['column'] => $data['sort']['value']
        );
        $parameter = array(
          'condition' => array(array(
              'column'  => $data['column'],
              'value'  => $data['value'],
              'clause'  => 'like'
            ), array(
              'column' => $data['filter_value'],
              'value'  => $data['merchant_id'],
              'clause' => '=' 
            )
          ),
          'sort'    => $sort,
          'limit'   => $data['limit'],
          'offset'  => $data['offset']
        );
        $this->model = new Transfer();
        $this->retrieveDB($parameter);
        $size = Transfer::where($data['column'], 'like', $data['value'])->where($data['filter_value'], '=', $data['merchant_id'])->count();
        $result = $this->response['data'];
      }else if($data['column'] == 'username'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('accounts as T2', 'T2.id', '=', 'T1.from')
          ->where('T2.username', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }else if($data['column'] == 'name'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('merchants as T2', 'T2.id', '=', 'T1.to')
          ->where('T2.name', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }
      if(sizeof($result) > 0){
        $i = 0;
        foreach ($result as $key) {
          $this->response['data'][$i]['transferred_products'] = app($this->transferredProductsClass)->getSizeNoDate('transfer_id', $result[$i]['id']);
          $this->response['data'][$i]['created_at_human'] = Carbon::createFromFormat('Y-m-d H:i:s', $result[$i]['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A');
          $this->response['data'][$i]['to_details'] = app($this->merchantClass)->getByParamsConsignments('id', $result[$i]['to']);
          $this->response['data'][$i]['from_details'] = app($this->merchantClass)->getByParamsConsignments('id', $result[$i]['from']);
          $this->response['data'][$i]['account'] = $this->retrieveAccountDetailsTransfer($result[$i]['account_id']);
          $i++;
        }
      }

      $this->response['size'] = $size;

      return $this->response();
    }

    public function retrieveAllowedOnly(Request $request){
      $data = $request->all();
      $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
      $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
      $size = null;
      $result = array();
      if($data['column'] == 'created_at'){
        $sort = array(
          $data['sort']['column'] => $data['sort']['value']
        );
        $parameter = array(
          'condition' => array(array(
              'column'  => $data['column'],
              'value'  => $data['value'],
              'clause'  => 'like'
            ), array(
              'column' => $data['filter_value'],
              'value'  => $data['merchant_id'],
              'clause' => '=' 
            )
          ),
          'sort'    => $sort,
          'limit'   => $data['limit'],
          'offset'  => $data['offset']
        );
        $this->model = new Transfer();
        $this->retrieveDB($parameter);
        $size = Transfer::where($data['column'], 'like', $data['value'])->where($data['filter_value'], '=', $data['merchant_id'])->count();
        $result = $this->response['data'];
      }else if($data['column'] == 'username'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('accounts as T2', 'T2.id', '=', 'T1.from')
          ->where('T2.username', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }else if($data['column'] == 'name'){
        $tempResult = DB::table('transfers as T1')
          ->select([
              DB::raw("SQL_CALC_FOUND_ROWS id")
          ])
          ->join('merchants as T2', 'T2.id', '=', 'T1.to')
          ->where('T2.name', 'like', $data['value'])
          ->where('T1.'.$data['filter_value'], '=', $data['merchant_id'])
          ->orderBy($data['column'], $data['sort']['value'])
          ->select('T1.*')
          ->offset($data['offset'])
          ->limit($data['limit'])
          ->get();

          $size = DB::select("SELECT FOUND_ROWS() as `rows`")[0]->rows;
          $this->response['data'] = json_decode($tempResult, true);
          $result = $this->response['data'];
      }
      if(sizeof($result) > 0){
        $i = 0;
        $array = array();
        foreach ($result as $key) {
          $item = array(
            'code'  =>  $key['code'],
            'name'  =>  $this->retrieveName($key['account_id']),
            'number_of_items'   =>  app($this->transferredProductsClass)->getSizeNoDate('transfer_id', $key['id']),
            'trasferred_on' => Carbon::createFromFormat('Y-m-d H:i:s', $key['created_at'])->copy()->tz($this->response['timezone'])->format('F j, Y H:i A'),
            'to'    => app($this->merchantClass)->getColumnValueByParams('id', $result[$i]['to'], 'name'),
            'from'  => app($this->merchantClass)->getColumnValueByParams('id', $result[$i]['from'], 'name')
          );
          $array[] = $item;
          $i++;
        }
        $this->response['data'] =  $array;
      }
      $this->response['size'] = $size;
      return $this->response();
    }

    public function retrieveConsignmentsImprove(Request $request){
      // get all products ? as this is based on consigments ?
      // limit
      // offset
      // get inventory
      // get total inventory both bundled and traces
    }

    public function retrieveConsignments(Request $request){
      $data = $request->all();
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.to', '=', $data['merchant_id'])
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->get(['T2.product_id', 'T2.created_at', 'T2.payload_value']);
      $result = $result->groupBy('product_id');
      // $this->response['data'] = $result;
      // return $this->response();
      $i = 0;
      $testArray = array();
      foreach ($result as $key => $value) {
        $size = 0;
        $bundledQty = 0;
        $productTrace = null;
        $test = null;
        $consumedValue = null;
        foreach ($value as $keyInner) {
          $productTrace = $keyInner->payload_value;
          $tSize = app($this->transferredProductsClass)->getSizeLimit('payload_value', $keyInner->payload_value, $keyInner->created_at);

          $bundled = app($this->bundledProductController)->getByParamsNoDetailsWithLimit('product_trace', $keyInner->payload_value, 1);
          $trace = app($this->productTraceClass)->getByParamsByFlag('id', $productTrace);
          if($data['type'] != 'USER'){
            $size += ($tSize > 0) ? 0 : 1;
          }else if($tSize == 0 && $bundled == null && $trace == true){
            $comsumed = 0;
            $comsumed = app($this->landBlockProductClass)->getTotalConsumedByTrace($data['merchant_id'], $productTrace, $keyInner->product_id);
            $size += (1 - $comsumed);
            $consumedValue = $size;
          }
          // if($tSize == 0 && $bundled == null && $trace == true && $data['type'] == 'USER'){
          //   // only to end user
          //   // should add user type on the parameter
          //   $comsumed = 0;
          //   $comsumed = app($this->landBlockProductClass)->getTotalConsumedByTrace($data['merchant_id'], $productTrace, $keyInner->product_id);
          //   $size += (1 - $comsumed);
          //   $consumedValue = $size;
          // }

          if($bundled != null){
            $bundledTransferred = TransferredProduct::where('payload_value', '=', $bundled['bundled_trace'])->where('deleted_at', '=', null)->limit(1)->count();
            if($bundledTransferred == 0){
              $bundledQty++;
            }
          }
          // $testArray[] = array(
          //   'product_id' => $keyInner->product_id,
          //   'trace' =>  $keyInner->payload_value,
          //   'test'  => $test,
          //   'consumed' => $consumedValue
          // );
        }
        if($size > 0){
          $product =  app($this->productClass)->getProductByParamsConsignments('id', $key);
          $product['qty'] = $size;
          $product['qty_in_bundled'] = $bundledQty;
          $product['productTrace'] = $productTrace;
          $product['test'] = $test;
          $this->response['data'][] = $product;
          $this->manageQtyWithBundled($product, $productTrace);
          $i++;
        }
      }
      // $this->response['data'] = $testArray;
      return $this->response();
    }

  public function retrieveByConsignmentsPagination(Request $request){
    $data = $request->all();
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
    $result = DB::table('transfers as T1')
    ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
    ->where('T1.to', '=', $data['merchant_id'])
    ->where('T2.deleted_at', '=', null)
    ->where('T1.deleted_at', '=', null)
    ->get();
    $result = $result->groupBy('product_id');
    $size = $result->count();
    $testArray = array();
    if(sizeof($result) > 0){  
      foreach($result as $key => $value){
        // account_id: "7"
        // code: "V6Q1G3IFL47HCOY5R0DEJAMXTWKZUP92"
        // created_at: "2020-03-25 07:59:24"
        // created_at_human: "March 25, 2020 15:59 PM"
        // deleted_at: null
        // description: "2 UNITS - WETTER"
        // featured: null
        // id: 8
        // inventories: null
        // merchant_id: "2"
        // price_settings: "fixed"
        // product_traces: null
        // qty: 0
        // qty_in_bundled: 0
        // rf: null
        // sku: null
        // status: "pending"
        // tag_array: null
        // tags: null
        // title: "2 x WETTER"
        // type: "bundled"
        // updated_at: "2020-03-25 07:59:24"
        $item = array(
          'title' => app($this->productClass)->getProductColumnByParams('id', $key, 'title'),
          'id' => $key,
          'merchant' => array(
            'name'  => 'test'
          ),
          'qty' => 0,
          'qty_in_bundled' => 0
        );
        $testArray[] = $item;
      }
    }
    $this->response['data'] = $testArray;
    $this->response['size'] = $size;
    return $this->response();
  }
    
    public function retrieveByPagination(Request $request){
      $data = $request->all();
      $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
      $data['limit'] = isset($data['offset']) ? $data['limit'] : 5;
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.to', '=', $data['merchant_id'])
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->get();
      $result = $result->groupBy('product_id');
      $i = 1;
      $size = $result->count();
      $testArray = array();
      if(sizeof($result) > 0){  
        foreach($result as $key){
          foreach($key as $innerKey){
            $item = array(
              'code'  =>  $innerKey->code,
              'name'  =>  $this->retrieveName($innerKey->account_id),
              'number_of_items'   =>  app($this->transferredProductsClass)->getSizeNoDate('transfer_id', $innerKey->id),
              'trasferred_on' => Carbon::createFromFormat('Y-m-d H:i:s', $innerKey->created_at)->copy()->tz($this->response['timezone'])->format('F j, Y H:i A'),
              'to'    => app($this->merchantClass)->getColumnValueByParams('id', $innerKey->to, 'name'),
              'from'  => app($this->merchantClass)->getColumnValueByParams('id', $innerKey->from, 'name')
            );
            if(!in_array($item, $testArray)){
              $count = count($testArray);
              if($count >= $data['limit']){
                array_shift($testArray);
              }
              $testArray[] = $item;
            }
          } 
          $this->response['data'] = $testArray;
        break;
        }
      }
    $this->response['size'] = $size;
    return $this->response();
  }

  public function retrieveProductTitle(Request $request){
    $data = $request->all();
    $result = DB::table('transfers as T1')
    ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
    ->where('T1.to', '=', $data['merchant_id'])
    ->where('T2.deleted_at', '=', null)
    ->where('T1.deleted_at', '=', null)
    ->get();
    $result = $result->groupBy('product_id');
    $data['offset'] = isset($data['offset']) ? $data['offset'] : 0;
    $data['limit'] = isset($data['limit']) ? $data['limit'] : 5;
    $i = 1;
    $size = $result->count();
    $testArray = array();
    // $this->response['data'] = $result;
    // return $this->response();
    if(sizeof($result) > 0){  
      foreach($result as $key => $value){
        $item = array(
          'title' => app($this->productClass)->getProductColumnByParams('id', $key, 'title'),
          'id' => $key
        );
        $testArray[] = $item;
      }
    }
    $this->response['data'] = $testArray;
    $this->response['size'] = $size;
    return $this->response();
  }

    public function retrieveTransferredItems(Request $request){
      $data = $request->all();
      $this->retrieveDB($data);
      if(sizeof($this->response['data']) <= 0){
        return $this->response();
      }
      $result = app($this->transferredProductsClass)->getAllByParamsOnly('transfer_id', $this->response['data'][0]['id']);
      if(sizeof($result) > 0){
        $array = array();
        foreach ($result as $key) {
          $trace = app($this->productTraceClass)->getByParamsDetails('id', $key['payload_value']);
          $item = array(
            'title'         => $trace[0]['product']['title'],
            'batch_number'  => $trace[0]['batch_number'],
            'manufacturing_date' => $trace[0]['manufacturing_date']
          );
          $array[] = $item;
        }
        $this->response['data'] = $array;
      }
      return $this->response();
    }

    public function manageQtyWithBundled($product, $productTrace){
      if($product['type'] != 'regular'){
        $bundled = app($this->bundledProductController)->getProductsByParamsNoDetailsDBFormat('bundled_trace', $productTrace);
        $this->response['others'] = $bundled;
        foreach ($bundled as $key) {
          $product = null;
          $index = null;
          $index = array_search(intval($key->product_on_settings), array_column($this->response['data'], 'id'), true);
          if(is_int($index)){
            $this->response['data'][$index]['qty_in_bundled'] += intval($key->size);
          }else{
            $product =  app($this->productClass)->getProductByParamsConsignments('id', intval($key->product_on_settings));
            $product['qty'] = 0;
            $product['qty_in_bundled'] = intval($key->size);
            $this->response['data'][] = $product;
          }
        }
      }
    }

    public function getQtyTransferred($merchantId, $productId){
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T1.to', '=', $merchantId)
      ->where('T2.product_id', '=', $productId)
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->get(['T2.*']);
      $result = $result->groupBy('product_id');
      $qty = 0;
      foreach ($result as $key => $value) {
        foreach ($value as $keyInner) {
          $tSize = app($this->transferredProductsClass)->getSize('payload_value', $keyInner->payload_value, $keyInner->created_at);
          $bundled = app($this->bundledProductController)->getByParamsNoDetails('product_trace', $keyInner->payload_value);
          if($tSize == 0 && $bundled == null){
            $qty++;
          }
        }
      }
      return $qty;
    }

    public function getOwn($traceId){
      $result = DB::table('transfers as T1')
      ->join('transferred_products as T2', 'T2.transfer_id', '=', 'T1.id')
      ->where('T2.payload_value', '=', $traceId)
      ->where('T2.deleted_at', '=', null)
      ->where('T1.deleted_at', '=', null)
      ->orderBy('T2.created_at', 'desc')
      ->first(['T1.id as t_id', 'T1.*', 'T2.*']);
      return $result;
    }

    public function basicRetrieve(Request $request){
      $data = $request->all();
      $this->model = new Transfer();
      $this->retrieveDB($data);
      return $this->response();
    }
}
