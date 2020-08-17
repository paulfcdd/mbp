<?php


namespace App\Controller\Front\Teaser;


use App\Traits\SerializerTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Traits\Dashboard\MacrosReplacementTrait;

class TeaserTopController extends TeaserController
{
    use SerializerTrait;
    use MacrosReplacementTrait;

    protected string $pageType = 'top';

    /**
     * @Route("/teasers", name="front.top_teasers")
     * @return Response
     */
    public function renderPage()
    {
        $this->getTeasers();

        return $this->render("front/$this->theme/teaser/all_teaser.html.twig", [
            'teasers' => $this->teasers,
            'city' => $this->getCity(),
            'width_teaser_block' => $this->cropVariant->getWidthTeaserBlock(),
            'height_teaser_block' => $this->cropVariant->getHeightTeaserBlock(),
        ]);
    }

    /**
     * @Route("/ajax-teasers/{page}", name="front.ajax_top_teasers")
     * @param int $page
     * @return Response
     */
    public function getAjaxTeasers(int $page = 1)
    {
        $this->getTeasers($page);
        $serializer = $this->serializer();
        $teasers = $this->replaceMacrosToCity();

        return JsonResponse::create($serializer->serialize($teasers, 'json'), 200);
    }

}