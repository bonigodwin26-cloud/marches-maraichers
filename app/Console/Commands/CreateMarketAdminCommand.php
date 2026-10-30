<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateMarketAdminCommand extends Command
{
    protected $signature = 'market:admin:create';

    protected $description = 'Créer le compte de l’équipe qui administre les marchés';

    public function handle(): int
    {
        $name = trim($this->ask('Nom de l’administrateur'));
        $email = strtolower(trim($this->ask('Adresse e-mail')));
        $password = $this->secret('Mot de passe (12 caractères minimum)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:12'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = new User;
        $admin->name = $name;
        $admin->email = $email;
        $admin->password = $password;
        $admin->email_verified_at = now();
        $admin->is_admin = true;
        $admin->save();

        $this->info("Compte administrateur créé : {$email}");

        return self::SUCCESS;
    }
}
