<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advanced_orders', function (Blueprint $table) {
            $table->id();

            $table->string('area_id')->default(0)->comment('地区ID');
            $table->string('address')->default('')->comment('详细地址');
            $table->string('name')->default('')->comment('单位/用户名称');
            $table->string('phone')->default('')->comment('联系方式');
            $table->tinyInteger('customer_type')->default(0)->comment('客户类型');
            $table->integer('advanced_total_installed')->default(0)->comment('预计安装总数');
            $table->decimal('advanced_amount')->default(0)->comment('预付金额（元）');
            $table->tinyInteger('payment_type')->default(0)->comment('付款方案');
            $table->tinyInteger('income_type')->default(0)->comment('收款方式');
            $table->integer('operator_user_id')->default(0)->comment('操作人');
            $table->string('remark')->default('')->comment('收款方式');

            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent()->comment('创建时间');
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate()->useCurrent()->comment('更新时间');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('areas');
    }
};
