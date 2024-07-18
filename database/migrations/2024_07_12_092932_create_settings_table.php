<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->float('input_cost', 3);
            $table->float('output_cost', 15);
            $table->string('api_key', '');
            $table->boolean('default_debug', false);
        });

        DB::table('settings')->insert([
            'input_cost' => 3,
            'output_cost' => 15,
            'api_key' => '',
            'default_debug' => false,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
