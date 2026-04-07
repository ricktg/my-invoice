<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $defaultRateType = (string) $form->get('defaultRateType')->getData();
            $defaultRateValue = $form->get('defaultRateValue')->getData();
            $defaultRateValue = $defaultRateValue !== null && $defaultRateValue !== '' ? (string) $defaultRateValue : null;

            $user->setDefaultDailyRate(null);
            $user->setDefaultHourlyRate(null);
            $user->setDefaultAnnualFixedRate(null);

            if ($defaultRateValue !== null) {
                if ($defaultRateType === ProfileType::RATE_TYPE_HOURLY) {
                    $user->setDefaultHourlyRate($defaultRateValue);
                } elseif ($defaultRateType === ProfileType::RATE_TYPE_ANNUAL_FIXED) {
                    $user->setDefaultAnnualFixedRate($defaultRateValue);
                } else {
                    $user->setDefaultDailyRate($defaultRateValue);
                }
            }

            $newPassword = (string) $form->get('newPassword')->getData();
            if ($newPassword !== '') {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Perfil atualizado com sucesso.');

            return $this->redirectToRoute('app_profile_edit');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
