<?php

namespace App\Http\Controllers;

use App\Exports\CSVExport;
use App\Models\Organization;
use App\Models\PaymentStyle;
use App\Models\QuizCategory;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Response;

class SubjectController extends Controller
{
    //view subject records to admin
    public function index( Request $request )
    {
        $per_page=$request->par_page??10;
        $data = $request->all();
        $page = ! empty( $data['page'] ) ? $data['page'] : 0;
        $sortedBy = ! empty( $request->get( 'sorted_by' ) ) ? $request->get( 'sorted_by' ) : 'id';
        $sortedOrder = ! empty( $request->get( 'sorted_order' ) ) ? $request->get( 'sorted_order' ) : 'DESC';
        $standard = ! empty( $data['name'] ) ? $data['name'] : "";
        $userId = ! empty( $data['user_id'] ) ? $data['user_id'] : "";

        $subjects=Subject::orderby($sortedBy, $sortedOrder);
        if (isset($data['name'])){
            $subjects=Subject::where('name','like','%'.$data['name'].'%')->orderby('id', 'desc');
        }
        if ($request->from) {
            $subjects = $subjects->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $subjects = $subjects->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }

        switch($request->input('status')){
            case 'Month':
                $subjects = $subjects->whereMonth('created_at',\Carbon\Carbon::now()->month);
                break;
            case 'Week':
                $subjects = $subjects->whereBetween('created_at',[\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'Today':
                $subjects = $subjects->whereDate('created_at',\Carbon\Carbon::now()->toDate());
                break;
        }



        if($request->status=='Month'){
            $subjects = $subjects->whereMonth('created_at',\Carbon\Carbon::now()->month);
        }
        if($request->status=='Week'){
            $subjects = $subjects->whereBetween('created_at',[\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        }
        if($request->status=='Today'){
            $subjects = $subjects->whereDate('created_at',\Carbon\Carbon::now()->toDate());
        }
        $subjectsClone = new Subject();
        $subjects1 = clone  $subjects;
        $subjectCount=$subjects1->count();
        $subjects_all=clone $subjectsClone;
        $record['All'] = $subjects_all->count();
        $subjects_month=clone $subjectsClone;
        $record['Month']=$subjects_month->whereMonth('created_at',\Carbon\Carbon::now()->month)->count();
        $subjects_week=clone $subjectsClone;
        $record['Week']=$subjects_week->whereBetween('created_at',[\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $subjects_today=clone $subjectsClone;
        $record['Today']=$subjects_today->whereDate('created_at',\Carbon\Carbon::now()->toDate())->count();
        if($request->isXmlHttpRequest()){
            if(!empty($request->par_page)) {
                $subjects = $subjects->paginate($request->par_page);
            }
            return view( 'subject.table', compact( 'subjectCount','subjects',
                'userId','standard', 'sortedBy', 'sortedOrder' ))->with('per_page',$per_page)->with($record);
        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $subjectsDatas = $subjects->get();
                $rows=([
                    'id' => 'subject ID',
                    'name' => 'Instructor Name',
                    'created_at' => 'Create on',
                ]);
                $recodes = [];
                $txtop = '';
                foreach ($subjectsDatas as $subjectsData) {
                    $cuRecord =  [
                        'id' => $subjectsData->id,
                        'name' => $subjectsData->name,
                        'created_at' => $subjectsData->created_at,
                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Subject') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
                    $fileName = exportFilePrefix('Subject') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }

            $subjects = $subjects->latest()->paginate($per_page);
            return view('subject.table')
                ->with('subjects', $subjects)->render();
        }

        $subjects=$subjects->paginate($per_page);
        return view( 'subject.index', compact( 'subjectCount','subjects',
            'userId','standard', 'sortedBy', 'sortedOrder' ))->with($record);
    }

    //create subject record
    public function saveAjax( Request $request )
    {
        $data = $request->all();
        $request->validate([
            "name"=> 'required|string|unique:subjects,name'.($request->subject_id ? ",".$request->subject_id : "")
        ]);
        if($data['id']!=0){
            $organization=Subject::where('id',$data['id'])->update([
                'name'=>$data['name']
            ]);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Subject updated successfully!';
            return response()->json($response);
        }
        else {
            $results = Subject::create($data);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Subject saved successfully!';
            return response()->json($response);
        }
    }

    //update new subject
    public function getDataForEditModel( Request $request )
    {
        $data = $request->all();
        $results=Subject::find($data['id']);
        $response = [];
        $response['success'] = false;
        $response['message'] = '';

        if ( ! empty( $results['id'] ) && $results['id'] > 0 ) {
            $response['success'] = true;
            $response['message'] = '';
            $response['data'] = $results;
        }
        return response()->json( $response );
    }

    //delete selected subject record
    public function delete( Request $request )
    {
        $data = $request->all();
        if ( empty( $data['id'] ) ) {
            return abort( 404 );
        }
        $isDelete = Subject::where('id',$data['id'])->delete();
        if ( $isDelete ) {
            $request->session()
                ->flash( 'success', 'Subject deleted successfully.' );
        } else {
            $request->session()
                ->flash( 'error', 'Oops, the subject was not deleted. Please try again.' );
        }
        return Redirect::back();
    }

}
