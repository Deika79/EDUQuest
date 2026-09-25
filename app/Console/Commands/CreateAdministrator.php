<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdministrator extends Command
{
    protected $signature = 'eduquest:create-admin';

    protected $description = 'Create the initial EDUQuest administrator interactively';

    public function handle(): int
    {
        $input = [
            'name' => trim((string) $this->ask('Full name')),
            'username' => mb_strtolower(trim((string) $this->ask('Username'))),
            'email' => mb_strtolower(trim((string) $this->ask('Email address'))),
            'password' => (string) $this->secret('Password'),
            'password_confirmation' => (string) $this->secret('Confirm password'),
        ];

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->components->error($validator->errors()->first());

            return self::FAILURE;
        }

        $administrator = new User;
        $administrator->fill($validator->validated());
        $administrator->role = UserRole::Administrator;
        $administrator->active = true;
        $administrator->must_change_password = false;
        $administrator->save();

        $this->components->info('Administrator created successfully.');

        return self::SUCCESS;
    }
}
