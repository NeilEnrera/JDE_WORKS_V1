<?php

class EmailHelper
{
    private static $smtp_host = 'smtp.gmail.com';
    private static $smtp_port = 587;
    private static $smtp_user = 'jdeworks0@gmail.com';
    private static $smtp_pass = 'iwwk ykxc ycsr dide';
    private static $from_name = 'JDE Works Support';

    // Update this to your live domain in production (e.g., https://jdeworks.com.ph)
    private static $base_url = 'http://localhost/JDE_WORKS/JDE_USER/';

    /**
     * Dispatch an email to be sent in the background.
     * Prevents the user from waiting for SMTP latency.
     */
    public static function dispatchAsync($type, $params)
    {
        $data = json_encode(['type' => $type, 'params' => $params]);
        
        // Logging for QA
        file_put_contents(__DIR__ . '/mail_log.txt', "[" . date('Y-m-d H:i:s') . "] Dispatching $type to {$params['to']}\n", FILE_APPEND);

        // Create a temporary file for the mail data
        $tmpDir = __DIR__ . DIRECTORY_SEPARATOR . 'temp_mail';
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0777, true);
        
        $tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'mail_' . microtime(true) . '_' . rand(1000, 9999) . '.json';
        file_put_contents($tmpFile, $data);

        $taskPath = __DIR__ . DIRECTORY_SEPARATOR . 'background_mailer_task.php';
        $phpPath = 'C:\xampp\php\php.exe';
        if (!file_exists($phpPath)) $phpPath = 'php';

        // Windows-specific background process spawning (start /B)
        // Pass the temp file path as an argument
        $cmd = "start /B \"\" \"" . $phpPath . "\" \"" . $taskPath . "\" \"" . $tmpFile . "\"";
        
        @pclose(popen($cmd, "r"));
        return true;
    }

    private static function getEmailLayout($content, $heroColor = '#D6A347')
    {
        $logoSrc = "cid:logo_img";
        $year = date('Y');

        return "<!DOCTYPE html>
        <html xmlns:v='urn:schemas-microsoft-com:vml' xmlns:o='urn:schemas-microsoft-com:office:office'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <!--[if mso]>
            <xml>
                <o:OfficeDocumentSettings>
                <o:AllowPNG/>
                <o:PixelsPerInch>96</o:PixelsPerInch>
                </o:OfficeDocumentSettings>
            </xml>
            <![endif]-->
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');
                body { margin: 0; padding: 0; background-color: #F9FAFB; font-family: 'Outfit', 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
                table { border-spacing: 0; border-collapse: collapse; }
                td { padding: 0; vertical-align: top; }
                .container { width: 100%; max-width: 600px; margin: 0 auto; background-color: #ffffff; }
                .content-padding { padding: 40px; }
                .pill-btn { display: inline-block; padding: 14px 28px; background-color: #012B43; color: #ffffff !important; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 10px rgba(1, 43, 67, 0.2); transition: all 0.3s ease; }
                .card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px; margin-bottom: 24px; }
                p { line-height: 1.6; margin: 0 0 16px 0; color: #475569; }
                h1, h2, h3 { color: #0f172a; margin: 0 0 16px 0; font-weight: 700; }
                .footer-text { color: #94a3b8; font-size: 13px; text-align: center; line-height: 1.6; }
                a { color: #D6A347; text-decoration: none; font-weight: 600; }
            </style>
        </head>
        <body>
            <center style='width: 100%; background-color: #F3F4F6; padding-top: 40px; padding-bottom: 60px;'>
                
                <!-- Main Wrapper with Rounded Corners -->
                <table class='container' cellpadding='0' cellspacing='0' style='border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background-color: #ffffff;'>
                    
                    <!-- Email Header (Logo | Reference Style Text) -->
                    <tr>
                        <td style='padding: 25px 30px; border-bottom: 1px solid #f1f5f9;'>
                            <table width='100%'>
                                <tr>
                                    <td align='left' style='vertical-align: middle;'>
                                        <img src='{$logoSrc}' alt='JDE Logo' width='100' style='display: block; width: 100px;'>
                                    </td>
                                    <td align='right' style='vertical-align: middle; font-family: \"Outfit\", sans-serif;'>
                                        <span style='font-size: 15px; letter-spacing: 0.5px; color: #012B43;'>
                                            <b style='font-weight: 800; text-transform: uppercase;'>JDE WORKS OF OUR HANDS</b> 
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Colored Hero Accents -->
                    <tr>
                        <td style='background: linear-gradient(135deg, {$heroColor}, #012B43); height: 8px; font-size: 1px; line-height: 1px;'>&nbsp;</td>
                    </tr>
                    
                    <!-- Main Body Content -->
                    <tr>
                        <td class='content-padding'>
                            {$content}
                        </td>
                    </tr>
                    
                    <!-- Reference Style Footer -->
                    <tr>
                        <td style='padding: 50px 40px; background-color: #ffffff; border-top: 1px solid #f1f5f9; text-align: center;'>
                            <div style='max-width: 450px; margin: 0 auto;'>
                                <p style='font-size: 12px; color: #94a3b8; line-height: 1.8;'>
                                    As always, thank you for your support. If you have comments or questions about our services, please reach out to our team.
                                </p>
                                <p style='font-size: 12px; color: #64748b; margin-top: 20px;'>
                                   JDE Works | 11 Esperanza, Novaliches, Quezon City, PH
                                </p>
                            </div>

                            <p style='margin: 30px 0 0 0; font-size: 11px; color: #cbd5e1;'>
                                &copy; {$year} JDE Works Of Our Hands 
                            </p>
                        </td>
                    </tr>
                </table>
            </center>
        </body>
        </html>";
    }


    public static function sendVerificationCode($to, $code)
    {
        $subject = "Your Verification Code - JDE Works";
        $message = self::getCodeTemplate($code);
        return self::sendSMTP($to, $subject, $message);
    }

    public static function sendVerificationEmail($to, $name, $token)
    {
        $subject = "Verify Your Email - JDE Works";
        $verificationLink = self::$base_url . "backend/verify.php?token=" . $token;

        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2;'>Welcome to JDE Works, $name!</h1>
            <p>Thank you for registering with us. To complete your sign-up and secure your account, please verify your email address by clicking the button below.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='$verificationLink' class='pill-btn'>Verify Email Address</a>
            </div>
            
            <div style='background-color: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; margin-top: 30px;'>
                <p style='margin: 0; font-size: 13px; color: #64748b;'><b>Why verify?</b> Verifying your email ensures that you receive order updates, appointment reminders, and important security notifications.</p>
            </div>
        ";

        $message = self::getEmailLayout($innerContent, '#D6A347');
        return self::sendSMTP($to, $subject, $message);
    }

    public static function sendContactMessage($fromName, $fromEmail, $phone, $subjectText, $messageText)
    {
        $to = self::$smtp_user; // Send to JDE's email
        $subject = "Inquiry: " . $subjectText;
        $innerBody = "
            <h1 style='font-size: 24px;'>New Studio Inquiry</h1>
            <p>You have received a new message from the JDE Works contact form.</p>
            
            <div class='card' style='background-color: #ffffff;'>
                <table width='100%'>
                    <tr>
                        <td style='padding-bottom: 20px;'>
                            <span style='font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 1px; display: block; margin-bottom: 5px;'>Sender Information</span>
                            <span style='font-size: 16px; font-weight: 600; color: #0f172a;'>$fromName</span><br>
                            <span style='font-size: 14px; color: #64748b;'>$fromEmail · $phone</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding-top: 15px; border-top: 1px solid #f1f5f9;'>
                            <span style='font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 1px; display: block; margin-bottom: 5px;'>Message Subject</span>
                            <span style='font-size: 15px; font-weight: 600; color: #012B43;'>$subjectText</span>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding-top: 20px;'>
                            <div style='background-color: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #f1f5f9;'>
                                <p style='margin: 0; white-space: pre-wrap; font-size: 15px; color: #334155;'>" . htmlspecialchars($messageText) . "</p>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>


        ";
        $body = self::getEmailLayout($innerBody, '#012B43');
        return self::sendSMTP($to, $subject, $body);
    }

    private static function sendSMTP($to, $subject, $content, $attachments = [])
    {
        // Safety check: ensure credentials are set
        if (self::$smtp_user === 'YOUR_EMAIL@gmail.com' || empty(self::$smtp_pass)) {
            $_SESSION['smtp_error'] = "Sender credentials not configured in email_helper.php";
            return false;
        }

        // Add Logo CID attachment dynamically
        $logoPath = dirname(__DIR__) . '/assets/img/logojd.png';
        if (file_exists($logoPath)) {
            $attachments[] = [
                'path' => $logoPath,
                'name' => 'logojd.png',
                'cid' => 'logo_img',
                'type' => mime_content_type($logoPath)
            ];
        }

        $boundary = "___JDE_WORKS_BOUNDARY_" . md5(time()) . "___";

        $header = "To: $to\r\n";
        $header .= "From: " . self::$from_name . " <" . self::$smtp_user . ">\r\n";
        $header .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $header .= "MIME-Version: 1.0\r\n";

        if (empty($attachments)) {
            $header .= "Content-Type: text/html; charset=UTF-8\r\n";
            $header .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $header .= $content;
        } else {
            $header .= "Content-Type: multipart/related; boundary=\"$boundary\"\r\n\r\n";

            // HTML Part
            $header .= "--$boundary\r\n";
            $header .= "Content-Type: text/html; charset=UTF-8\r\n";
            $header .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $header .= $content . "\r\n\r\n";

            // Attachment (CID) Parts
            foreach ($attachments as $att) {
                if (!file_exists($att['path']))
                    continue;
                $fileData = base64_encode(file_get_contents($att['path']));

                $header .= "--$boundary\r\n";
                $header .= "Content-Type: {$att['type']}; name=\"{$att['name']}\"\r\n";
                $header .= "Content-Transfer-Encoding: base64\r\n";
                $header .= "Content-ID: <{$att['cid']}>\r\n";
                $header .= "Content-Disposition: inline; filename=\"{$att['name']}\"\r\n\r\n";
                $header .= chunk_split($fileData) . "\r\n";
            }

            $header .= "--$boundary--\r\n";
        }

        try {
            $smtp = fsockopen(self::$smtp_host, self::$smtp_port, $errno, $errstr, 10);
            if (!$smtp)
                throw new Exception("Connection failed: $errstr");

            self::expect($smtp, "220");
            fwrite($smtp, "EHLO localhost\r\n");
            self::expect($smtp, "250");

            // Switch to TLS for Port 587
            fwrite($smtp, "STARTTLS\r\n");
            self::expect($smtp, "220");
            if (!stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("TLS encryption failed.");
            }

            fwrite($smtp, "EHLO localhost\r\n");
            self::expect($smtp, "250");

            fwrite($smtp, "AUTH LOGIN\r\n");
            self::expect($smtp, "334");
            fwrite($smtp, base64_encode(self::$smtp_user) . "\r\n");
            self::expect($smtp, "334");
            fwrite($smtp, base64_encode(self::$smtp_pass) . "\r\n");
            self::expect($smtp, "235");

            fwrite($smtp, "MAIL FROM: <" . self::$smtp_user . ">\r\n");
            self::expect($smtp, "250");
            fwrite($smtp, "RCPT TO: <$to>\r\n");
            self::expect($smtp, "250");
            fwrite($smtp, "DATA\r\n");
            self::expect($smtp, "354");

            // Send header and body in chunks to handle large contents
            $fullData = $header . "\r\n.\r\n";
            $length = strlen($fullData);
            $offset = 0;
            while ($offset < $length) {
                $written = fwrite($smtp, substr($fullData, $offset, 8192));
                if ($written === false)
                    break;
                $offset += $written;
            }

            self::expect($smtp, "250");
            fwrite($smtp, "QUIT\r\n");
            fclose($smtp);
            
            file_put_contents(__DIR__ . '/mail_log.txt', "[" . date('Y-m-d H:i:s') . "] SUCCESS: Email sent to $to\n", FILE_APPEND);
            return true;
        } catch (Exception $e) {
            $errMsg = $e->getMessage();
            file_put_contents(__DIR__ . '/mail_log.txt', "[" . date('Y-m-d H:i:s') . "] FAILED: To $to - Error: $errMsg\n", FILE_APPEND);
            $_SESSION['smtp_error'] = $errMsg;
            return false;
        }
    }

    private static function expect($res, $code)
    {
        $data = "";
        while ($str = fgets($res, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ")
                break;
        }
        if (strpos($data, $code) !== 0)
            throw new Exception("SMTP Error: " . trim($data));
    }
    public static function sendOrderStatusUpdate($to, $name, $orderID, $status, $items = [])
    {
        $subject = "Order Update: #ORD-" . str_pad($orderID, 3, '0', STR_PAD_LEFT);

        $statusColors = [
            'shipped' => '#0F172A',
            'ready to deliver' => '#D6A347',
            'ready for pick up' => '#D6A347',
            'awaiting balance' => '#E67E22',
            'delivered' => '#10B981',
            'completed' => '#10B981',
            'cancelled' => '#EF4444'
        ];

        $themeColor = $statusColors[strtolower($status)] ?? '#012B43';
        $baseUrl = self::$base_url;

        // Collect images for CID attachments
        $attachments = [];
        $itemsHtml = "";

        if (!empty($items)) {
            $itemsHtml = "<div style='margin-top: 30px;'><h3 style='font-size: 16px; margin-bottom: 15px;'>Order Summary</h3>";
            foreach ($items as $idx => $item) {
                $relativeImagePath = str_replace('../', '', $item['productImage'] ?? '');
                $localPath = dirname(__DIR__) . '/' . $relativeImagePath;
                $imgSrc = self::$base_url . $relativeImagePath;

                if (file_exists($localPath) && !empty($relativeImagePath)) {
                    $cid = "prod_img_" . $idx;
                    $attachments[] = ['path' => $localPath, 'name' => basename($localPath), 'cid' => $cid, 'type' => mime_content_type($localPath)];
                    $imgSrc = "cid:" . $cid;
                }

                $itemsHtml .= "
                <div class='card' style='padding: 15px; margin-bottom: 15px; background-color: #ffffff;'>
                    <table width='100%'>
                        <tr>
                            <td width='60' style='padding-right: 15px;'>
                                <img src=\"$imgSrc\" width='60' height='60' style='border-radius: 8px; object-fit: cover;'>
                            </td>
                            <td>
                                <span style='font-size: 14px; font-weight: 700; color: #0f172a; display: block;'>" . htmlspecialchars($item['productName']) . "</span>
                                <span style='font-size: 12px; color: #64748b;'>Qty: {$item['quantity']} · Size: " . htmlspecialchars($item['size'] ?? 'Custom') . "</span>
                            </td>
                            <td align='right'>
                                <span style='font-size: 14px; font-weight: 700; color: #012B43;'>₱" . number_format((float) ($item['price'] ?? 0), 2) . "</span>
                            </td>
                        </tr>
                    </table>
                </div>";
            }
            $itemsHtml .= "</div>";
        }

        $statusMessages = [
            'shipped' => "Exciting news! Your items have been hand-packed with care and are now on their way through our logistics partners. We've ensured everything meets our quality standards before dispatch.",
            'ready to deliver' => "Great news! Our team has finalized your pieces, and they are now tagged for immediate delivery. You should receive a call from our rider shortly.",
            'ready for pick up' => "Your craftsmanship journey is complete! Your order is now safe and sound at our studio, waiting for you to bring it home.",
            'awaiting balance' => "Your pieces are looking great! We have reached the stage where the remaining balance is required. Once you've settled the final payment and uploaded the proof, we can proceed with dispatching your order immediately.",
            'delivered' => "We hope you love your new JDE Works pieces! Thank you for trusting our hands with your style. Feel free to tag us on social media!",
            'completed' => "This transaction is now officially fulfilled. It has been a pleasure serving you, and we hope to see you again soon for your next tailoring need.",
            'cancelled' => "We are sorry to note that your order has been cancelled. If you believe this is an error or need a refund status, please contact us immediately."
        ];

        $statusNote = $statusMessages[strtolower($status)] ?? "We have updated the status of your order in our system.";

        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2;'>Your order is $status!</h1>
            <p>Hello $name, we have a fresh update on your order <strong>#ORD-" . str_pad($orderID, 3, '0', STR_PAD_LEFT) . "</strong>. Our team has been working hard to ensure everything is perfect.</p>
            
            <div class='card' style='background-color: " . ($themeColor . '1A') . "; border-color: " . ($themeColor . '33') . "; text-align: center; padding: 30px;'>
                 <span style='background-color: $themeColor; color: #ffffff; font-size: 11px; font-weight: 800; padding: 6px 16px; border-radius: 30px; text-transform: uppercase; letter-spacing: 1px;'>$status</span>
                 <h2 style='margin: 20px 0 0 0; color: $themeColor;'>#ORD-" . str_pad($orderID, 3, '0', STR_PAD_LEFT) . "</h2>
            </div>

            <p style='font-style: italic; color: #475569; border-left: 3px solid $themeColor; padding-left: 15px; margin: 20px 0;'>\"$statusNote\"</p>

            " . (strtolower($status) === 'ready for pick up' ? "
            <div class='card' style='background-color: #FEFCE8; border-color: #FEF08A;'>
                <h3 style='margin-top: 0; color: #854D0E; font-size: 16px;'>📍 Collection Point</h3>
                <p style='margin-bottom: 5px; color: #854D0E;'><b>Hilltop Branch</b><br>11 Esperanza, Novaliches, Hilltop Subd. Greater Lagro, Quezon City</p>
                <p style='margin: 0; font-size: 13px; color: #A16207;'><b>Studio Hours:</b> Mon-Sat, 8:00 AM - 5:00 PM</p>
            </div>
            " : "") . "

            $itemsHtml


        ";

        $message = self::getEmailLayout($innerContent, $themeColor);
        return self::sendSMTP($to, $subject, $message, $attachments);
    }

    public static function sendAppointmentReminder($to, $name, $appointmentDate, $appointmentTime, $serviceType)
    {
        $subject = "Reminder: Your Appointment - JDE Works";
        $formattedDate = date('F j, Y', strtotime($appointmentDate));
        $formattedTime = date('g:i A', strtotime($appointmentTime));

        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2;'>See you today, $name!</h1>
            <p>Just a friendly reminder about your visit to our studio today. We've prepared everything for your session.</p>
            
            <div class='card'>
                <div style='margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;'>
                    <span style='font-size: 11px; text-transform: uppercase; font-weight: 800; color: #D6A347; letter-spacing: 1px; display: block; margin-bottom: 5px;'>Selected Service</span>
                    <h3 style='margin: 0; color: #012B43;'>$serviceType</h3>
                </div>
                
                <table width='100%'>
                    <tr>
                        <td width='50%'>
                            <span style='font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 1px; display: block; margin-bottom: 5px;'>Date</span>
                            <span style='font-size: 16px; font-weight: 600; color: #0f172a;'>$formattedDate</span>
                        </td>
                        <td width='50%'>
                            <span style='font-size: 11px; text-transform: uppercase; font-weight: 800; color: #94a3b8; letter-spacing: 1px; display: block; margin-bottom: 5px;'>Time</span>
                            <span style='font-size: 16px; font-weight: 600; color: #0f172a;'>$formattedTime</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style='background-color: #f1f5f9; border-radius: 12px; padding: 20px; text-align: center;'>
                <h4 style='margin: 0 0 10px 0; color: #012B43;'>💡 Tips for your visit</h4>
                <p style='font-size: 14px; margin: 0;'>Please arrive 5-10 minutes early. If you need to reschedule, let us know at least 4 hours in advance.</p>
            </div>
        ";

        $message = self::getEmailLayout($innerContent, '#012B43');
        return self::sendSMTP($to, $subject, $message);
    }

    private static function getCodeTemplate($code)
    {
        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2;'>Verification Code</h1>
            <p>Use the secure code below to complete your password reset request. This code is unique to you and will expire in 10 minutes.</p>
            
            <div class='card' style='text-align: center; background-color: #f8fafc;'>
                <span style='color: #012B43; font-size: 38px; font-weight: 800; letter-spacing: 12px; display: block; padding: 10px 0;'>$code</span>
            </div>
            
            <div style='background-color: #fef3c7; border-radius: 12px; padding: 15px; border: 1px solid #fde68a;'>
                <p style='margin: 0; font-size: 13px; color: #92400e;'><b>Security Tip:</b> Never share this code with anyone. JDE Works staff will never ask for your verification code.</p>
            </div>
        ";
        return self::getEmailLayout($innerContent, '#D6A347');
    }
    public static function sendAppointmentConfirmation($to, $name, $date, $time, $serviceType)
    {
        $subject = "Appointment Received - JDE Works";
        $formattedDate = date('F j, Y', strtotime($date));

        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2;'>We've got your request!</h1>
            <p>Hello $name, thank you for choosing JDE Works. We have received your booking and our team is currently reviewing the schedule.</p>
            
            <div class='card' style='background-color: #f0f9ff; border-color: #bae6fd;'>
                <div style='margin-bottom: 15px;'>
                    <span style='background-color: #0ea5e9; color: #ffffff; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;'>Pending Review</span>
                </div>
                <h3 style='margin: 0 0 10px 0;'>$serviceType</h3>
                <p style='margin: 0; font-size: 15px; font-weight: 500;'>📅 $formattedDate at $time</p>
            </div>

            <p>You don't need to do anything else right now. We will send you another email as soon as your appointment is confirmed.</p>
            

        ";

        $message = self::getEmailLayout($innerContent, '#0EA5E9');
        return self::sendSMTP($to, $subject, $message);
    }

    public static function sendAppointmentStatusNotification($to, $name, $date, $time, $serviceType, $status)
    {
        $subject = "Appointment Status Update - JDE Works";
        $formattedDate = date('F j, Y', strtotime($date));
        $isAccepted = (strtolower($status) === 'accepted');
        $themeColor = $isAccepted ? '#10B981' : '#EF4444';
        $bgColor = $isAccepted ? '#ECFDF5' : '#FEF2F2';
        $borderColor = $isAccepted ? '#A7F3D0' : '#FECACA';

        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2; color: $themeColor;'>" . ($isAccepted ? 'Booking Confirmed!' : 'Update regarding your request') . "</h1>
            <p>Hello $name, we have reviewed your request and we are excited to have you at our studio for your <strong>$serviceType</strong> session.</p>
            
            <div class='card' style='background-color: $bgColor; border-color: $borderColor;'>
                <div style='margin-bottom: 15px;'>
                    <span style='background-color: $themeColor; color: #ffffff; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;'>" . strtoupper($status) . "</span>
                </div>
                <h3 style='margin: 0 0 10px 0; color: #012B43;'>$serviceType</h3>
                <p style='margin: 0; font-size: 15px; color: #475569;'>📅 $formattedDate at $time</p>
            </div>

            <div style='margin: 35px 0;'>
                " . ($isAccepted ? "
                <h3 style='font-size: 18px; color: #012B43;'>✨ What to expect & how to prepare</h3>
                <p>To ensure you get the best experience during your session, please keep the following in mind:</p>
                <ul style='color: #475569; padding-left: 20px; font-size: 14px; line-height: 1.8;'>
                    <li><b>Be on Time:</b> Please arrive at least 10 minutes before your scheduled time for a quick consultation.</li>
                    <li><b>References:</b> Feel free to bring any photos, reference images, or fabric samples you'd like us to look at.</li>
                    <li><b>Studio Protocol:</b> We maintain a clean and professional environment; your comfort is our priority.</li>
                </ul>
                
                <div class='card' style='margin-top: 30px; background-color: #f8fafc; border: 1px dashed #cbd5e1;'>
                    <h4 style='margin: 0 0 8px 0; color: #012B43;'>📍 Studio Location</h4>
                    <p style='font-size: 13px; margin: 0;'>11 Esperanza, Hilltop Subd. Greater Lagro, Novaliches, Quezon City</p>
                </div>


                " : "
                <p style='margin-bottom: 25px;'>Unfortunately, we couldn't accommodate your requested time at this moment. This might be due to a sudden change in studio availability or a conflicting booking.</p>
                <p><b>Next steps:</b> You can check our live calendar for other available slots that might fit your schedule better.</p>
                

                ") . "
            </div>
        ";

        $message = self::getEmailLayout($innerContent, $themeColor);
        return self::sendSMTP($to, $subject, $message);
    }

    public static function sendAppointmentBlockedNotification($to, $name, $date, $time, $serviceType)
    {
        $subject = "Important: Appointment Update - JDE Works";
        $formattedDate = date('F j, Y', strtotime($date));

        $innerContent = "
            <h1 style='font-size: 28px; line-height: 1.2; color: #F59E0B;'>Schedule Adjustment Required</h1>
            <p>Hello $name, we are writing to inform you that your appointment on <strong>$formattedDate</strong> has been cancelled as our studio will be unavailable on this date.</p>
            
            <div class='card' style='background-color: #FFFBEB; border-color: #FEF3C7;'>
                <div style='margin-bottom: 12px;'>
                    <span style='background-color: #F59E0B; color: #ffffff; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase;'>Reschedule Required</span>
                </div>
                <h3 style='margin: 0 0 10px 0; color: #92400E;'>$serviceType</h3>
                <p style='margin: 0; font-size: 15px; color: #B45309;'>📅 Original Date: $formattedDate at $time</p>
            </div>

            <p>We apologize for any inconvenience caused. Please select a new time that works for you by clicking the button below.</p>
            

        ";

        $message = self::getEmailLayout($innerContent, '#F59E0B');
        return self::sendSMTP($to, $subject, $message);
    }
}
