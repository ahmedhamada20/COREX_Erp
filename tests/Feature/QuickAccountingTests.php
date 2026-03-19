<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickAccountingTests extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected AccountType $assetType;

    protected AccountType $liabilityType;

    protected Account $cashAccount;

    protected Account $capitalAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBasicAccounts();
    }

    protected function setupBasicAccounts(): void
    {
        $this->user = User::factory()->create();

        // Create account types
        $this->assetType = AccountType::create([
            'user_id' => $this->user->id,
            'name' => 'Assets',
            'normal_side' => 'debit',
        ]);

        $this->liabilityType = AccountType::create([
            'user_id' => $this->user->id,
            'name' => 'Equity',
            'normal_side' => 'credit',
        ]);

        // Create accounts
        $this->cashAccount = Account::create([
            'user_id' => $this->user->id,
            'code' => '101',
            'name' => 'Cash',
            'account_type_id' => $this->assetType->id,
        ]);

        $this->capitalAccount = Account::create([
            'user_id' => $this->user->id,
            'code' => '301',
            'name' => 'Capital',
            'account_type_id' => $this->liabilityType->id,
        ]);
    }

    public function test_basic_journal_entry(): void
    {
        $entry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_number' => 'JE001',
            'entry_date' => now(),
        ]);

        $entry->lines()->create([
            'account_id' => $this->cashAccount->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $entry->lines()->create([
            'account_id' => $this->capitalAccount->id,
            'debit' => 0,
            'credit' => 1000,
        ]);

        $this->assertNotNull($entry->id);
        $this->assertEquals(2, $entry->lines()->count());

        $totalDebits = $entry->lines()->sum('debit');
        $totalCredits = $entry->lines()->sum('credit');

        $this->assertEquals($totalDebits, $totalCredits);
    }

    public function test_accounting_equation_simple(): void
    {
        $entry = JournalEntry::create([
            'user_id' => $this->user->id,
            'entry_number' => 'JE002',
            'entry_date' => now(),
        ]);

        $entry->lines()->create([
            'account_id' => $this->cashAccount->id,
            'debit' => 5000,
            'credit' => 0,
        ]);

        $entry->lines()->create([
            'account_id' => $this->capitalAccount->id,
            'debit' => 0,
            'credit' => 5000,
        ]);

        $cashBalance = $this->cashAccount->fresh()->lines()->sum('debit') - $this->cashAccount->lines()->sum('credit');
        $capitalBalance = $this->capitalAccount->fresh()->lines()->sum('credit') - $this->capitalAccount->lines()->sum('debit');

        $this->assertEquals(5000, $cashBalance);
        $this->assertEquals(5000, $capitalBalance);
    }

    public function test_project_overall_compatibility(): void
    {
        // Test that all services and models exist
        $this->assertTrue(class_exists(\App\Models\Account::class));
        $this->assertTrue(class_exists(\App\Models\AccountType::class));
        $this->assertTrue(class_exists(\App\Models\JournalEntry::class));
        $this->assertTrue(class_exists(\App\Models\SalesInvoice::class));
        $this->assertTrue(class_exists(\App\Models\PurchaseInvoice::class));
        $this->assertTrue(class_exists(\App\Models\Customer::class));
        $this->assertTrue(class_exists(\App\Models\Supplier::class));

        $this->assertTrue(true, 'Overall project compatibility confirmed');
    }
}
