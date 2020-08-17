<?php


namespace App\Controller\Dashboard\MediaBuyer;


use App\Controller\Dashboard\DashboardController;
use App\Controller\Dashboard\NewsControllerInterface;
use App\Entity\News;
use App\Entity\NewsType;
use App\Entity\User;
use App\Entity\UserSettings;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class SettingsController extends DashboardController
{
    /**
     * @Route("/mediabuyer/settings/edit", name="mediabuyer_dashboard.settings_edit")
     */
    public function editAction(UserPasswordEncoderInterface $encoder)
    {
        $form = $this->createSettingsForm($this->getUser());

        $newPassword = $form->get('changed_password')->getData();
        if (!is_null($newPassword) && !empty($newPassword) && $newPassword !== "") {
            $this->getUser()->setPassword($encoder->encodePassword($this->getUser(), $newPassword));
            $this->addFlash('success', $this->getFlashMessage('settings_edit_password'));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $this->createOrChangeUserSetting($form, 'ecrm_teasers_view_count');
            $this->createOrChangeUserSetting($form, 'ecrm_news_view_count');
            $this->createOrChangeUserSetting($form, 'default_currency');
            $this->entityManager->flush();
            $this->addFlash('success', $this->getFlashMessage('settings_edit'));
            return $this->redirectToRoute('mediabuyer_dashboard.settings_edit', []);
        }

        $this->getUserSettings($form, 'ecrm_teasers_view_count' );
        $this->getUserSettings($form, 'ecrm_news_view_count' );
        $this->getUserSettings($form, 'default_currency' );

        return $this->render('dashboard/mediabuyer/settings/form.html.twig', [
            'h1_header_text' => 'Настройки',
            'form' => $form->createView(),
        ]);
    }

    private function getUserSettings($form, $slug) {
        $repo = $this->entityManager->getRepository(UserSettings::class)->findBy(
            ['slug' => $slug, 'user' => $this->getUser()]
        ) ;
        if (!empty($repo)) {
            return $form->get($slug)->setData($repo[0]->getValue());
        }

    }

    private function createOrChangeUserSetting($form, $slug) {
        $ecrmViewCount = intval($form->get($slug)->getData());

        $userSettingsRepo = $this->entityManager->getRepository(UserSettings::class)->findBy(
            ['slug' => $slug, 'user' => $this->getUser()]
        );

        if(empty($userSettingsRepo)) {
            $userSettings = new UserSettings();
            $userSettings->setUser($this->getUser());
            $userSettings->setSlug($slug);
            $userSettings->setValue($ecrmViewCount);
            $this->entityManager->persist($userSettings);
            $this->entityManager->flush();
        } else {
            $userSettingsRepo[0]->setValue($ecrmViewCount);
            $this->entityManager->flush();
        }
    }

}