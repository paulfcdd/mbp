<?php

namespace App\Controller\Dashboard\Traits;

use App\Entity\EntityInterface;

trait ActionsTrait
{
    public function getActionButtons(EntityInterface $entity, array $actions)
    {
        return $this->renderView('dashboard/partials/table/action_buttons.html.twig', [
            'entity_item' => $entity,
            'actions' => $actions,
        ]);
    }
}