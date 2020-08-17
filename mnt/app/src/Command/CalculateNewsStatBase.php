<?php
namespace App\Command;

use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;

abstract class CalculateNewsStatBase extends Command
{
    public EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function isInactiveOrDeleted($newsItem)
    {
        return $newsItem['is_deleted'] || !$newsItem['isActive'];
    }

    protected function isOlderThen24Hours($newsItem)
    {
        return $newsItem['updatedAt'] > Carbon::now()->subHours(24);
    }

    protected function calculateInnerCTR($innerClick, $innerShow)
    {
        if ($innerShow != 0 ) {
            return $innerClick / $innerShow * 100;
        }

        return 0;
    }

    protected function calculateInnerECPM($approvedConversionsAmountSumm, $innerShow)
    {
        if ($approvedConversionsAmountSumm && $innerShow != 0) {
            return $approvedConversionsAmountSumm / $innerShow * 1000;
        }

        return 0;
    }

    protected function calculateProbiv($teasersClickCount, $clickCount)
    {
        if ($clickCount != 0) {
            return $teasersClickCount / $clickCount * 100;
        }
        
        return 0;
    }

    protected function calculateApprove($approvedConversionsCount, $allConversionsCount)
    {
        if ($allConversionsCount != 0) {
            return $approvedConversionsCount / $allConversionsCount;
        }

        return 0;
    }

    protected function calculateEPC($approvedConversionsAmountSumm, $teasersClickCount)
    {
        if ($teasersClickCount) {
            return $approvedConversionsAmountSumm / $teasersClickCount;
        }
        
        return 0;
    }

    protected function calculateCR($allConversionsCount, $teasersClickCount)
    {
        if ($teasersClickCount) {
            return $allConversionsCount / $teasersClickCount * 100;
        }
        
        return 0;
    }
}