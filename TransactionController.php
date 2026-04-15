<?php

namespace App\Http\Controllers;

use App\Exports\CSVExport;
use App\Models\OrganizationSubscription;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Response;

class TransactionController extends Controller
{
    //view transaction records
    public function index(Request $request)
    {
        $per_page = $request->par_page ?? 10;

        $allSubscription = OrganizationSubscription::orderby($request->get('sorted_by', 'id'), $request->get('sorted_order', 'desc'))->with('organization', 'subscriptionPlan');

        $allSubscription = $allSubscription->whereHas('organization', function ($q) use ($request) {
            if ($request->ordname)
                $q->where('name', 'like', '%' . $request->ordname . '%');
        });
        if ($request->plan) {
            $allSubscription = $allSubscription->where('plan_name', 'like', '%' . $request->plan . '%');
        }
        if ($request->transaction_id) {
            $allSubscription = $allSubscription->where('transaction_id', 'like', '%' . $request->transaction_id . '%');
        }
        if ($request->price) {
            $allSubscription = $allSubscription->where('plan_price', $request->price);
        }

        if ($request->from) {
            $allSubscription = $allSubscription->whereDate('created_at', '>=', date('Y-m-d', strtotime($request->from)));
        }
        if ($request->to) {
            $allSubscription = $allSubscription->whereDate('created_at', '<=', date('Y-m-d', strtotime($request->to)));
        }

        switch($request->input('status')){
            case 'Month':
                $allSubscription = $allSubscription->whereMonth('created_at', \Carbon\Carbon::now()->month);
                break;
            case 'Week':
                $allSubscription = $allSubscription->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'Today':
                $allSubscription = $allSubscription->whereDate('created_at', \Carbon\Carbon::now()->day);
                break;
            case 'Inactive':
                $allSubscription=$allSubscription->where('transaction_status','0');
                break;
        }


        $allSubscriptionClone = new OrganizationSubscription();
        $allSubscriptionc = clone $allSubscription;
        $transactionCount = $allSubscriptionc->count();
        $allSubscription_all = clone $allSubscriptionClone;
        $record['All'] = $allSubscription_all->count();
        $allSubscription_month = clone $allSubscriptionClone;
        $record['Month'] = $allSubscription_month->whereMonth('created_at', \Carbon\Carbon::now()->month)->count();
        $allSubscription_week = clone $allSubscriptionClone;
        $record['Week'] = $allSubscription_week->whereBetween('created_at', [\Illuminate\Support\Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $allSubscription_today = clone $allSubscriptionClone;
        $record['Today'] = $allSubscription_today->whereDate('created_at', \Carbon\Carbon::now()->day)->count();
        $allSubscription_Failed = clone $allSubscriptionClone;
        $record['Failed'] = $allSubscription_Failed->where('transaction_status', '0')->count();

        if ($request->isXmlHttpRequest()) {
            if (!empty($request->par_page)) {
                $allSubscription = $allSubscription->paginate($request->par_page);
            }
            return view('transaction.transaction_table', compact('allSubscription', 'transactionCount'))
                ->with('per_page', $per_page)->with($record);
        }

        if (($request->ajax() && $request->filter) || (in_array($request->export, ['csv', 'text']))) {
            if ((in_array($request->export, ['csv', 'text']))) {
                $allSubscriptionDatas = $allSubscription->get();
                $rows = ([
                    'id' => 'Transaction ID',
                    'name' => 'Transaction Name',
                    'plan' => 'Transaction Plan',
                    'price' => 'Price',
                    'months' => 'Plan Months',
                    'transid' => 'Transaction Id',
                    'created_at' => 'Join On',
                ]);
                $keys = array_keys($rows);
                $txtop = '';
                foreach ($allSubscriptionDatas as $allSubscriptionData) {
                    $cuRecord = [
                        'id' => $allSubscriptionData->id,
                        'name' => $allSubscriptionData->organization->name,
                        'plan' => $allSubscriptionData->plan_name,
                        'price' => $allSubscriptionData->plan_price,
                        'month' => $allSubscriptionData->plan_months,
                        'transid' => $allSubscriptionData->transaction_id,
                        'created_at' => $allSubscriptionData->created_at,
                    ];
                    $recodes[] = $cuRecord;
                    $txtop .= implode(" , ", array_values($cuRecord)) . " \n";
                }
                if ($request->export == 'csv') {
                    $csv = new CSVExport($recodes, array_values($rows));
                    $fileName = exportFilePrefix('Transactions') . '.csv';
                    return Excel::download($csv, $fileName);
                } else {
                    $output = implode(" , ", $rows) . " \n" . $txtop;
                    $fileName = exportFilePrefix('Transactions') . '.txt';
                    return Response::make($output, 200, [
                        'Content-type' => 'text/plain',
                        'Content-Disposition' => sprintf('attachment; filename="%s"', $fileName),
                        'Content-Length' => strlen($output)
                    ]);
                }
            }
            $allSubscription = $allSubscription->paginate($per_page);
            return view('transaction.transaction_table')
                ->with('allSubscription', $allSubscription)->render();
        }
        $allSubscription = $allSubscription->paginate($per_page);
        return view('transaction.transaction_history', compact('allSubscription', 'transactionCount'))->with($record);
    }
}

