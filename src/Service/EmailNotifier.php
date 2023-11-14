<?php

namespace App\Service;

class EmailNotifier
{
    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var array
     */
    private $addresses;

    public function __construct(\Swift_Mailer $mailer, \Twig_Environment $twig, string $environment)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;

        if ($environment == 'prod') {
            $this->addresses = [
                'sboyko@zaraffasoft.com',
                'm.nardoni@terravision.eu',
                'webmarketing@terravision.eu'
            ];
        } else {
            $this->addresses = [
                'sboyko@zaraffasoft.com'
            ];
        }
    }

    public function notifyAboutError($errorMessage, $transactionJson, $subject)
    {
        $transactionData = json_decode($transactionJson, true);
        $ticketId = $transactionData['ticketCode'] ?? $transactionData['ticketNr'];

        $subject = sprintf($subject . ' [%s]', $ticketId);
        $message = (new \Swift_Message($subject))
            ->setFrom('domain@terravision.eu')
            ->setTo($this->addresses)
            ->setBody(
                $this->twig->render('emails/transaction-process-error.html.twig', [
                    'errorMessage' => $errorMessage,
                    'transaction' => json_encode($transactionData, JSON_PRETTY_PRINT)
                ]),
                'text/html'
            );

        $this->mailer->send($message);
    }
}