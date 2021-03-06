<!DOCTYPE html>
<html>
<head>
    <title><?= "$message ($code)"; ?></title>
    <style type="text/css">
        body { background-color: #fff; color: #666; text-align: center; font-family: arial, sans-serif; }
        div.dialog { width: 25em; padding: 0 4em; margin: 4em auto 0 auto; border: 1px solid #ccc; border-right-color: #999; border-bottom-color: #999; }
        h1 { font-size: 100%; color: #f00; line-height: 1.5em; }
    </style>
</head>

<body>
    <div class="dialog">
        <h1><?= "Error $code: $message"; ?></h1>
        <p>
            The page you were looking for doesn't exist.
            You may have mistyped the address or the page may have moved.
        </p>
    </div>
</body>
</html>
