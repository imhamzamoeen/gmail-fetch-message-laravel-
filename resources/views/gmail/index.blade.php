<!DOCTYPE html>
<html>
<head>
    <title>Gmail Inbox</title>
</head>
<body>
    <h1>Emails with subject "upwork"</h1>
    <ul>
        @foreach($messages as $message)
            <li>{{ $message->getSnippet() }}</li>
        @endforeach
    </ul>
</body>
</html>
