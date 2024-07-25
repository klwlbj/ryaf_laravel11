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
        Schema::create('other_order', function (Blueprint $table) {
            $table->id('order_id');
            $table->string('order_iid')->comment('唯一标识');
            $table->unsignedInteger('order_user_name')->comment('单位');
            $table->unsignedInteger('order_phone')->comment('手机号');
            $table->timestamp('order_prospecter_date')->nullable()->comment('发生日期');
            $table->unsignedInteger('order_delivery_number')->comment('安装数量');
            $table->tinyInteger('order_project_type')->default(0)->comment('项目类型');
            $table->tinyInteger('order_contract_type')->default(0)->comment('收款类型');
            $table->unsignedInteger('order_pay_cycle')->comment('分期数');
            $table->string('order_address')->default('')->comment('详细地址');
            $table->string('order_area_id')->default(0)->comment('地区ID');
            $table->string('order_remark')->default('')->comment('备注');

            $table->tinyInteger('order_pay_way')->comment('收款类型');
            $table->unsignedInteger('order_account_receivable')->comment('应收金额');
            $table->unsignedInteger('order_funds_received')->comment('实收金额');
            $table->timestamp('order_actual_delivery_date')->nullable()->comment('收款日期');
            $table->integer('order_operator_user_id')->default(0)->comment('操作人');

            $table->softDeletes();
            $table->timestamp('order_crt_time')->useCurrent()->comment('创建时间');
            $table->timestamp('order_upd_time')->nullable()->useCurrentOnUpdate()->useCurrent()->comment('更新时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_order');
    }
};
