<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
</head>
<body style="font-family: Arial, sans-serif;">

    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f9f9f9;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #fff; margin: 20px; padding: 20px;">
                    <tr>
                        <td align="center">
                            <h1 style="color: #333;">Reset Your Password</h1>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <p style="color: #333;">Hello,</p>
                            <p style="color: #333;">You are receiving this email because we received a password reset request for your account.</p>
                            <p style="color: #333;">Please click the button below to reset your password:</p>
                            <p style="text-align: center; margin-top: 20px;">
                                <a href="{{ $data['resetlink'] }}" style="background-color: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Reset Password</a>
                            </p>
                            <p style="color: #333;">If you did not request a password reset, no further action is required.</p>
                            <p style="color: #333;">Thank you,</p>
                            <p style="color: #333;">Your Company Name</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>
