<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome</title>
</head>
<body>
    <p>Hi {{ $user->name }},</p>
    <p>Welcome to {{ config('app.name') }}! Your account has been created successfully.</p>
</body>
</html>
