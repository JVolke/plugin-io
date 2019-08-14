<?php

namespace IO\Services;

use Plenty\Plugin\Mail\Contracts\MailerContract;
use Plenty\Plugin\Mail\Models\ReplyTo;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Translation\Translator;
use Plenty\Plugin\Log\Loggable;


class ContactMailService
{
    use Loggable;

    public function sendMail($mailTemplate, $mailData = [])
    {
        $recipient = $mailData['recipient'];

        if ( !strlen($recipient) )
        {
            /** @var TemplateConfigService $templateConfigService */
            $templateConfigService = pluginApp(TemplateConfigService::class);
            $recipient = $templateConfigService->get('contact.shop_mail');

        }

        if(!strlen($recipient) || !strlen($mailTemplate))
        {
            $this->getLogger(__CLASS__)->error("IO::Debug.ContactMailService_noRecipient");
            return false;
        }

        /** @var Twig */
        $twig = pluginApp(Twig::class);

        $mailBody = $twig->render(
            $mailTemplate,
            $mailData
        );

        if(!strlen($mailBody))
        {
            $this->getLogger(__CLASS__)->error("IO::Debug.ContactMailService_noMailContent");
            return false;
        }
        $this->getLogger(__CLASS__)->error("MailData", $mailData);

        /** @var MailerContract $mailer */
        $mailer = pluginApp(MailerContract::class);

        $replyTo = null;
        if ( array_key_exists('replyTo', $mailData) )
        {
            /** @var ReplyTo $replyTo */
            $replyTo = pluginApp(ReplyTo::class);
            $replyTo->mailAddress = $mailData['replyTo']['mail'];
            $replyTo->name = $mailData['replyTo']['name'];
        }

        $translator = pluginApp(Translator::class);
        $subject = $translator->trans(
            'Ceres::Template.contactMailSubject',
            [
                'subject' => $mailData['subject'],
                'data'    => $mailData['data']
            ]
        );

        $mailer->sendHtml($mailBody, $recipient, $subject, $mailData['cc'] ?? [], $mailData['bcc'] ?? [], $replyTo);
        return true;
    }
}
