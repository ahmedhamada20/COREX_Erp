<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected \App\Models\User $user;

    protected AccountType $assetType;

    protected AccountType $liabilityType;

    protected AccountType $equityType;

    protected AccountType $revenueType;

    protected AccountType $expenseType;

    protected Account $cash;

    protected Account $ap;

    protected Account $capital;

    protected Account $revenue;

    protected Account $expense;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = \App\Models\User::factory()->create();

        $this->assetType = AccountType::factory()->create(['user_id' => $this->user->id, 'name' => 'Assets', 'normal_side' => 'debit']);
        $this->liabilityType = AccountType::factory()->create(['user_id' => $this->user->id, 'name' => 'Liabilities', 'normal_side' => 'credit']);
        $this->equityType = AccountType::factory()->create(['user_id' => $this->user->id, 'name' => 'Equity', 'normal_side' => 'credit']);
        $this->revenueType = AccountType::factory()->create(['user_id' => $this->user->id, 'name' => 'Revenue', 'normal_side' => 'credit']);
        $this->expenseType = AccountType::factory()->create(['user_id' => $this->user->id, 'name' => 'Expenses', 'normal_side' => 'debit']);

        $this->cash = Account::create([
            'user_id' => $this->user->id,
            'account_type_id' => $this->assetType->id,
            'name' => 'Cash',
            'account_number' => '101',
            'start_balance' => 0,
            'status' => true,
        ]);

        $this->ap = Account::create([
            'user_id' => $this->user->id,
            'account_type_id' => $this->liabilityType->id,
            'name' => 'AP',
            'account_number' => '201',
            'start_balance' => 0,
            'status' => true,
        ]);

        $this->capital = Account::create([
            'user_id' => $this->user->id,
            'account_type_id' => $this->equityType->id,
            'name' => 'Capital',
            'account_number' => '301',
            'start_balance' => 0,
            'status' => true,
        ]);

        $this->revenue = Account::create([
            'user_id' => $this->user->id,
            'account_type_id' => $this->revenueType->id,
            'name' => 'Sales Revenue',
            'account_number' => '401',
            'start_balance' => 0,
            'status' => true,
        ]);

        $this->expense = Account::create([
            'user_id' => $this->user->id,
            'account_type_id' => $this->expenseType->id,
            'name' => 'Expense',
            'account_number' => '501',
            'start_balance' => 0,
            'status' => true,
        ]);
    }

    public function test_ledger_must_be_balanced(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $je = JournalEntry::create([
                'user_id' => $this->user->id,
                'entry_number' => 'JE-'.$i,
                'entry_date' => now()->toDateString(),
            ]);

            $je->lines()->create([
                'user_id' => $this->user->id,
                'account_id' => $this->cash->id,
                'debit' => 1000,
                'credit' => 0,
            ]);

            $je->lines()->create([
                'user_id' => $this->user->id,
                'account_id' => $this->capital->id,
                'debit' => 0,
                'credit' => 1000,
            ]);
        }

        $totalDebits = JournalEntryLine::query()->where('user_id', $this->user->id)->sum('debit');
        $totalCredits = JournalEntryLine::query()->where('user_id', $this->user->id)->sum('credit');

        $this->assertEqualsWithDelta((float) $totalDebits, (float) $totalCredits, 0.01);
    }

    public function test_accounting_equation_must_hold(): void
    {
        // Opening: Cash 50k / Capital 50k
        $opening = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_number' => 'JE-OPEN',
            'entry_date' => now()->toDateString(),
        ]);

        $opening->lines()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->cash->id,
            'debit' => 50000,
            'credit' => 0,
        ]);

        $opening->lines()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->capital->id,
            'debit' => 0,
            'credit' => 50000,
        ]);

        // Liability: AP credit 8000 / Cash debit? (مثال مشتريات على الحساب: Dr Expense 8000 Cr AP 8000)
        $purchaseOnCredit = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_number' => 'JE-AP',
            'entry_date' => now()->toDateString(),
        ]);

        $purchaseOnCredit->lines()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->expense->id,
            'debit' => 8000,
            'credit' => 0,
        ]);

        $purchaseOnCredit->lines()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->ap->id,
            'debit' => 0,
            'credit' => 8000,
        ]);

        // Revenue: Cash 13000 / Revenue 13000
        $sale = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_number' => 'JE-SALE',
            'entry_date' => now()->toDateString(),
        ]);

        $sale->lines()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->cash->id,
            'debit' => 13000,
            'credit' => 0,
        ]);

        $sale->lines()->create([
            'user_id' => $this->user->id,
            'account_id' => $this->revenue->id,
            'debit' => 0,
            'credit' => 13000,
        ]);

        $assets = $this->balanceByType($this->assetType->id);
        $liabilities = $this->balanceByType($this->liabilityType->id);
        $equity = $this->balanceByType($this->equityType->id);
        $revenues = $this->balanceByType($this->revenueType->id);
        $expenses = $this->balanceByType($this->expenseType->id);

        // ✅ المعادلة مع صافي الربح:
        // Assets = Liabilities + Equity + (Revenues - Expenses)
        $rhs = $liabilities + $equity + ($revenues - $expenses);

        $this->assertEqualsWithDelta(
            $assets,
            $rhs,
            0.01,
            "المعادلة يجب أن تكون صحيحة: Assets({$assets}) = L({$liabilities}) + E({$equity}) + (Rev({$revenues}) - Exp({$expenses}))"
        );
    }

    protected function balanceByType(int $accountTypeId): float
    {
        $type = AccountType::query()->where('id', $accountTypeId)->firstOrFail();

        // normal side logic:
        // Debit-normal => balance = debit - credit
        // Credit-normal => balance = credit - debit
        $sumDebit = (float) JournalEntryLine::query()
            ->where('user_id', $this->user->id)
            ->whereHas('account', fn ($q) => $q->where('account_type_id', $accountTypeId))
            ->sum('debit');

        $sumCredit = (float) JournalEntryLine::query()
            ->where('user_id', $this->user->id)
            ->whereHas('account', fn ($q) => $q->where('account_type_id', $accountTypeId))
            ->sum('credit');

        return $type->normal_side === 'credit'
            ? round($sumCredit - $sumDebit, 2)
            : round($sumDebit - $sumCredit, 2);
    }
}
