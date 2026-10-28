<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateFirstAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-first-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactively create the first HR Admin account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (User::query()->where('role', User::ROLE_HR_ADMIN)->exists()) {
            $this->error('An HR Admin account already exists.');

            return self::FAILURE;
        }

        $name = trim((string) $this->ask('Administrator name'));
        $email = Str::lower(trim((string) $this->ask('Administrator email')));
        $password = (string) $this->secret('Password (at least 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! hash_equals($password, $confirmation)) {
            $this->error('The password confirmation does not match.');

            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role = User::ROLE_HR_ADMIN;
        $user->is_active = true;
        $user->save();

        $this->info('The initial HR Admin account was created.');

        return self::SUCCESS;
    }
}
