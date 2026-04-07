<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\VersionCheckService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class SystemController extends AbstractController
{
    #[Route('/system/version-check', name: 'app_system_version_check', methods: ['GET'])]
    public function versionCheck(VersionCheckService $versionCheckService): JsonResponse
    {
        return $this->json($versionCheckService->check());
    }
}
