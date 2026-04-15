<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Mail\InviteInstructorMail;
use App\Models\Instructor;
use App\Models\Organization;
use App\Models\PaymentStyle;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionType;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Input\Input;

class QuizQuestionController extends Controller
{
    //view add quiz question page
    public function index(Request $request, $id)
    {
        $quiz = Quiz::where('user_id', $request->user()->id);
        $quiz = $quiz->where('id', '=', $id);
        $quiz = $quiz->first();
        if (empty($quiz)) {
            $request->session()
                ->flash('error', 'Sorry, that quiz is invalid');
            return redirect('instructor/quiz');
        }
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'id';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $questionName = !empty($data['question']) ? $data['question'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $quizCategoryId = QuizCategory::pluck('standard', 'id')->toArray();
        $subjectId = Subject::pluck('name', 'id')->toArray();
        $questionTypeId = QuestionType::pluck('type', 'id')->toArray();
        $questions = Question::with('questionOptions')->where('quiz_id', $id)->orderby($sortedBy, $sortedOrder);
        if (isset($data['question'])) {
            $questions = $questions->where([['question', 'like', '%' . $data['question'] . '%'], ['user_id', auth()->id()]]);
        }
        $questions1 = clone $questions;
        $questionCount = $questions1->count();
        $questions = $questions->paginate(10);
        return view('instructors.quiz_question.index', compact('questionCount', 'quiz', 'questions', 'quizCategoryId', 'subjectId', 'questionTypeId', 'userId', 'questionName', 'sortedBy', 'sortedOrder', 'id'));
    }

    //update quiz questions
    public function saveAjax(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'options' => 'required',
            'options.*' => 'required'
        ]);
        $result = $validator->fails();
        if ($result) {
            $response['success'] = false;
            $response['message'] = 'All the values for this options are required.';
            return response()->json($response);
        }
//        dd($data,$result);
//        dd($result);
        if ($data['id'] != 0) {
            $question = Question::where('id', $data['id'])->update([
                'question' => $data['question'],
                'score' => $data['score'],
                'quiz_category_id' => $data['quiz_category_id'],
                'subject_id' => $data['subject_id'],
                'question_type_id' => $data['question_type_id'],
            ]);

            QuestionOption::where('question_id', $data['id'])->delete();

            foreach ($data['options'] as $key => $option) {
                QuestionOption::create([
                    'option' => $option,
                    'is_correct' => $data['checks'][$key],
                    'question_id' => $data['id']
                ]);
            }
            $response = [];
            $response['success'] = true;
            $response['message'] = 'Great! The question was updated successfully!';
            return response()->json($response);
        } else {
            $data['user_id'] = auth()->id();
            if (count($data['options']) < 2) {
                $response = [];
                $response['success'] = false;
                $response['message'] = 'Please provide at least 2 options';
                return response()->json($response);
            }
            $question = Question::create($data);
//            dd(count($data['options']));
            foreach ($data['options'] as $key => $option) {
                QuestionOption::create([
                    'option' => $option,
                    'is_correct' => $data['checks'][$key],
                    'question_id' => $question->id
                ]);
            }
            $response = [];
            $response['success'] = true;
            $response['message'] = 'Great! The question was created successfully!';
            return response()->json($response);
        }
    }

    //edit quiz question
    public function getDataForEditModel(Request $request)
    {
        $data = $request->all();
//        dd($data);
        $results = Question::with('quizCategory', 'subject', 'questionType', 'questionOptions')->find($data['id']);
//        dd($results);
        $response = [];
        $response['success'] = false;
        $response['message'] = '';

        if (!empty($results['id']) && $results['id'] > 0) {
            $response['success'] = true;
            $response['message'] = '';
            $response['data'] = $results;
//            dd($response['data']->paymentStyle['style']);
        }
        return response()->json($response);
    }

    //delete quiz question
    public function delete(Request $request, $id)
    {
        $data = $request->all();
        if (empty($quiz)) {
            $request->session()
                ->flash('error', 'Sorry, that quiz is invalid');
            return redirect('instructor/quiz');
        }
        if (empty($data['id'])) {
            $request->session()
                ->flash('error', 'Sorry, that question is invalid');
            return redirect('instructor/quiz');
        }
        $isDelete = Question::where('id', $data['id'])->delete();

        if ($isDelete) {
            $request->session()
                ->flash('success', 'Question deleted successfully.');
        } else {
            $request->session()
                ->flash('error', 'Oops, the question was not deleted. Please try again.');
        }
        return Redirect::back();
    }
}
