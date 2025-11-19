<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User; // Import your User model
use Illuminate\Support\Facades\Hash;

class ResetUserPassword extends Command
{
    // The name and signature of the command
    protected $signature = 'user:reset-password {email} {password}';

    // The command description
    protected $description = 'Resets the password for a given user by their email address';

    // The logic to reset the user's password
    public function handle()
    {
        // Get the email and password from the arguments
        $email = $this->argument('email');
        $newPassword = $this->argument('password');

        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error('User not found.');
            return 1;
        }

        // Update the password
        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info("Password for user '{$email}' has been reset successfully.");

        return 0;
    }
}
