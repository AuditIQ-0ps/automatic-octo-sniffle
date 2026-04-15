<?php

namespace App\Http\Controllers;

use App\Exports\CSVExport;
use App\Models\QuizCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Maatwebsite\Excel\Facades\Excel;
use Response;

class QuizCategoryController extends Controller
{
    //view quiz category
    public function index(Request $request)
    {
        $per_page = $request->par_page ?? 10;
        $data = $request->all();
        $page = !empty($data['page']) ? $data['page'] : 0;
        $sortedBy = !empty($request->get('sorted_by')) ? $request->get('sorted_by') : 'id';
        $sortedOrder = !empty($request->get('sorted_order')) ? $request->get('sorted_order') : 'DESC';
        $standard = !empty($data['standard']) ? $data['standard'] : "";
        $userId = !empty($data['user_id']) ? $data['user_id'] : "";

        $quizCategories = QuizCategory::orderby($sortedBy, $sortedOrder);
        if (isset($data['standard'])) {
            $quizCategories = $quizCategories->where('standard', 'like', '%' . $data['standard'] . '%');
        }
        if ($request->from) {
            $quizCategories = $quizCategories->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $quizCategories = $quizCategories->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }
        if ($request->from) {
            $quizCategories = $quizCategories->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $quizCategories = $quizCategories->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }
        if ($request->status == 'Month') {
            $quizCategories = $quizCategories->whereMonth('created_at', \Carbon\Carbon::now()->month);
        }
        if ($request->status == 'Week') {
            $quizCategories = $quizCategories->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        }
        if ($request->status == 'Today') {
            $quizCategories = $quizCategories->whereDate('created_at', \Carbon\Carbon::now()->day);
        }

        $quizCategoriesClone = new QuizCategory();
        $quizCategories1 = clone $quizCategories;
        $quizCategoryCount = $quizCategories1->count();
        $quizCategories_all = clone $quizCategoriesClone;
        $record['All'] = $quizCategories_all->count();
        $quizCategories_month = clone $quizCategoriesClone;
        $record['Month'] = $quizCategories_month->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
        $quizCategories_week = clone $quizCategoriesClone;
        $record['Week'] = $quizCategories_week->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $quizCategories_today = clone $quizCategoriesClone;
        $record['Today'] = $quizCategories_today->whereDate('created_at', \Carbon\Carbon::now()->day)->count();

        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $quizCategories = $quizCategories->paginate($request->par_page);
            }
            return view('quiz_category.table', compact('quizCategoryCount', 'quizCategories', 'userId', 'standard', 'sortedBy', 'sortedOrder'))
                ->with('per_page', $per_page)->with($record);

        }
        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $quizCategoriesDatas = $quizCategories->get();
                $rows = ([
                    'id' => 'QuizCategories ID',
                    'standard' => 'QuizCategories standard',
                    'created_at' => 'Created On',
                ]);
                $keys = array_keys($rows);
                $recodes = [];
                $txtop = '';
                foreach ($quizCategoriesDatas as $quizCategoriesData) {
                    $recodes[] = [
                        'id' => $quizCategoriesData->id,
                        'standard' => $quizCategoriesData->standard,
                        'created_at' => $quizCategoriesData->created_at,
                    ];
                    $row = [];
                    foreach ($rows as $k => $value) {
                        if ($k == 'id') {
                            $row[$value] = count($recodes) + 1;
                        } else {
                            $row[$value] = $quizCategoriesData[$k];
//                            dd($row[$value]);
                        }
                    }
//                    $recodes[] = array_values($row);
                    $txtop .= implode(" , ", array_values($row)) . " \n";
                }
//                dd($recodes);
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('QuizCategories') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
                    $fileName = exportFilePrefix('QuizCategories') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $quizCategories = $quizCategories->latest()->paginate($per_page);
            return view('organization.table')
                ->with('quizCategories', $quizCategories)->render();
        }
        $quizCategories = $quizCategories->paginate($per_page);
        return view('quiz_category.index', compact('quizCategoryCount', 'quizCategories', 'userId', 'standard', 'sortedBy', 'sortedOrder'))->with($record);
    }

    //create new quiz category
    public function saveAjax(Request $request)
    {
        $data = $request->all();
        $request->validate([
            "standard" => 'required|string|unique:quiz_categories,standard' . ($request->id ? "," . $request->id : "")
        ],
            [
                "standard.required" => "Please enter standard.",
                "standard.unique" => "The standard has already been taken.",
            ]);
        if ($data['id'] != 0) {
            $organization = QuizCategory::where('id', $data['id'])->update([
                'standard' => $data['standard']
            ]);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Category updated successfully!';
            return response()->json($response);
        } else {
            $results = QuizCategory::create($data);

            $response = [];
            $response['success'] = true;
            $response['message'] = 'Category saved successfully!';
            return response()->json($response);
        }
    }

    //update quiz category
    public function getDataForEditModel(Request $request)
    {
        $data = $request->all();
        $results = QuizCategory::find($data['id']);
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

    //delete selected quiz category
    public function delete(Request $request)
    {
        $data = $request->all();
        if (empty($data['id'])) {
            return abort(404);
        }
        $isDelete = QuizCategory::where('id', $data['id'])->delete();
        if ($isDelete) {
            $request->session()
                ->flash('success', 'Category deleted successfully.');
        } else {
            $request->session()
                ->flash('error', 'Oops, the category was not deleted. Please try again.');
        }
        return Redirect::back();
    }

}
