<?php

namespace App\Http\Controllers\Instructor;

use App\Exports\CSVExport;
use App\Http\Controllers\Controller;
use App\Mail\InviteStudentMail;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\QuizCategory;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Response;

class VideoCallController extends Controller
{
    //view all livestream
    public function index(Request $request)
    {
        $per_page = $request->par_page ?? 10;
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'updated_at';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $instructorName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";
        $category=QuizCategory::pluck('standard','id');
        $subject=Subject::all();
        if (isset($sortedBy) && isset($sortedOrder)) {
            $events = Event::where('user_id', auth()->id())->orderBy($sortedBy, $sortedOrder);
        } else {
            $events = Event::where('user_id', auth()->id())->orderBy('id', 'desc');
        }
        $eventCount = Event::where('user_id', auth()->id())->count();
        $total = 'active';
//        $events = Event::where('user_id', auth()->id())->orderBy($sortedBy, $sortedOrder);
        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $events = $events->paginate($request->par_page);
            }
            return view('instructors.video_call.table', compact('events', 'sortedBy', 'sortedOrder', 'total', 'eventCount'))->with('per_page',$per_page);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $eventDatas = $events->latest()->get();
                $rows = ([
                    'id' => 'Event ID',
                    'class' => 'Class',
                    'date_time' => 'Date time',
                    'duration' => 'Duration',
                ]);
                $keys = array_keys($rows);
                $txtop = '';
                foreach ($eventDatas as $eventData) {
                    $cuRecord = [
                        'id' => $eventData->id,
                        'class' => $eventData->class,
                        'date_time' => $eventData->date,
                        'duration' => $eventData->duration,

                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Events') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
//                    dd($output);
                    $fileName = exportFilePrefix('Events') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $events=$events->paginate($per_page);
            return view('organization.table')
                ->with('instructors.video_call.table', $events)->render();
        }
        $events=$events->paginate($per_page);
        return view('instructors.video_call.index', compact('events', 'sortedBy', 'sortedOrder', 'total', 'eventCount'));
    }

    //view upcoming livestream
    public function ViewUpcoming(Request $request)
    {
        $data = $request->all();
        $per_page = $request->par_page ?? 10;
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'updated_at';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $instructorName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $student = Student::where('user_id', auth()->id())->first();
        $instructors = Instructor::where('organization_id', $student['organization_id'] ?? 0)->pluck('user_id')->toArray();

        if (isset($sortedBy) && isset($sortedOrder)) {
            $events = Event::where('user_id', auth()->id())->whereDate('date', '>=', date('Y-m-d'))->orderBy($sortedBy, $sortedOrder);
        } else {
            $events = Event::where('user_id', auth()->id())->whereDate('date', '>=', date('Y-m-d'))->orderBy('id', 'desc');
        }
        $class = 'active';
        $eventCount = Event::where('user_id', auth()->id())->count();
//        dd($instructors,$events);
        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $events = $events->paginate($request->par_page);
            }
            return view('instructors.video_call.table', compact('events', 'sortedBy', 'sortedOrder', 'class', 'eventCount'))->with('per_page',$per_page);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $eventDatas = $events->latest()->get();
                $rows = ([
                    'id' => 'Event ID',
                    'class' => 'Class',
                    'date_time' => 'Date time',
                    'duration' => 'Duration',
                ]);
                $keys = array_keys($rows);
                $txtop = '';
                foreach ($eventDatas as $eventData) {
                    $cuRecord = [
                        'id' => $eventData->id,
                        'class' => $eventData->class,
                        'date_time' => $eventData->date,
                        'duration' => $eventData->duration,

                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Events') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
//                    dd($output);
                    $fileName = exportFilePrefix('Events') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $events=$events->paginate($per_page);
            return view('organization.table')
                ->with('instructors.video_call.table', $events)->render();
        }
        $events=$events->paginate($per_page);
        return view('instructors.video_call.index', compact('events', 'sortedBy', 'sortedOrder', 'class', 'eventCount'));
    }

    //view past livestream
    public function ViewPast(Request $request)
    {
        $data = $request->all();
        $per_page = $request->par_page ?? 10;
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'updated_at';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $instructorName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $student = Student::where('user_id', auth()->id())->first();
        $instructors = Instructor::where('organization_id', $student['organization_id'] ?? 0)->pluck('user_id')->toArray();

        if (isset($sortedBy) && isset($sortedOrder)) {
            $events = Event::where('user_id', auth()->id())->whereDate('date', '<', date('Y-m-d'))->orderBy($sortedBy, $sortedOrder)/*->paginate(10)*/;
        } else {
            $events = Event::where('user_id', auth()->id())->whereDate('date', '<', date('Y-m-d'))->orderBy('id', 'desc')/*->paginate(10)*/;
        }
        $eventCount = Event::where('user_id', auth()->id())->count();
        $past = 'active';
        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $events = $events->paginate($request->par_page);
            }
            return view('instructors.video_call.table', compact('events', 'sortedBy', 'sortedOrder', 'past', 'eventCount'))->with('per_page',$per_page);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $eventDatas = $events->latest()->get();
                $rows = ([
                    'id' => 'Event ID',
                    'class' => 'Class',
                    'date_time' => 'Date time',
                    'duration' => 'Duration',
                ]);
                $keys = array_keys($rows);
                $txtop = '';
                foreach ($eventDatas as $eventData) {
                    $cuRecord = [
                        'id' => $eventData->id,
                        'class' => $eventData->class,
                        'date_time' => $eventData->date,
                        'duration' => $eventData->duration,

                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Events') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
//                    dd($output);
                    $fileName = exportFilePrefix('Events') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $events=$events->paginate($per_page);
            return view('organization.table')
                ->with('instructors.video_call.table', $events)->render();
        }
        $events=$events->paginate($per_page);
//        dd($instructors,$events);
        return view('instructors.video_call.index', compact('events', 'sortedBy', 'sortedOrder', 'past', 'eventCount'));
    }

    //create new livestream
    public function saveAjax(Request $request)
    {
//        dd($request->all());
        $data = $request->all();
//        dd($data);
//        $data['instructorId']=auth()->id();
        $data['date']= Carbon::parse($data['date'])->format('Y-m-d');

        $request->validate([
            'date' => 'after:today'
        ]);
        $user = User::where('id', auth()->id())->first();

        $event = Event::create($data);

        $organizationId = Instructor::where('user_id', auth()->id())->pluck('organization_id')->first();
        $emails = User::Join('students', function ($j) use ($organizationId) {
            $j->on('students.user_id', 'users.id')->where('students.organization_id', $organizationId);
        })->pluck('email')->toarray();
        if (count($emails) > 0) {
            $details = [
                'subject' => 'Invitation for live class',
                'class' => $event->class,
                'date' => $event->date,
                'user_name'=>$user->name,
                'time' => $event->time,
                'datetime' => $event->StartDateTime,
                'duration' => $event->duration,
                'instructor' => ($user['first_name'] ?? '') . " " . ($data['last_name'] ?? ''),
                'email' => $emails,
                'type' => 'Class'
            ];

            dispatch(new \App\Jobs\SendEmailJob($details));
        }

        $response = [];
        $response['success'] = true;
        $response['message'] = 'Great! The event was created successfully!';
        return response()->json($response);
    }

    //start livestream
    public function startCall(Request $request)
    {
        $data = $request->all();
        $event = Event::find($data['id']);
        date_default_timezone_set('Asia/Kolkata');
        $currentTime = time();
//        echo date('H:i',$currentTime);
//        if (((int) date('H:i', $currentTime)) >= $event['start_at']) {
//          echo 'sdcsf';
//        }
//        dd(date('H:i')>$event['start_at']);
        if (date('Y-m-d') == $event['date']) {
            if (((int)date('H:i', $currentTime)) >= $event['start_at']) {
                $bytes = random_bytes(10);
//                   $joinUrl= url('startStreaming/'.bin2hex($bytes));
                $joinUrl = bin2hex($bytes);
                $result = Event::where('id', $data['id'])->update(['join_link' => $joinUrl]);
                $response = [];
                $response['success'] = true;
                $response['message'] = $joinUrl;
                $response['class'] = $event['class'];
                return response()->json($response);
            } else {
//                    dd("Sdas");
                $response = [];
                $response['success'] = false;
                $response['message'] = 'time error';
                $response['class'] = $event['class'];
                $response['id'] = $data['id'];
                return response()->json($response);
            }
        } else {
            $response = [];
            $response['success'] = false;
            $response['message'] = 'Sorry that date was invalid. Please reenter a correct date';
            $response['class'] = $event['class'];
            $response['id'] = $data['id'];
            return response()->json($response);
        }
    }

    //start urgent livestream
    public function startUrgentCall($id, $class)
    {
        $event = Event::find($id);
        $bytes = random_bytes(10);
//                   $joinUrl= url('startStreaming/'.bin2hex($bytes));
        $joinUrl = bin2hex($bytes);
        $result = Event::where('id', $id)->update(['join_link' => $joinUrl]);
        $response = [];
        $response['success'] = true;
        $response['message'] = $joinUrl;
        return \redirect(url('instructor/startStreaming/' . $class . '/' . $joinUrl));
    }

    //start livestream
    public function startStreaming($class, $token)
    {
        $instructor = User::where('id', auth()->id())->pluck('first_name')->first();
        $instructorImage = User::where('id', auth()->id())->pluck('photo')->first();
        $event = Event::where('class', $class)->first();
        $event->is_live = 1;
        $event->save();
        $id = $event->id;
//        dd($instructorImage);
        return view('instructors.video_call.start-call', compact('class', 'id', 'instructor', 'instructorImage'));
    }

    //create peer record
    public function savePeer(Request $request)
    {
        $response = Event::where('id', $request->id)->first();

        if ($request->leave) {
            $response->is_live = 0;
            $response->end_at = date('Y-m-d h:i:s');


        } else {
            $response->peer_id = $request->peer_id;
            $response->uid = $request->uid;
        }
        $response->save();

        return response()->json($response);
    }
}
