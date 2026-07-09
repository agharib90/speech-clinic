<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settings') || ! Schema::hasColumn('settings', 'currency')) {
            return;
        }

        $query = DB::table('settings')
            ->where('currency', 'ر.س');

        if (Schema::hasColumn('settings', 'currency_symbol')) {
            $query->where('currency_symbol', 'ج.م');
        }

        $query->update(['currency' => 'ج.م']);
    }

    public function down(): void
    {
        // Intentionally left blank: this migration corrects a display symbol only.
    }
};
