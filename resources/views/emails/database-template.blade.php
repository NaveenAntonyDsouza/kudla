<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f7; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
        .content { background: #fff; border-radius: 8px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .content h1 { font-size: 22px; color: #1a1a1a; margin-top: 0; }
        .content p { margin: 12px 0; color: #4a4a4a; }
        .content a[style] { display: inline-block; margin: 16px 0; }
        .content ul { padding-left: 20px; color: #4a4a4a; }
        .content li { margin: 6px 0; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="content">
            {!! $body !!}
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
