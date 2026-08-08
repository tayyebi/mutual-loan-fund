<?php

namespace App\Http\Controllers;

use App\Domain\Accounting\AccountingService;
use App\Domain\Accounting\LineDraft;
use App\Domain\Money\Decimal;
use App\Domain\Money\Money;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Group;
use App\Models\JournalEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

class LedgerController extends Controller
{
    public function index(Request $request, Group $group): View
    {
        $entries = JournalEntry::query()
            ->forGroup($group)
            ->with(['lines.account', 'transaction'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('entry_date', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('entry_date', '<=', $to))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('ledger.index', [
            'group' => $group,
            'entries' => $entries,
            'filters' => $request->only(['status', 'from', 'to']),
        ]);
    }

    public function show(Group $group, JournalEntry $journalEntry): View
    {
        return view('ledger.show', [
            'group' => $group,
            'entry' => $journalEntry->load([
                'lines.account', 'lines.costCenter', 'transaction',
                'reverses', 'reversal', 'creator', 'poster', 'period',
            ]),
        ]);
    }

    /**
     * Corrections never edit a posted entry: they post a reversal, and then the
     * correct entry.
     */
    public function reverse(Request $request, Group $group, JournalEntry $journalEntry, AccountingService $accounting): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $reversal = $accounting->reverse($journalEntry, $data['reason'], $request->user());
        } catch (Throwable $e) {
            return back()->withErrors(['entry' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.ledger.show', [$group, $reversal])
            ->with('status', "Posted {$reversal->entry_number}, reversing {$journalEntry->entry_number}.");
    }

    public function createAdjustment(Group $group): View
    {
        return view('ledger.adjustment', [
            'group' => $group,
            'accounts' => $group->accounts()->where('is_active', true)->orderBy('code')->get(),
            'costCenters' => $group->costCenters()->orderBy('code')->get(),
            'currencies' => array_keys((array) config('fund.currencies')),
        ]);
    }

    public function storeAdjustment(Request $request, Group $group, AccountingService $accounting): RedirectResponse
    {
        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:500'],
            'reason' => ['required', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.cost_center_id' => ['nullable', 'integer'],
            'lines.*.side' => ['required', 'in:debit,credit'],
            'lines.*.currency' => ['required', 'string'],
            'lines.*.amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
            'lines.*.description' => ['nullable', 'string', 'max:200'],
        ]);

        $accounts = $group->accounts()->whereIn('id', collect($data['lines'])->pluck('account_id'))->get()->keyBy('id');
        $costCenters = $group->costCenters()->whereIn('id', collect($data['lines'])->pluck('cost_center_id')->filter())->get()->keyBy('id');

        $lines = [];

        foreach ($data['lines'] as $line) {
            /** @var ?Account $account */
            $account = $accounts->get((int) $line['account_id']);

            if (! $account) {
                return back()->withInput()->withErrors(['lines' => 'One of the chosen accounts does not belong to this fund.']);
            }

            /** @var ?CostCenter $costCenter */
            $costCenter = $line['cost_center_id'] ? $costCenters->get((int) $line['cost_center_id']) : null;

            $lines[] = [
                'account' => $account,
                'side' => $line['side'] === 'debit' ? LineDraft::SIDE_DEBIT : LineDraft::SIDE_CREDIT,
                'amount' => Money::of(Decimal::of($line['amount']), $line['currency']),
                'cost_center' => $costCenter,
                'description' => $line['description'] ?? null,
            ];
        }

        try {
            $entry = $accounting->postAdjustment(
                $group,
                $lines,
                $data['reason'],
                Carbon::parse($data['entry_date']),
                $request->user(),
                $data['description']
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.ledger.show', [$group, $entry])
            ->with('status', "Adjustment {$entry->entry_number} posted.");
    }
}
