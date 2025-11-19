<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alyms Auto App Account Activation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #0073e6;
            color: #ffffff;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
        }
        .content {
            padding: 20px;
        }
        .content p {
            line-height: 1.6;
        }
        /* .content a {
            color: #0073e6;
            text-decoration: none;
            font-weight: bold;
        } */
        .content ul {
            list-style-type: none;
            padding: 0;
        }
        .content ul li {
            margin-bottom: 10px;
        }
        .footer {
            background-color: #f4f4f4;
            color: #888;
            padding: 20px;
            /* text-align: center; */
            font-size: 14px;
        }
        .footer p {
            margin: 0;
        }

        .content ul li a{
            text-decoration:underline !important;
        }

        button {
    background: #0073e6;
    border: none;
    padding: 8px;
    border-radius: 7px;
    color: #fff;
    margin-top: 1em;
    margin-bottom: 1em;
}


    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Alyms Auto</h1>
        </div>
        <div class="content">
            <p>Hello {{ $data['user'] }},</p>
            <br>
            <p>Your Alyms auto app account is active now. Just click the link below to set up your password and start using the app:</p>
            <a href="{{ $data['resetlink'] }}"><button class="btn_pass">Generate your password</button></a>
            <p><b>Download the app here:</b></p>
            <ul class="link">
                <li><b>iOS</b>: <a href="[iOS App Link]">iOS App Link</a></li>
                <li><b>Android</b>: <a href="[Android App Link]">Android App Link</a></li>
            </ul>
            <p>Let's build something great together!</p>
        </div>
    </div>
</body>
</html>

