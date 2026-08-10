<?php

namespace App\Http\Controllers;

use App\Domain\Frameworks\FrameworkComplianceChecker;
use App\Domain\Policies\Exceptions\PolicyValidationException;
use App\Domain\Policies\PolicyService;
use App\Models\Group;
use App\Models\GroupPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * Versioned group policies.
 *
 * Saving a draft and publishing it are deliberately separate actions: a draft
 * changes nothing, and publication is an explicit, confirmed, audited step.
 */
class PolicyController extends Controller
{
    /**
     * Version history and drafts — administrative by construction, since this
     * whole controller now sits behind /g. Members read the rules in force at
     * /u/{group}/fund/rules, served by App\Http\Controllers\Member\FundController.
     */
    public function index(Group $group, PolicyService $policies): View
    {
        return view('policies.index', [
            'group' => $group,
            'versions' => $policies->history($group),
            'active' => $policies->activePolicy($group),
            'draft' => $policies->draftPolicy($group),
        ]);
    }

    public function store(Group $group, PolicyService $policies): RedirectResponse
    {
        $this->authorize('create', GroupPolicy::class);

        try {
            $draft = $policies->createDraft($group, request()->user());
        } catch (RuntimeException $e) {
            return back()->withErrors(['policy' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.policies.edit', [$group, $draft->version])
            ->with('status', "Draft v{$draft->version} created from the active policy.");
    }

    public function show(Group $group, GroupPolicy $policy, FrameworkComplianceChecker $frameworkChecker): View
    {
        $this->authorize('view', $policy);

        return view('policies.show', [
            'group' => $group,
            'policy' => $policy->load(['creator', 'publisher']),
            'config' => $policy->toPolicyConfig(),
            'usage' => [
                'loans' => $policy->loans()->count(),
                'transactions' => $policy->transactions()->count(),
            ],
            'framework_warnings' => $frameworkChecker->check($policy->toPolicyConfig(), $group->financialFramework),
        ]);
    }

    public function edit(Group $group, GroupPolicy $policy, PolicyService $policies, FrameworkComplianceChecker $frameworkChecker): View
    {
        $this->authorize('update', $policy);

        return view('policies.edit', [
            'group' => $group,
            'policy' => $policy,
            'config' => $policy->toPolicyConfig(),
            'errors_from_validator' => $policies->validate($policy->toPolicyConfig()),
            'framework_warnings' => $frameworkChecker->check($policy->toPolicyConfig(), $group->financialFramework),
        ]);
    }

    public function update(Request $request, Group $group, GroupPolicy $policy, PolicyService $policies): RedirectResponse
    {
        $this->authorize('update', $policy);

        // Only known category/field keys survive: PolicyConfig::merged ignores
        // anything else, so a crafted request cannot inject configuration.
        $input = (array) $request->input('config', []);

        $policies->updateDraft($policy, $input, $request->user());

        $errors = $policies->validate($policy->refresh()->toPolicyConfig());

        return redirect()
            ->route('g.policies.edit', [$group, $policy->version])
            ->with('status', $errors === []
                ? 'Draft saved. It affects nothing until you publish it.'
                : 'Draft saved, but it cannot be published until the problems below are fixed.')
            ->withErrors($errors);
    }

    public function destroy(Group $group, GroupPolicy $policy, PolicyService $policies): RedirectResponse
    {
        $this->authorize('delete', $policy);

        $policies->deleteDraft($policy, request()->user());

        return redirect()
            ->route('g.policies.index', $group)
            ->with('status', "Draft v{$policy->version} deleted.");
    }

    /**
     * The confirmation screen: what changes, and what stays as it was.
     */
    public function confirmPublish(Group $group, GroupPolicy $policy, PolicyService $policies, FrameworkComplianceChecker $frameworkChecker): View
    {
        $this->authorize('publish', $policy);

        return view('policies.publish', [
            'group' => $group,
            'policy' => $policy,
            'changes' => $policies->pendingChanges($policy),
            'active' => $policies->activePolicy($group),
            'errors_from_validator' => $policies->validate($policy->toPolicyConfig()),
            'framework_warnings' => $frameworkChecker->check($policy->toPolicyConfig(), $group->financialFramework),
        ]);
    }

    public function publish(Request $request, Group $group, GroupPolicy $policy, PolicyService $policies): RedirectResponse
    {
        $this->authorize('publish', $policy);

        // Publication is never a side effect of saving: it needs its own,
        // deliberate confirmation.
        $request->validate(['confirm' => ['accepted']]);

        try {
            $policies->publish($policy, $request->user());
        } catch (PolicyValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (RuntimeException $e) {
            return back()->withErrors(['policy' => $e->getMessage()]);
        }

        return redirect()
            ->route('g.policies.index', $group)
            ->with('status', "Policy v{$policy->version} is active. Existing operations keep the version they were created under.");
    }

    public function compare(Group $group, GroupPolicy $policy, int $other, PolicyService $policies): View
    {
        $this->authorize('view', $policy);

        $comparand = $group->policies()->where('version', $other)->firstOrFail();

        $this->authorize('view', $comparand);

        return view('policies.compare', [
            'group' => $group,
            'from' => $comparand,
            'to' => $policy,
            'changes' => $policies->compare($comparand, $policy),
        ]);
    }
}
