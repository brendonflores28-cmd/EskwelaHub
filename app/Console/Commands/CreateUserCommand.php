<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\AsCommand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;

/**
 * Creates a staff or admin account from the terminal. This is the only way to
 * create the first admin on production, so no default password ever exists.
 */
#[AsCommand(name: 'eskwelahub:create-user', description: 'Create a staff or admin account')]
class CreateUserCommand extends Command
{
    protected $signature = 'eskwelahub:create-user
        {email : Email address (used to log in)}
        {role : staff or admin}
        {--name= : Full name}';

    public function handle(): int
    {
        $role = Role::tryFrom((string) $this->argument('role'));
        if (! in_array($role, [Role::Staff, Role::Admin], true)) {
            $this->error('Role must be "staff" or "admin".');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Full name');
        $password = password('Password (min 12 characters, hidden)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $this->argument('email'), 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
                'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $this->argument('email'),
            'password' => $password,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role->value);

        $this->info("Created {$role->label()} account for {$user->email}. Two-factor setup is required at first login.");

        return self::SUCCESS;
    }
}
