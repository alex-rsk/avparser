<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчёт готов</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e0e0e0;
        }
        .header {
            background-color: #4f46e5;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
        }
        .body {
            padding: 32px 40px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.7;
            margin: 0 0 16px;
        }
        .info-box {
            background-color: #f9f9f9;
            border-left: 4px solid #4f46e5;
            border-radius: 4px;
            padding: 16px 20px;
            margin: 24px 0;
            font-size: 14px;
            color: #555555;
        }
        .info-box strong {
            display: block;
            margin-bottom: 4px;
            color: #333333;
        }
        .attachment-note {
            background-color: #eef2ff;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 13px;
            color: #4338ca;
            margin: 24px 0 0;
        }
        .footer {
            border-top: 1px solid #e0e0e0;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999999;
        }
        .footer a {
            color: #4f46e5;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        <div class="header">
            <h1>Отчёт готов</h1>
        </div>

        <div class="body">
            <p>Отчёт по поисковому запросу {{ $searchQuery }}</p>

            <p>
                Сбор данных с {$fromDmy} по {$toDmy}
            </p>

            <div class="info-box">
                <strong>Файл Excel во вложении</strong>                
            </div>

            <p>
                При возникновении вопросов обратитесь в техподдержку
            </p>

            @if (!empty($attachmentName))
                <div class="attachment-note">
                    📎 Attached: {{ $attachmentName }}
                </div>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. все права.<br>
            <a href="#">Отписаться</a> &nbsp;·&nbsp; <a href="#">Политика хранения персональных данных</a>
        </div>

    </div>
</body>
</html>