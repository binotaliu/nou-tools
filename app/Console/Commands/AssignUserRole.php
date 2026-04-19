<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\search;
use function Laravel\Prompts\select;

class AssignUserRole extends Command
{
    protected $signature = 'user:assign-role
                            {email? : The email of the user}
                            {role? : The role to assign (admin, discount_store)}';

    protected $description = 'Assign a role to a user';

    public function handle(): int
    {
        $email = $this->argument('email') ?? search(
            label: 'Search for user by email',
            placeholder: 'E.g. user@example.com',
            options: fn (string $value) => strlen($value) > 0
                ? User::whereLike('email', "%{$value}%")->pluck('email')->all()
                : [],
        );

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email [{$email}] not found.");

            return self::FAILURE;
        }

        $roleValue = $this->argument('role') ?? select(
            label: 'Which role to assign?',
            options: collect(UserRole::cases())->mapWithKeys(
                fn (UserRole $r) => [$r->value => "{$r->label()} — {$r->description()}"]
            )->all(),
        );

        $role = UserRole::tryFrom($roleValue);

        if (! $role) {
            $validValues = implode(', ', array_column(UserRole::cases(), 'value'));
            $this->error("Invalid role [{$roleValue}]. Valid values: {$validValues}");

            return self::FAILURE;
        }

        if ($user->hasRole($role)) {
            $this->warn("User [{$email}] already has the [{$role->label()}] role.");

            return self::SUCCESS;
        }

        $user->addRole($role);

        $this->info("Role [{$role->label()}] assigned to user [{$email}].");
        $this->line('Current roles: '.implode(', ', array_map(
            fn (UserRole $r) => $r->label(),
            $user->getRoles(),
        )));

        return self::SUCCESS;
    }
}
