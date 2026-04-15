<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionType;
use App\Models\Quiz;
use App\Models\QuizCategory;
use App\Models\QuizResult;
use App\Models\Student;
use App\Models\StudentQuiz;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ViewQuizController extends Controller
{
    //display all quiz's record
    public function index(Request $request)
    {
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'updated_at';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';


        $student = Student::where('user_id', auth()->id())->first();

        if (isset($sortedBy) && isset($sortedOrder)) {
            $instructors = Instructor::where('organization_id', $student['organization_id'])->orderby($sortedBy, $sortedOrder)->pluck('user_id')->toArray();
        } else {

            $instructors = Instructor::where('organization_id', $student['organization_id'])->pluck('user_id')->toArray();
        }

        $instructorGroup = Quiz::with('user', 'questions')->whereHas('user')->whereHas('questions')->whereIn('user_id', $instructors)->paginate(10);
//       $instructorGroup = $instructorQuestion->groupBy('first_name')->first();
//dd($instructorQuestion);
//        $questionCount=Question::count();

        return view('students.view_quiz.index', compact('instructorGroup', 'sortedBy', 'sortedOrder'));
    }

    //user start quiz
    public function takeQuiz($id)
    {
        $name=Quiz::where('id',$id)->first();
//        $quiz_name=$name->pluck('quiz_name');
//        dd($name);
        $question = Question::with('questionOptions')->where('quiz_id', $id)->paginate(10);
//           dd($question);
        if (!$question) {
            return redirect()->back()->withErrors(["error" => "Sorry, we could not find that question. Please try again"]);
        }
        Session::put('quiz_id', $id);
        return view('students.view_quiz.start_quiz', compact('question', 'id','name'));

    }

    //save quiz records
    public function saveQuiz(Request $request)
    {
        $data = $request->all();
//        dd($data);
//        if (!isset($data['option']) || (isset($data['option']) && count($data['option'])<count($data['question_id'])))
//        {
//            $response['success'] = false;
//            $response['message'] = 'Please provide the answers to all questions.';
//            return response()->json($response);
////            return response('')->withErrors(['error' => "Please provide the answers to all questions."]);
//        }

        if (!isset($data['question_id'])) {
            return redirect()->back()->withErrors(['error' => "Question not found. please try again"]);
        }
        $question = Question::with('questionOptions')->whereIn('id', $data['question_id'])->get();

        if (count($data['question_id']) != count($data['option'])) {
            return redirect()->back()->withInput($data)->withErrors(['error' => "Please provide the answers to all questions."]);
        }
        $questionCount = count($data['question_id']);

        $total = 0;
        $achieve = 0;
        $correct = 0;
//        dd($achieve);
        foreach ($data['score'] as $score) {
            $total += $score;
        }
        $question = Question::with('questionOptions')->whereIn('id', $data['question_id'])->get();
        $quizResult = QuizResult::create([
            'user_id' => auth()->id(),
            'user_instructor_id' => $data['question_user_id'],
            'total_score' => $total,
            'date' => Carbon::now()
        ]);
        $quiz = Quiz::with('user', 'questions')->whereHas('user')->whereHas('questions')->where('id', $data['question_user_id'])->first();
        foreach ($question as $key => $questionValue) {
            $key++;
//            foreach ($questionValue->questionOptions as $questionOption)
//            {
            $correctAnswerId = QuestionOption::where(['question_id' => $questionValue['id'], 'is_correct' => 1])->first();
            $option = QuestionOption::where('id', $data['option'][$key])->first();
//                dd($questionValue['score']);
//                dd($option,$option['is_correct'],$questionValue['score']);
            if ($option['is_correct'] == 1) {
                $correct = 1;
                $achieve += $questionValue['score'];
//                    dd($achieve);
            } else {
                $correct = 0;
            }

            StudentQuiz::create([
                'quiz_result_id' => $quizResult->id,
                'user_id' => auth()->id(),
                'question_id' => $questionValue['id'],
                'question_option_id' => $data['option'][$key],
                'correct' => $correct,
                'correct_answer_id' => $correctAnswerId->id
            ]);
//            }
        }

        QuizResult::where('id', $quizResult->id)->update(['achieve_score' => $achieve]);
        Session::forget('quiz_id');
//        dd($data,$total,$achieve);
        return view('students.view_quiz.view_result', compact('total', 'achieve', 'questionCount','quiz'));
    }

}
