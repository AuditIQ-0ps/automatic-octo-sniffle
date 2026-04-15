<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\ReportMail;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizResult;
use App\Models\StreamUser;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    //view activity records of all organizations
    public function index()
    {

        $organizationCount = Organization::whereHas('user')->count();
        $organizations = Organization::pluck('name', "id")->toarray();
        $instructorCount = Instructor::whereHas('user')->whereHas('organization')->count();
        return view('admin.activity.index', compact('organizationCount', 'instructorCount', 'organizations'));
    }

    //filter activity records
    public function getMonthRecord(Request $request)
    {
        $data = $request->all();
//        dd($data);
        $start = Carbon::parse($data['date_start'])->format('Y-m-d');
        $end = Carbon::parse($data['date_end'])->format('Y-m-d');
        if ($request->organizations) {
            $data['org'] = $org = Organization::with('user')->where('id', $request->organizations)->first();
        }
//        return [$start,$end];
        $response['organizationRecord'] = Organization::whereHas('user')->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        if ($request->organizations) {
            $response['organizationRecord'] = $response['organizationRecord']->where('id', $request->organizations);
        }
        $response['organizationRecord'] = $response['organizationRecord']->get();

        $response['instructorRecord'] = Instructor::whereHas('user')->whereHas('organization', function ($q) use ($request) {
            if ($request->organizations) {
                $q->where('id', $request->organizations);
            }

        })->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();

//        $response['instructorUsers'] = $response['instructorRecord']->pluck('user_id')->toArray();

        $response['eventRecord'] = Event::with('user', 'streamUser')->whereHas('user.instructor', function ($q) use ($data) {
            if (isset($data['org']))
                $q->where('organization_id', $data['org']->id ?? 0);
        })->where(function ($query) use ($start, $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        });
        if (isset($org)) {
            $response['eventRecord'] = $response['eventRecord']->wherehas('user.instructor', function ($q) use ($org) {
                $q->where('organization_id', $org->id ?? 0);
            });
        }

        $response['eventRecord'] = $response['eventRecord']->get();

        $response['instructorGroup'] = Quiz::with('user', 'results', 'questions')->whereHas('user.instructor', function ($q) use ($data) {
            if (isset($data['org']))
                $q->where('organization_id', $data['org']->id ?? 0);
        })->where(function ($query) use ($start, $end) {
//            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        });

        $response['instructorGroup'] = $response['instructorGroup']->get();


        $response['org'] = $org ?? [];
//        return ($response);
        $viewRender = view('admin.activity.table')->with($response)->render();
        return response()->json(array('success' => true, 'html' => $viewRender));
    }

    //send activity record selected organization
    public function getMonthRecordSend(Request $request)
    {
        $data = $request->all();
//        dd($data);
        $start = Carbon::parse($data['date_start'])->format('Y-m-d');
        $end = Carbon::parse($data['date_end'])->format('Y-m-d');
//        if ($request->organizations) {
        $data['org'] = $org = Organization::with('user')->where('id', $request->organizations)->first();
//        dd($data['org']->user->email);
//        }
        if (!$org) {
            return redirect()->back()->withErrors(["Oops, something went wrong. Please select organization and try again!"]);
        }
//        return [$start,$end];
        $response['organizationRecord'] = Organization::whereHas('user')->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        if ($request->organizations) {
            $response['organizationRecord'] = $response['organizationRecord']->where('id', $request->organizations);
        }
        $response['organizationRecord'] = $response['organizationRecord']->get();

        $response['instructorRecord'] = Instructor::whereHas('user')->whereHas('organization', function ($q) use ($request) {
            if ($request->organizations) {
                $q->where('id', $request->organizations);
            }

        })->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end)->get();

//        $response['instructorUsers'] = $response['instructorRecord']->pluck('user_id')->toArray();

        $response['eventRecord'] = Event::with('user', 'streamUser')->whereHas('user.instructor', function ($q) use ($data) {
            if (isset($data['org']))
                $q->where('organization_id', $data['org']->id ?? 0);
        })->where(function ($query) use ($start, $end) {
            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        });
        if (isset($org)) {
            $response['eventRecord'] = $response['eventRecord']->wherehas('user.instructor', function ($q) use ($org) {
                $q->where('organization_id', $org->id ?? 0);
            });
        }

        $response['eventRecord'] = $response['eventRecord']->get();

        $response['instructorGroup'] = Quiz::with('user', 'results', 'questions')->whereHas('user.instructor', function ($q) use ($data) {
            if (isset($data['org']))
                $q->where('organization_id', $data['org']->id ?? 0);
        })->where(function ($query) use ($start, $end) {
//            $query->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
        });

        $response['instructorGroup'] = $response['instructorGroup']->get();


        $response['org'] = $org ?? [];
        $pdf = PDF::loadView('pdf.activity', $response);
//
        $name = date('dmYghi') . 'activity.pdf';
        $pdf->save(public_path('reports/' . $name));

        $data = array(
            'email' => $org->user->email,
            'start_date' => $data['date_start'],
            'date_end' => $data['date_end'],
            'type' => 'report',
            'file' => $name,
            'org' => $org->toArray(),
            'path'=>url('public/reports/'.$name)
        );
        Log::info($data['path']);
//        dd($data['path']);

                Mail::to($data['email'])->send(new ReportMail($data));
//        dispatch(new \App\Jobs\SendEmailJob($data));

        return redirect()->back()->withInput($request->all())->withSuccess("Report's sent successfully.");
    }
}
