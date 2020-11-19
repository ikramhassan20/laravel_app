<?php

namespace App\Http\Resources\V1\Notifications;

use App\Components\AppStatusCodes;
use App\Helpers\CommonHelper;
use App\Mail\NotificationMail;
use App\Notification;
use App\NotificationsLog;
use Carbon\Carbon;
use Mockery\Matcher\Not;

class SendEmail
{
    /**
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $company;

    /**
     * @var array
     */
    protected $message;

    /**
     * @var array
     */
    protected $sender;

    protected $mail_from_address;

    protected $subject;

    protected $host;

    protected $port;

    protected $encryption;

    protected $username;

    protected $password;

    public function __construct($company, $recipient, $sender = null)
    {
        //  dd(config('mail'));
        //  dd($recipient);
        $this->mail_from_address = config('mail.from.address');
        $this->subject = config('mail.from.name');
        $this->host = config('mail.host');
        $this->port = config('mail.port');
        $this->encryption = config('mail.encryption');
        $this->username = config('mail.username');
        $this->password = config('mail.password');
        $this->sender = !empty($sender) ? $sender : config('mail.from');
        $this->company = $company;
        $this->message = [
            'subject' => !empty($recipient['subject']) ? $recipient['subject'] : $this->subject,
            'mail_from' => !empty($recipient['from_email']) ? $recipient['from_email'] : $this->mail_from_address,
            'name' => $this->subject,
            'message' => $recipient['message'],
            'receiver_email' => $recipient['email'],
            'receiver_name' => "{$recipient['firstname']} {$recipient['lastname']}"
        ];
    }

    /**
     * Send notification.
     *
     * @throws \Exception
     *
     * @return array
     */
    public function send()
    {
        return $this->sendEmailThroughCompanySetting();
    }

    /**
     * Send email through company email setting.
     *
     * @param \Illuminate\Database\Eloquent\Model $setting
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function sendEmailThroughCompanySetting()
    {
        try {
            $transport = (new \Swift_SmtpTransport($this->host, $this->port, $this->encryption))
                ->setUsername($this->username)
                ->setPassword($this->password);

            $mailer = new \Swift_Mailer($transport);

            $message = (new \Swift_Message())
                ->setSubject($this->message['subject'])
                ->setBody($this->message['message'], 'text/html')
                ->setFrom(config('mail.from.address'), config('mail.from.name'))
                ->setReplyTo(config('mail.reply_to.address'),config('mail.reply_to.name'))
                ->setTo($this->message['receiver_email'], $this->message['receiver_name']);
            $notification = new Notification();
            $notification->email = $this->message['receiver_email'];
            $notification->payload = \GuzzleHttp\json_encode(
                array_filter($this->message),
                true
            );
            $response = $mailer->send($message);
            if ((bool)$response === true) {
                $notification->sent = true;
                $notification->sent_at = Carbon::now();
                $notification->save();
                $saveNotification = CommonHelper::saveNotificationlogs($notification->id, 'success', 'Email Send Successfully.');
                return [
                    'status' => AppStatusCodes::HTTP_OK,
                    'data' => $this->message,
                    'message' => 'Email sent successfully.'
                ];
            } else {
                $notification->sent = false;
                $notification->sent_at = Carbon::now();
                $notification->save();
                $saveNotification = CommonHelper::saveNotificationlogs($notification->id, 'error', 'Failed email not send.');
                return [
                    'status' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                    'data' => "Unable to send email to address {$this->message['receiver_email']}.",
                    'message' => 'Failed Email Not Send.'
                ];
            }
        } catch (\Exception $exception) {
            return [
                'status'=>AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'code' => AppStatusCodes::HTTP_UNPROCESSABLE_ENTITY,
                'data'=>$exception->getMessage(),
                'message' => $exception->getMessage()
            ];
        }
    }

    /**
     * Send email through default email setting.
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function sendEmailThroughDefaultSetting()
    {
        try {
            \Mail::to($this->message['receiver_email'])
                ->send(new NotificationMail($this->message, $this->sender));

            return [
                'code' => AppStatusCodes::HTTP_OK,
                'message' => 'Email sent successfully'
            ];
        } catch (\Exception $exception) {
            return [
                'code' => AppStatusCodes::HTTP_OK,
                'message' => $exception->getMessage()
            ];
        }
    }
}
