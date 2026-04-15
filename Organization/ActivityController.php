<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\Question;
use App\Models\QuizResult;
use App\Models\StreamUser;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{

    //view activity records
    public function index()
    {
        $organization = Organization::where('user_id', auth()->id())->first();
        $instructor = Instructor::with('organization')->where('organization_id', $organization['id'])->get();
        $instructorCount = count($instructor);

        $student = Student::with('organization')->where('organization_id', $organization['id'])->get();
        $studentCount = count($student);

        $instructorUsers = $instructor->pluck('user_id')->toArray();
        $events = Event::whereIn('user_id', $instructorUsers)->get();
        $eventCount = count($events);

        $eventIds = $events->pluck('id')->toArray();
        $streamUsers = StreamUser::whereIn('event_id', $eventIds)->get();
        $streamUserCount = count($streamUsers);
        if (\auth()->user()->role_id == 1) {
            $layoutType = 'admin';

        }
        if (\auth()->user()->role_id == 2) {
            $layoutType = 'organization';
        }
        return view('organization.activity.index', compact('instructor', 'instructorCount', 'student', 'studentCount',
            'events', 'eventCount', 'streamUserCount','layoutType'));
    }

    //filter activity records
    public function getMonthRecord(Request $request)
    {

        $data = $request->all();
        $start = Carbon::parse($data['date_start'])->format('Y-m-d');
        $end = Carbon::parse($data['date_end'])->format('Y-m-d');

        $organizationRecord = Organization::where('user_id', auth()->id())->first();
        $response['instructorRecord'] = Instructor::with('user')->where('organization_id', $organizationRecord['id'])->where(function ($query) use ($start, $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        })->get();

        $response['StudentRecord'] = Student::with('user')->where('organization_id', $organizationRecord['id'])->where(function ($query) use ($start, $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        })->get();

        $response['instructorUsers'] = $response['instructorRecord']->pluck('user_id')->toArray();
//        dd($response['instructorUsers']);
        $response['eventRecord'] = Event::with('user', 'streamUser')->whereIn('user_id', $response['instructorUsers'])->where(function ($query) use ($start, $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        })->get();

        $response['instructorGroup'] = Question::with('user')->whereIn('user_id', $response['instructorUsers'])->groupBy('user_id')->select('user_id')->where(function ($query) use ($start, $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        })->get();

        $arr = [];
        foreach ($response['instructorGroup'] as $key => $value) {
            $arr[$key] = $value->user_id;
        }
        $response['result'] = QuizResult::whereIn('user_instructor_id', $arr)->groupBy('user_id')->select('user_id', DB::raw('count(*) as total'))->paginate(10);

        $viewRender = view('organization.activity.table')->with($response)->render();
        return response()->json(array('success' => true, 'html' => $viewRender));
//        return view("organization.activity.table")->with($response)->render();
    }
}
