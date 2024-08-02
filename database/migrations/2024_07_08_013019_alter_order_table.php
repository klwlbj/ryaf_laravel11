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
        Schema::table('order', function (Blueprint $table) {
            $table->integer('advanced_order_id')->default(0)->comment('预付订单ID');
            $table->decimal('security_deposit_funds', 10, 2)->default(0)->comment('保证金金额');
            $table->decimal('order_device_funds', 10, 2)->default(0)->comment('设备费用');
            $table->unsignedInteger('order_amount_given')->default(0)->comment('赠送数量');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order', function (Blueprint $table) {
            $table->dropColumn('advanced_order_id');
            $table->dropColumn('security_deposit_funds');
        });
    }
};
