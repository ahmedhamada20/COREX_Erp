<?php

use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\User;

test('cannot change start balance from accounts screen after movements exist', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $this->actingAs($user);

    // نوع حساب افتراضي بطبيعة مدين
    $type = AccountType::create([
        'user_id' => $user->id,
        'name' => 'الأصول',
        'code' => 'AST',
        'status' => true,
        'allow_posting' => true,
        'normal_side' => 'debit',
    ]);

    // حساب برصيد افتتاحي
    $account = Account::create([
        'user_id' => $user->id,
        'account_type_id' => $type->id,
        'name' => 'Test Account',
        'account_number' => 'ACC-TEST',
        'start_balance' => 100,
        'current_balance' => 100,
        'status' => true,
    ]);

    // إضافة حركة على الحساب
    $entry = JournalEntry::create([
        'user_id' => $user->id,
        'entry_number' => 'JE-1',
        'entry_date' => now()->toDateString(),
        'total_debit' => 100,
        'total_credit' => 100,
        'status' => 'posted',
    ]);

    JournalEntryLine::create([
        'user_id' => $user->id,
        'journal_entry_id' => $entry->id,
        'account_id' => $account->id,
        'debit' => 100,
        'credit' => 0,
        'currency_code' => 'EGP',
        'line_no' => 1,
    ]);

    // محاولة تعديل رصيد أول المدة من شاشة الحسابات
    $response = $this->put(route('accounts.update', $account), [
        'account_type_id' => $account->account_type_id,
        'parent_account_id' => $account->parent_account_id,
        'name' => 'Test Account Updated',
        'account_number' => $account->account_number,
        'start_balance' => 200, // محاولة تغيير رصيد أول المدة
        'current_balance' => $account->current_balance,
        'status' => $account->status,
    ]);

    $response->assertSessionHasErrors([
        'status' => 'لا يمكن تعديل رصيد أول المدة بعد وجود حركات على الحساب. يجب إنشاء قيد تسوية محاسبي بدلاً من ذلك.',
    ]);

    $account->refresh();

    expect((float) $account->start_balance)->toBe(100.0);
});
