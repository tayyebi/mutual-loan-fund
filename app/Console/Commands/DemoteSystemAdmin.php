<?php

namespace App\Console\Commands;

use App\Domain\SystemAdmin\SystemAdminService;
use App\Models\User;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * The CLI counterpart to PromoteSystemAdmin: revokes system administrator
 * rights, so a promotion can always be undone even without web access.
 */
class DemoteSystemAdmin extends Command
{
    protected $signature = 'admin:demote {email : Email address of a system administrator}';

    protected $description = 'Revoke a user\'s system administrator rights';

    public function handle(SystemAdminService $systemAdmin): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user is registered with the email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if (! $user->isSystemAdmin()) {
            $this->info("{$user->email} is not a system administrator.");

            return self::SUCCESS;
        }

        try {
            $systemAdmin->demote($user);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$user->email} is no longer a system administrator.");

        return self::SUCCESS;
    }
}
