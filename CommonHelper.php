<?php


namespace App\Helpers;


use App\Classes\Models\Notification\Notification;
use App\Classes\Models\User\User;
use Illuminate\Support\Facades\Log;

use \Mailjet\Resources;


class CommonHelper
{


    public static function sendAndSave()
    {
        $args = func_get_args();
        $argsData = current($args);

//        dd($argsData);
//        dd($argsData);

        $recivers = $argsData['user_id'];
        $fcmsWithUserId = [];
        if (gettype($argsData['user_id']) == "array") {
//            $recivers = [$argsData['user_id']];
            $fcmsWithUserId = User::where([['status', "=", 1], [$argsData['user_field'], '=', 1], ['fcm_token', "!=", null]])->whereIn('user_id', $recivers)->pluck('fcm_token', 'user_id');
        } else {
            $user = $argsData['user']->toarray();
            //  unset($argsData['user']);
            if ($user['status'] == 1 && $user[$argsData['user_field']] == 1 && $user['fcm_token'] != null) {
                $fcmsWithUserId[$user['user_id']] = $user['fcm_token'];
            }
//            print_r($fcmsWithUserId);
//            die;
        }
//        dd($recivers);

        $currentDate = \DateFacades::getCurrentDateTime('format-1');
        $recodes = [];
        $fcms = [];
        $uniqid = uniqid("noti_");
//        echo "<pre>";

        if (count($fcmsWithUserId) > 0) {
            foreach ($fcmsWithUserId as $user_id => $fcm) {
                $a = $argsData;
                $a['user_id'] = $user_id;
                $a['created_at'] = $currentDate;
                $a['updated_at'] = $currentDate;
                $a['unique_group_id'] = $uniqid;
                unset($a['user_field']);
                if (isset($a['user']))
                    unset($a['user']);
                $recodes[] = $a;
                $fcms[] = $fcm;
//                print_r($recodes);die;
            }

            $nti = new Notification();

            \DB::table($nti->getTableName())->insert($recodes);
            return CommonHelper::sendNotifications($fcms, ['title' => $argsData['title'], 'body' => $argsData['description']]);
        }


    }
    public static  function sendMailUsingMailjet($fromEmail,$fromName,$subject='Marro',$html){
        $mj = new \Mailjet\Client(env('MAILJET_APIKEY','811107e4a575a9f90df93d0fec0a7fe8'), env('MAILJET_APISECRET',"89de1a0921995ec379422ebfc0b8c268"),true,['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "operations@marroapp.com",
                        'Name' => "Marro"
                    ],
                    'To' => [
                        [
                            'Email' => $fromEmail,
                            'Name' => $fromName
                        ]
                    ],
                    'Subject' => $subject,
//                            'TextPart' => "Greetings from Mailjet!",
                    'HTMLPart' => $html //view('student.auth.emails.passwordnew')->with($data)->render()
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);

        $response->success() ;
        $data=$response->getData();

        if(!(isset($data['Messages']) && current($data['Messages'])['Status']=="success"))
        {
            throw new \Exception("Please try again.");
        }
//        if($data)
    }

    public static function sendNotifications($fcms = [], $notificationData = ['title' => "Dot", 'body' => "Notification"])
    {

        $api_key = env('NOTIFICATION_KEY');
        $headers = array('Authorization: key=' . $api_key, 'Content-Type: application/json');
        $url = 'https://fcm.googleapis.com/fcm/send';

        $fields = array(
            'registration_ids' => $fcms,
            'data' => $notificationData,
            'notification' => $notificationData,
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'alert' => $notificationData,
                    'badge' => 42,
                    'sound' => 'default',
                ],
            ],
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        curl_close($ch);
//            Log::info("notification Send " . count($deviceTokens) . "-> failures" .$result);
        return $result;

    }

    public static function NotificationStore()
    {
        $args = func_get_args();
        $argsData = current($args);
        $Class = ["Follow" => "follow_id", "Classes" => "class_id"];

        $Status = [
            "Schedule" => 1, "Sent" => 0, "View" => 2, "Failed" => 3
        ];
        $NotificationType = [
            "Classes" => 0, "System" => 1, "Message" => 2, "Follow" => 3
        ];
        $saveData = [];


        if (isset($argsData['type'])) {
//            echo "123";


            $saveData['status'] = $Status['Sent'];
            $saveData['display_date'] = \DateFacades::getCurrentDateTime('format-1');
//            dd($saveData);

            if (in_array($argsData['type'], array_keys($Class)) && isset($argsData['object'])) {

                $saveData["notification_type"] = $NotificationType[$argsData['type']];

                if ($argsData['type'] == "Follow") {
                    $saveData[$Class[$argsData['type']]] = $argsData['object']->id;
                    $saveData['user_id'] = $argsData['object']->follower->user_id;
                    $saveData['user'] = $argsData['object']->follower;
                    $saveData['created_user_id'] = $argsData['following_id'];
                    $saveData['title'] = $argsData['object']->following->name;
                    $saveData['description'] = "is now Following you";
                    $saveData['user_field'] = 'follow_alert_notification';

                } elseif ($argsData['type'] == "Classes") {

                    $class = $argsData['object'];
                    $saveData[$Class[$argsData['type']]] = $class->class_id;
                    $saveData['user_id'] = User::where('user_id',"!=",$class->user->user_id)->pluck('user_id')->toArray();//$class->user->followers->pluck('follower_id')->toArray();
                    $saveData['created_user_id'] = $class->user_id;
                    $saveData['title'] = $class->user->name;
                    $saveData['user_field'] = 'class_alert_notification';
                    if ($class->is_live_stream) {
                        $saveData['description'] = "started a class \"" . $class->title . "\".";
                    } else {
                        $saveData['description'] = "scheduled a new class \"" . $class->title . "\" for " . \DateFacades::dateFormat($class->stream_time, 'User-1');
                    }
//                    dd($argsData,$saveData);

                }

            } else if (in_array($argsData['type'], array_keys($NotificationType))) {
                $saveData["notification_type"] = $NotificationType[$argsData['type']];
                $saveData['title'] = $argsData['title'];
                $saveData['user_id'] = $argsData['user']->user_id;
                $saveData['user'] = $argsData['user'];
                $saveData['description'] = $argsData['description'];
                $saveData['user_field'] = 'system_alert_notification';

            }
        }
        if (!empty($saveData)) {
            CommonHelper::sendAndSave($saveData);

        }
//        $unique_group_id=uniqid("notification_");
//
//            Notification::create();
    }

    public static function paypalConfig($code = null, $grant_type = "client_credentials", $toeknname = 'access_token')
    {

        /* echo "<pre>";
         print_r([
             $code,
             $grant_type,
             $toeknname
         ]);*/
        $mode = strtolower(env('PAYPAL_MODE', "sandbox"));
        $paypalMode = "";
        $paypalURl = "";
        $paypalURloauth2 = "https://api.sandbox.paypal.com/v1/oauth2/token";
        if (in_array($mode, ["live", "production"])) {
            $paypalURl = "https://api-m.paypal.com/v2/";
            $paypalMode = "LIVE_";
            $paypalURloauth2 = "https://api.paypal.com/v1/oauth2/token";
        } else {
            $paypalURl = "https://api-m.sandbox.paypal.com/v2/";
            $paypalMode = "SANDBOX_";
            $paypalURloauth2 = "https://api.sandbox.paypal.com/v1/oauth2/token";
        }
        $authtokne = env($paypalMode . 'CLIENT_ID') . ":" . env($paypalMode . 'SECRET_ID');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypalURloauth2);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $authtokne);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=" . $grant_type . (isset($code) ? "&" . ($grant_type == 'authorization_code' ? 'code=' : 'refresh_token=') . $code : ""));
        $result = curl_exec($ch);

//        print_r($result);
//        if($grant_type="refresh_token")
//        {
//            dd($result);
//        }
        if ($result) {
            $data = json_decode($result, true);
//            return $data;
            if (!isset($data[$toeknname])) {

                throw new \Exception("Invalid server credentials");
            }
            $accesstoken = $data[$toeknname];
        }

        return [
            "url" => $paypalURl,
            "basic_token" => $authtokne,
            "access_token" => $accesstoken,
            "mode" => $paypalMode
        ];
    }

    public static function checkOrderStatus($orderID)
    {

        $paypalConfig = CommonHelper::paypalConfig();
        $curl = curl_init();
        $url = $paypalConfig['url'] . "checkout/orders/" . $orderID;
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $paypalConfig["access_token"]
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    public static function createTokenApi($amount)
    {
        $paypalConfig = CommonHelper::paypalConfig();
        $curl = curl_init();
        $url = $paypalConfig['url'] . "checkout/orders";
        $body = [
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => url('api/CheckTransactionStatus?paypal=true'),
                "cancel_url" => url('api/CheckTransactionStatus?paypal=true'),
            ],
            "purchase_units" => [
                ["amount" => ["currency_code" => "USD", "value" => $amount]]
            ]
        ];
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Prefer:return=representation',
                'Authorization: Bearer ' . $paypalConfig['access_token']
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response, true);


    }

    public static function userinfo($token)
    {
        $curl = curl_init();
        if (env("PAYPAL_MODE") != "production") {
            $url = "https://api.sandbox.paypal.com/";
        } else {
            $url = "https://api.paypal.com/";
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url . "v1/identity/oauth2/userinfo?schema=paypalv1.1",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "authorization: Bearer  " . $token,
                "content-type: application/x-www-form-urlencoded"
            ),
        ));
        $response = curl_exec($curl);

        if (empty($response)) {
            return redirect(url('api/home?status=fail'));
        }

        $err = curl_error($curl);
        curl_close($curl);
        return json_decode($response);

    }

    public static function payoutsRequest($email, $amount){

    }

//    public static function payoutsRequest($email, $amount)
//    {
//        $data = CommonHelper::paypalConfig();
//        $access_token = $data['access_token'];
//        $headers[] = "Content-Type: application/json";
//        $headers[] = "Authorization: Bearer $access_token";;
//        $curl = curl_init();
//        $url = "https://api-m.sandbox.paypal.com/v1/payments/payouts";
//        if (in_array($data['mode'], ["live", "production"])) {
//            $url = "https://api-m.paypal.com/v1/payments/payouts";
//        }
//        $time = time();
//        //--- Prepare sender batch header
//        $sender_batch_header["sender_batch_id"] = $time;
////        $sender_batch_header["email_subject"] = "Payout Received";
////        $sender_batch_header["email_message"] = "You have received a payout, Thank you for using our services";
//
//        $receiver["recipient_type"] = "EMAIL";
//        $receiver["note"] = "Thank you for your services";
//        $receiver["sender_item_id"] = $time++;
//        $receiver["receiver"] = $email;
//        $receiver["amount"]["value"] = $amount;
//        $receiver["amount"]["currency"] = "USD";
//        $items[] = $receiver;
//        $data["sender_batch_header"] = $sender_batch_header;
//        $data["items"] = $items;
//        curl_setopt($curl, CURLOPT_URL, $url);
//        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//
//        //--- If any headers set add them to curl request
//        if (!empty($headers)) {
//            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
//        }
//
//        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
//
//
////--- If any data is supposed to be send along with request add it to curl request
//        if ($data) {
//            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
//        }
////--- Any extra curl options to add in curl object
////        if ($curl_options ?? []) {
//        foreach ($curl_options ?? [] as $option_key => $option_value) {
//            curl_setopt($curl, $option_key, $option_value);
//        }
////        }
//
//        $response = curl_exec($curl);
//        $error = curl_error($curl);
//        curl_close($curl);
//
////--- If curl request returned any error return the error
//        if ($error) {
//            throw new \Exception("Oops, something went wrong! Please try again");
//        }
////--- Return response received from call
//        return json_decode($response, 1);
//    }

    public static  function CheckPayoutStatus($id)
    {
        $data = CommonHelper::paypalConfig();
        $access_token = $data['access_token'];
        $curl = curl_init();
        $url = "https://api-m.sandbox.paypal.com/v1/payments/payouts/";
        if (in_array($data['mode'], ["live", "production"])) {
            $url = "https://api-m.paypal.com/v1/payments/payouts/";
        }
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url.$id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                "Authorization: Bearer $access_token"
            ),
        ));

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);


//--- If curl request returned any error return the error
        if ($error) {
            Log::info($error);
            return false;
//            throw new \Exception("Oops, something went wrong! Please try again");
        }
        return json_decode($response);
    }

}
