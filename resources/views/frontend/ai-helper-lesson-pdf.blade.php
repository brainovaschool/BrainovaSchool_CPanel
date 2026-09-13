<!DOCTYPE html>
<html>
<head>
    <title>Lesson Plan</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #1e293b;
        }
        .header {
            background: #007585;
            color: #ffffff;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 6px 0;
            font-size: 18px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
        }
        .content {
            line-height: 1.6;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>{{ $data['fields']['lesson_title'] }}</h2>
        <p>
            Grade: {{ $data['fields']['grade'] }} &nbsp;|&nbsp;
            Subject: {{ $data['fields']['subject'] }} &nbsp;|&nbsp;
            Term: {{ $data['fields']['term'] }} &nbsp;|&nbsp;
            Unit: {{ $data['fields']['unit'] }} &nbsp;|&nbsp;
            Module: {{ $data['fields']['module'] }}
        </p>
    </div>

    <div class="content">
        {!! $data['content_html'] !!}
    </div>

</body>
</html>
