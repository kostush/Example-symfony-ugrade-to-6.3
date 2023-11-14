<?php

namespace App\Service\Autostradale;

use Symfony\Component\HttpFoundation\RequestStack;

class TicketProcessor
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(string $projectDir, RequestStack $requestStack)
    {
        $this->projectDir = $projectDir;
        $this->requestStack = $requestStack;
    }

    public function addLogo($bookingId, $originalTicketLink): string
    {
        try {
            $relativePath = "/autostradale/tickets/$bookingId.pdf";
            $filePath = $this->projectDir . '/public' . $relativePath;
            file_put_contents($filePath, file_get_contents($originalTicketLink));

            $pdf = new \FPDI();
            $pageCount = $pdf->setSourceFile($filePath);

            for ($currentPage = 1; $currentPage <= $pageCount; $currentPage++) {
                $pdf->AddPage();
                $pdf->useTemplate(
                    $pdf->importPage($currentPage)
                );

                $pdf->Image($this->projectDir . '/public/terravision.jpg', 150, 14, 50, 10);
            }

            $pdf->Output($filePath, 'F');

            return $this->requestStack->getCurrentRequest()->getUriForPath($relativePath);
        } catch (\Exception $ex) {
            return $originalTicketLink;
        }
    }
}