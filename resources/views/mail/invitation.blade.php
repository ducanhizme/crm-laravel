<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            padding: 20px;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 600px;
        }

        .header {
            background-color: #4CAF50;
            color: white;
            padding: 10px 0;
            text-align: center;
        }

        .content {
            padding: 20px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: white;
            background-color: #4CAF50;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            padding: 10px;
            font-size: 12px;
            color: #777;
        }
    </style>
    <title></title>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Invitation to Join Workspace</h1>
    </div>
    <div class="content">
        <p>You have been invited to join our workspace. Click the button below to accept the invitation:</p>
        <a href="{{ $url }}" class="button">Join Workspace</a>
    </div>
    <div class="footer">
        <p>If you did not request this invitation, please ignore this email.</p>
    </div>
</div>
</body>
</html>
