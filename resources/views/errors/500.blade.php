<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 — Server error | Pixely Platform</title>
    <style>
        :root { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; }
        main { width: min(560px, calc(100% - 32px)); padding: 48px 0; }
        .code { margin: 0 0 8px; font-size: 14px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #64748b; }
        h1 { margin: 0 0 16px; font-size: clamp(2.25rem, 8vw, 4rem); line-height: 1; }
        p { margin: 0 0 24px; line-height: 1.6; color: #475569; }
        a { display: inline-block; padding: 10px 16px; border-radius: 8px; background: #0f172a; color: #fff; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <p class="code">500 · Pixely Platform</p>
    <h1>Something went wrong</h1>
    <p>The server could not complete your request. Please try again later.</p>
    <a href="{{ url('/') }}">Back to website</a>
</main>
</body>
</html>
