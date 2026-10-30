<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seconds to wait before this line fires during rehearsal.
        // Null means no wait (or the lead-in, for the first line).
        Schema::table('recordings', function (Blueprint $table) {
            $table->decimal('delay_seconds', 4, 1)->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('recordings', function (Blueprint $table) {
            $table->dropColumn('delay_seconds');
        });
    }
};
