<?php

namespace Vixedin\System\Modules;

use Swift_Mailer;
use Swift_SmtpTransport;

/**
 * Class Emailer
 *
 * @package Tso\System
 */
class Mailer
{
    /**
     * @var Swift_Mailer
     */
    private Swift_Mailer $mailer;

    /**
     * Undocumented function
     */
    public function __construct()
    {

        // Create the Transport
        $transport = (new Swift_SmtpTransport(APP_SMTP_HOST, APP_SMTP_PORT, APP_SMTP_SECURITY))
            ->setUsername(APP_SMTP_USER)
            ->setPassword(APP_SMTP_PASS);

        // Create the Mailer using your created Transport
        $this->mailer = new Swift_Mailer($transport);
    }

    /**
     * @param $messageData
     * @param bool $ordersEmail
     * @return int
     */
    public function send($messageData, bool $ordersEmail = false): int
    {
        if (isset($messageData['bcc'])) {
            // Create a message
            $message = (new \Swift_Message($messageData['subject']))
                ->setFrom([APP_SMTP_FROM_EMAIL => ($messageData['toName'] ?? APP_NAME)])
                ->setTo($messageData['to'])
                ->setBcc($messageData['bcc'])
                ->setBody($messageData['body'], APP_SMTP_BODY_TYPE);
        } else {
            // Create a message
            if ($ordersEmail) {
                $trans = (new \Swift_SmtpTransport(APP_SMTP_HOST, APP_SMTP_PORT, APP_SMTP_SECURITY))
                    ->setUsername(ORDERS_EMAIL)
                    ->setPassword(ORDERS_EMAIL_PASS);
                $m = new Swift_Mailer($trans);

                $message = (new \Swift_Message($messageData['subject']))
                    ->setFrom([ORDERS_EMAIL => ($messageData['toName'] ?? APP_NAME)])
                    ->setTo($messageData['to'])
                    ->setBody($messageData['body'], APP_SMTP_BODY_TYPE);
                return $m->send($message);

            } else {
                $message = (new \Swift_Message($messageData['subject']))
                    ->setFrom([APP_SMTP_FROM_EMAIL => ($messageData['toName'] ?? APP_NAME)])
                    ->setTo($messageData['to'])
                    ->setBody($messageData['body'], APP_SMTP_BODY_TYPE);
            }
        }
        // Send the message
        return $this->mailer->send($message);
    }
}
