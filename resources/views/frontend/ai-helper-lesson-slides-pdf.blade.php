<!DOCTYPE html>
<html>
<head>
    <title>Lesson Plan Slides</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            color: #1e293b;
        }
        .slide {
            width: 100%;
            padding: 40px 60px;
            box-sizing: border-box;
        }
        .pagebreak {
            page-break-before: always;
        }

        /* cover slide */
        .cover-logo { width: 90px; margin-bottom: 14px; }
        .cover-title { font-size: 46px; color: #0f1b3d; letter-spacing: 2px; margin: 0; }
        .cover-ribbon {
            display: inline-block;
            background: #0097b2;
            color: #ffffff;
            padding: 6px 22px;
            border-radius: 6px;
            font-size: 16px;
            letter-spacing: 3px;
            margin: 14px 0 40px 0;
        }
        .cover-info-table { width: 70%; border-collapse: collapse; border: 2px solid #0f1b3d; border-radius: 12px; }
        .cover-info-table td { padding: 14px 20px; font-size: 18px; border-bottom: 1px solid #e2e8f0; }
        .cover-info-table tr:last-child td { border-bottom: none; }
        .cover-info-table b { color: #0f1b3d; }

        /* content slide */
        .slide-badge {
            display: inline-block;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            color: #fff;
            text-align: center;
            line-height: 46px;
            font-size: 22px;
            font-weight: bold;
            margin-right: 16px;
        }
        .slide-heading-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .slide-heading-table td { vertical-align: middle; }
        .slide-heading { font-size: 30px; font-weight: bold; color: #0f1b3d; }
        .slide-time { float: right; background: #ffffff; border: 2px solid #0f1b3d; border-radius: 30px; padding: 6px 20px; font-size: 15px; }

        .slide-body { font-size: 19px; line-height: 1.8; }
        .slide-body ul { margin: 0; padding-left: 30px; }
        .slide-body li { margin-bottom: 12px; }
        .slide-sub { font-size: 16px; color: #475569; margin-top: 16px; }

        .bg-goals { background: #e8f7ef; } .badge-goals { background: #16a34a; }
        .bg-question { background: #fff4e6; } .badge-question { background: #f59e0b; }
        .bg-matters { background: #f3e8ff; } .badge-matters { background: #9333ea; }
        .bg-checkpoints { background: #eaf2ff; } .badge-checkpoints { background: #2563eb; }
        .bg-reflection { background: #fdf0ff; } .badge-reflection { background: #c026d3; }
        .bg-wonder { background: #fff1f2; } .badge-wonder { background: #ef4444; }
        .bg-discover { background: #fff7ed; } .badge-discover { background: #f97316; }
        .bg-explore { background: #eff6ff; } .badge-explore { background: #2563eb; }
        .bg-create { background: #f0fdf4; } .badge-create { background: #16a34a; }
        .bg-beyond { background: #e6fbfd; } .badge-beyond { background: #0097b2; }
        .bg-closing { background: #f1f5f9; } .badge-closing { background: #475569; }

        .option-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .option-table td { width: 25%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px; vertical-align: top; font-size: 15px; }
        .option-label { font-weight: bold; display: block; margin-bottom: 6px; color: #0f1b3d; }

        .closing-col { width: 50%; vertical-align: top; padding-right: 20px; }
    </style>
</head>
<body>

    {{-- Slide 1: Cover --}}
    <div class="slide" style="text-align:center;padding-top:90px;">
        <img class="cover-logo" src="{{ $data['logo'] }}" alt="logo"><br>
        <h1 class="cover-title">LESSON PLAN</h1>
        <span class="cover-ribbon">BEYOND CLASSROOMS</span>
        <table class="cover-info-table" style="margin:0 auto;">
            <tr><td><b>Grade:</b> {{ $data['fields']['grade'] }}</td></tr>
            <tr><td><b>Subject:</b> {{ $data['fields']['subject'] }}</td></tr>
            <tr><td><b>Term:</b> {{ $data['fields']['term'] }}</td></tr>
            <tr><td><b>Unit:</b> {{ $data['fields']['unit'] }}</td></tr>
            <tr><td><b>Module:</b> {{ $data['fields']['module'] }}</td></tr>
            <tr><td><b>Lesson:</b> {{ $data['fields']['lesson_title'] }}</td></tr>
        </table>
    </div>

    {{-- Slide 2: Learning Goals --}}
    <div class="slide pagebreak bg-goals">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-goals">G</span><span class="slide-heading">LEARNING GOALS</span>
        </td></tr></table>
        <div class="slide-body">
            <p>By the end of this learning experience, learners will be able to:</p>
            <ul>
                @foreach ($data['plan']['learning_goals'] as $goal)
                    <li>{{ $goal }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Slide 3: Essential Question --}}
    <div class="slide pagebreak bg-question">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-question">?</span><span class="slide-heading">ESSENTIAL QUESTION</span>
        </td></tr></table>
        <div class="slide-body">{{ $data['plan']['essential_question'] }}</div>
    </div>

    {{-- Slide 4: Why Does This Matter --}}
    <div class="slide pagebreak bg-matters">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-matters">!</span><span class="slide-heading">WHY DOES THIS MATTER?</span>
        </td></tr></table>
        <div class="slide-body">{{ $data['plan']['why_matters'] }}</div>
    </div>

    {{-- Slide 5: Learning Checkpoints --}}
    <div class="slide pagebreak bg-checkpoints">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-checkpoints">C</span><span class="slide-heading">LEARNING CHECKPOINTS</span>
        </td></tr></table>
        <div class="slide-body">
            <p>The educator gathers evidence through:</p>
            <ul>
                @foreach ($data['plan']['checkpoints'] as $checkpoint)
                    <li>{{ $checkpoint }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Slide 6: Reflection & Growth --}}
    <div class="slide pagebreak bg-reflection">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-reflection">R</span><span class="slide-heading">REFLECTION &amp; GROWTH</span>
            <span class="slide-time">5 min</span>
        </td></tr></table>
        <div class="slide-body">
            <ul>
                @foreach ($data['plan']['reflection'] as $reflect)
                    <li>{{ $reflect }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Slide 7: Wonder --}}
    <div class="slide pagebreak bg-wonder">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-wonder">1</span><span class="slide-heading">WONDER (Spark Curiosity)</span>
            <span class="slide-time">10 min</span>
        </td></tr></table>
        <div class="slide-body">
            <p><b>Hook:</b> {{ $data['plan']['wonder_hook'] }}</p>
            <ul>
                @foreach ($data['plan']['wonder_bullets'] as $bullet)
                    <li>{{ $bullet }}</li>
                @endforeach
            </ul>
            <p class="slide-sub"><b>Allow:</b> {{ $data['plan']['wonder_allow'] }}</p>
        </div>
    </div>

    {{-- Slide 8: Discover --}}
    <div class="slide pagebreak bg-discover">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-discover">2</span><span class="slide-heading">DISCOVER (I Do)</span>
            <span class="slide-time">15 min</span>
        </td></tr></table>
        <div class="slide-body">
            <ul>
                @foreach ($data['plan']['discover_bullets'] as $bullet)
                    <li>{{ $bullet }}</li>
                @endforeach
            </ul>
            <p class="slide-sub"><b>Key Vocabulary:</b> {{ implode(', ', $data['plan']['discover_vocabulary']) }}</p>
        </div>
    </div>

    {{-- Slide 9: Explore Together --}}
    <div class="slide pagebreak bg-explore">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-explore">3</span><span class="slide-heading">EXPLORE TOGETHER (We Do)</span>
            <span class="slide-time">15 min</span>
        </td></tr></table>
        <div class="slide-body">
            <ul>
                @foreach ($data['plan']['explore_bullets'] as $bullet)
                    <li>{{ $bullet }}</li>
                @endforeach
            </ul>
            <p class="slide-sub">{{ $data['plan']['explore_share'] }}</p>
        </div>
    </div>

    {{-- Slide 10: Create & Apply --}}
    <div class="slide pagebreak bg-create">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-create">4</span><span class="slide-heading">CREATE &amp; APPLY (You Do)</span>
            <span class="slide-time">15 min</span>
        </td></tr></table>
        <table class="option-table">
            <tr>
                <td><span class="option-label">Option A (Artist)</span>{{ $data['plan']['create_option_a'] }}</td>
                <td><span class="option-label">Option B (Engineer)</span>{{ $data['plan']['create_option_b'] }}</td>
                <td><span class="option-label">Option C (Journalist)</span>{{ $data['plan']['create_option_c'] }}</td>
                <td><span class="option-label">Option D (Story Creator)</span>{{ $data['plan']['create_option_d'] }}</td>
            </tr>
        </table>
    </div>

    {{-- Slide 11: Beyond Classrooms --}}
    <div class="slide pagebreak bg-beyond">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-beyond">B</span><span class="slide-heading">BEYOND CLASSROOMS</span>
        </td></tr></table>
        <div class="slide-body">
            <ul>
                @foreach ($data['plan']['beyond_classrooms'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Slide 12: Educator Notes & Misconceptions --}}
    <div class="slide pagebreak bg-closing">
        <table class="slide-heading-table"><tr><td>
            <span class="slide-badge badge-closing">i</span><span class="slide-heading">EDUCATOR NOTES &amp; MISCONCEPTIONS</span>
        </td></tr></table>
        <table style="width:100%;border-collapse:collapse;">
            <tr>
                <td class="closing-col">
                    <p style="font-weight:bold;color:#0f1b3d;">Educator Notes / Resources</p>
                    <p class="slide-body" style="font-size:16px;">{{ $data['plan']['educator_notes'] }}</p>
                </td>
                <td class="closing-col">
                    <p style="font-weight:bold;color:#0f1b3d;">Possible Misconceptions</p>
                    <p class="slide-body" style="font-size:16px;">{{ $data['plan']['misconceptions'] }}</p>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
