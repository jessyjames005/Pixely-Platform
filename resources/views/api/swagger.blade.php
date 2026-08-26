<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Pixely Platform API Documentation</title>

    @vite([
    'resources/css/app.css',
    'resources/js/app.ts',
    ])
</head>

<body>
    <div id="swagger-ui"></div>

    <script>
        window.addEventListener('load', () => {
            window.SwaggerUIBundle({
                url: @json(route('api.openapi')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    window.SwaggerUIBundle.presets.apis,
                    window.SwaggerUIStandalonePreset,
                ],
                layout: 'StandaloneLayout',
            });
        });
    </script>
</body>
</html>
