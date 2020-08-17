<?php

namespace App\Twig\Dashboard\MediaBuyer;

use App\Entity\Algorithm;
use App\Entity\AlgorithmsAggregatedStatistics;
use App\Entity\Design;
use App\Entity\DesignsAggregatedStatistics;
use App\Entity\EntityInterface;
use App\Entity\User;
use App\Twig\AppExtension;
use Cocur\Slugify\Slugify;
use Twig\TwigFunction;

class SplitTestsExtension extends AppExtension
{
    const PNG = '.png';

    public function getFunctions()
    {
        return [
            new TwigFunction('get_design_screen', [$this, 'getDesignScreen']),
            new TwigFunction('get_algorithm_statistic', [$this, 'getAlgorithmStatistic']),
            new TwigFunction('get_design_statistic', [$this, 'getDesignStatistic']),
            new TwigFunction('render_active_checkbox', [$this, 'renderActiveCheckbox'])
        ];
    }

    /**
     * @param string $designName
     * @return string
     */
    public function getDesignScreen(string $designName)
    {
        $slugify = new Slugify();

        return $this->twigEnvironment->render('dashboard/partials/table/image_preview.html.twig', [
            'image_path' =>$this->container->getParameter('design_screen_path') . DIRECTORY_SEPARATOR . $slugify->slugify($designName) . self::PNG,
        ]);
    }

    /**
     * @param Algorithm $algorithm
     * @param User $mediaBuyer
     * @return string
     */
    public function getAlgorithmStatistic(Algorithm $algorithm, User $mediaBuyer)
    {
        return $this->entityManager->getRepository(AlgorithmsAggregatedStatistics::class)->getAlgorithmBuyerStatistic($algorithm, $mediaBuyer);
    }

    /**
     * @param Design $design
     * @param User $mediaBuyer
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getDesignStatistic(Design $design, User $mediaBuyer)
    {
        $statistic = $this->entityManager->getRepository(DesignsAggregatedStatistics::class)->getDesignBuyerStatistic($design, $mediaBuyer);

        return $this->twigEnvironment->render('dashboard/mediabuyer/split-tests/partials/design-statistic-row.html.twig', [
            'design' => $design,
            'statistic' => $statistic,
        ]);
    }

    /**
     * @param EntityInterface $entity
     * @param User $user
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderActiveCheckbox(EntityInterface $entity, User $user)
    {
        return $this->twigEnvironment->render('dashboard/partials/table/active_checkbox.html.twig', [
            'is_active' => $this->entityManager->getRepository(get_class($entity))->getIsActiveForBuyer($entity, $user) ? true : false,
            'id' => $entity->getId()
        ]);
    }
}