<?php
namespace App\Http\Controllers\allUser;

use App\Http\Controllers\Controller;
use App\Models\activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AllUserController extends Controller
{

    //fetch trackers records of all rolls(organization,instructor,user)
    public function index($id, Request $request)
    {
        $name=User::where('id',$id)->get();

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
        $bodyData = [
            'dateString'=>$data,
            'id' => $id,
//        'id'=>'11',
        ];
        $url = "http://54.241.70.63:3300/users/user-activity-detail";

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
        if ($err) {
            echo "cURL Error #:" . $err;
            $responseBody = [];
            return view('user.index', compact('responseBody'))->with('name',$name);
        } else {
//                print_r(json_decode($responseBody));
            return view('user.index', compact('responseBody'))->with('name',$name);
        }

        return view('user.index');
    }
}

?>
