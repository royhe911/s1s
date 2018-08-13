<?php

class Mail
{
    // 单例
    private static $instance;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new PHPMailer\PHPMailer\PHPMailer(true);
        }
        return self::$instance;
    }

    /**
     * 邮件发送
     * 
     * @param $to_user 发件人 数组（多个） 字符串（单个）
     * @param $subject 邮件标题            
     * @param $body 邮件内容            
     * @return bool
     */
    public static function sendEmail($to_user, $subject, $body)
    {
        $email_conf = config('other_api.email');
        $mail = self::instance();
        try {
            // Server settings
            // $mail->SMTPDebug = 2; // Enable verbose debug output
            $mail->isSMTP(); // Set mailer to use SMTP
            $mail->Host = $email_conf['host']; // Specify main and backup SMTP servers
            $mail->SMTPAuth = true; // Enable SMTP authentication
            $mail->Username = $email_conf['addr']; // SMTP username
            $mail->Password = $email_conf['pass']; // SMTP password
            $mail->SMTPSecure = $email_conf['security']; // Enable TLS encryption, `ssl` also accepted
            $mail->Port = $email_conf['port']; // TCP port to connect to
            $mail->CharSet = "utf8"; // 字符设置
            $mail->Encoding = "base64"; // 编码方式
                                        
            // Recipients
            $mail->setFrom($email_conf['addr'], "=?utf-8?B?" . base64_encode("{$email_conf['name']}") . "?=");
            if (is_array($to_user)) {
                foreach ($to_user as $v) {
                    $mail->addAddress($v);
                }
            } else {
                $mail->addAddress($to_user); // Add a recipient
            }
            // $mail->addAddress('ellen@example.com'); // Name is optional
            // $mail->addReplyTo('info@example.com', 'Information');
            // $mail->addCC('cc@example.com');
            // $mail->addBCC('bcc@example.com');
            
            // Attachments
            // $mail->addAttachment('/var/tmp/file.tar.gz'); // Add attachments
            // $mail->addAttachment('/tmp/image.jpg', 'new.jpg'); // Optional name
            
            // Content
            $mail->isHTML(true); // Set email format to HTML
            $mail->Subject = "=?utf-8?B?" . base64_encode("{$subject}") . "?=";
            $mail->Body = $body;
            // $mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
