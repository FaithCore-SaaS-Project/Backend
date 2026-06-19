<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FinanceIncome;
use App\Models\FinanceExpense;
use App\Models\Member;
use App\Models\EventRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function financial(Request $request)
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());
        $format = $request->query('format', 'json');

        $incomes = FinanceIncome::with('category')
            ->whereBetween('income_date', [$startDate, $endDate])
            ->get();
            
        $expenses = FinanceExpense::with('category')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();

        $totalIncome = $incomes->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $net = $totalIncome - $totalExpense;

        $data = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'incomes' => $incomes,
            'expenses' => $expenses,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_balance' => $net,
            'church_id' => $request->user()->church_id
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.financial', $data);
            return $pdf->download("financial_report_{$startDate}_to_{$endDate}.pdf");
        }

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($incomes, $expenses) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Type', 'Category', 'Amount', 'Date', 'Description']);
                foreach ($incomes as $i) {
                    fputcsv($file, ['Income', $i->category->name ?? 'N/A', $i->amount, $i->income_date, $i->description]);
                }
                foreach ($expenses as $e) {
                    fputcsv($file, ['Expense', $e->category->name ?? 'N/A', $e->amount, $e->expense_date, $e->description]);
                }
                fclose($file);
            }, "financial_report.csv");
        }

        return response()->json($data);
    }

    public function members(Request $request)
    {
        $format = $request->query('format', 'json');
        
        $query = Member::with(['family']);
        
        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }
        
        $members = $query->orderBy('last_name')->get();

        $data = [
            'members' => $members,
            'total' => $members->count()
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.members', $data);
            return $pdf->download("member_directory.pdf");
        }

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($members) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['First Name', 'Last Name', 'Email', 'Phone', 'Gender', 'Status', 'Join Date']);
                foreach ($members as $m) {
                    fputcsv($file, [$m->first_name, $m->last_name, $m->email, $m->phone, $m->gender, $m->status, $m->join_date]);
                }
                fclose($file);
            }, "member_directory.csv");
        }

        return response()->json($data);
    }

    public function attendance(Request $request)
    {
        $format = $request->query('format', 'json');
        
        $query = EventRegistration::with(['event', 'member']);
        
        if ($request->has('event_id')) {
            $query->where('event_id', $request->query('event_id'));
        }
        
        $registrations = $query->orderBy('created_at', 'desc')->get();

        $data = [
            'registrations' => $registrations,
            'total_registered' => $registrations->where('status', 'registered')->count(),
            'total_checked_in' => $registrations->where('status', 'checked_in')->count(),
            'total_no_show' => $registrations->where('status', 'no_show')->count(),
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.attendance', $data);
            return $pdf->download("attendance_report.pdf");
        }

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($registrations) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Event', 'Member', 'Status', 'Date Registered']);
                foreach ($registrations as $r) {
                    fputcsv($file, [$r->event->title ?? 'N/A', ($r->member->first_name ?? '') . ' ' . ($r->member->last_name ?? ''), $r->status, $r->created_at]);
                }
                fclose($file);
            }, "attendance_report.csv");
        }

        return response()->json($data);
    }
}
