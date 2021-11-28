<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->biginteger('invoiceid')->unsigned()->index()->comment('id Счет/УПД');
		$table->foreign('invoiceid')->references('id')->on('invoices');

            $table->biginteger('rsn_sysobjid')->unsigned()->nullable()->comment('sysobjid записи-основания');
		$table->foreign('rsn_sysobjid')->references('id')->on('sysobjs');
            $table->biginteger('rsn_objid')->unsigned()->nullable()->comment('');

            $table->string('reason',160)->nullable()->comment('Основание оплаты');

            $table->decimal('plnpaysum',12,2)->nullable()->comment('Планируемая сумма оплаты');

            $table->timestamp('fctpay_at')->nullable()->comment('Когда проведена оплата');
            $table->bigInteger('fctpay_by')->unsigned()->nullable()->comment('Регистратор проведенной оплаты');
            $table->decimal('fctpaysum',12,2)->nullable()->comment('Фактическая сумма оплаты');

            $table->string('notes',160)->nullable()->comment('Примечания');

            $table->boolean('active')->default(true);
            $table->boolean('locked')->default(false)->comment('Блокирован от изменений');

            $table->timestamp('created_at')->nullable()->useCurrent=true;
            $table->bigInteger('created_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, создавшего запись');
            $table->timestamp('updated_at')->nullable()->useCurrent=true;
            $table->bigInteger('updated_by')->nullable()->unsigned()->default(1)
                ->comment('UserID, изменившего запись');

		$table->index(['rsn_sysobjid','rsn_objid']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_payments');
    }
}
