<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { background: #1a73e8; color: #fff; padding: 12px 16px; }
        .header h2 { margin: 0; font-size: 18px; }
        .content { padding: 16px; }
        .content p { margin: 8px 0; line-height: 1.4; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $subjectLine }}</h2>
    </div>
    <div class="content">
        @foreach(explode("\n", trim($details)) as $line)
            @if(trim($line) !== '')
                <p>{{ $line }}</p>
            @endif
        @endforeach
    </div>
</body>
</html>
