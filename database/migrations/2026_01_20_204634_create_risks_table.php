<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRisksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['operational', 'financial', 'compliance', 'strategic', 'reputational']);
            $table->integer('likelihood')->default(1); // 1-5 scale
            $table->integer('impact')->default(1); // 1-5 scale
            $table->decimal('risk_score', 3, 1)->default(1.0); // calculated: likelihood × impact
            $table->enum('status', ['identified', 'assessed', 'mitigating', 'closed'])->default('identified');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('department')->nullable();
            $table->date('identified_date');
            $table->date('target_closure_date')->nullable();
            $table->date('actual_closure_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['category', 'status']);
            $table->index(['risk_score']);
            $table->index(['owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('risks');
    }
}
