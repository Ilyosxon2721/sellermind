<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" type="image/png" href="https://www.ozon.ru/favicon.ico" />
    <link href="https://cdn2.ozone.ru/s3/redoc/fonts/fonts.css" rel="stylesheet" />
    <link href="ozon.css" rel="stylesheet" />
    <link href="styles.css" rel="stylesheet" />
    <script nonce="**CSP_NONCE**">
        const path = document.location.pathname
        if (path.charAt(path.length - 1) !== '/') {
            document.location.pathname += '/';
        }
        if (path.substr(path.length - 3) === 'ru/') {
            document.location.pathname = path.substr(0, path.length - 3);
        }
    </script>
    <script nonce="**CSP_NONCE**" src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
</head>

<body>
    <div id="redoc-container"></div>
    <script nonce="**CSP_NONCE**" type="module" src="scripts/index.js"></script>
</body>

</html>