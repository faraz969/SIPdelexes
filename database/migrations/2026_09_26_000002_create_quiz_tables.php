<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('lecturer_id')->nullable()->constrained('lecturers')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('academic_year');
            $table->string('semester');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->decimal('total_marks', 8, 2)->default(0);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('closes_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('show_score_to_student')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'academic_year', 'semester']);
            $table->index(['is_published', 'opens_at', 'closes_at']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'single_choice',
                'multiple_choice',
                'true_false',
                'fill_blank',
                'short_answer',
                'essay',
                'code',
            ]);
            $table->text('question_text');
            $table->decimal('marks', 8, 2)->default(1);
            $table->unsignedInteger('sequence')->default(0);
            $table->text('correct_answer')->nullable(); // fill_blank / true_false / optional short key
            $table->text('code_language')->nullable(); // for code questions hint
            $table->timestamps();

            $table->index(['quiz_id', 'sequence']);
        });

        Schema::create('quiz_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->onDelete('cascade');
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->enum('status', ['in_progress', 'submitted', 'graded'])->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->text('lecturer_feedback')->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'student_id', 'attempt_number'], 'quiz_attempts_unique');
            $table->index(['quiz_id', 'status']);
        });

        Schema::create('quiz_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->onDelete('cascade');
            $table->foreignId('quiz_question_id')->constrained('quiz_questions')->onDelete('cascade');
            $table->json('selected_option_ids')->nullable();
            $table->longText('answer_text')->nullable();
            $table->decimal('marks_awarded', 8, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->boolean('is_auto_graded')->default(false);
            $table->text('grader_comment')->nullable();
            $table->timestamps();

            $table->unique(['quiz_attempt_id', 'quiz_question_id'], 'quiz_attempt_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempt_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_question_options');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
