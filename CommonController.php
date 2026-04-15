<?php

namespace App\Http\Controllers;

use App\Mail\SendMessageMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CommonController extends Controller
{
    //send mail to selected user(organization,instructor,user)
    public function send_mail(Request $request)
    {
        $user = Auth::user();
        $receiver = User::find($request->id);

        if (!$receiver) {
            $response['success'] = false;
            $response['message'] = '';
            return response()->json($response);
        }
        $data = array(
            'sender_name' => $user->first_name,
            'receiver_name' => $receiver->first_name,
            'sender_email' => $user->email,
            'email' => $receiver->email,
            'message' => $request->Message,
            'type' => 'message'

        );
        dispatch(new \App\Jobs\SendEmailJob($data));
        $response['success'] = true;
        $response['message'] = 'Thanks, the message was sent successfully!';
        return response()->json($response);
    }

    //check organization subscription
    public function expire_subscription(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect('/');
        }

        $org = '';
        if ($user->role_id == 3) {
            $user->load('instructor.organization');
            if (!$user->instructor->organization)
                return redirect('/');
            $name = $user->instructor->organization->name;

        } else if ($user->role_id == 4) {
            $user->load('student.organization');
            if (!$user->student->organization)
                return redirect('/');
            $name = $user->student->organization->name;
        }


        return view('subscription-exipre')->with('user', $user)->with('oname', $name);
    }

    //view report issue to instructor and organization
    public function reportIssue()
    {
        if (\auth()->user()->role_id == 2) {
            $layoutType = 'organization';
        }
        if (\auth()->user()->role_id == 3) {
            $layoutType = 'instructors';
        }
        return view('common.report_issue', compact('layoutType'));
    }

    //direct message to admin
    public function admin_message()
    {
        $user = \auth()->user();

        if ($user->role_id == 2) {
            $parent = User::where('role_id', 1)->first();
            $layoutType = 'organization';
            $parent['type'] = 'Admin';
        }
        if ($user->role_id == 3) {
            $parent = $user->instructor->organization->user;

            $layoutType = 'instructors';
            $parent['type'] = 'Organization';
        }
        return view('common.parent_message', compact('layoutType'))->with('parent', $parent);
    }

    //send issue to admin
    public function sendIssue(Request $request)
    {
        $user = Auth::user();
        if ($request->parent) {
            $receiver = User::find($request->parent);
            if (!$receiver) {
                $response['success'] = false;
                $response['message'] = '';
                return response()->json($response);
            }
            $data = array(
                'sender_name' => $user->name,
                'receiver_name' => $receiver->name,
                'sender_email' => $user->email,
                'email' => $receiver->email,
                'message' => $request->Message,
                'type' => 'message'
            );
        } else {
//            $email = 'info@marroapp.com';
            $email ='operations@marroapp.com';
            if (!$user) {
                $response['success'] = false;
                $response['message'] = '';
                return response()->json($response);
            }
            $data = array(
                'sender_name' => $user->first_name,
                'sender_email' => $user->email,
                'email' => $email,
                'message' => $request->Message,
                'type' => 'issue'

            );
        }
        dispatch(new \App\Jobs\SendEmailJob($data));
        $response['success'] = true;
        $response['message'] = 'Thanks, the message was sent successfully!';
        return response()->json($response);

    }
}
