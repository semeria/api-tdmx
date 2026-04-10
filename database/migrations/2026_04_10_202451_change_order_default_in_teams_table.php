<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('teams')->whereNull('order')->orWhere('order', '!=', 1)->update(['order' => 1]);
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('order')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('order')->default(0)->change();
        });
    }
};
