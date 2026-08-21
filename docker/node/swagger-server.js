import express from 'express';
import swaggerUi from 'swagger-ui-dist';
import fs from 'node:fs';

const app = express();

const PORT = 8081;

const openApiFile = '/var/www/docs/api/openapi.yml';

app.get('/openapi.yml', (_request, response) => {
    response.type('yaml').send(
        fs.readFileSync(openApiFile, 'utf8')
    );
});

app.use(
    '/swagger',
    express.static(swaggerUi.getAbsoluteFSPath())
);

app.get('/', (_request, response) => {
    response.send(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Pixely Platform API</title>
            <link rel="stylesheet" href="/swagger/swagger-ui.css">
        </head>
        <body>
            <div id="swagger-ui"></div>

            <script src="/swagger/swagger-ui-bundle.js"></script>
            <script src="/swagger/swagger-ui-standalone-preset.js"></script>

            <script>
                window.onload = () => {
                    SwaggerUIBundle({
                        url: '/openapi.yml',
                        dom_id: '#swagger-ui',
                        presets: [
                            SwaggerUIBundle.presets.apis,
                            SwaggerUIStandalonePreset,
                        ],
                        layout: 'StandaloneLayout',
                    });
                };
            </script>
        </body>
        </html>
    `);
});

app.listen(PORT, '0.0.0.0', () => {
    console.log(`Swagger UI running on port ${PORT}`);
});
