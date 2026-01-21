<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskAssessmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('risk_id')->constrained('risks')->onDelete('cascade');
            $table->foreignId('assessor_id')->constrained('users')->onDelete('cascade');
            $table->integer('likelihood_before')->nullable(); // 1-5 scale
            $table->integer('impact_before')->nullable(); // 1-5 scale
            $table->decimal('risk_score_before', 3, 1)->nullable();
            $table->integer('likelihood_after'); // 1-5 scale
            $table->integer('impact_after'); // 1-5 scale
            $table->decimal('risk_score_after', 3, 1); // calculated
            $table->text('assessment_notes')->nullable();
            $table->date('assessment_date');
            $table->timestamps();
            
            $table->index(['risk_id', 'assessment_date']);
            $table->index(['assessor_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('risk_assessments');
    }
}
