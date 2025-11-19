<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate for Your Car Repair</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            margin: 20px auto;
            padding: 20px;
            max-width: 600px;
            border: 1px solid #dddddd;
            border-radius: 5px;
        }
        .email-header, .email-footer {
            text-align: center;
        }
        .email-body {
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h2>Estimate for Your Car Repair</h2>
        </div>
        <div class="email-body">
            <table>
                <tr>
                    <td>Dear {{ isset($data['customer_f_name']) && !empty($data['customer_f_name']) ? $data['customer_f_name'] : 'Customer' }} 
                        {{ isset($data['customer_l_name']) && !empty($data['customer_l_name']) ? $data['customer_l_name'] : '' }},
                    </td>
                </tr>
                <tr>
                    <td>We've prepared an estimate for your car repair. Please review the details mentioned/attached and let us know if you have any questions or need further clarification.</td>
                </tr>
                <tr>
                    <td>Best Regards,</td>
                </tr>
                <tr>
                    <td>Alyms Auto</td>
                </tr>
            </table>
        </div>
        <div class="email-footer">
            <p>&copy; 2024. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
