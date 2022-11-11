<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('e_search_updates', function (Blueprint $table) {
            $table->id();

            $table->string('connection_name');

            $table->string('resource');

            $table->string('type');

            $table->string('name');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::drop('e_search_updates');
    }
};
