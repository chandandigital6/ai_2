<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\GrowyaCrmActivity;
use App\Models\GrowyaCrmContact;
use App\Services\BusinessAccessService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaBusinessCrmController extends Controller
{
    private const STATUSES = ['new', 'contacted', 'interested', 'converted', 'closed'];

    private const SOURCES = ['manual', 'import', 'product_enquiry', 'service_enquiry', 'wishlist', 'chat', 'other'];

    private const CSV_COLUMNS = ['name', 'email', 'phone', 'company', 'status', 'source', 'tags', 'next_follow_up_at', 'notes'];

    private const IMPORT_MAX_ROWS = 2000;

    // Explicit CSV escape char (PHP 8.4 deprecates relying on the default).
    private const CSV_ESCAPE = '\\';

    private function authorizeBusiness(Request $request, Business $business): void
    {
        abort_unless(auth()->user() && app(BusinessAccessService::class)->can(auth()->user(), $business, 'customers.view'), 403);
    }

    private function contact(Business $business, int $contact): GrowyaCrmContact
    {
        return GrowyaCrmContact::where('business_id', $business->id)->findOrFail($contact);
    }

    private function assignees(Business $business)
    {
        return DB::table('business_users')->join('users', 'users.id', '=', 'business_users.user_id')
            ->where('business_users.business_id', $business->id)->where('business_users.is_active', true)
            ->where('users.status', 'active')->orderBy('users.name')->get(['users.id', 'users.name']);
    }

    private function rules(Business $business, ?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'nullable',
                'email',
                'max:190',
                // Email is unique per business (same rule the CSV import follows).
                Rule::unique((new GrowyaCrmContact)->getTable(), 'email')
                    ->where('business_id', $business->id)->ignore($ignoreId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:190'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'source' => ['required', Rule::in(self::SOURCES)],
            'tags' => ['nullable', 'string', 'max:500'],
            'next_follow_up_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('business_users', 'user_id')
                ->where('business_id', $business->id)->where('is_active', true)],
        ];
    }

    private function normalizeTags(?string $tags): ?string
    {
        if ($tags === null || trim($tags) === '') {
            return null;
        }
        $list = array_unique(array_filter(array_map('trim', explode(',', $tags)), fn ($t) => $t !== ''));

        return $list ? mb_substr(implode(', ', $list), 0, 500) : null;
    }

    private function validated(Request $request, Business $business, ?int $ignoreId = null): array
    {
        // Lower-case/trim the email BEFORE validation so the unique check is accurate.
        $email = $request->input('email');
        $request->merge(['email' => is_string($email) && trim($email) !== '' ? mb_strtolower(trim($email)) : null]);

        $data = $request->validate($this->rules($business, $ignoreId));
        $data['email'] = $data['email'] ?? null;
        $data['phone'] = ! empty($data['phone']) ? trim($data['phone']) : null;
        $data['tags'] = $this->normalizeTags($data['tags'] ?? null);

        return $data;
    }

    /** Prevent CSV/Excel formula injection in exported cells (keeps normal phone numbers like +968... intact). */
    private function csvSafe($value)
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }
        if (preg_match('/^[=+\-@\t\r]/', $value) && ! preg_match('/^\+?[0-9][0-9\s().\-]*$/', $value)) {
            return "'".$value;
        }

        return $value;
    }

    private function audit(Business $business, GrowyaCrmContact $contact, Request $request, string $type, string $description): void
    {
        GrowyaCrmActivity::create([
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'actor_id' => $request->user()->id,
            'type' => $type,
            'description' => $description,
        ]);
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($request, $business);
        $query = GrowyaCrmContact::where('business_id', $business->id);
        if ($request->filled('q')) {
            // Escape LIKE wildcards so "%" or "_" in the search box are treated as plain text.
            $q = addcslashes(trim(mb_substr((string) $request->q, 0, 120)), '%_\\');
            $query->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")->orWhere('company', 'like', "%{$q}%"));
        }
        if (in_array($request->status, self::STATUSES, true)) {
            $query->where('status', $request->status);
        }
        if (in_array($request->source, self::SOURCES, true)) {
            $query->where('source', $request->source);
        }
        if ($request->boolean('overdue')) {
            $query->where('next_follow_up_at', '<', now())->whereNotIn('status', ['converted', 'closed']);
        }
        $contacts = $query->latest('id')->paginate(20)->withQueryString();
        $totals = GrowyaCrmContact::where('business_id', $business->id)
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('growya-crm.index', compact('business', 'contacts', 'totals'));
    }

    public function create(Request $request, Business $business)
    {
        $this->authorizeBusiness($request, $business);

        return view('growya-crm.form', ['business' => $business, 'contact' => null, 'assignees' => $this->assignees($business)]);
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeBusiness($request, $business);
        $data = $this->validated($request, $business);
        $contact = DB::transaction(function () use ($business, $request, $data) {
            $contact = GrowyaCrmContact::create(['business_id' => $business->id, ...$data]);
            $this->audit($business, $contact, $request, 'created', 'Customer created');

            return $contact;
        });

        return redirect()->route('growya.crm.show', [$business, $contact->id])->with('success', 'Customer created.');
    }

    public function show(Request $request, Business $business, int $contact)
    {
        $this->authorizeBusiness($request, $business);
        $customer = $this->contact($business, $contact);
        // Newest activity first, with a stable order.
        $customer->load([
            'assignedUser:id,name',
            'activities' => fn ($q) => $q->latest('id')->with('actor:id,name'),
        ]);

        return view('growya-crm.show', compact('business', 'customer'));
    }

    public function edit(Request $request, Business $business, int $contact)
    {
        $this->authorizeBusiness($request, $business);

        return view('growya-crm.form', ['business' => $business, 'contact' => $this->contact($business, $contact), 'assignees' => $this->assignees($business)]);
    }

    public function update(Request $request, Business $business, int $contact)
    {
        $this->authorizeBusiness($request, $business);
        $customer = $this->contact($business, $contact);
        $data = $this->validated($request, $business, $customer->id);
        DB::transaction(function () use ($customer, $business, $request, $data) {
            $customer->fill($data);
            $changed = array_keys($customer->getDirty());
            if (! $changed) {
                return; // nothing changed, don't write an empty audit entry
            }
            $customer->save();
            $notes = [];
            if (in_array('status', $changed, true)) {
                $notes[] = 'status changed to '.$customer->status;
            }
            if (in_array('assigned_to', $changed, true)) {
                $notes[] = 'assignee updated';
            }
            if (in_array('next_follow_up_at', $changed, true)) {
                $notes[] = 'follow-up date updated';
            }
            $this->audit($business, $customer, $request, 'updated', $notes ? implode(', ', $notes) : 'Contact details updated');
        });

        return redirect()->route('growya.crm.show', [$business, $contact])->with('success', 'Customer updated.');
    }

    public function note(Request $request, Business $business, int $contact)
    {
        $this->authorizeBusiness($request, $business);
        $customer = $this->contact($business, $contact);
        $data = $request->validate([
            'type' => ['required', Rule::in(['note', 'call', 'meeting', 'follow_up'])],
            'description' => ['required', 'string', 'max:5000'],
        ]);
        $this->audit($business, $customer, $request, $data['type'], $data['description']);

        return back()->with('success', 'Activity saved.');
    }

    public function export(Request $request, Business $business)
    {
        $this->authorizeBusiness($request, $business);

        return response()->streamDownload(function () use ($business) {
            $h = fopen('php://output', 'w');
            fwrite($h, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Arabic/Unicode names correctly
            fputcsv($h, self::CSV_COLUMNS, ',', '"', self::CSV_ESCAPE);
            GrowyaCrmContact::where('business_id', $business->id)->chunkById(500, function ($rows) use ($h) {
                foreach ($rows as $c) {
                    fputcsv($h, [
                        $this->csvSafe($c->name),
                        $this->csvSafe($c->email),
                        $this->csvSafe($c->phone),
                        $this->csvSafe($c->company),
                        $c->status,
                        $c->source,
                        $this->csvSafe($c->tags),
                        $c->next_follow_up_at?->format('Y-m-d H:i:s'),
                        $this->csvSafe($c->notes),
                    ], ',', '"', self::CSV_ESCAPE);
                }
            });
            fclose($h);
        }, 'growya-business-'.$business->id.'-customers.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request, Business $business)
    {
        $this->authorizeBusiness($request, $business);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $file = fopen($request->file('file')->getRealPath(), 'r');
        $head = fgetcsv($file, 0, ',', '"', self::CSV_ESCAPE);
        if ($head) {
            // Excel-saved CSVs often start with a BOM, which would break the "name" header match.
            $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $head[0]);
            $head = array_map(fn ($h) => strtolower(trim((string) $h)), $head);
        }
        if (! $head || ! in_array('name', $head, true) || array_diff($head, self::CSV_COLUMNS) || count($head) !== count(array_unique($head))) {
            fclose($file);

            return back()->withErrors(['file' => 'Invalid header. Use the exported CRM CSV column names (name is required).']);
        }

        // Load existing emails once instead of querying for every row.
        $existing = array_flip(array_map('mb_strtolower', GrowyaCrmContact::where('business_id', $business->id)
            ->whereNotNull('email')->pluck('email')->all()));

        $created = 0;
        $skipped = 0;
        $rows = 0;
        $truncated = false;
        while (($cols = fgetcsv($file, 0, ',', '"', self::CSV_ESCAPE)) !== false) {
            if ($cols === [null]) {
                continue; // blank line
            }
            if (++$rows > self::IMPORT_MAX_ROWS) {
                $truncated = true;
                break;
            }
            if (count($cols) !== count($head) || ! mb_check_encoding(implode('', $cols), 'UTF-8')) {
                $skipped++;

                continue;
            }
            $r = array_combine($head, $cols);
            $name = trim($r['name'] ?? '');
            $email = trim($r['email'] ?? '') !== '' ? mb_strtolower(trim($r['email'])) : null;
            if ($name === '' || mb_strlen($name) > 150 || ($email && (! filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190))) {
                $skipped++;

                continue;
            }
            if ($email && isset($existing[$email])) {
                $skipped++; // already exists (in DB or earlier in this file)

                continue;
            }
            $status = strtolower(trim($r['status'] ?? ''));
            $status = in_array($status, self::STATUSES, true) ? $status : 'new';
            $source = strtolower(trim($r['source'] ?? ''));
            $source = in_array($source, self::SOURCES, true) ? $source : 'import';
            try {
                $followUp = ! empty(trim($r['next_follow_up_at'] ?? '')) ? Carbon::parse($r['next_follow_up_at'])->format('Y-m-d H:i:s') : null;
            } catch (\Throwable) {
                $followUp = null;
            }
            try {
                DB::transaction(function () use ($business, $request, $r, $name, $email, $status, $source, $followUp) {
                    $customer = GrowyaCrmContact::create([
                        'business_id' => $business->id,
                        'name' => $name,
                        'email' => $email,
                        'phone' => mb_substr(trim($r['phone'] ?? ''), 0, 30) ?: null,
                        'company' => mb_substr(trim($r['company'] ?? ''), 0, 190) ?: null,
                        'status' => $status,
                        'source' => $source,
                        'tags' => $this->normalizeTags($r['tags'] ?? null),
                        'notes' => mb_substr(trim($r['notes'] ?? ''), 0, 10000) ?: null,
                        'next_follow_up_at' => $followUp,
                    ]);
                    $this->audit($business, $customer, $request, 'imported', 'Customer imported from CSV');
                });
                if ($email) {
                    $existing[$email] = true;
                }
                $created++;
            } catch (QueryException $e) {
                $skipped++;
            }
        }
        fclose($file);

        $message = "CSV import: {$created} created, {$skipped} skipped.";
        if ($truncated) {
            $message .= ' Only the first '.self::IMPORT_MAX_ROWS.' rows were processed.';
        }

        return back()->with('success', $message);
    }
}
