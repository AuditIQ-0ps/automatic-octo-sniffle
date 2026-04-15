<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\StreamUser;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class StudentJoinCall extends Controller
{
    //view all livestream
    public function viewEvent(Request $request)
    {
        $data = $request->all();
        $per_page = $request->par_page ?? 10;
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'updated_at';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $instructorName = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $student = Student::where('user_id', auth()->id())->first();
        $instructors = Instructor::where('organization_id', $student['organization_id'])->pluck('user_id')->toArray();

        if (isset($sortedBy) && isset($sortedOrder)) {
            $events = Event::whereIn('user_id', $instructors)->orderBy($sortedBy, $sortedOrder)/*->paginate(10)*/;
        } else {
            $events = Event::whereIn('user_id', $instructors)->orderBy('id', 'desc')/*->paginate(10)*/;
        }
        $total = 'active';
        $eventCount = Event::whereIn('user_id', $instructors)->count();

        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $events = $events->paginate($request->par_page);
            }
            return view('students.video_call.table', compact('events', 'sortedBy', 'sortedOrder', 'total', 'eventCount'))->with('per_page',$per_page);
        }
        $events=$events->paginate($per_page);
        return view('students.video_call.index', compact('events', 'sortedBy', 'sortedOrder', 'total', 'eventCount'));
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
        $instructors = Instructor::where('organization_id', $student['organization_id'])->pluck('user_id')->toArray();

        if (isset($sortedBy) && isset($sortedOrder)) {
            $events = Event::whereIn('user_id', $instructors)->whereDate('date', '>=', date('Y-m-d'))->orderBy($sortedBy, $sortedOrder)/*->paginate(10)*/;
        } else {
            $events = Event::whereIn('user_id', $instructors)->whereDate('date', '>=', date('Y-m-d'))->orderBy('id', 'desc')/*->paginate(10)*/;
        }
        $class = 'active';
        $eventCount = Event::whereIn('user_id', $instructors)->count();
//        dd($instructors,$events);
        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $events = $events->paginate($request->par_page);
            }
            return view('students.video_call.table', compact('events', 'sortedBy', 'sortedOrder', 'class', 'eventCount'))->with('per_page',$per_page);
        }
        $events=$events->paginate($per_page);
        return view('students.video_call.index', compact('events', 'sortedBy', 'sortedOrder', 'class', 'eventCount'));
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
        $instructors = Instructor::where('organization_id', $student['organization_id'])->pluck('user_id')->toArray();

        if (isset($sortedBy) && isset($sortedOrder)) {
            $events = Event::whereIn('user_id', $instructors)->whereDate('date', '<', date('Y-m-d'))->orderBy($sortedBy, $sortedOrder)/*->paginate(10)*/;
        } else {
            $events = Event::whereIn('user_id', $instructors)->whereDate('date', '<', date('Y-m-d'))->orderBy('id', 'desc')/*->paginate(10)*/;
        }
        $past = 'active';
        $eventCount = Event::whereIn('user_id', $instructors)->count();

//        dd($instructors,$events);
        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $events = $events->paginate($request->par_page);
            }
            return view('students.video_call.table', compact('events', 'sortedBy', 'sortedOrder', 'past', 'eventCount'))->with('per_page',$per_page);
        }
        $events=$events->paginate($per_page);
        return view('students.video_call.index', compact('events', 'sortedBy', 'sortedOrder', 'past', 'eventCount'));
    }

    //join livestream
    public function joinCall(Request $request)
    {
        $data = $request->all();
        $event = Event::where('id', $data['id'])->first();
        if ($event['join_link'] != null) {
            $response = [];
            $response['success'] = true;
            $response['message'] = $event['join_link'];
            $response['class'] = $event['class'];
//            dd($response);

        } else {
            $response['success'] = false;
            $response['message'] = "metting not found";
        }
        return response()->json($response);
    }

    public function joinClass($class, $token)
    {
        $event = Event::where('class', $class)->first();
        if(!$event->is_live){
            return redirect()->back()->withErrors(["Sorry, this Livestream hasn't started yet"]);
        }
        $id = $event->id;
        return view('students.video_call.join-call', compact('class', 'id', 'event'));
    }

    //create peer record
    public function savePeer(Request $request)
    {
//        dd($request->all());
        $response = StreamUser::create(['peer_id' => $request->peer_id, 'uid' => $request->uid, 'event_id' => $request->id, 'user_id' => auth()->id()]);
        return response()->json($response);
    }
}
