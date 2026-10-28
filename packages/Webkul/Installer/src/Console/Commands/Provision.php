<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Helpers\DatabaseManager;

class Provision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bagisto:provision
        { --demo-samples : Also seed sample products, as BAGISTO_DEMO_SAMPLES=true does. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a migrated database and create the admin from BAGISTO_ADMIN_PASSWORD and BAGISTO_ADMIN_EMAIL (default admin@example.com). Does nothing once an admin exists, so it is safe to run on every release.';

    /**
     * Make the shop ready to use without the web installer, which needs a writable `.env` and `storage/`.
     */
    public function handle(DatabaseManager $databaseManager): int
    {
        if ($databaseManager->isInstalled()) {
            $this->components->info('Bagisto is already provisioned.');

            return self::SUCCESS;
        }

        $email = (string) env('BAGISTO_ADMIN_EMAIL', 'admin@example.com') ?: 'admin@example.com';
        $password = (string) env('BAGISTO_ADMIN_PASSWORD');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
            $this->components->error('Set a BAGISTO_ADMIN_PASSWORD of at least 8 characters (and a valid BAGISTO_ADMIN_EMAIL if you set one).');

            return self::FAILURE;
        }

        // Seeding over existing shop data would collide on ids; an admin-less database that holds data needs a person.
        if (DB::table('channels')->exists()) {
            $this->components->error('The database has shop data but no admin. Refusing to seed over it.');

            return self::FAILURE;
        }

        $locale = (string) env('APP_LOCALE', 'en');
        $currency = (string) env('APP_CURRENCY', 'USD');

        $seeded = $databaseManager->seed([
            'default_locale' => $locale,
            'allowed_locales' => [$locale],
            'default_currency' => $currency,
            'allowed_currencies' => [$currency],
            'skip_admin_creation' => true,
        ]);

        if (! $seeded || ! $databaseManager->createAdminUser([
            'name' => (string) env('BAGISTO_ADMIN_NAME', 'Admin'),
            'email' => $email,
            'password' => $password,
        ])) {
            $this->components->error('Seeding failed; see the log.');

            return self::FAILURE;
        }

        if ($this->option('demo-samples') || filter_var(env('BAGISTO_DEMO_SAMPLES'), FILTER_VALIDATE_BOOLEAN)) {
            $databaseManager->seedSampleProducts([
                'default_locale' => $locale,
                'allowed_locales' => [$locale],
                'default_currency' => $currency,
                'allowed_currencies' => [$currency],
            ]);
        }

        $this->components->info("Bagisto provisioned. Admin: {$email}");

        return self::SUCCESS;
    }
}
