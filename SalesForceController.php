<?php

namespace App\Http\Controllers;
use App\Models\Organization;
use GuzzleHttp\Client;
use Illuminate\Http\Request;


class SalesForceController extends Controller
{
    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    //view tracker records
    public function index(Request $request)
    {
        $organizations = Organization::pluck('name','id')->toarray();
//        dd($organizations);
        $data='';
        switch($request->input('status')){
            case 'month':
                $data='month';
                break;
            case 'week':
                $data='week';
                break;
            case 'today':
                $data='today';
                break;
        }


        if($request->from ){
            $fromDate =$request->input('from');
        }
        if($request->to !=NULL){
            $toDate =$request->input('to');
        }
       $organization_name=Organization::where('id',$request->organizations)->pluck('name');
//        dd($organization_name);
        $bodyData = [];
        if (auth()->user()->role_id == 1) {
            if($organization_name!=null) {
                $bodyData = [
                    'from' => $fromDate ?? '',
                    'to' => $toDate ?? '',
                    'dateString' => $data,
                    'id' => auth()->user()->id,
//                'id' => 1,
                ];
            }
            else{
                $bodyData = [
                    'organization_name'=>'Gilbert Classical Academy',
                    'from' => $fromDate ?? '',
                    'to' => $toDate ?? '',
                    'dateString' => $data,
                    'id' => auth()->user()->id,
//                'id' => 1,
                ];
            }
//            $url = "http://54.241.70.63:3300/users/all-users-activity-detail";
            $url = "http://54.241.70.63:3300/users/user-activity-detail-organization";
//            $url = "http://192.168.40.108:3300/users/user-activity-detail-organization";

        }
//        dd($bodyData);
        if (auth()->user()->role_id == 2) {
            $bodyData = [
                'from'=>$fromDate?? '',
                'to'=>$toDate?? '',
                'dateString'=>$data,
                'id' => auth()->user()->id,
//                'id' => 3,
            ];
            $url = "http://54.241.70.63:3300/users/all-organization-activity-detail";
        }
        if (auth()->user()->role_id == 3) {
            $bodyData = [
                'from'=>$fromDate?? '',
                'to'=>$toDate?? '',
                'dateString'=>$data,
                'id' => auth()->user()->id,
//                'id' => 4,
            ];
            $url = "http://54.241.70.63:3300/users/all-instructor-activity-detail";
        }
        if (auth()->user()->role_id == 4) {
            $bodyData = [
                'from'=>$fromDate?? '',
                'to'=>$toDate?? '',
                'dateString'=>$data,
                'id' => auth()->user()->id,
//                'id' => 11,
            ];
            $url = "http://54.241.70.63:3300/users/user-activity-detail";
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,// your preferred url
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($bodyData),
            CURLOPT_HTTPHEADER => array(
                // Set here requred headers
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
            ),
        ));

        $responseBody = json_decode(curl_exec ($curl));
        $err = curl_error($curl);
        curl_close($curl);
//        dd($responseBody->data);
        if ($err) {
            echo "cURL Error #:" . $err;
            $responseBody = [];
            return view('salesforce_logs.index', compact('responseBody','organizations'));
        } else {
//                print_r(json_decode($responseBody));
            return view('salesforce_logs.index', compact('responseBody','organizations'));
        }

    }

}
