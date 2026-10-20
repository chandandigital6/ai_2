<?php

namespace App\Services\Extensions;

use App\Models\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JournalService
{
    public function post(int $businessId, int $actor, array $data, ?int $reverses = null): object
    {
        return DB::transaction(function () use ($businessId, $actor, $data, $reverses) {
            Business::whereKey($businessId)->lockForUpdate()->firstOrFail();
            $requestHash = hash('sha256', json_encode([$data['entry_date'], $data['memo'], $reverses, array_map(fn ($l) => [(int) $l['account_id'], (int) $l['debit_paise'], (int) $l['credit_paise']], $data['lines'])]));
            $old = DB::table('growya_journals')->where('business_id', $businessId)->where('request_key', $data['request_key'])->first();
            if ($old) {
                if ($old->request_hash !== $requestHash) {
                    throw ValidationException::withMessages(['request_key' => 'Already used for another journal.']);
                }

                return $old;
            }
            $debit = 0;
            $credit = 0;
            foreach ($data['lines'] as $line) {
                $d = (int) $line['debit_paise'];
                $c = (int) $line['credit_paise'];
                if (($d > 0 && $c > 0) || ($d === 0 && $c === 0) || $d < 0 || $c < 0) {
                    throw ValidationException::withMessages(['lines' => 'Each line needs a debit OR a credit.']);
                }
                if (! DB::table('growya_journal_accounts')->where('business_id', $businessId)->where('id', $line['account_id'])->exists()) {
                    throw ValidationException::withMessages(['lines' => 'Account is outside this business.']);
                }$debit += $d;
                $credit += $c;
            }
            if ($debit !== $credit || $debit === 0 || $debit > 100000000000000) {
                throw ValidationException::withMessages(['lines' => 'Debits and credits must balance to a positive total.']);
            }
            if ($reverses && DB::table('growya_journals')->where('reverses_id', $reverses)->exists()) {
                throw ValidationException::withMessages(['journal' => 'Journal already reversed.']);
            }
            $id = DB::table('growya_journals')->insertGetId(['business_id' => $businessId, 'created_by' => $actor, 'request_key' => $data['request_key'], 'request_hash' => $requestHash, 'entry_date' => $data['entry_date'], 'memo' => $data['memo'], 'reverses_id' => $reverses, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($data['lines'] as $line) {
                DB::table('growya_journal_lines')->insert(['journal_id' => $id, 'account_id' => $line['account_id'], 'debit_paise' => $line['debit_paise'], 'credit_paise' => $line['credit_paise'], 'created_at' => now(), 'updated_at' => now()]);
            }

            return DB::table('growya_journals')->find($id);
        }, 3);
    }
}
