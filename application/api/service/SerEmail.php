<?php
/**
 * Created by PhpStorm.
 * User: administrator_liwy
 * Date: 2019/5/17
 * Time: 15:08
 */

namespace app\api\service;



use app\lib\exception\CommonException;
use PHPMailer\PHPMailer\PHPMailer;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use think\Exception;

class SerEmail
{

    /**
     * @param $head
     * @param $body
     * @param $address_array
     * 异步发送邮件
     */
    public function sendEmail($head, $body, $address_array)
    {
        $post_data['head'] = $head;
        $post_data['body'] = $body;
        $post_data['address_array'] = json_encode($address_array);
        $url = config('my_config.local_url') . 'api/v1/send_email';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch,CURLOPT_NOSIGNAL,1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 200);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//这个是重点。
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $t = curl_exec($ch);
        curl_close($ch);
    }


    public function setEmail($head, $body, $address_array = [])
    {

        $mail = new PHPMailer();
        if (count($address_array) > 0) {
            foreach ($address_array as $k => $v) {
                $mail->addAddress($v);
            }
        } else {
            $mail->addAddress('987303897@qq.com');
            $head = '无有效收件人';
        }
        //为正文添加结尾
        $body .= "\n\n本邮件由系统自动发送，请勿直接回复！\n感谢您的访问，祝您使用愉快";

        $mail->isSMTP();// 使用SMTP服务
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
        $mail->Host = config('my_config.email_host');// 发送方的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
        $mail->Username = config('my_config.email_address');/// 发送方用户名
        $mail->Password = config('my_config.email_password');// 发送方邮箱密码
        //$mail->SMTPSecure = "ssl";// 使用ssl协议方式
        $mail->Port = config('my_config.email_port');// 163邮箱的ssl协议方式端口号是465/994

        $mail->setFrom(config('my_config.email_address'), "江苏岗隆数码科技有限公司");// 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
        //$mail->addAddress($to_email, '管理员');// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
        //$mail->addReplyTo("13890605917@163.com", "系统");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        //$mail->addCC("xxx@163.com");// 设置邮件抄送人，可以只写地址，上述的设置也可以只写地址(这个人也能收到邮件)
        //$mail->addBCC("xxx@163.com");// 设置秘密抄送人(这个人也能收到邮件)
        //$mail->addAttachment("bug0.jpg");// 添加附件

        $mail->Subject = $head;// 邮件标题

        $mail->Body = $body;// 邮件正文

        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用

        try {
            $mail->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            throw new CommonException(['msg'=>$e]);
        }
        /* if(!$mail->send()){// 发送邮件
             发生错误
         }else{
             成功
         }*/
    }

    public function newSetEmail($head, $body, $address_array = [])
    {
        $transport = (new Swift_SmtpTransport(config('my_config.email_host'), config('my_config.email_port')))
            ->setUsername(config('my_config.email_address'))
            ->setPassword(config('my_config.email_password'));

        $mailer = new Swift_Mailer($transport);

        if (count($address_array) === 0) {
            $address_array = ['987303897@qq.com'];
            $head = '无有效收件人';
        }

        //为正文添加结尾
        $body .= "\n\n本邮件由系统自动发送，请勿直接回复！\n感谢您的访问，祝您使用愉快";

        $message = (new Swift_Message($head))
            ->setFrom([config('my_config.email_address') => '江苏岗隆数码科技有限公司'])
            ->setTo($address_array)
            ->setBody($body);
        try {
            $result = $mailer->send($message);
        }catch (Exception $e){
            throw new CommonException(['msg'=>$e]);
        }


        //Log::write($head . $result);

        return true;
    }
}