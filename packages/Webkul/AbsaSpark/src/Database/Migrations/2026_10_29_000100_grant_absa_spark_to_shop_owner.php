<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = DB::table('roles')->where('name', 'Shop owner')->where('permission_type', 'custom')->first();

        if (! $role) {
            return;
        }

        $permissions = json_decode((string) $role->permissions, true) ?: [];

        if (in_array('settings.absa_spark', $permissions, true)) {
            return;
        }

        $permissions[] = 'settings.absa_spark';

        DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode(array_values($permissions))]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'Shop owner')->where('permission_type', 'custom')->first();

        if (! $role) {
            return;
        }

        $permissions = array_values(array_diff(json_decode((string) $role->permissions, true) ?: [], ['settings.absa_spark']));

        DB::table('roles')->where('id', $role->id)->update(['permissions' => json_encode($permissions)]);
    }
};
