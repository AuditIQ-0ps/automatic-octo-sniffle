<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Intervention\Image\Facades\Image;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    //display data in admin home page
    public function index()
    {
        $user = Auth::user();
        $currentYear = date('Y');
        $currentMonth = date('M');
        $date = \Carbon\Carbon::today()->subDays(7);
        $lastStartOfWeek = Carbon::parse($date)->startOfWeek();
        $lastEndOfWeek = Carbon::parse($date)->endOfWeek();

        updateUserInFirebase();
        $transactionsCount = OrganizationSubscription::with('organization')->whereHas('organization')->take(6)->orderBy('id', 'DESC')->count();

        // organization
        $organizationCount['total'] = Organization::whereHas('user')->count();
        $organizationCount['weekly'] = Organization::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $organizationCount['last_week'] = Organization::whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $organizationCount['percent'] = (($organizationCount['weekly'] - $organizationCount['last_week']) * 100) / ($organizationCount['last_week'] ?: 1);
        $organizationCount['percent'] = number_format($organizationCount['percent']);
//        dd($organizationCount);
        // instructor count
        $instructorCount['total'] = Instructor::whereHas('user')->whereHas('organization')->count();
        $instructorCount['weekly'] = Instructor::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $instructorCount['last_week'] = Instructor::whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $instructorCount['percent'] = (($instructorCount['weekly'] - $instructorCount['last_week']) * 100) / ($instructorCount['last_week'] ?: 1);
        $instructorCount['percent'] = number_format($instructorCount['percent']);

        $studentCount['total'] = Student::whereHas('user')->whereHas('organization')->count();
        $studentCount['weekly'] = Student::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $studentCount['last_week'] = Student::whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $studentCount['percent'] = (($studentCount['weekly'] - $studentCount['last_week']) * 100) / ($studentCount['last_week'] ?: 1);
        $studentCount['percent'] = number_format($studentCount['percent']);

        $organization = Organization::with('user')->take(6)->orderBy('id', 'DESC')->get();
        $instructor = Instructor::with('user')->take(6)->orderBy('id', 'DESC')->get();
        $student = Student::with('user')->whereHas('user')->take(6)->orderBy('id', 'DESC')->get();
        $transactions = OrganizationSubscription::with('organization')->whereHas('organization')->take(6)->orderBy('id', 'DESC')->get();
//        dd($organization);

        // transactions counts
        $allTransactionsCounts['monthly'] = number_format(OrganizationSubscription::success()->whereMonth('created_at', '=', date('m'))->count());
        $allTransactionsCounts['yearly'] = number_format(OrganizationSubscription::success()->whereYear('created_at', '=', $currentYear)->count());
        $allTransactionsCounts['total'] = number_format(OrganizationSubscription::success()->count());

        //salesforce
        $salesFroce['total']=number_format(User::count());
        $salesFroce['weekly']=User::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $salesFroce['last_week']=User::whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $salesFroce['percent']=(($salesFroce['weekly'] - $salesFroce['last_week']) * 100) / ($salesFroce['last_week'] ?: 1);
        $salesFroce['percent'] = number_format($salesFroce['percent']);
//        dd($salesFroce);

        // Livestreams
        $allEvents['weekly'] = Event::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $allEvents['last_week'] = Event::whereBetween('created_at', [$lastStartOfWeek, $lastEndOfWeek])->count();
        $allEvents['total'] = number_format(Event::count());
        $allEvents['percent'] = (($allEvents['weekly'] - $allEvents['last_week']) * 100) / ($allEvents['last_week'] ?: 1);
        $allEvents['percent'] = number_format($allEvents['percent']);

        // transactions sales sum
        $allTransactionsSales['monthly'] = number_format(OrganizationSubscription::success()->whereMonth('created_at', '=', date('m'))->sum('plan_price'));
        $allTransactionsSales['yearly'] = number_format(OrganizationSubscription::success()->whereYear('created_at', '=', $currentYear)->sum('plan_price'));
        $allTransactionsSales['total'] = number_format(OrganizationSubscription::success()->sum('plan_price'));

        // transactions sales sum
        $allTransactionsPlans['Pro'] = number_format(OrganizationSubscription::success()->where('plan_name', 'Pro')->count());
        $allTransactionsPlans['Basic'] = number_format(OrganizationSubscription::success()->where('plan_name', 'Basic')->count());
        $allTransactionsPlans['Enterprise'] = number_format(OrganizationSubscription::success()->where('plan_name', 'Enterprise')->count());

//        dd($allTransactionsPlans);
        $transactionsCount = [];
        for ($i = 1; $i <= date('t'); $i++) {
            $instructors = User::select(\DB::raw('count(*) as count'))
                ->whereMonth('created_at', '=', date('m'))
                ->whereDay('created_at', '=', $i)
                ->whereYear('created_at', '=', $currentYear)
                ->where('role_id', 3)->count();
            $instructorsCount[] = $instructors;
            $instructorLables[] = $currentMonth . ' ' . $i;
            $trs = OrganizationSubscription::whereMonth('created_at', '=', date('m'))
                ->whereDay('created_at', '=', $i)
                ->whereYear('created_at', '=', $currentYear)->success()->sum('plan_price');
            $transactionsCount[] = $trs;
            $transactionsLables[] = $currentMonth . ' ' . $i;
        }

        for ($i = 1; $i <= date('t'); $i++) {
            $customer = User::select(\DB::raw('count(*) as count'))
                ->whereMonth('created_at', '=', date('m'))
                ->whereDay('created_at', '=', $i)
                ->whereYear('created_at', '=', $currentYear)
                ->where('role_id', 4)->count();
            $usersCount[] = $customer;
            $usersLables[] = $currentMonth . ' ' . $i;
        }

        for ($i = 1; $i <= date('t'); $i++) {
            $event = Event::select(\DB::raw('count(*) as count'))
                ->whereMonth('created_at', '=', date('m'))
                ->whereDay('created_at', '=', $i)
                ->whereYear('created_at', '=', $currentYear)->count();
            $eventCount[] = $event;
            $eventLables[] = $currentMonth . ' ' . $i;
        }

        for ($i = 1; $i <= date('t'); $i++) {

            $organizations = User::select(\DB::raw('count(*) as count'))
                ->whereMonth('created_at', '=', date('m'))
                ->whereDay('created_at', '=', $i)
                ->whereYear('created_at', '=', $currentYear)
                ->where('role_id', 2)->count();

            $organizationsCount[] = $organizations;
            $organizationsLables[] = $currentMonth . ' ' . $i;
        }
        //       dd($instructor);
        $data['lables'] = $instructorLables ?? [];
        $data['userLables'] = $usersLables ?? [];
        $data['eventLables']=$eventLables ?? [];
        $data['userList'] = $usersCount ?? [];
        $data['eventList']=$eventCount ?? [];
        $data['transactionsList'] = $transactionsCount ?? [];
        //        $data['transactionsLables'] = $transactionsLables ?? [];
        $data['organizationLables'] = $organizationsLables ?? [];
        $data['organizationList'] = $organizationsCount ?? [];
        $data['instructorList'] = $instructorsCount ?? [];
        //        $data['eventList'] = $eventCount ?? [];
//dd($transactions);
        return view('home')->with('organizationCount', $organizationCount)
            ->with('instructorCount', $instructorCount)
            ->with('transactions', $transactions)
            ->with('transactionsCount', $transactionsCount)
            ->with('salesFroce',$salesFroce)
            ->with('allTransactionsCounts', $allTransactionsCounts)
            ->with('allTransactionsSales', $allTransactionsSales)
            ->with('allTransactionsPlans', $allTransactionsPlans)
            ->with('allEvents', $allEvents)
            ->with('studentCount', $studentCount)
            ->with('organization', $organization)
            ->with('instructor', $instructor)
            ->with('student', $student)
            ->with('eventCount',$eventCount)
            /*  ->with('usersCount',$usersCount)->with('usersLables',$usersLables)*/ ->with($data);
    }

    //view admin profile
    public function profile()
    {
        $admin = User::where('id', auth()->id())->first();
        return view('admin.profile.index', compact('admin'));
    }

    // update admin profile records
    public function saveProfile(Request $request)
    {
        $data = $request->all();
        //        dd($data);
        $result = User::where('id', auth()->id())->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email']
        ]);

        if ($data['emoji-img']) {
            $path = $data['emoji-img'];
            $type = pathinfo($path, PATHINFO_EXTENSION);
            //            dd($path);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

            $img = $base64;
            $folderPath = "images/"; //path location

            $image_parts = explode(";base64,", $img);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1]);
            $uniqid = time();
            $file = public_path() . '/' . $folderPath . $uniqid . '.' . $image_type;
            $filename = $uniqid . '.' . $image_type;
            file_put_contents($file, $image_base64);

            $photo = '/public/images' . '/' . $filename;
            $result = User::where('id', auth()->id())->update(['photo' => $photo]);
        }

//        if ($request->hasFile('photo')) {
//            $image = $request->file('photo');
//            $path = public_path('images');
//            echo $path . "<br>";
//            $filename = time() . '.' . $image->extension();
//            $image->move($path, $filename);
//            $photo = '/public/images' . '/' . $filename;
//            $result = User::where('id', auth()->id())->update(['photo' => $photo]);
//        }
        if ($request->hasFile('photo')) {
            $image = $request->file('photo');
            $filename = time() . '.' . $image->extension();
            $image=Image::make($image->path())->resize(200,200);
            $path = public_path('/images' . '/' . $filename);
            $image->save($path,50);

            $photo = '/public/images' . '/' . $filename;
            $result = User::where('id', $data['user_id'])->update(['photo' => $photo]);
        }

        if (!empty($result)) {
            $request->session()->flash('success', 'Admin profile updated successfully.');
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }

    //update admin password
    public function changePassword(Request $request)
    {
        $data = $request->all();
        if ($data['password'] != $data['password_confirmation']) {
            return Redirect::back()->withInput($data)->withErrors(['password_confirmation'=>'Sorry, the 2 passwords did not match, please match them and submit again.']);
        }
        $result = User::where('id', $data['user_id'])->update(['password' => Hash::make($data['password'])]);
        if (!empty($result)) {
            $request->session()->flash('success', "admin password updated successfully.");
            return Redirect::back();
        } else {
            return Redirect::back()->withErrors($result['message']);
        }
    }

    //send message to selected role(organization,instructor,user)
    public function message(Request $request)
    {
        //        dd($request->all());
        $user = $request->user();
        $data2 = [];
        $database = app('firebase.database');
        updateUserInFirebase();

        if (isset($request->user_id) && isset($request->open_chat)) {
            $chatUser = User::find($request->user_id);
            if (!$chatUser || $request->user_id == $user->id) {
                $message['error'] = "User not found!";
                return $message;
            }
            updateUserInFirebase($request->user_id);
            $chat_data = $database->getReference('chat')->getSnapshot();
            //            dd($chat_data->getValue());
            $chat_id = '';
            foreach ($chat_data->getValue() ?? [] as $k => $v) {
                //                dd($v['sender_id']);
                if (isset($v['sender_id']) && isset($v['reciever_id'])) {
                    if ($v['sender_id'] == $chatUser->id && $v['reciever_id'] == $user->id)
                        $chat_id = $k;
                    elseif ($v['sender_id'] == $user->id && $v['reciever_id'] == $chatUser->id)
                        $chat_id = $k;
                }
            }

            if (empty($chat_id)) {
                $chat = [
                    "date_time" => time(),
                    "isread_receiver" => $chatUser->id . "_true",
                    "isread_sender" => $user->id . "_true",
                    "message" => "",
                    "reciever_id" => $chatUser->id,
                    "reciever_name" => $chatUser->name,
                    "reciever_profile" => $chatUser->profile,
                    "sender_id" => $user->id,
                    "sender_name" => $user->name,
                    "sender_profile" => $user->profile,
                    "type" => "text",
                ];

                $chat_id = $database->getReference('chat')->push()->getKey();
                $chat_data = $database->getReference('chat/' . $chat_id)->update($chat);
            } else {
                $chat_data = $database->getReference('chat/' . $chat_id)->getSnapshot();
                $newdata = $olddata = $chat_data->getValue();
                if ($olddata['reciever_id'] == $user->id) {
                    $newdata['reciever_name'] = $user->name;
                    $newdata['reciever_profile'] = $user->profile;
                    $newdata['sender_name'] = $chatUser->name;
                    $newdata['sender_profile'] = $chatUser->profile;
                } else {
                    $newdata['reciever_name'] = $chatUser->name;
                    $newdata['reciever_profile'] = $chatUser->profile;
                    $newdata['sender_name'] = $user->name;
                    $newdata['sender_profile'] = $user->profile;
                }
                $chat_data = $database->getReference('chat/' . $chat_id)->update($newdata);
            }
            $data2['chat_id'] = $chat_id;
            $data2['chat_data'] = $chat_data;
            return $data2;
        }
        if (isset($request->user_id)) {
            $chatUser = User::find($request->user_id);
            if (!$chatUser || $request->user_id == $user->id)
                return redirect()->back()->withInput($request->all())->withErrors(["flash" => "User not found!"]);
            updateUserInFirebase($request->user_id);
            $chat_data = $database->getReference('chat')->getSnapshot();
            //            dd($chat_data->getValue());
            $chat_id = '';
            foreach ($chat_data->getValue() ?? [] as $k => $v) {
                if (isset($v['sender_id']) && isset($v['reciever_id'])) {
                    if ($v['sender_id'] == $chatUser->id && $v['reciever_id'] == $user->id)
                        $chat_id = $k;
                    elseif ($v['sender_id'] == $user->id && $v['reciever_id'] == $chatUser->id)
                        $chat_id = $k;
                }
            }

            if (empty($chat_id)) {
                $chat = [
                    "date_time" => time(),
                    "isread_receiver" => $chatUser->id . "_true",
                    "isread_sender" => $user->id . "_true",
                    "message" => "",
                    "reciever_id" => $chatUser->id,
                    "reciever_name" => $chatUser->name,
                    "reciever_profile" => $chatUser->profile,
                    "sender_id" => $user->id,
                    "sender_name" => $user->name,
                    "sender_profile" => $user->profile,
                    "type" => "text",
                ];

                $chat_id = $database->getReference('chat')->push()->getKey();
                $chat_data = $database->getReference('chat/' . $chat_id)->update($chat);
            } else {
                $chat_data = $database->getReference('chat/' . $chat_id)->getSnapshot();
                $newdata = $olddata = $chat_data->getValue();
                if ($olddata['reciever_id'] == $user->id) {
                    $newdata['reciever_name'] = $user->name;
                    $newdata['reciever_profile'] = $user->profile;
                    $newdata['sender_name'] = $chatUser->name;
                    $newdata['sender_profile'] = $chatUser->profile;
                } else {
                    $newdata['reciever_name'] = $chatUser->name;
                    $newdata['reciever_profile'] = $chatUser->profile;
                    $newdata['sender_name'] = $user->name;
                    $newdata['sender_profile'] = $user->profile;
                }
                $chat_data = $database->getReference('chat/' . $chat_id)->update($newdata);
            }
            $data2['chat_id'] = $chat_id;
        }

        return view('message')->with('user', $user)->with($data2);
    }

    public function search(Request $request)
    {
        $users = User::where('first_name', 'like', '%' . $request->name . '%')->orWhere('last_name', 'like', '%' . $request->name . '%')->get();

        return $users;
    }
}
