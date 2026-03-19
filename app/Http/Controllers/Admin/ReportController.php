<?php

namespace App\Http\Controllers\Admin;

use App\Services\Reporting\BalanceSheetGenerator;
use App\Services\Reporting\AccountStatementGenerator;
use App\Services\Reporting\CashFlowStatementGenerator;
use App\Services\Reporting\IncomeStatementGenerator;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends AdminBaseController
{
    public function __construct(
        private readonly BalanceSheetGenerator $balanceSheetGenerator,
        private readonly IncomeStatementGenerator $incomeStatementGenerator,
        private readonly AccountStatementGenerator $accountStatementGenerator,
        private readonly CashFlowStatementGenerator $cashFlowStatementGenerator,
    ) {}

    public function index(): \Illuminate\View\View
    {
        return view('admin.reports.index');
    }

    public function balanceSheet(Request $request): \Illuminate\View\View
    {
        $asOfDate = $request->input('as_of_date', now()->toDateString());

        $report = $this->balanceSheetGenerator->generate(
            tenantId: $this->ownerId(),
            asOfDate: $asOfDate,
        );

        return view('admin.reports.balance_sheet', compact('report', 'asOfDate'));
    }

    public function incomeStatement(Request $request): \Illuminate\View\View
    {
        $fromDate = $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());

        $report = $this->incomeStatementGenerator->generate(
            tenantId: $this->ownerId(),
            startDate: $fromDate,
            endDate: $toDate,
        );

        return view('admin.reports.income_statement', compact('report', 'fromDate', 'toDate'));
    }

    public function trialBalance(Request $request): \Illuminate\View\View
    {
        $fromDate = $request->input('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->input('to_date', now()->toDateString());
        $ownerId = $this->ownerId();

        $accounts = DB::table('account_balances')
            ->join('accounts', 'account_balances.account_id', '=', 'accounts.id')
            ->join('account_types', 'accounts.account_type_id', '=', 'account_types.id')
            ->where('account_balances.user_id', $ownerId)
            ->whereNull('accounts.deleted_at')
            ->select(
                'accounts.account_number',
                'accounts.name',
                'account_types.name as type_name',
                'account_types.normal_side',
                'account_balances.balance',
            )
            ->orderBy('accounts.account_number')
            ->get();

        $totalDebit = $accounts->where('normal_side', 'debit')->sum('balance');
        $totalCredit = $accounts->where('normal_side', 'credit')->sum('balance');

        return view('admin.reports.trial_balance', compact(
            'accounts', 'totalDebit', 'totalCredit', 'fromDate', 'toDate'
        ));
    }

    public function accountStatement(Request $request): \Illuminate\View\View
    {
        $ownerId = $this->ownerId();
        $fromDate = (string) $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = (string) $request->input('to_date', now()->toDateString());

        $selectedAccountId = $request->filled('account_id') ? (int) $request->input('account_id') : null;

        if ($request->filled('customer_id')) {
            $customer = Customer::query()
                ->where('user_id', $ownerId)
                ->find((int) $request->input('customer_id'));

            if ($customer && $customer->account_number) {
                $selectedAccountId = (int) Account::query()
                    ->where('user_id', $ownerId)
                    ->where('account_number', $customer->account_number)
                    ->value('id');
            }
        }

        if ($request->filled('supplier_id')) {
            $supplier = Supplier::query()
                ->where('user_id', $ownerId)
                ->find((int) $request->input('supplier_id'));

            if ($supplier && $supplier->account_number) {
                $selectedAccountId = (int) Account::query()
                    ->where('user_id', $ownerId)
                    ->where('account_number', $supplier->account_number)
                    ->value('id');
            }
        }

        $report = $this->accountStatementGenerator->generate(
            tenantId: $ownerId,
            fromDate: $fromDate,
            toDate: $toDate,
            accountId: $selectedAccountId,
        );

        $accounts = Account::query()->where('user_id', $ownerId)->orderBy('account_number')->get();
        $customers = Customer::query()->where('user_id', $ownerId)->orderBy('name')->get();
        $suppliers = Supplier::query()->where('user_id', $ownerId)->orderBy('name')->get();

        return view('admin.reports.account_statement', compact(
            'report',
            'accounts',
            'customers',
            'suppliers',
            'fromDate',
            'toDate',
            'selectedAccountId'
        ));
    }

    public function cashFlowStatement(Request $request): \Illuminate\View\View
    {
        $fromDate = (string) $request->input('from_date', now()->startOfMonth()->toDateString());
        $toDate = (string) $request->input('to_date', now()->toDateString());

        $report = $this->cashFlowStatementGenerator->generate(
            tenantId: $this->ownerId(),
            fromDate: $fromDate,
            toDate: $toDate,
        );

        return view('admin.reports.cash_flow_statement', compact('report', 'fromDate', 'toDate'));
    }
}
