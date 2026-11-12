{{-- Shared shell for the branded error pages: self-contained, no vite,
     so it renders even when the app or the assets are down. --}}
<!DOCTYPE html>
<html lang="fa" dir="rtl" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} — Nobat | نوبت</title>
    <meta name="theme-color" content="#101010">
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #101010;
            color: #f5f5f5;
            font-family: "Vazirmatn", "Vazir", Tahoma, "Segoe UI", sans-serif;
            text-align: center;
            padding: 2rem;
        }
        .card { max-width: 30rem; width: 100%; }
        .brand {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: .04em;
            color: #a3a3a3;
        }
        .brand span { color: #f5f5f5; }
        .code {
            margin: 1.2rem 0 .4rem;
            font-size: clamp(4.5rem, 18vw, 7.5rem);
            font-weight: 800;
            line-height: 1;
            color: #fff;
        }
        .code small {
            display: block;
            font-size: .8rem;
            font-weight: 400;
            letter-spacing: .28em;
            color: #525252;
        }
        .message {
            margin: 1.4rem 0 2.2rem;
            font-size: 1rem;
            line-height: 2;
            color: #d4d4d4;
        }
        .message small {
            display: block;
            margin-top: .35rem;
            font-size: .8rem;
            color: #737373;
        }
        .action {
            display: inline-block;
            padding: .65rem 1.9rem;
            border-radius: .75rem;
            background: #f5f5f5;
            color: #101010;
            font-size: .92rem;
            font-weight: 600;
            text-decoration: none;
            transition: opacity .15s ease;
        }
        .action:hover { opacity: .85; }
        .line { width: 3.5rem; height: 2px; background: #262626; margin: 1.6rem auto; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand"><span>Nobat</span> | نوبت</div>
        <div class="code">{{ $code }}<small>{{ $titleEn }}</small></div>
        <div class="line"></div>
        <p class="message">{{ $message }}
            <small>{{ $messageEn }}</small>
        </p>
        <a class="action" href="/">{{ $action }}</a>
    </main>
</body>
</html>
