{{-- backend/homework/quiz_questions_modal.blade.php --}}
{{-- 
    Renders the modal content for viewing a homework quiz's questions.
    Data source: homework_quiz_questions table (Brainova custom).
    This is COMPLETELY SEPARATE from the online-exam question_banks table.
--}}
<div class="modal-content" id="modalWidth">
    <div class="modal-header modal-header-image">
        <h5 class="modal-title">
            <i class="fa-solid fa-list-check"></i>
            Quiz Questions — {{ $homework->title ?? 'Homework Quiz' }}
        </h5>
        <button type="button"
                class="m-0 btn-close d-flex justify-content-center align-items-center"
                data-bs-dismiss="modal" aria-label="Close">
            <i class="fa fa-times text-white"></i>
        </button>
    </div>
    <div class="modal-body p-4">
        @if ($questions->isEmpty())
            <div class="alert alert-warning">
                No questions found for this quiz. Please check the uploaded CSV.
            </div>
        @else
            <div class="table-responsive">
                @if (dashboard_feature_enabled('teacher', 'quiz_skill_tagging'))
                    <p class="text-muted small mb-3">Tagging a question with a Skill lets it feed the skill-mastery system when a student answers it — optional, and doesn't change how the quiz is scored.</p>
                @endif
                <table class="table ot-table-bg table-bordered">
                    <thead class="thead">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Question</th>
                            <th>Options</th>
                            <th>Correct Answer</th>
                            <th>Hint</th>
                            @if (dashboard_feature_enabled('teacher', 'quiz_skill_tagging'))
                                <th style="width:200px;">Skill</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="tbody">
                        @foreach ($questions as $i => $q)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td><strong>{{ $q->question }}</strong></td>
                            <td>
                                <ol type="A" class="mb-0 ps-3">
                                    <li>{{ $q->option_a }}</li>
                                    <li>{{ $q->option_b }}</li>
                                    <li>{{ $q->option_c }}</li>
                                    <li>{{ $q->option_d }}</li>
                                </ol>
                            </td>
                            <td>
                                <span class="badge bg-success text-white px-2 py-1">
                                    {{ $q->correct_answer }}
                                </span>
                            </td>
                            <td class="text-muted small">
                                {{ $q->hint ?? '—' }}
                            </td>
                            @if (dashboard_feature_enabled('teacher', 'quiz_skill_tagging'))
                                <td>
                                    <select class="form-control form-control-sm quiz-question-skill" data-question-id="{{ $q->id }}">
                                        <option value="">Not tagged</option>
                                        @foreach ($skills ?? [] as $skill)
                                            <option value="{{ $skill->id }}" {{ ($q->skill_id ?? null) == $skill->id ? 'selected' : '' }}>{{ $skill->title }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary py-2 px-4"
                data-bs-dismiss="modal">Close</button>
    </div>
</div>

<script>
(function () {
    // Inline (not @push) since this modal's content is loaded as an AJAX
    // fragment, not part of the main page's initial Blade render.
    document.querySelectorAll('.quiz-question-skill').forEach(function (select) {
        select.addEventListener('change', function () {
            fetch('{{ route("homework.quiz-question.skill") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    question_id: select.getAttribute('data-question-id'),
                    skill_id: select.value,
                }),
            });
        });
    });
})();
</script>

