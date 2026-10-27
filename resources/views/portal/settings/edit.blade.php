@extends('backend.master')
@section('title')
    {{ $data['title'] }}
@endsection
@section('content')
    @php($settings = $data['settings'])
    <div class="page-content">

        <div class="page-header">
            <div class="row">
                <div class="col-sm-6">
                    <h4 class="bradecrumb-title mb-1">{{ $data['title'] }}</h4>
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
                        <li class="breadcrumb-item">Team Portal Settings</li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="card ot-card">
            <div class="card-body">
                <form action="{{ route('portal-settings.update') }}" method="post">
                    @csrf

                    <h4>Revision score table</h4>
                    <p class="text-secondary">Score out of 5 given for how many times a task was sent back before it was approved. The last box covers "that many or more". Changing this re-scores every already-completed task.</p>
                    <div class="row g-2 mb-4">
                        @foreach ($settings->revision_scores as $i => $score)
                            <div class="col-auto">
                                <label class="form-label">{{ $i }} revision{{ $i == 1 ? '' : 's' }}{{ $i == count($settings->revision_scores) - 1 ? '+' : '' }}</label>
                                <input type="number" name="revision_scores[]" class="form-control ot-input" min="0" max="5" value="{{ $score }}" style="width:90px">
                            </div>
                        @endforeach
                    </div>

                    <h4 class="mt-4">Work log — day template</h4>
                    <p class="text-secondary">Used to pre-fill each employee's daily work log with one-hour rows.</p>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Day starts at</label>
                            <input type="time" name="day_start" class="form-control ot-input" value="{{ $settings->day_start }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Work hours per day</label>
                            <input type="number" step="0.5" min="1" name="work_day_hours" class="form-control ot-input" value="{{ $settings->work_day_hours }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Lunch after (hours worked)</label>
                            <input type="number" step="0.5" min="0" name="lunch_after_hours" class="form-control ot-input" value="{{ $settings->lunch_after_hours }}">
                        </div>
                    </div>

                    <h4>Standard activities</h4>
                    <p class="text-secondary">One per line — shown in the work log dropdown alongside an employee's assigned tasks.</p>
                    <textarea data-list-for="activities" class="form-control ot-input mb-2" rows="5">{{ implode("\n", $settings->activities ?: \App\Models\Portal\PortalSetting::DEFAULT_ACTIVITIES) }}</textarea>
                    <div id="activitiesInputs">
                        @foreach ($settings->activities ?: \App\Models\Portal\PortalSetting::DEFAULT_ACTIVITIES as $activity)
                            <input type="hidden" name="activities[]" value="{{ $activity }}">
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-lg ot-btn-primary mt-3">Save Settings</button>
                </form>
            </div>
        </div>

        <div class="card ot-card mt-24">
            <div class="card-body">
                <h4>Task categories &amp; SOPs</h4>
                <p class="text-secondary">Each category can carry its own instructions (text and/or a file) - whoever
                    picks up a task in that category sees them. Add, rename, or delete categories here; the category
                    picker on the task form uses this same list.</p>

                <form action="{{ route('portal-settings.task-categories') }}" method="post" enctype="multipart/form-data">
                    @csrf

                    <div id="taskCategoriesContainer">
                        @foreach ($data['taskCategories'] as $cat)
                            <div class="task-category-row mb-4 pb-4 border-bottom">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="categories[{{ $cat->id }}][name]" value="{{ $cat->name }}" class="form-control ot-input" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">SOP instructions</label>
                                        <textarea name="categories[{{ $cat->id }}][sop_text]" class="form-control ot-input" rows="2">{{ $cat->sop_text }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">SOP file (optional)</label>
                                        @php($fileInputId = 'cat_' . $cat->id . '_sop_file')
                                        <div class="ot_fileUploader left-side mb-1">
                                            <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                                            <button class="primary-btn-small-input" type="button">
                                                <label class="btn btn-sm ot-btn-primary" for="{{ $fileInputId }}">Browse</label>
                                                <input type="file" class="d-none bn-file-input" id="{{ $fileInputId }}" name="categories[{{ $cat->id }}][sop_file]">
                                            </button>
                                        </div>
                                        @if ($cat->sopUpload)
                                            <a href="{{ globalAsset($cat->sopUpload->path) }}" target="_blank">Current file</a>
                                        @endif
                                    </div>
                                </div>
                                <label class="d-inline-flex align-items-center gap-2 mt-2">
                                    <input type="checkbox" name="categories[{{ $cat->id }}][delete]" value="1" class="delete-category-checkbox">
                                    <span class="text-danger">Delete this category</span>
                                </label>
                            </div>
                        @endforeach
                    </div>

                    <div id="newTaskCategoriesContainer"></div>

                    <button type="button" id="addTaskCategoryBtn" class="btn btn-outline-secondary mb-3">+ Add a category</button>
                    <br>
                    <button type="submit" class="btn btn-lg ot-btn-primary">Save Categories</button>
                </form>
            </div>
        </div>
    </div>

    <template id="newTaskCategoryTemplate">
        <div class="task-category-row mb-4 pb-4 border-bottom">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="new_categories[__INDEX__][name]" value="" class="form-control ot-input" placeholder="Category name" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label">SOP instructions</label>
                    <textarea name="new_categories[__INDEX__][sop_text]" class="form-control ot-input" rows="2"></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">SOP file (optional)</label>
                    <div class="ot_fileUploader left-side mb-1">
                        <input class="form-control ot-input bn-file-placeholder" type="text" placeholder="No file chosen" readonly>
                        <button class="primary-btn-small-input" type="button">
                            <label class="btn btn-sm ot-btn-primary" for="new_cat___INDEX___sop_file">Browse</label>
                            <input type="file" class="d-none bn-file-input" id="new_cat___INDEX___sop_file" name="new_categories[__INDEX__][sop_file]">
                        </button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm mt-2 remove-new-task-category">Remove this category</button>
        </div>
    </template>

    <script>
        // Keep each list's hidden [] inputs in sync with its textarea so the
        // backend always receives a clean array, one item per line.
        document.querySelectorAll('textarea[data-list-for]').forEach(function (textarea) {
            var name = textarea.getAttribute('data-list-for');
            var container = document.getElementById('activitiesInputs');
            textarea.addEventListener('input', function () {
                container.innerHTML = '';
                this.value.split("\n").map(function (v) { return v.trim(); }).filter(Boolean).forEach(function (v) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name + '[]';
                    input.value = v;
                    container.appendChild(input);
                });
            });
        });

        (function () {
            document.addEventListener('change', function (e) {
                if (e.target.classList.contains('delete-category-checkbox')) {
                    e.target.closest('.task-category-row').style.opacity = e.target.checked ? '0.4' : '1';
                }
            });

            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-new-task-category')) {
                    e.target.closest('.task-category-row').remove();
                }
            });

            var newCategoryIndex = 0;
            document.getElementById('addTaskCategoryBtn').addEventListener('click', function () {
                var html = document.getElementById('newTaskCategoryTemplate').innerHTML.split('__INDEX__').join(newCategoryIndex);
                var wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                document.getElementById('newTaskCategoriesContainer').appendChild(wrapper.firstElementChild);
                newCategoryIndex++;
            });

            // main.js hides every input[type=file] on every page - clicking
            // the visible "Browse" <label> still opens the picker natively
            // via its for="", so this only needs to mirror the chosen
            // filename into the readonly text box next to it. Delegated so
            // it also covers File fields added later via "+ Add a category".
            document.addEventListener('change', function (e) {
                if (!e.target.classList.contains('bn-file-input')) return;
                var wrapper = e.target.closest('.ot_fileUploader');
                var placeholder = wrapper ? wrapper.querySelector('.bn-file-placeholder') : null;
                if (placeholder) {
                    placeholder.value = e.target.files.length ? e.target.files[0].name : '';
                }
            });
        })();
    </script>
@endsection
