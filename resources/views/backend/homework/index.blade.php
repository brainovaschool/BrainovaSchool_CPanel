@extends('backend.master')
@section('title') {{ @$data['title'] }} @endsection

@push('css')
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ global_asset('backend/assets/css/homework-teacher-gamified.css') }}">
@endpush

@section('content')
<div class="page-content homework-index-page hw-gamified">

  <input type="hidden" id="url" value="{{ url('/') }}">

  <div class="hw-game-hero mt-20">
    <div class="hw-game-hero-inner row align-items-start g-3">
      <div class="col-lg">
        <p class="hw-game-eyebrow">Mission control</p>
        <h1 class="hw-game-title">{{ @$data['title'] }}</h1>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
          <span class="hw-game-badge"><i class="fa-solid fa-trophy"></i> Level up your classes</span>
        </div>
        <p class="hw-game-lead">
          Pick your arena (filters), deploy to load charts and the quest log, then review submissions like a pro.
        </p>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ ___('common.home') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ @$data['title'] }}</li>
          </ol>
        </nav>
      </div>
      @if(hasPermission('homework_create'))
      <div class="col-lg-auto">
        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
          <a href="{{ route('homework.create') }}" class="btn btn-lg ot-btn-primary">
            <span><i class="fa-solid fa-plus"></i></span>
            <span>{{ ___('common.add') }}</span>
          </a>
        </div>
      </div>
      @endif
    </div>
  </div>

  <div class="table-content table-basic mt-20">
    <div class="card ot-card hw-mission-panel">
      <div class="card-header">
        <span class="hw-section-chip"><i class="fa-solid fa-crosshairs"></i> Stage 1</span>
        <h4 class="hw-mission-title mb-0">{{ ___('common.filter') }}</h4>
        <p class="hw-mission-desc">Choose class, section, subject, and task type—then <strong>Deploy</strong> to reveal charts and the live quest log.</p>
      </div>
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-6 col-md-6 col-xl-3">
            <label class="filter-label" for="filter-class">{{ ___('academic.class') }}</label>
            <select id="filter-class" class="form-select form-select-sm" autocomplete="off">
              <option value="">All Classes</option>
              @foreach($data['classes'] ?? [] as $item)
                @if(!empty($item->class))
                  <option value="{{ $item->class->id }}">{{ $item->class->name }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-6 col-xl-3">
            <label class="filter-label" for="filter-section">{{ ___('academic.section') }}</label>
            <select id="filter-section" class="form-select form-select-sm" autocomplete="off">
              <option value="">All Sections</option>
            </select>
          </div>
          <div class="col-6 col-md-6 col-xl-3">
            <label class="filter-label" for="filter-subject">{{ ___('academic.subject') }}</label>
            <select id="filter-subject" class="form-select form-select-sm" autocomplete="off">
              <option value="">All Subjects</option>
            </select>
          </div>
          <div class="col-6 col-md-6 col-xl-3">
            <label class="filter-label" for="filter-task-type">Task type</label>
            <select id="filter-task-type" class="form-select form-select-sm" autocomplete="off">
              <option value="all">All Types</option>
              <option value="quiz">Quiz</option>
              <option value="homework">Homework</option>
              <option value="project">Project</option>
              <option value="activity">Activity</option>
              <option value="game">Game</option>
              <option value="assignment">Assignment</option>
            </select>
          </div>
          <div class="col-12 col-lg-auto ms-lg-auto d-flex gap-2 justify-content-lg-end">
            <button type="button" class="btn ot-btn-primary hw-btn-deploy" id="proceed-btn" title="{{ ___('common.submit') }}">
              <i class="fa-solid fa-rocket me-1"></i>Deploy
            </button>
            <button type="button" class="btn btn-outline-secondary hw-btn-reset" id="reset-filters-btn" title="{{ ___('common.reset') }}">
              <i class="fa-solid fa-rotate-left"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="results-container" class="homework-results-panel mt-20" style="display:none">

    <div class="row g-3 mb-3">
      <div class="col-md-6">
        <div class="card ot-card h-100 hw-chart-mission">
          <div class="card-header border-0 hw-chart-mission__header">
            <div class="hw-chart-mission__icon" aria-hidden="true">
              <i class="fa-solid fa-chart-pie"></i>
            </div>
            <div class="hw-chart-mission__text">
              <p class="hw-chart-mission__eyebrow">Hand-ins</p>
              <h5 class="hw-chart-mission__title">Submission snapshot</h5>
              <p class="hw-chart-mission__desc">Submitted, still waiting, and overdue—one ring chart for your current filters.</p>
            </div>
          </div>
          <div class="card-body hw-chart-mission__canvas">
            <canvas id="donut-chart-filtered"></canvas>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card ot-card h-100 hw-chart-mission">
          <div class="card-header border-0 hw-chart-mission__header">
            <div class="hw-chart-mission__icon hw-chart-mission__icon--line" aria-hidden="true">
              <i class="fa-solid fa-chart-line"></i>
            </div>
            <div class="hw-chart-mission__text">
              <p class="hw-chart-mission__eyebrow">Momentum</p>
              <h5 class="hw-chart-mission__title">Scores &amp; participation</h5>
              <p class="hw-chart-mission__desc">Average marks trend with the share of students who submitted—spot engagement dips early.</p>
            </div>
          </div>
          <div class="card-body hw-chart-mission__canvas">
            <canvas id="line-chart-filtered"></canvas>
          </div>
        </div>
      </div>
    </div>

    <div class="table-content table-basic hw-quest-log-wrap">
      <div class="card ot-card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <span class="hw-section-chip"><i class="fa-solid fa-scroll"></i> Stage 2</span>
            <h4 class="mb-0">{{ @$data['title'] }} — quest log</h4>
            <div class="hw-eval-column mt-2 w-100">
              <div id="hw-evaluation-status" class="hw-evaluation-status" role="status" aria-live="polite"></div>
              <p class="hw-eval-legend small mb-0 mt-2">
                <strong>Need your marks</strong> = students who already handed in work. <strong>Haven&rsquo;t submitted</strong> = still waiting on the student (not your grading queue). Use the orange <strong>Grade</strong> button to mark work.
              </p>
            </div>
          </div>
          <span id="table-count-badge" class="badge bg-secondary rounded-pill px-3 py-2">— records</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-bordered religion-table mb-0" id="hw-quest-log-table">
              <thead class="thead">
                <tr>
                  <th class="serial" style="width:48px">#</th>
                  <th class="hw-sortable" data-sort-col="1" scope="col" tabindex="0" aria-sort="none">
                    {{ ___('common.title') ?? 'Title' }} <i class="hw-sort-icon fa-solid fa-sort" aria-hidden="true"></i>
                  </th>
                  <th class="hw-sortable" data-sort-col="2" scope="col" tabindex="0" aria-sort="none" style="white-space:nowrap">
                    Class / Section <i class="hw-sort-icon fa-solid fa-sort" aria-hidden="true"></i>
                  </th>
                  <th class="hw-sortable" data-sort-col="3" scope="col" tabindex="0" aria-sort="none" style="white-space:nowrap">
                    {{ ___('academic.subject') }} <i class="hw-sort-icon fa-solid fa-sort" aria-hidden="true"></i>
                  </th>
                  <th class="hw-sortable" data-sort-col="4" scope="col" tabindex="0" aria-sort="none" style="white-space:nowrap">
                    Type <i class="hw-sort-icon fa-solid fa-sort" aria-hidden="true"></i>
                  </th>
                  <th class="hw-sortable" data-sort-col="5" scope="col" tabindex="0" aria-sort="none" style="white-space:nowrap">
                    {{ ___('common.due_date') ?? 'Due date' }} <i class="hw-sort-icon fa-solid fa-sort" aria-hidden="true"></i>
                  </th>
                  <th class="hw-sortable" data-sort-col="6" scope="col" tabindex="0" aria-sort="none" style="white-space:nowrap;width:72px">
                    {{ ___('common.marks') ?? 'Marks' }} <i class="hw-sort-icon fa-solid fa-sort" aria-hidden="true"></i>
                  </th>
                  <th style="white-space:nowrap;min-width:108px">Submissions</th>
                  <th class="action" style="white-space:nowrap;min-width:120px">{{ ___('common.action') }}</th>
                </tr>
              </thead>
              <tbody id="filtered-table-body" class="tbody">
                <tr>
                  <td colspan="9" class="text-center text-muted py-5">
                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i>
                    Choose filters and hit <strong>Deploy</strong> to load your quest log.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

  </div>

</div>

<div class="modal fade" id="mEv" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header modal-header-image">
        <h5 class="modal-title">Evaluation</h5>
        <button type="button"
                class="m-0 btn-close d-flex justify-content-center align-items-center"
                data-bs-dismiss="modal">
          <i class="fa fa-times text-white"></i>
        </button>
      </div>
      <form action="{{ route('homework.evaluation.submit') }}" method="post">
        @csrf
        <input type="hidden" name="homework_id" id="hw_id">
        <div class="modal-body p-4" id="ev-body">
          <div class="text-center py-5">
            <i class="fa-solid fa-spinner fa-spin fs-3 text-primary"></i>
          </div>
        </div>
        <div class="modal-footer d-flex flex-wrap align-items-center justify-content-between gap-2">
          <p class="small text-muted mb-0" id="ev-save-hint">
            <i class="fa-solid fa-circle-info me-1"></i>Enter marks for submitted students, then save.
          </p>
          <div class="d-flex gap-2 ms-auto">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
              {{ ___('common.cancel') }}
            </button>
            <button type="submit" class="btn ot-btn-primary hw-btn-save-grades">
              <i class="fa-solid fa-floppy-disk me-1"></i>Save grades
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="mQ" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl"></div>
</div>

@endsection

@push('script')

@include('backend.partials.delete-ajax')

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
let donutChartInstance = null;
let lineChartInstance  = null;

var HW_PROCEED_BTN_HTML = @json('<i class="fa-solid fa-rocket me-1"></i>Deploy');
var HW_LOADING_BTN_HTML = '<i class="fa-solid fa-spinner fa-spin me-1" aria-hidden="true"></i>';

var hwQuestLogSortState = { col: null, dir: 'asc' };

function resetHwQuestLogSortHeaders() {
  hwQuestLogSortState.col = null;
  hwQuestLogSortState.dir = 'asc';
  $('#hw-quest-log-table thead th.hw-sortable').each(function () {
    $(this).attr('aria-sort', 'none');
    $(this).find('.hw-sort-icon').attr('class', 'hw-sort-icon fa-solid fa-sort');
  });
}

function sortHwQuestLogTable(colIndex) {
  var $tbody = $('#filtered-table-body');
  var $rows = $tbody.find('tr').filter(function () {
    var $tds = $(this).find('td');
    return $tds.length >= 8 && $tds.filter('[colspan]').length === 0;
  });
  if ($rows.length < 2) {
    return;
  }

  if (hwQuestLogSortState.col === colIndex) {
    hwQuestLogSortState.dir = hwQuestLogSortState.dir === 'asc' ? 'desc' : 'asc';
  } else {
    hwQuestLogSortState.col = colIndex;
    hwQuestLogSortState.dir = 'asc';
  }

  var dir = hwQuestLogSortState.dir;

  function valOf(tr) {
    var v = $(tr).children('td').eq(colIndex).attr('data-sort');
    return v === undefined || v === null ? '' : String(v);
  }

  function cmpDue(va, vb) {
    var ae = va === '', be = vb === '';
    if (ae && be) {
      return 0;
    }
    if (ae) {
      return 1;
    }
    if (be) {
      return -1;
    }
    return va.localeCompare(vb);
  }

  function cmpMarks(va, vb) {
    function num(s) {
      if (s === '') {
        return null;
      }
      var n = parseFloat(s);
      return isNaN(n) ? null : n;
    }
    var na = num(va), nb = num(vb);
    if (na === null && nb === null) {
      return 0;
    }
    if (na === null) {
      return 1;
    }
    if (nb === null) {
      return -1;
    }
    return na - nb;
  }

  var sorted = $rows.get().sort(function (a, b) {
    var va = valOf(a), vb = valOf(b);
    var c = 0;
    if (colIndex === 5) {
      c = cmpDue(va, vb);
    } else if (colIndex === 6) {
      c = cmpMarks(va, vb);
    } else {
      c = va.localeCompare(vb, undefined, { sensitivity: 'base', numeric: true });
    }
    if (c !== 0) {
      return dir === 'asc' ? c : -c;
    }
    var ida = parseInt($(a).attr('data-hw-row-id'), 10) || 0;
    var idb = parseInt($(b).attr('data-hw-row-id'), 10) || 0;
    return ida - idb;
  });

  $tbody.append(sorted);

  $tbody.find('tr td.hw-quest-num').each(function (i) {
    $(this).text(i + 1);
  });

  $('#hw-quest-log-table thead th.hw-sortable').each(function () {
    var idx = parseInt($(this).data('sort-col'), 10);
    var $icon = $(this).find('.hw-sort-icon');
    if (idx === hwQuestLogSortState.col) {
      $(this).attr('aria-sort', dir === 'asc' ? 'ascending' : 'descending');
      $icon.attr('class', 'hw-sort-icon fa-solid ' + (dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down'));
    } else {
      $(this).attr('aria-sort', 'none');
      $icon.attr('class', 'hw-sort-icon fa-solid fa-sort');
    }
  });
}

function renderHwEvaluationStatus(es) {
  var $el = $('#hw-evaluation-status');
  if (!$el.length) {
    return;
  }
  if (!es || es.submissions_awaiting_marks === undefined) {
    $el.empty();
    return;
  }
  var hp = parseInt(es.homeworks_pending_evaluation, 10) || 0;
  var sa = parseInt(es.submissions_awaiting_marks, 10) || 0;
  var graded = parseInt(es.submissions_graded, 10) || 0;
  var pipelineSubmitted = parseInt(es.pipeline_submitted, 10) || 0;
  var notSubmitted = parseInt(es.pipeline_not_submitted, 10) || 0;

  if (sa === 0) {
    var thirdValue = notSubmitted > 0 ? String(notSubmitted) : (graded > 0 ? '✓' : '—');
    var thirdLabel = notSubmitted > 0 ? 'Haven\'t submitted yet' : (pipelineSubmitted > 0 ? 'All hand-ins graded' : 'No hand-ins yet');
    var thirdClass = notSubmitted > 0 ? 'hw-eval-stat--waiting' : 'hw-eval-stat--done';
    var thirdIcon = notSubmitted > 0 ? 'fa-user-clock' : 'fa-flag-checkered';
    var note = '';

    if (notSubmitted > 0 && pipelineSubmitted === 0) {
      note = '<p class="hw-eval-note hw-eval-note--info mb-0 mt-2"><i class="fa-solid fa-circle-info me-1"></i>' +
        '<strong>0 need your marks.</strong> ' + notSubmitted + ' student slot' + (notSubmitted === 1 ? '' : 's') +
        ' still waiting for a submission — open tasks below when work arrives.</p>';
    } else if (notSubmitted > 0) {
      note = '<p class="hw-eval-note hw-eval-note--info mb-0 mt-2"><i class="fa-solid fa-circle-info me-1"></i>' +
        '<strong>0 need your marks</strong> right now. ' + notSubmitted + ' student' + (notSubmitted === 1 ? '' : 's') +
        ' still need to submit (shown in the chart as Pending / Overdue).</p>';
    } else if (graded > 0) {
      note = '<p class="hw-eval-note hw-eval-note--success mb-0 mt-2"><i class="fa-solid fa-circle-check me-1"></i>' +
        'Every submitted hand-in has marks. Nothing waiting for you to grade.</p>';
    } else {
      note = '<p class="hw-eval-note mb-0 mt-2"><i class="fa-solid fa-inbox me-1"></i>' +
        'No student submissions in this filter yet.</p>';
    }

    $el.html(
      '<div class="row g-2 hw-eval-stats">' +
        '<div class="col-sm-4">' +
          '<div class="hw-eval-stat hw-eval-stat--done">' +
            '<span class="hw-eval-stat__icon"><i class="fa-solid fa-circle-check"></i></span>' +
            '<div><span class="hw-eval-stat__value">' + graded + '</span>' +
            '<span class="hw-eval-stat__label">Already graded</span></div>' +
          '</div></div>' +
        '<div class="col-sm-4">' +
          '<div class="hw-eval-stat hw-eval-stat--done">' +
            '<span class="hw-eval-stat__icon"><i class="fa-solid fa-clipboard-check"></i></span>' +
            '<div><span class="hw-eval-stat__value">0</span>' +
            '<span class="hw-eval-stat__label">Need your marks</span></div>' +
          '</div></div>' +
        '<div class="col-sm-4">' +
          '<div class="hw-eval-stat ' + thirdClass + '">' +
            '<span class="hw-eval-stat__icon"><i class="fa-solid ' + thirdIcon + '"></i></span>' +
            '<div><span class="hw-eval-stat__value">' + thirdValue + '</span>' +
            '<span class="hw-eval-stat__label">' + thirdLabel + '</span></div>' +
          '</div></div>' +
      '</div>' + note
    );
    return;
  }

  $el.html(
    '<div class="row g-2 hw-eval-stats">' +
      '<div class="col-sm-4">' +
        '<div class="hw-eval-stat hw-eval-stat--urgent" title="Open a task and use Grade">' +
          '<span class="hw-eval-stat__icon"><i class="fa-solid fa-pen-to-square"></i></span>' +
          '<div><span class="hw-eval-stat__value">' + sa + '</span>' +
          '<span class="hw-eval-stat__label">Need your marks</span></div>' +
        '</div></div>' +
      '<div class="col-sm-4">' +
        '<div class="hw-eval-stat hw-eval-stat--tasks">' +
          '<span class="hw-eval-stat__icon"><i class="fa-solid fa-list-check"></i></span>' +
          '<div><span class="hw-eval-stat__value">' + hp + '</span>' +
          '<span class="hw-eval-stat__label">Tasks with backlog</span></div>' +
        '</div></div>' +
      '<div class="col-sm-4">' +
        '<div class="hw-eval-stat hw-eval-stat--done">' +
          '<span class="hw-eval-stat__icon"><i class="fa-solid fa-check-double"></i></span>' +
          '<div><span class="hw-eval-stat__value">' + graded + '</span>' +
          '<span class="hw-eval-stat__label">Already graded</span></div>' +
        '</div></div>' +
    '</div>'
  );
}

$(document).ready(function () {

  $('#hw-quest-log-table').on('click', 'thead th.hw-sortable', function () {
    sortHwQuestLogTable(parseInt($(this).data('sort-col'), 10));
  });
  $('#hw-quest-log-table').on('keydown', 'thead th.hw-sortable', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      sortHwQuestLogTable(parseInt($(this).data('sort-col'), 10));
    }
  });

  $('#filter-class').on('change', function () {
    const classId = $(this).val();

    $('#filter-section').val('').empty()
      .append('<option value="">All Sections</option>');
    $('#filter-subject').val('').empty()
      .append('<option value="">All Subjects</option>');

    if (!classId) return;
    loadSectionsByClass(classId);
  });

  $('#filter-section').on('change', function () {
    const classId   = $('#filter-class').val();
    const sectionId = $(this).val();

    $('#filter-subject').val('').empty()
      .append('<option value="">All Subjects</option>');

    if (!classId || !sectionId) return;
    loadSubjectsBySection(classId, sectionId);
  });

  $('#proceed-btn').on('click', function () {
    getFilteredReport();
  });

  $('#reset-filters-btn').on('click', function () {
    $('#filter-class').val('');
    $('#filter-section').empty().append('<option value="">All Sections</option>');
    $('#filter-subject').empty().append('<option value="">All Subjects</option>');
    $('#filter-task-type').val('all');
    if (donutChartInstance) { donutChartInstance.destroy(); donutChartInstance = null; }
    if (lineChartInstance)  { lineChartInstance.destroy();  lineChartInstance  = null; }
    $('#hw-evaluation-status').empty();
    $('#results-container').stop(true, true).hide().css({ visibility: '', opacity: '' });
  });

  function loadSectionsByClass(classId) {
    $.ajax({
      url:      '{{ route("homework.ajax.sections-by-class") }}',
      method:   'GET',
      data:     { class_id: classId },
      dataType: 'json',
      success: function (response) {
        if (response.success && response.data) {
          const $sel = $('#filter-section').empty().append('<option value="">All Sections</option>');
          response.data.forEach(function (section) {
            $sel.append('<option value="' + section.id + '">' + section.name + '</option>');
          });
        }
      },
      error: function () { console.error('Failed to load sections'); }
    });
  }

  function loadSubjectsBySection(classId, sectionId) {
    $.ajax({
      url:      '{{ route("homework.ajax.subjects-by-section") }}',
      method:   'GET',
      data:     { class_id: classId, section_id: sectionId },
      dataType: 'json',
      success: function (response) {
        if (response.success && response.data) {
          const $sel = $('#filter-subject').empty().append('<option value="">All Subjects</option>');
          response.data.forEach(function (subject) {
            $sel.append('<option value="' + subject.id + '">' + subject.name + '</option>');
          });
        }
      },
      error: function () { console.error('Failed to load subjects'); }
    });
  }

  function getFilteredReport() {
    const $btn = $('#proceed-btn');
    $btn.html(HW_LOADING_BTN_HTML).prop('disabled', true);

    const filters = {
      class:     $('#filter-class'    ).val(),
      section:   $('#filter-section'  ).val(),
      subject:   $('#filter-subject'  ).val(),
      task_type: $('#filter-task-type').val(),
    };

    $.ajax({
      url:      '{{ route("homework.ajax.filtered-report") }}',
      method:   'POST',
      data:     Object.assign(filters, { _token: '{{ csrf_token() }}' }),
      dataType: 'json',
      success: function (response) {
        if (response.success) {
          $('#filtered-table-body').html(response.table_html);
          resetHwQuestLogSortHeaders();
          const rowCount = $('#filtered-table-body tr:not(.hh)').length;
          $('#table-count-badge').text(rowCount + ' record' + (rowCount !== 1 ? 's' : ''));

          if (donutChartInstance) { donutChartInstance.destroy(); donutChartInstance = null; }
          if (lineChartInstance)  { lineChartInstance.destroy();  lineChartInstance  = null; }

          renderHwEvaluationStatus(response.evaluation_status);

          var $results = $('#results-container');
          $results.css({ display: 'block', visibility: 'hidden', opacity: 0 });

          const donutCtx = document.getElementById('donut-chart-filtered');
          if (donutCtx && response.donut_data && Array.isArray(response.donut_data.data)) {
            var donutVals = response.donut_data.data.map(function (v) { return Number(v) || 0; });
            var donutLabels = response.donut_data.labels ? response.donut_data.labels.slice() : [];
            var donutColors = response.donut_data.colors ? response.donut_data.colors.slice() : [];
            var donutSum = donutVals.reduce(function (a, b) { return a + b; }, 0);
            if (donutSum === 0) {
              donutLabels = ['No activity yet'];
              donutVals = [1];
              donutColors = ['#e2e8f0'];
            }
            var donutPalette = ['#10b981', '#f59e0b', '#dc2626'];
            donutChartInstance = new Chart(donutCtx, {
              type: 'doughnut',
              data: {
                labels: donutLabels,
                datasets: [{
                  data: donutVals,
                  backgroundColor: donutColors.length >= donutVals.length ? donutColors : donutPalette,
                  borderWidth: 3,
                  borderColor: '#ffffff',
                  hoverOffset: 4
                }]
              },
              options: {
                cutout: '65%',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    position: 'bottom',
                    labels: { font: { size: 11 }, padding: 14, usePointStyle: true }
                  },
                  tooltip: {
                    enabled: donutSum > 0,
                    callbacks: {
                      label: function (ctx) {
                        var raw = Number(ctx.raw) || 0;
                        var sum = ctx.dataset.data.reduce(function (a, b) { return a + (Number(b) || 0); }, 0);
                        var pct = sum > 0 ? Math.round((raw / sum) * 1000) / 10 : 0;
                        return ' ' + (ctx.label || '') + ': ' + raw + ' (' + pct + '%)';
                      }
                    }
                  }
                }
              }
            });
          }

          const lineCtx = document.getElementById('line-chart-filtered');
          if (lineCtx && response.trend_data && response.trend_data.datasets && response.trend_data.datasets.length) {
            var td = response.trend_data;
            var scales = {
              x: { grid: { display: false }, ticks: { font: { size: 10 } } },
              y: {
                type: 'linear',
                display: true,
                position: 'left',
                beginAtZero: true,
                grid: {},
                ticks: { font: { size: 10 } },
                title: {
                  display: true,
                  font: { size: 10, weight: '600' },
                  text: td.y_left_title || 'Score'
                }
              }
            };
            if (td.y_suggested_max) {
              scales.y.suggestedMax = td.y_suggested_max;
            }
            if (td.dual_axis) {
              scales.y1 = {
                type: 'linear',
                display: true,
                position: 'right',
                beginAtZero: true,
                max: 100,
                grid: { drawOnChartArea: false },
                ticks: { font: { size: 10 } },
                title: {
                  display: true,
                  font: { size: 10, weight: '600' },
                  text: td.y_right_title || '% submitted'
                }
              };
            }

            lineChartInstance = new Chart(lineCtx, {
              type: 'line',
              data: {
                labels: td.labels,
                datasets: td.datasets.map(function (ds) {
                  var bc = ds.borderColor || '#1d4ed8';
                  return {
                    label: ds.label,
                    data: ds.data,
                    yAxisID: ds.y_axis_id || 'y',
                    borderColor: bc,
                    backgroundColor: ds.backgroundColor || 'rgba(29,78,216,0.08)',
                    borderWidth: typeof ds.borderWidth === 'number' ? ds.borderWidth : 2.5,
                    tension: typeof ds.tension === 'number' ? ds.tension : 0.35,
                    fill: ds.fill !== false,
                    spanGaps: ds.spanGaps === true,
                    pointRadius: 3,
                    pointBackgroundColor: bc,
                    pointHoverRadius: 4
                  };
                })
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: scales,
                plugins: {
                  tooltip: {
                    callbacks: {
                      label: function (ctx) {
                        var v = ctx.raw;
                        var lbl = ctx.dataset.label || '';
                        if (v === null || v === undefined) {
                          return ' ' + lbl + ': —';
                        }
                        var sfx = '';
                        if (ctx.dataset.yAxisID === 'y1') {
                          sfx = '%';
                        } else if (td.y_suggested_max && ctx.dataset.yAxisID === 'y') {
                          sfx = '%';
                        }
                        return ' ' + lbl + ': ' + v + sfx;
                      }
                    }
                  }
                }
              }
            });
          }

          $results.css({ visibility: 'visible', opacity: 1 });
          window.requestAnimationFrame(function () {
            if (donutChartInstance && typeof donutChartInstance.resize === 'function') donutChartInstance.resize();
            if (lineChartInstance && typeof lineChartInstance.resize === 'function') lineChartInstance.resize();
          });
        }
      },
      error: function (xhr) {
        console.error('Failed to load filtered report:', xhr);
        alert('Error loading report. Please check your filters and try again.');
      },
      complete: function () {
        $btn.html(HW_PROCEED_BTN_HTML).prop('disabled', false);
      }
    });
  }

});

function openEval(id) {
  $('#hw_id').val(id);
  $('#ev-body').html('<div class="text-center py-5"><i class="fa-solid fa-spinner fa-spin fs-3 text-primary"></i></div>');

  var modalEl = document.getElementById('mEv');
  if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  } else if (modalEl && typeof $ !== 'undefined' && $.fn.modal) {
    $(modalEl).modal('show');
  }

  $.post(
    $('#url').val() + '/homework/students',
    { homework_id: id, _token: $('meta[name="csrf-token"]').attr('content') },
    function (d) {
      if (d && d.view) {
        $('#ev-body').html(d.view);
      } else {
        $('#ev-body').html('<p class="text-danger p-3">Unexpected response.</p>');
      }
    }
  ).fail(function () {
    $('#ev-body').html('<p class="text-danger p-3">Failed to load evaluation.</p>');
  });
}

function vQ(id) {
  $.ajax({
    type: 'GET', dataType: 'html', data: { id: id },
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    url: $('#url').val() + '/homework/view-questions',
    success: function (d) { $('#mQ .modal-dialog').html(d); }
  });
}

</script>

@endpush
