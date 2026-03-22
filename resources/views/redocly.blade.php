<!DOCTYPE html>
<html lang="zh-Hant">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ config('app.name') }} API 文件</title>
        <style>
            body {
                margin: 0;
                padding: 0;
            }

            #redoc-container {
                height: 100vh;
            }
        </style>
    </head>
    <body>
        <div id="redoc-container"></div>
        <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
        <script>
            Redoc.init(
                '{{ url('docs/api.yaml') }}',
                {
                    hideSecuritySection: true,
                    expandResponses: '200,201',
                },
                document.getElementById('redoc-container')
            )
        </script>
    </body>
</html>
