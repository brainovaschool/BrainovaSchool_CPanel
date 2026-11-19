# Brainova Assessment: Laravel integration kit

Targets the existing Brainova stack: Laravel, Blade, Bootstrap, jQuery and the Repository Pattern.
Nothing here assumes access to the production database. Run it in staging first.

## 1. Tables (migrations)

```php
Schema::create('assessment_questions', function (Blueprint $t) {
    $t->uuid('id')->primary(); $t->foreignId('subject_id'); $t->unsignedBigInteger('topic_id'); $t->string('objective')->nullable();
    $t->unsignedTinyInteger('grade_min')->nullable(); $t->unsignedTinyInteger('grade_max')->nullable();
    $t->enum('difficulty', ['strengthen','hard','extend']); $t->string('type'); $t->longText('body'); $t->json('options')->nullable(); $t->json('answer_key');
    $t->text('explanation')->nullable(); $t->text('marking')->nullable(); $t->unsignedSmallInteger('est_seconds')->default(45); $t->unsignedTinyInteger('marks')->default(1);
    $t->string('prerequisite_topic')->nullable(); $t->string('source')->nullable(); $t->enum('status', ['draft','approved'])->default('draft'); $t->decimal('irt_b', 4, 2)->default(0); $t->timestamps();
});
Schema::create('assessments', function (Blueprint $t) {
    $t->uuid('id')->primary(); $t->string('title'); $t->string('purpose'); $t->enum('application', ['school','course']); $t->foreignId('course_id')->nullable();
    $t->json('grades'); $t->json('topics'); $t->enum('model', ['fixed','adaptive']); $t->enum('mode', ['independent','live']); $t->json('config'); $t->enum('status', ['draft','published','closed'])->default('draft');
    $t->foreignId('created_by'); $t->timestamps();
});
Schema::create('assessment_assignments', function (Blueprint $t) {
    $t->id(); $t->uuid('assessment_id'); $t->foreignId('student_id'); $t->timestamp('due_at')->nullable(); $t->boolean('parent_confirmed')->default(false); $t->string('parent_name')->nullable(); $t->timestamp('parent_confirmed_at')->nullable();
    $t->unique(['assessment_id','student_id']);
});
Schema::create('assessment_attempts', function (Blueprint $t) {
    $t->uuid('id')->primary(); $t->uuid('assessment_id'); $t->foreignId('student_id'); $t->unsignedTinyInteger('attempt_no'); $t->timestamp('started_at'); $t->timestamp('submitted_at')->nullable();
    $t->unsignedInteger('duration_ms')->nullable(); $t->json('accommodations')->nullable(); $t->json('adaptive_trace')->nullable(); $t->boolean('released')->default(false); $t->timestamps();
});
Schema::create('assessment_responses', function (Blueprint $t) {
    $t->id(); $t->uuid('attempt_id'); $t->uuid('question_id'); $t->unsignedSmallInteger('seq'); $t->json('question_snapshot'); $t->json('answer')->nullable();
    $t->enum('status', ['correct','incorrect','skipped','unanswered','pending']); $t->decimal('marks', 5, 2)->default(0); $t->unsignedInteger('time_ms')->default(0); $t->unsignedSmallInteger('changes')->default(0); $t->string('error_pattern')->nullable();
});
Schema::create('assessment_topic_results', function (Blueprint $t) {
    $t->id(); $t->uuid('attempt_id'); $t->string('topic'); $t->unsignedSmallInteger('attempted'); $t->unsignedSmallInteger('correct'); $t->decimal('estimate', 5, 2); $t->decimal('sd', 5, 2);
    $t->unsignedTinyInteger('level_index')->nullable(); $t->string('confidence');
});
Schema::create('assessment_placements', function (Blueprint $t) {
    $t->id(); $t->uuid('attempt_id'); $t->foreignId('student_id'); $t->string('system_recommendation'); $t->string('final_recommendation')->nullable(); $t->text('teacher_notes')->nullable();
    $t->boolean('finalised')->default(false); $t->foreignId('finalised_by')->nullable(); $t->timestamp('finalised_at')->nullable();
});
Schema::create('assessment_events', function (Blueprint $t) { $t->id(); $t->uuid('attempt_id'); $t->string('type'); $t->string('note')->nullable(); $t->timestamp('occurred_at'); });
```

## 2. Routes (routes/api.php)

```php
Route::prefix('assessments/v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('assignments', [AssignmentController::class, 'index']);
    Route::post('attempts', [AttemptController::class, 'start'])->middleware('can:take,assessment');
    Route::put('attempts/{attempt}/responses', [AttemptController::class, 'saveResponses']);
    Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit']);
    Route::post('attempts/{attempt}/events', [AttemptController::class, 'event']);
    Route::get('attempts/{attempt}/report', [ReportController::class, 'show']);
    Route::middleware('role:teacher|admin')->group(function () {
        Route::apiResource('assessments', AssessmentController::class);
        Route::apiResource('questions', QuestionController::class);
        Route::post('assessments/{assessment}/publish', [AssessmentController::class, 'publish']);
        Route::post('attempts/{attempt}/release', [ReportController::class, 'release']);
        Route::put('attempts/{attempt}/placement', [PlacementController::class, 'finalise']);
        Route::post('live-sessions', [LiveSessionController::class, 'store']);
    });
});
```

## 3. Repository pattern

```php
interface AttemptRepositoryInterface {
    public function start(Assessment $a, int $studentId): Attempt;
    public function saveResponses(Attempt $at, array $responses): Attempt;
    public function submit(Attempt $at): Attempt;
    public function forStudent(int $studentId): Collection;
}
final class EloquentAttemptRepository implements AttemptRepositoryInterface { /* Eloquent queries; no business rules here */ }
// Bind in AppServiceProvider: $this->app->bind(AttemptRepositoryInterface::class, EloquentAttemptRepository::class);
```

Keep scoring, adaptive selection and analysis in services (`AdaptiveEngine`, `ScoringService`, `PlacementService`). Port the logic in this app's engine one-to-one so results match: a 3-parameter logistic model with slope 1.2, EAP estimates on a -3 to 3 grid, a topic prior centred on the overall estimate, step limits and prerequisite checks.

## 4. Security and roles

- Authenticate with the existing Laravel guard (Sanctum or Passport). Reuse the LMS users table; never duplicate accounts.
- Roles: admin, teacher, student, parent via spatie/laravel-permission. Policies: students read only their own attempts and only when `released`; parents read linked children; teachers read their classes.
- Never send answer keys to the browser during an attempt. Return the presented question without `answer_key`; score on the server.
- Store attempts on the server after every answer so a refresh or lost connection does not lose work.
- Rate-limit the attempt endpoints, log access to reports, and keep personal data to what is needed. No facial recognition or invasive monitoring.

## 5. Front end (Blade, Bootstrap, jQuery)

Embed the assessment screens as Blade views. The browser code calls the endpoints above with `$.ajax`, sending the CSRF or bearer token. Live sessions use Laravel Reverb (or Pusher) channels: `live-session.{id}` for question events and a private channel per student for answers.

## 6. PDFs and exports

Render the report Blade view with barryvdh/laravel-dompdf (or Snappy) for `?format=pdf`. CSV exports use `fastexcel` or `maatwebsite/excel` with the same columns as this app's exports.

## 7. AI question generation

Call the model from a queued job on the server (never from the browser). Save results as `draft` questions. A teacher must approve each one before it can appear in an assessment.
