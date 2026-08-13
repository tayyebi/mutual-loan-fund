<?php

namespace App\Http\Controllers\Fund;

use App\Domain\Reports\ReportService;
use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Transaction;
use App\Models\Treasury;
use App\Support\GroupContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Record a transaction" — a short wizard ending at the existing, unchanged
 * TransactionController::store (via StoreTransactionRequest). The single
 * page it replaces showed every field for every type at once, because
 * without JavaScript it had no way to hide what a given type doesn't use;
 * this wizard knows the chosen type from the moment step 1 redirects, so
 * later steps show only the fields that type actually needs. No type and no
 * field is removed — see the per-type mapping in details()/amount()/evidence().
 */
class TransactionWizardController extends Controller
{
    private const TYPES = [
        Transaction::TYPE_CONTRIBUTION,
        Transaction::TYPE_LOAN_REPAYMENT,
        Transaction::TYPE_TREASURY_TRANSFER,
        Transaction::TYPE_TREASURY_EXCHANGE,
        Transaction::TYPE_FEE,
    ];

    private const COUNTER_TREASURY_TYPES = [
        Transaction::TYPE_TREASURY_TRANSFER,
        Transaction::TYPE_TREASURY_EXCHANGE,
    ];

    public function type(Request $request, Group $group): View
    {
        return view('transactions.wizard-type', [
            'group' => $group,
            'type' => $request->query('type', Transaction::TYPE_CONTRIBUTION),
        ]);
    }

    public function typeStore(Request $request, Group $group): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
        ]);

        return redirect()->route('g.transactions.create.details', [$group, 'type' => $data['type']]);
    }

    public function details(Request $request, Group $group, ReportService $reports, GroupContext $context): View
    {
        $type = $request->query('type');

        return view('transactions.wizard-details', [
            'group' => $group,
            'type' => $type,
            'treasuries' => $this->treasuries($group),
            'loans' => $type === Transaction::TYPE_LOAN_REPAYMENT ? $reports->repayableLoans($context->membership()) : null,
            'treasury_id' => $request->query('treasury_id'),
            'counter_treasury_id' => $request->query('counter_treasury_id'),
            'loan_id' => $request->query('loan_id'),
        ]);
    }

    public function detailsStore(Request $request, Group $group): RedirectResponse
    {
        $type = $request->input('type');

        $rules = [
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'treasury_id' => ['required', 'integer'],
        ];

        if ($type === Transaction::TYPE_LOAN_REPAYMENT) {
            $rules['loan_id'] = ['required', 'integer'];
        }

        if (in_array($type, self::COUNTER_TREASURY_TYPES, true)) {
            $rules['counter_treasury_id'] = ['required', 'integer', 'different:treasury_id'];
        }

        $data = $request->validate($rules);

        return redirect()->route('g.transactions.create.amount', [$group, ...$data]);
    }

    public function amount(Request $request, Group $group): View
    {
        return view('transactions.wizard-amount', [
            'group' => $group,
            'type' => $request->query('type'),
            'treasury_id' => $request->query('treasury_id'),
            'counter_treasury_id' => $request->query('counter_treasury_id'),
            'loan_id' => $request->query('loan_id'),
            'treasury' => $this->findTreasury($group, $request->query('treasury_id')),
            'amount' => $request->query('amount'),
            'counter_amount' => $request->query('counter_amount'),
        ]);
    }

    public function amountStore(Request $request, Group $group): RedirectResponse
    {
        $type = $request->input('type');

        $rules = [
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'treasury_id' => ['required', 'integer'],
            'counter_treasury_id' => ['nullable', 'integer'],
            'loan_id' => ['nullable', 'integer'],
            'amount' => ['required', 'string', 'regex:/^\d+(\.\d+)?$/'],
        ];

        if ($type === Transaction::TYPE_TREASURY_EXCHANGE) {
            $rules['counter_amount'] = ['required', 'string', 'regex:/^\d+(\.\d+)?$/'];
        }

        $data = array_filter($request->validate($rules), fn ($value) => $value !== null);

        return redirect()->route('g.transactions.create.evidence', [$group, ...$data]);
    }

    public function evidence(Request $request, Group $group): View
    {
        return view('transactions.wizard-evidence', [
            'group' => $group,
            'type' => $request->query('type'),
            'treasury' => $this->findTreasury($group, $request->query('treasury_id')),
            'treasury_id' => $request->query('treasury_id'),
            'counter_treasury_id' => $request->query('counter_treasury_id'),
            'loan_id' => $request->query('loan_id'),
            'amount' => $request->query('amount'),
            'counter_amount' => $request->query('counter_amount'),
            'today' => now()->toDateString(),
        ]);
    }

    /**
     * @return Collection<int, Treasury>
     */
    private function treasuries(Group $group): Collection
    {
        return $group->treasuries()->where('status', Treasury::STATUS_ACTIVE)->orderBy('name')->get();
    }

    private function findTreasury(Group $group, mixed $treasuryId): ?Treasury
    {
        return $this->treasuries($group)->firstWhere('id', (int) $treasuryId);
    }
}
