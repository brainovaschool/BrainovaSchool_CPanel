<!DOCTYPE html>
<html>
<head>
    <title>Lesson Plan</title>
    <style>
        @page {
            margin: 16px 20px;
        }
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 9.5px;
            color: #1e293b;
        }

        /*
         * No outer bordered wrapper here on purpose: dompdf renders a
         * border/border-radius badly on a container that has to split across
         * a page break (stray border lines, blank pages). Each section below
         * carries its own background/border instead, and page-break-inside:
         * avoid keeps a section from being sliced mid-box if it ever does
         * need a second page.
         */

        /* header */
        .head-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .head-table td { vertical-align: middle; }
        .brand-logo { width: 44px; }
        .head-title { padding-left: 12px; }
        .head-title h1 { margin: 0; font-size: 20px; color: #0f1b3d; letter-spacing: 1px; }
        .ribbon {
            display: inline-block;
            background: #0097b2;
            color: #ffffff;
            padding: 2px 12px;
            border-radius: 4px;
            font-size: 9px;
            letter-spacing: 2px;
            margin-top: 3px;
        }

        /* info bar */
        .info-table { width: 100%; border-collapse: collapse; border: 1px solid #0f1b3d; border-radius: 8px; margin-bottom: 8px; page-break-inside: avoid; }
        .info-table td { border-right: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: top; }
        .info-table td:last-child { border-right: none; }
        .info-box-label { background: #0f1b3d; color: #fff; font-size: 8px; letter-spacing: 1px; padding: 2px 7px; border-radius: 4px; display: inline-block; margin-bottom: 3px; }
        .info-box-value { font-size: 10.5px; font-weight: bold; }
        .info-line { font-size: 9.5px; margin-bottom: 4px; }
        .info-line b { color: #0f1b3d; }

        /* two-column layout */
        .cols-table { width: 100%; border-collapse: collapse; }
        .cols-table > tr > td { vertical-align: top; width: 50%; padding: 0; }
        .col-left-cell { padding-right: 7px; }
        .col-right-cell { padding-left: 7px; }

        /* left column boxes */
        .box { border-radius: 6px; padding: 6px 9px; margin-bottom: 5px; page-break-inside: avoid; }
        .box-title { font-size: 9.5px; font-weight: bold; margin-bottom: 4px; }
        .badge { display: inline-block; width: 15px; height: 15px; border-radius: 50%; color: #fff; text-align: center; font-size: 8.5px; font-weight: bold; line-height: 15px; margin-right: 5px; }
        .box p { margin: 0 0 3px 0; font-size: 9px; line-height: 1.35; }
        .box ul { margin: 3px 0 0 0; padding-left: 14px; }
        .box li { font-size: 9px; line-height: 1.4; margin-bottom: 1px; }

        .box-goals { background: #e8f7ef; }
        .badge-goals { background: #16a34a; }
        .box-question { background: #fff4e6; }
        .badge-question { background: #f59e0b; }
        .box-matters { background: #f3e8ff; }
        .badge-matters { background: #9333ea; }
        .box-checkpoints { background: #eaf2ff; }
        .badge-checkpoints { background: #2563eb; }
        .box-reflection { background: #fdf0ff; }
        .badge-reflection { background: #c026d3; }

        /* right column numbered sections */
        .step { border-radius: 6px; padding: 6px 9px; margin-bottom: 5px; page-break-inside: avoid; }
        .step-head-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .step-title { font-size: 9.5px; font-weight: bold; }
        .time-pill { float: right; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 20px; padding: 1px 8px; font-size: 8px; }
        .step p { margin: 0 0 3px 0; font-size: 9px; line-height: 1.35; }
        .step ul { margin: 3px 0 0 0; padding-left: 14px; }
        .step li { font-size: 9px; line-height: 1.4; margin-bottom: 1px; }

        .step-wonder { background: #fff1f2; }
        .badge-wonder { background: #ef4444; }
        .step-discover { background: #fff7ed; }
        .badge-discover { background: #f97316; }
        .step-explore { background: #eff6ff; }
        .badge-explore { background: #2563eb; }
        .step-create { background: #f0fdf4; }
        .badge-create { background: #16a34a; }
        .step-beyond { background: #e6fbfd; }
        .badge-beyond { background: #0097b2; }

        .option-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .option-table td { width: 25%; background: #ffffff; border: 1px solid #d1d5db; border-radius: 6px; padding: 5px; vertical-align: top; font-size: 8.5px; }
        .option-label { font-weight: bold; display: block; margin-bottom: 2px; }

        /* footer */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 5px; page-break-inside: avoid; }
        .footer-table td { width: 50%; vertical-align: top; padding: 7px 9px; font-size: 9px; line-height: 1.35; border-radius: 6px; }
        .footer-notes { background: #f1f5f9; }
        .footer-misconceptions { background: #fff1f2; }
        .footer-title { font-size: 9.5px; font-weight: bold; margin-bottom: 3px; }

        .journey-bar { background: #0f1b3d; border-radius: 6px; margin-top: 6px; padding: 7px; page-break-inside: avoid; }
        .journey-table { width: 100%; border-collapse: collapse; }
        .journey-table td { width: 16.6%; text-align: center; color: #ffffff; font-size: 7.5px; line-height: 1.3; vertical-align: top; }
        .journey-badge { display: inline-block; width: 16px; height: 16px; border-radius: 50%; background: #0097b2; color: #fff; line-height: 16px; font-size: 8.5px; font-weight: bold; margin-bottom: 3px; }
    </style>
</head>
<body>

    <table class="head-table">
        <tr>
            <td style="width:70px;"><img class="brand-logo" src="{{ $data['logo'] }}" alt="logo"></td>
            <td class="head-title">
                <h1>LESSON PLAN</h1>
                <span class="ribbon">BEYOND CLASSROOMS</span>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width:16%;">
                <span class="info-box-label">GRADE</span><br>
                <span class="info-box-value">{{ $data['fields']['grade'] }}</span>
            </td>
            <td style="width:16%;">
                <span class="info-box-label">SUBJECT</span><br>
                <span class="info-box-value">{{ $data['fields']['subject'] }}</span>
            </td>
            <td>
                <div class="info-line"><b>Term:</b> {{ $data['fields']['term'] }}</div>
                <div class="info-line"><b>Unit:</b> {{ $data['fields']['unit'] }}</div>
                <div class="info-line"><b>Module:</b> {{ $data['fields']['module'] }}</div>
                <div class="info-line" style="margin-bottom:0;"><b>Lesson:</b> {{ $data['fields']['lesson_title'] }}</div>
            </td>
        </tr>
    </table>

    <table class="cols-table">
        <tr>
            <td class="col-left-cell">

                <div class="box box-goals">
                    <div class="box-title"><span class="badge badge-goals">G</span>LEARNING GOALS</div>
                    <p>By the end of this learning experience, learners will be able to:</p>
                    <ul>
                        @foreach ($data['plan']['learning_goals'] as $goal)
                            <li>{{ $goal }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="box box-question">
                    <div class="box-title"><span class="badge badge-question">?</span>ESSENTIAL QUESTION</div>
                    <p>{{ $data['plan']['essential_question'] }}</p>
                </div>

                <div class="box box-matters">
                    <div class="box-title"><span class="badge badge-matters">!</span>WHY DOES THIS MATTER?</div>
                    <p>{{ $data['plan']['why_matters'] }}</p>
                </div>

                <div class="box box-checkpoints">
                    <div class="box-title"><span class="badge badge-checkpoints">C</span>LEARNING CHECKPOINTS</div>
                    <p>The educator gathers evidence through:</p>
                    <ul>
                        @foreach ($data['plan']['checkpoints'] as $checkpoint)
                            <li>{{ $checkpoint }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="box box-reflection">
                    <div class="box-title"><span class="badge badge-reflection">R</span>REFLECTION &amp; GROWTH <span style="font-weight:normal;font-size:9px;">(5 min)</span></div>
                    <ul>
                        @foreach ($data['plan']['reflection'] as $reflect)
                            <li>{{ $reflect }}</li>
                        @endforeach
                    </ul>
                </div>

            </td>
            <td class="col-right-cell">

                <div class="step step-wonder">
                    <table class="step-head-table"><tr>
                        <td><span class="step-title"><span class="badge badge-wonder">1</span>WONDER (Spark Curiosity)</span></td>
                        <td style="text-align:right;"><span class="time-pill">10 min</span></td>
                    </tr></table>
                    <p><b>Hook:</b> {{ $data['plan']['wonder_hook'] }}</p>
                    <ul>
                        @foreach ($data['plan']['wonder_bullets'] as $bullet)
                            <li>{{ $bullet }}</li>
                        @endforeach
                    </ul>
                    <p style="margin-top:4px;"><b>Allow:</b> {{ $data['plan']['wonder_allow'] }}</p>
                </div>

                <div class="step step-discover">
                    <table class="step-head-table"><tr>
                        <td><span class="step-title"><span class="badge badge-discover">2</span>DISCOVER (I Do)</span></td>
                        <td style="text-align:right;"><span class="time-pill">15 min</span></td>
                    </tr></table>
                    <ul>
                        @foreach ($data['plan']['discover_bullets'] as $bullet)
                            <li>{{ $bullet }}</li>
                        @endforeach
                    </ul>
                    <p style="margin-top:4px;"><b>Key Vocabulary:</b> {{ implode(', ', $data['plan']['discover_vocabulary']) }}</p>
                </div>

                <div class="step step-explore">
                    <table class="step-head-table"><tr>
                        <td><span class="step-title"><span class="badge badge-explore">3</span>EXPLORE TOGETHER (We Do)</span></td>
                        <td style="text-align:right;"><span class="time-pill">15 min</span></td>
                    </tr></table>
                    <ul>
                        @foreach ($data['plan']['explore_bullets'] as $bullet)
                            <li>{{ $bullet }}</li>
                        @endforeach
                    </ul>
                    <p style="margin-top:4px;">{{ $data['plan']['explore_share'] }}</p>
                </div>

                <div class="step step-create">
                    <table class="step-head-table"><tr>
                        <td><span class="step-title"><span class="badge badge-create">4</span>CREATE &amp; APPLY (You Do)</span></td>
                        <td style="text-align:right;"><span class="time-pill">15 min</span></td>
                    </tr></table>
                    <table class="option-table">
                        <tr>
                            <td><span class="option-label">Option A (Artist)</span>{{ $data['plan']['create_option_a'] }}</td>
                            <td><span class="option-label">Option B (Engineer)</span>{{ $data['plan']['create_option_b'] }}</td>
                            <td><span class="option-label">Option C (Journalist)</span>{{ $data['plan']['create_option_c'] }}</td>
                            <td><span class="option-label">Option D (Story Creator)</span>{{ $data['plan']['create_option_d'] }}</td>
                        </tr>
                    </table>
                </div>

                <div class="step step-beyond">
                    <div class="step-title"><span class="badge badge-beyond">B</span>BEYOND CLASSROOMS</div>
                    <ul>
                        @foreach ($data['plan']['beyond_classrooms'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>

            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td class="footer-notes">
                <div class="footer-title">EDUCATOR NOTES / RESOURCES</div>
                {{ $data['plan']['educator_notes'] }}
            </td>
            <td class="footer-misconceptions">
                <div class="footer-title">POSSIBLE MISCONCEPTIONS</div>
                {{ $data['plan']['misconceptions'] }}
            </td>
        </tr>
    </table>

    <div class="journey-bar">
        <table class="journey-table">
            <tr>
                <td><span class="journey-badge">1</span><br>Wonder<br>I set my intention</td>
                <td><span class="journey-badge">2</span><br>Observe<br>I notice and look</td>
                <td><span class="journey-badge">3</span><br>Explore<br>I investigate</td>
                <td><span class="journey-badge">4</span><br>Create<br>I apply my ideas</td>
                <td><span class="journey-badge">5</span><br>Reflect<br>I think about learning</td>
                <td><span class="journey-badge">6</span><br>Beyond<br>I carry it into real life</td>
            </tr>
        </table>
    </div>

</body>
</html>
