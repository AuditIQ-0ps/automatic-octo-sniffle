<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class QuizController extends Controller
{

    //display all quiz's record
    public function index(Request $request)
    {
        $data = $request->all();
        $data['user'] = $request->user();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'id';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $standard = !empty($data['name']) ? $data['name'] : "";
        $userId = !empty($data['quiz_name']) ? $data['user_id'] : "";

        $subjects = Quiz::orderby($sortedBy, $sortedOrder)->where('user_id', $data['user']->id);
        if (isset($data['name'])) {
            $subjects = Quiz::where('quiz_name', 'like', '%' . $data['name'] . '%')->orderby('id', 'desc');
        }
        if ($request->from) {
            $subjects = $subjects->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $subjects = $subjects->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }
        $subjects1 = clone $subjects;
        $subjects = $subjects->paginate(10);
        $subjectCount = $subjects1->count();

        return view('quiz.index', compact('subjectCount', 'subjects', 'userId', 'standard', 'sortedBy', 'sortedOrder'));
    }

    //create new quiz
    public function saveAjax(Request $request)
    {
//        dd(2);
        $data = $request->all();
        $organization1 = Quiz::where('quiz_name', $request->quiz_name)->where('user_id', $request->user()->id);
        if ($data['id'] != 0) {
            $organization1 = $organization1->where('id', '!=', $data['id']);
            $organization1 = $organization1->count();
            if ($organization1 > 0) {
                $response['success'] = false;
                $response['message'] = 'The quiz name has already been taken.';
                return response()->json($response);
            }
            $organization = Quiz::where('id', $data['id'])->update([
                'quiz_name' => $data['quiz_name']
            ]);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Quiz updated successfully!';
            return response()->json($response);
        } else {
            $organization1 = $organization1->count();
            if ($organization1 > 0) {
                $response['success'] = false;
                $response['message'] = 'The quiz name has already been taken.';
                return response()->json($response);
            }
            $data['user_id'] = $request->user()->id;
            $results = Quiz::create($data);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Quiz saved successfully!';
            return response()->json($response);
        }
    }

    //edit quiz name
    public function getDataForEditModel(Request $request)
    {
        $data = $request->all();
        $results = Quiz::whereId($data['id'])->where('user_id', $request->user()->id)->first();
//        dd($results);
        $response = [];
        $response['success'] = false;
        $response['message'] = '';

        if (!empty($results['id']) && $results['id'] > 0) {
            $response['success'] = true;
            $response['message'] = '';
            $response['data'] = $results;
        }
        return response()->json($response);
    }

    //delete quiz record
    public function delete(Request $request)
    {
        $data = $request->all();
        if (empty($data['id'])) {
            return abort(404);
        }
        $isDelete = Quiz::whereId($data['id'])->where('user_id', $request->user()->id)->delete();
        if ($isDelete) {
            $request->session()
                ->flash('success', 'Quiz deleted successfully.');
        } else {
            $request->session()
                ->flash('error', 'Oops, the quiz was not deleted. Please try again.');
        }
        return redirect('instructor/quiz');
    }
}
