<?php

namespace Increment\Marketplace\Paddock\Http;

use Illuminate\Http\Request;
use App\Http\Controllers\APIController;
use Increment\Marketplace\Paddock\Models\PaddockPlanTask;
use Increment\Marketplace\Paddock\Models\Paddock;
use Increment\Marketplace\Paddock\Models\SprayMix;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaddockPlanTaskController extends APIController
{
    //
    function __construct(){
        $this->model = new PaddockPlanTask();
        $this->notRequired = array();
    }
    
    public function retrieve(Request $request){
        $data = $request->all();
        $this->model = new PaddockPlanTask();
        $this->retrieveDB($data);
        for ($i=0; $i < count($this->response['data']); $i++){
             $spraymixdata= SprayMix::select('name')->where('id','=', $this->response['data'][$i]['spray_mix_id'])->get();
             if (count($spraymixdata) != 0){
                $this->response['data'][$i]['spray_mix_name'] = $spraymixdata[0]['name'];
             }
        }
        return $this->response();
    }

    public function retrieveTaskByPaddock($paddockPlanId){
        $result = PaddockPlanTask::where('paddock_plan_id', '=', $paddockPlanId)->get(['spray_mix_id', 'id', 'paddock_plan_id', 'due_date']);
        if(sizeof($result) > 0){
            return $result;
        }else{
            return null;
        }
    }

    public function retrievePaddockTaskByPaddock($paddockId){
        $result = Paddock::where('id', '=', $paddockId)->get();
        if(sizeof($result) > 0){
            return $result;
        }else{
            return null;
        }
    }

    public function retrieveAvailablePaddocks(Request $request){
        $data = $request->all();
        $result = DB::table('paddock_plans_tasks as T1')
                ->leftJoin('paddocks as T2', 'T1.paddock_id', '=', 'T2.id')
                ->leftJoin('paddock_plans as T3', 'T3.id', '=', 'T1.paddock_plan_id')
                ->leftJoin('crops as T4', 'T4.id', '=', 'T3.crop_id')
                ->where('T1.spray_mix_id', '=', $data['spraymix_id'])
                ->where('T2.merchant_id', $data['merchant_id'])
                ->get();
        if(sizeof($result) > 0){
            $this->response['data'] = $result;
        }else{
            return $this->response['data'] = [];
        }
        return $this->response();

    }
}
