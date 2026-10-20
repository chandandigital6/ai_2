<?php

namespace App\Http\Controllers\Extensions;

use App\Models\Business;
use App\Services\Extensions\JournalService;
use App\Services\Extensions\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AccountingController extends ExtensionController
{
    public function accounts(Request $r, Business $business)
    {
        $this->business($r, $business, 'accounting.view');

        return response()->json(['data' => DB::table('growya_journal_accounts')->where('business_id', $business->id)->orderBy('code')->get()]);
    }

    public function account(Request $r, Business $business)
    {
        $this->business($r, $business, 'accounting.manage');
        $d = $r->validate(['code' => ['required', 'alpha_dash', 'max:30', Rule::unique('growya_journal_accounts', 'code')->where('business_id', $business->id)], 'name' => ['required', 'string', 'max:150'], 'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'income', 'expense'])], 'is_cash' => ['required', 'boolean']]);
        abort_if($d['is_cash'] && $d['type'] !== 'asset', 422, 'Cash/bank account must be an asset.');
        $id = DB::table('growya_journal_accounts')->insertGetId($d + ['business_id' => $business->id, 'created_at' => now(), 'updated_at' => now()]);

        return $this->reply($r, ['id' => $id] + $d, 201);
    }

    public function index(Request $r, Business $business)
    {
        $this->business($r, $business, 'accounting.view');
        $rows = DB::table('growya_journals')->where('business_id', $business->id)->orderByDesc('entry_date')->orderByDesc('id')->paginate(25);
        $rows->getCollection()->transform(function ($j) {
            $j->lines = DB::table('growya_journal_lines')->where('journal_id', $j->id)->get();

            return $j;
        });

        return response()->json(['data' => $rows]);
    }

    public function store(Request $r, Business $business, JournalService $service)
    {
        $this->business($r, $business, 'accounting.manage');
        if (! $r->is('api/*')) {
            $r->validate(['lines' => ['required', 'array', 'max:100'], 'lines.*.debit_rupees' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000000000'], 'lines.*.credit_rupees' => ['nullable', 'decimal:0,2', 'min:0', 'max:10000000000']]);
            $lines = collect($r->input('lines'))->filter(fn ($l) => ! empty($l['account_id']))->map(fn ($l) => ['account_id' => $l['account_id'], 'debit_paise' => RazorpayService::paise((string) ($l['debit_rupees'] ?? '0')), 'credit_paise' => RazorpayService::paise((string) ($l['credit_rupees'] ?? '0'))])->values()->all();
            $r->merge(['lines' => $lines]);
        }
        $d = $r->validate(['request_key' => ['required', 'string', 'max:100'], 'entry_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'memo' => ['required', 'string', 'max:500'], 'lines' => ['required', 'array', 'min:2', 'max:100'], 'lines.*.account_id' => ['required', 'integer'], 'lines.*.debit_paise' => ['required', 'integer', 'min:0', 'max:1000000000000'], 'lines.*.credit_paise' => ['required', 'integer', 'min:0', 'max:1000000000000']]);

        return $this->reply($r, (array) $service->post($business->id, $r->user()->id, $d), 201);
    }

    public function reverse(Request $r, Business $business, int $journal, JournalService $service)
    {
        $this->business($r, $business, 'accounting.manage');
        $d = $r->validate(['request_key' => ['required', 'string', 'max:100'], 'entry_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'], 'memo' => ['required', 'string', 'max:500']]);
        $original = DB::table('growya_journals')->where('business_id', $business->id)->find($journal);
        abort_unless($original, 404);
        abort_if($original->reverses_id, 422, 'Reverse the original entry only.');
        $d['lines'] = DB::table('growya_journal_lines')->where('journal_id', $journal)->get()->map(fn ($l) => ['account_id' => $l->account_id, 'debit_paise' => $l->credit_paise, 'credit_paise' => $l->debit_paise])->all();

        return $this->reply($r, (array) $service->post($business->id, $r->user()->id, $d, $journal), 201);
    }

    public function reports(Request $r, Business $business)
    {
        $this->business($r, $business, 'accounting.view');
        $d = $r->validate(['from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from']]);
        $from = $d['from'] ?? now()->startOfMonth()->toDateString();
        $to = $d['to'] ?? today()->toDateString();
        $accounts = DB::table('growya_journal_accounts')->where('business_id', $business->id)->orderBy('code')->get();
        $cashIn = 0;
        $cashOut = 0;
        $trialDebit = 0;
        $trialCredit = 0;
        $income = 0;
        $expense = 0;
        $rows = $accounts->map(function ($a) use ($business, $from, $to, &$cashIn, &$cashOut, &$trialDebit, &$trialCredit, &$income, &$expense) {
            $base = DB::table('growya_journal_lines as l')->join('growya_journals as j', 'j.id', '=', 'l.journal_id')->where('j.business_id', $business->id)->where('l.account_id', $a->id);
            $opening = (clone $base)->where('j.entry_date', '<', $from)->selectRaw('COALESCE(SUM(l.debit_paise),0)-COALESCE(SUM(l.credit_paise),0) as balance')->value('balance');
            $period = (clone $base)->whereBetween('j.entry_date', [$from, $to])->selectRaw('COALESCE(SUM(l.debit_paise),0) as debits, COALESCE(SUM(l.credit_paise),0) as credits')->first();
            $balance = (int) $opening + (int) $period->debits - (int) $period->credits;
            $trialDebit += max(0, $balance);
            $trialCredit += max(0, -$balance);
            if ($a->is_cash) {
                $cashIn += (int) $period->debits;
                $cashOut += (int) $period->credits;
            }if ($a->type === 'income') {
                $income += (int) $period->credits - (int) $period->debits;
            }if ($a->type === 'expense') {
                $expense += (int) $period->debits - (int) $period->credits;
            }

            return ['account_id' => $a->id, 'code' => $a->code, 'name' => $a->name, 'type' => $a->type, 'opening_paise' => (int) $opening, 'debit_paise' => (int) $period->debits, 'credit_paise' => (int) $period->credits, 'closing_paise' => $balance];
        });

        return response()->json(['data' => ['from' => $from, 'to' => $to, 'accounts' => $rows, 'trial_debit_paise' => $trialDebit, 'trial_credit_paise' => $trialCredit, 'income_paise' => $income, 'expense_paise' => $expense, 'profit_paise' => $income - $expense, 'cash_in_paise' => $cashIn, 'cash_out_paise' => $cashOut, 'net_cash_movement_paise' => $cashIn - $cashOut]]);
    }
}
