<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CreateHamkAdmin extends Command
{
    protected $signature = 'hamk:create-admin {--name=} {--email=}';

    protected $description = 'Create the first HAMK Inventory administrator using a securely prompted password';

    public function handle(): int
    {
        $connection = DB::connection();
        $connection->getPdo();
        $this->line('Connected database: '.$connection->getDriverName().' / '.$connection->getDatabaseName());

        if (User::where('role', 'administrator')->exists()) {
            $this->error('An administrator already exists. Use hamk:reset-admin-password to reset an account.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Administrator name');
        $email = $this->option('email') ?: $this->ask('Administrator email');

        if (! is_string($name) || trim($name) === '' || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('A name and valid email address are required.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists()) {
            $this->error('That email address is already registered.');

            return self::FAILURE;
        }

        $password = $this->secret('Choose a password (at least 12 characters)');
        $confirmation = $this->secret('Confirm the password');
        if (! hash_equals($password, $confirmation)) {
            $this->error('The passwords did not match. No account was created.');

            return self::FAILURE;
        }
        $validator = validator(['password' => $password], ['password' => ['required', Password::min(12)]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        DB::transaction(fn () => User::create([
            'name' => trim($name),
            'email' => strtolower(trim($email)),
            'password' => Hash::make($password),
            'role' => 'administrator',
        ]));

        $this->info('Administrator created. Keep the password secure.');

        return self::SUCCESS;
    }
}
