<?php

namespace App\Http\Middleware;

use App\Models\Group;
use App\Support\GroupContext;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The tenant boundary, enforced on every group request.
 *
 *   authenticated user → group exists → active membership → object belongs to group
 *
 * The last step is the one that matters most: after route model binding, every
 * bound model carrying a group_id is checked against the group in the URL.
 * Changing an id in the address bar to reach another fund's data returns 404,
 * whatever the controller would otherwise have done with it.
 */
class ResolveGroupContext
{
    public function __construct(private readonly GroupContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $group = $request->route('group');

        if (! $group instanceof Group) {
            abort(404);
        }

        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Your account is not active.');
        }

        $membership = $user->membershipFor($group);

        // A non-member must not even learn that the group exists.
        if (! $membership || ! $membership->hasAccess()) {
            abort(404);
        }

        if (! $group->isActive()) {
            abort(403, 'This fund is suspended.');
        }

        $this->assertBoundModelsBelongToGroup($request, $group);

        $this->context->set($group, $membership);

        view()->share('groupContext', $this->context);

        return $next($request);
    }

    private function assertBoundModelsBelongToGroup(Request $request, Group $group): void
    {
        foreach ($request->route()->parameters() as $parameter) {
            if (! $parameter instanceof Model || $parameter instanceof Group) {
                continue;
            }

            $groupId = $parameter->getAttribute('group_id');

            if ($groupId !== null && (int) $groupId !== (int) $group->getKey()) {
                abort(404);
            }
        }
    }
}
