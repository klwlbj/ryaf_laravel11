<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pre_installation', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('name');
            $table->unsignedInteger('installation_count');
            $table->date('registration_date');
            $table->string('address');
            $table->string('handwritten_address')->default('');
            $table->string('address_code', 50);
            $table->ipAddress('ip_address');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_installation');
    }
};
