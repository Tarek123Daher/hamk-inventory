<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ResetHamkAdminPassword extends Command
{
    protected $signature = 'hamk:reset-admin-password {email : Email address of the existing administrator}';

    protected $description = 'Reset an existing HAMK administrator password without retrieving the old password';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))
            ->where('role', 'administrator')
            ->first();
        if (! $user) {
            $this->error('No administrator account was found for that email address.');

            return self::FAILURE;
        }

        $password = $this->secret('Choose a new password (at least 12 characters)');
        $confirmation = $this->secret('Confirm the new password');
        if (! hash_equals($password, $confirmation)) {
            $this->error('The passwords did not match. No changes were made.');

            return self::FAILURE;
        }

        $validator = validator(['password' => $password], ['password' => ['required', Password::min(12)]]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first('password'));

            return self::FAILURE;
        }

        $user->update(['password' => Hash::make($password)]);
        $this->info('Administrator password reset. The new password was not displayed.');

        return self::SUCCESS;
    }
}
