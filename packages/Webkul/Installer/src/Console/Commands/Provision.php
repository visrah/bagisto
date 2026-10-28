<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\ShopDefaults;

class Provision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bagisto:provision
        { --demo-samples : Also seed sample products, as BAGISTO_DEMO_SAMPLES=true does. }
        { --migrate : Run the migrations first, holding a database lock so replicas booting together do not race. }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed a migrated database and create the admin from BAGISTO_ADMIN_PASSWORD and BAGISTO_ADMIN_EMAIL (default admin@example.com). Does nothing once an admin exists, so it is safe to run on every release.';

    /**
     * Make the shop ready to use without the web installer, which needs a writable `.env` and `storage/`.
     *
     * Two replicas migrating one database corrupt it, because MySQL DDL auto-commits and a half-run migration is not rolled back, so the migration runs under a lock.
     */
    public function handle(DatabaseManager $databaseManager): int
    {
        if (! $this->option('migrate')) {
            return $this->provision($databaseManager);
        }

        $lockName = 'bagisto_provision_'.DB::getDatabaseName();

        if ((int) DB::selectOne('SELECT GET_LOCK(?, 120) AS locked', [$lockName])->locked !== 1) {
            $this->components->error('Could not acquire the provision lock within 120s.');

            return self::FAILURE;
        }

        try {
            $this->call('migrate', ['--force' => true]);

            return $this->provision($databaseManager);
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        }
    }

    /**
     * Seed a migrated database and create the admin, doing nothing once an admin exists.
     *
     * A database that holds shop data but no admin is left alone, because seeding over it would collide on ids and needs a person to decide.
     */
    protected function provision(DatabaseManager $databaseManager): int
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

        if (DB::table('channels')->exists()) {
            $this->components->error('The database has shop data but no admin. Refusing to seed over it.');

            return self::FAILURE;
        }

        $locale = (string) env('APP_LOCALE', 'en');
        $currency = (string) env('APP_CURRENCY', 'USD');

        $provisioned = $this->provisionAtomically($databaseManager, [
            'default_locale' => $locale,
            'allowed_locales' => [$locale],
            'default_currency' => $currency,
            'allowed_currencies' => [$currency],
            'skip_admin_creation' => true,
        ], [
            'name' => (string) env('BAGISTO_ADMIN_NAME', 'Admin'),
            'email' => $email,
            'password' => $password,
        ]);

        if (! $provisioned) {
            $this->components->error('Seeding failed and was rolled back; see the log.');

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

    /**
     * Seed, create the admin and write the shop defaults in one transaction, so a failure leaves no half-built shop behind.
     *
     * The seeders report their own failures and return false instead of throwing, so the outcome decides between commit and rollback.
     *
     * @param  array<string, mixed>  $seed
     * @param  array{name: string, email: string, password: string}  $admin
     */
    protected function provisionAtomically(DatabaseManager $databaseManager, array $seed, array $admin): bool
    {
        DB::beginTransaction();

        try {
            $provisioned = $databaseManager->seed($seed) && $databaseManager->createAdminUser($admin);

            if ($provisioned) {
                app(ShopDefaults::class)->apply([
                    'country' => env('SHOP_COUNTRY'),
                    'store_name' => env('SHOP_STORE_NAME'),
                    'vat_number' => env('SHOP_TAX_ID'),
                ]);
            }
        } catch (Throwable $exception) {
            report($exception);

            $provisioned = false;
        }

        $provisioned ? DB::commit() : DB::rollBack();

        return $provisioned;
    }
}
