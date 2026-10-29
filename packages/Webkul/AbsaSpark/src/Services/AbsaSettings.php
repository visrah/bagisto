<?php

namespace Webkul\AbsaSpark\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\CacheGeneration;
use Webkul\Core\Repositories\CoreConfigRepository;

class AbsaSettings
{
    /**
     * Prefix shared by every saved Absa Spark setting.
     */
    public const PREFIX = 'sales.payment_methods.absaspark.';

    /**
     * Fiserv sandbox endpoint.
     */
    public const SANDBOX_URL = 'https://prod.emea.api.fiservapps.com/sandbox/exp/v1';

    /**
     * Fiserv production endpoint.
     */
    public const LIVE_URL = 'https://prod.emea.api.fiservapps.com/exp/v1';

    /**
     * Settings that are stored encrypted and never shown again.
     *
     * @var array<int, string>
     */
    public const SECRETS = ['api_key', 'secret'];

    /**
     * Read one saved setting.
     */
    public function get(string $key): mixed
    {
        return core()->getConfigData(self::PREFIX.$key);
    }

    /**
     * Whether the method is switched on.
     */
    public function enabled(): bool
    {
        return (bool) $this->get('active');
    }

    /**
     * The merchant's store id.
     */
    public function storeId(): ?string
    {
        return $this->text($this->get('store_id'));
    }

    /**
     * The decrypted API key.
     */
    public function apiKey(): ?string
    {
        return $this->decrypted('api_key');
    }

    /**
     * The decrypted API secret.
     */
    public function secret(): ?string
    {
        return $this->decrypted('secret');
    }

    /**
     * Whether payments go to the sandbox instead of production.
     */
    public function sandbox(): bool
    {
        $value = $this->get('sandbox');

        return $value === null || (bool) $value;
    }

    /**
     * The Fiserv endpoint for the chosen mode, unless the environment points it elsewhere, for example at a test stub.
     */
    public function baseUrl(): string
    {
        return config('absa-spark.base_url') ?: ($this->sandbox() ? self::SANDBOX_URL : self::LIVE_URL);
    }

    /**
     * Whether every credential needed to call Absa is saved.
     */
    public function isConfigured(): bool
    {
        return $this->storeId() !== null
            && $this->apiKey() !== null
            && $this->secret() !== null;
    }

    /**
     * Whether a secret setting has a saved value, without revealing it.
     */
    public function hasSecret(string $key): bool
    {
        return $this->decrypted($key) !== null;
    }

    /**
     * Save settings, encrypting secrets and keeping a saved secret when a blank is submitted.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::SECRETS, true)) {
                if ($this->text($value) === null) {
                    continue;
                }

                $value = 'enc:'.Crypt::encryptString(trim($value));
            }

            $this->store($key, $value);
        }
    }

    /**
     * Write one setting row, replacing any earlier value, and move the configuration cache on so the change is read at once.
     */
    protected function store(string $key, mixed $value): void
    {
        DB::table('core_config')->updateOrInsert(
            ['code' => self::PREFIX.$key, 'channel_code' => null, 'locale_code' => null],
            ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
        );

        CacheGeneration::bump(CoreConfigRepository::class);
    }

    /**
     * Read and decrypt a secret setting, or null when it is missing or unreadable.
     */
    protected function decrypted(string $key): ?string
    {
        $value = $this->text($this->get($key));

        if ($value === null || ! str_starts_with($value, 'enc:')) {
            return null;
        }

        try {
            return Crypt::decryptString(substr($value, 4));
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Trim a value and treat blank as missing.
     */
    protected function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
