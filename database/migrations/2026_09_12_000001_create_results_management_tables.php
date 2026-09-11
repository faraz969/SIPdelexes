<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('affiliation')->nullable(); // e.g. UCC, UDS, KNUST
            $table->string('code')->unique();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('grading_scheme_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scheme_id')->constrained('grading_schemes')->onDelete('cascade');
            $table->string('grade', 10);
            $table->decimal('min_mark', 5, 2);
            $table->decimal('max_mark', 5, 2);
            $table->decimal('grade_point', 4, 2);
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();

            $table->unique(['grading_scheme_id', 'grade']);
        });

        Schema::create('grading_scheme_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grading_scheme_id')->constrained('grading_schemes')->onDelete('cascade');
            $table->string('name');
            $table->decimal('min_cgpa', 4, 2);
            $table->decimal('max_cgpa', 4, 2);
            $table->unsignedInteger('sequence')->default(0);
            $table->timestamps();
        });

        // Template components configured on the Course master.
        Schema::create('course_assessment_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('code', 50);
            $table->string('name');
            $table->enum('category', ['class', 'exam']);
            $table->decimal('max_mark', 8, 2);
            $table->decimal('contribution', 8, 2); // points toward Class(30) or Exam(70)
            $table->unsignedInteger('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['course_id', 'code']);
        });

        Schema::create('course_result_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->string('academic_year');
            $table->string('semester');
            $table->foreignId('lecturer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'submitted',
                'returned',
                'hod_approved',
                'approved',
                'published',
            ])->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->text('return_comments')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('hod_approved_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'academic_year', 'semester', 'version'], 'course_result_sheets_unique');
        });

        Schema::create('assessment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_result_sheet_id')->constrained('course_result_sheets')->onDelete('cascade');
            $table->foreignId('course_assessment_component_id')->constrained('course_assessment_components')->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->decimal('raw_mark', 8, 2)->nullable();
            $table->decimal('contribution_mark', 8, 2)->nullable();
            $table->string('special_status', 30)->nullable(); // ABS, INCOMPLETE, etc.
            $table->string('source', 30)->default('manual'); // manual, csv, correction
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(
                ['course_result_sheet_id', 'course_assessment_component_id', 'student_id'],
                'assessment_scores_unique'
            );
        });

        Schema::create('course_result_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_result_sheet_id')->constrained('course_result_sheets')->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->decimal('class_mark', 8, 2)->nullable();
            $table->decimal('exam_mark', 8, 2)->nullable();
            $table->decimal('final_mark', 8, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->decimal('grade_point', 4, 2)->nullable();
            $table->decimal('credit_hours', 5, 2)->nullable();
            $table->decimal('quality_points', 8, 2)->nullable();
            $table->string('special_status', 30)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->unique(['course_result_sheet_id', 'student_id', 'version'], 'course_result_records_unique');
        });

        Schema::create('result_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_result_sheet_id')->constrained('course_result_sheets')->onDelete('cascade');
            $table->string('stage', 40); // submit, hod, registrar, publish, return
            $table->string('action', 40); // submitted, approved, returned, published
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('result_import_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_result_sheet_id')->constrained('course_result_sheets')->onDelete('cascade');
            $table->foreignId('course_assessment_component_id')->constrained('course_assessment_components')->onDelete('cascade');
            $table->string('filename')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->enum('status', ['preview', 'committed', 'failed'])->default('preview');
            $table->timestamps();
        });

        Schema::create('result_import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('result_import_batch_id')->constrained('result_import_batches')->onDelete('cascade');
            $table->unsignedInteger('row_number')->nullable();
            $table->string('student_id_value')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_import_errors');
        Schema::dropIfExists('result_import_batches');
        Schema::dropIfExists('result_approvals');
        Schema::dropIfExists('course_result_records');
        Schema::dropIfExists('assessment_scores');
        Schema::dropIfExists('course_result_sheets');
        Schema::dropIfExists('course_assessment_components');
        Schema::dropIfExists('grading_scheme_classifications');
        Schema::dropIfExists('grading_scheme_lines');
        Schema::dropIfExists('grading_schemes');
    }
};
