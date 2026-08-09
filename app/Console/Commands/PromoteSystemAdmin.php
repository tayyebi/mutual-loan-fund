<?php

namespace App\Console\Commands;

use App\Domain\SystemAdmin\SystemAdminService;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Bootstraps or extends the platform's system administrators. There is no
 * web-based way to grant the very first one, so this is the only path in.
 */
class PromoteSystemAdmin extends Command
{
    protected $signature = 'admin:promote {email : Email address of an already-registered user}';

    protected $description = 'Grant an existing user system administrator rights';

    public function handle(SystemAdminService $systemAdmin): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error("No user is registered with the email {$this->argument('email')}.");

            return self::FAILURE;
        }

        if ($user->isSystemAdmin()) {
            $this->info("{$user->email} is already a system administrator.");

            return self::SUCCESS;
        }

        $systemAdmin->promote($user);

        $this->info("{$user->email} is now a system administrator.");

        return self::SUCCESS;
    }
}
