<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateOperator extends Command
{
    protected $signature = 'watchpost:operator {email} {name} {--generate : Save a generated password to a private local file}';

    protected $description = 'Create an internal operator with an interactively entered password';

    public function handle(): int
    {
        $path = storage_path('app/private/operator-credentials.txt');
        if ($this->option('generate') && file_exists($path)) {
            $this->error('The credentials file already exists; refusing to overwrite it.');

            return self::FAILURE;
        }
        $password = $this->option('generate') ? bin2hex(random_bytes(20)) : $this->secret('Password (at least 14 characters)');
        $data = ['email' => $this->argument('email'), 'name' => $this->argument('name'), 'password' => $password];
        $validator = Validator::make($data, ['email' => 'required|email|max:254|unique:users,email', 'name' => 'required|string|max:255', 'password' => 'required|string|min:14']);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        if ($this->option('generate')) {
            $oldMask = umask(0077);
            try {
                $file = fopen($path, 'x');
                if (! $file) {
                    throw new \RuntimeException('Cannot create credentials file.');
                }
                try {
                    $content = 'Email: '.$data['email'].PHP_EOL.'Password: '.$password.PHP_EOL;
                    if (fwrite($file, $content) !== strlen($content)) {
                        throw new \RuntimeException('Cannot write credentials file.');
                    }
                } finally {
                    fclose($file);
                }
            } finally {
                umask($oldMask);
            }
        }
        User::create($data);
        $this->info('Operator created.');

        return self::SUCCESS;
    }
}
