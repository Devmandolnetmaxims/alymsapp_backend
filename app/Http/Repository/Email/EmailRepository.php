<?php

namespace App\Http\Repository\Email;

use App\Mail\EmailServices;
use Illuminate\Support\Facades\Mail;
use PDF;


class EmailRepository
{
    public static function SendEmail($email, $subject, $views, $data = null, $attachment = []) {
        if(!$email) {
            return false;
        }
        $pdfPath = [];
        if (isset($data['html_content']) && $data['html_content']) {
            $pdf = PDF::loadHTML($data['html_content']);
            $uniqueFilename = 'billing_' . time() . '.pdf';
            $pdfPath = storage_path("app_pdf/{$uniqueFilename}");
            $pdf->save($pdfPath);
            // $attachment[] = [
            //     'file' => $pdfPath,
            //     'name' => 'billing.pdf',
            //     'mime' => 'application/pdf',
            // ];
            Mail::to($email)->send(new EmailServices($subject,$views, $data, $pdfPath));
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
                return true;
            }
        }
        
        
        $mail = Mail::to($email)->send(new EmailServices($subject, $views, $data, $attachment));
        if($mail) {
            return true;
        }
    }
}
