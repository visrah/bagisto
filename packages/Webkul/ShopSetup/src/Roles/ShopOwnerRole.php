<?php

namespace Webkul\ShopSetup\Roles;

use Illuminate\Support\Facades\DB;
use Webkul\User\Repositories\RoleRepository;

class ShopOwnerRole
{
    /**
     * Name of the role a shop's owner works under.
     */
    public const string NAME = 'Shop owner';

    /**
     * Permissions the role leaves out, each with everything beneath it: the technical settings a single shop does not need.
     *
     * @var array<int, string>
     */
    protected const array HIDDEN = [
        'sales.transactions',
        'sales.bookings',
        'sales.rma',
        'catalog.attributes',
        'catalog.families',
        'customers.groups',
        'customers.gdpr_requests',
        'marketing.communications',
        'marketing.search_seo',
        'marketing.promotions.catalog_rules',
        'settings.locales',
        'settings.currencies',
        'settings.exchange_rates',
        'settings.inventory_sources',
        'settings.channels',
        'settings.roles',
        'settings.users',
        'settings.data_transfer',
        'settings.taxes.tax_categories',
    ];

    /**
     * Create a new shop owner role.
     */
    public function __construct(protected RoleRepository $roles) {}

    /**
     * Every permission the role is given: all the application knows, except the hidden ones and what lies beneath them.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return collect(config('acl'))
            ->pluck('key')
            ->reject(fn (string $key) => $this->isHidden($key))
            ->values()
            ->all();
    }

    /**
     * Whether a permission is left out of the role.
     */
    public function isHidden(string $key): bool
    {
        foreach (self::HIDDEN as $hidden) {
            if ($key === $hidden || str_starts_with($key, $hidden.'.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The permissions that must exist for the hidden list to mean what it says.
     *
     * @return array<int, string>
     */
    public static function hiddenPermissions(): array
    {
        return self::HIDDEN;
    }

    /**
     * Create the role, or bring its permissions up to date, and give it to the admin.
     */
    public function assignTo(int $adminId): void
    {
        $attributes = [
            'name' => self::NAME,
            'description' => 'Runs the shop day to day: products, orders, customers, content, discounts, appearance, tax and shipping.',
            'permission_type' => 'custom',
            'permissions' => $this->permissions(),
        ];

        $role = $this->roles->findOneByField('name', self::NAME);

        $role = $role ? $this->roles->update($attributes, $role->id) : $this->roles->create($attributes);

        DB::table('admins')->where('id', $adminId)->update(['role_id' => $role->id]);
    }
}
