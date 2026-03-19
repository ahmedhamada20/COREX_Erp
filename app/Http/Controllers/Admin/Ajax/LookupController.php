<?php

namespace App\Http\Controllers\Admin\Ajax;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Treasuries;
use Illuminate\Http\Request;

class LookupController extends AdminBaseController
{
    private function select2Response($paginator, callable $map): array
    {
        $items = $paginator->getCollection()->map($map)->values()->all();

        return [
            'results' => $items,
            'more' => $paginator->hasMorePages(),
        ];
    }

    // ========= Treasuries =========
    public function treasuries(Request $request)
    {
        $ownerId = $this->ownerId();
        $q = trim((string) $request->get('q', ''));
        $exclude = (int) $request->get('exclude', 0);

        $query = Treasuries::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->when($exclude > 0, fn ($qq) => $qq->where('id', '!=', $exclude))
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('is_master')
            ->orderBy('name');

        $p = $query->paginate(20);

        return response()->json($this->select2Response($p, function ($t) {
            $text = $t->name;
            if ($t->code) {
                $text .= " - {$t->code}";
            }
            if ($t->is_master) {
                $text .= ' (رئيسية)';
            }

            return ['id' => $t->id, 'text' => $text];
        }));
    }

    public function treasuriesById(Request $request)
    {
        $ownerId = $this->ownerId();
        $id = (int) $request->get('id');

        $t = Treasuries::query()->where('user_id', $ownerId)->where('id', $id)->first();
        if (! $t) {
            return response()->json(null);
        }

        $text = $t->name;
        if ($t->code) {
            $text .= " - {$t->code}";
        }
        if ($t->is_master) {
            $text .= ' (رئيسية)';
        }

        return response()->json(['id' => $t->id, 'text' => $text]);
    }

    // ========= Customers =========
    public function customers(Request $request)
    {
        $ownerId = $this->ownerId();
        $q = trim((string) $request->get('q', ''));

        $query = Customer::query()
            ->where('user_id', $ownerId)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $p = $query->paginate(20);

        return response()->json($this->select2Response($p, function ($c) {
            $text = $c->name;
            if (! empty($c->code)) {
                $text .= " - {$c->code}";
            }
            if (! empty($c->phone)) {
                $text .= " | {$c->phone}";
            }

            return ['id' => $c->id, 'text' => $text];
        }));
    }

    public function customersById(Request $request)
    {
        $ownerId = $this->ownerId();
        $id = (int) $request->get('id');

        $c = Customer::query()->where('user_id', $ownerId)->where('id', $id)->first();
        if (! $c) {
            return response()->json(null);
        }

        $text = $c->name;
        if (! empty($c->code)) {
            $text .= " - {$c->code}";
        }
        if (! empty($c->phone)) {
            $text .= " | {$c->phone}";
        }

        return response()->json(['id' => $c->id, 'text' => $text]);
    }

    // ========= Suppliers =========
    public function suppliers(Request $request)
    {
        $ownerId = $this->ownerId();
        $q = trim((string) $request->get('q', ''));

        $query = Supplier::query()
            ->where('user_id', $ownerId)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $p = $query->paginate(20);

        return response()->json($this->select2Response($p, function ($s) {
            $text = $s->name;
            if (! empty($s->code)) {
                $text .= " - {$s->code}";
            }
            if (! empty($s->phone)) {
                $text .= " | {$s->phone}";
            }

            return ['id' => $s->id, 'text' => $text];
        }));
    }

    public function suppliersById(Request $request)
    {
        $ownerId = $this->ownerId();
        $id = (int) $request->get('id');

        $s = Supplier::query()->where('user_id', $ownerId)->where('id', $id)->first();
        if (! $s) {
            return response()->json(null);
        }

        $text = $s->name;
        if (! empty($s->code)) {
            $text .= " - {$s->code}";
        }
        if (! empty($s->phone)) {
            $text .= " | {$s->phone}";
        }

        return response()->json(['id' => $s->id, 'text' => $text]);
    }

    // ========= Accounts =========
    public function accounts(Request $request)
    {
        $ownerId = $this->ownerId();
        $q = trim((string) $request->get('q', ''));

        $query = Account::query()
            ->where('user_id', $ownerId)
            ->where('status', 1)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('account_number', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $p = $query->paginate(20);

        return response()->json($this->select2Response($p, function ($a) {
            $text = $a->name;
            if (! empty($a->account_number)) {
                $text .= " - {$a->account_number}";
            }

            return ['id' => $a->id, 'text' => $text];
        }));
    }

    public function accountsById(Request $request)
    {
        $ownerId = $this->ownerId();
        $id = (int) $request->get('id');

        $a = Account::query()->where('user_id', $ownerId)->where('id', $id)->first();
        if (! $a) {
            return response()->json(null);
        }

        $text = $a->name;
        if (! empty($a->account_number)) {
            $text .= " - {$a->account_number}";
        }

        return response()->json(['id' => $a->id, 'text' => $text]);
    }
}
