<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\InvoiceEmailLog;
use App\Entity\InvoiceItem;
use App\Entity\User;
use App\Form\InvoiceType;
use App\Repository\CompanyRepository;
use App\Repository\InvoiceRepository;
use App\Service\BusinessDayCalculator;
use App\Service\CurrencyConversionService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/invoices')]
final class InvoiceController extends AbstractController
{
    #[Route('', name: 'app_invoice_index')]
    public function index(InvoiceRepository $invoiceRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('invoice/index.html.twig', [
            'invoices' => $invoiceRepository->findByOwner($user),
        ]);
    }

    #[Route('/new', name: 'app_invoice_new')]
    public function new(
        Request $request,
        CompanyRepository $companyRepository,
        BusinessDayCalculator $businessDayCalculator,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $companies = $companyRepository->findByOwner($user);
        if ($companies === []) {
            $this->addFlash('warning', 'Cadastre pelo menos uma empresa antes de criar invoices.');

            return $this->redirectToRoute('app_company_new');
        }

        $invoice = new Invoice();
        $invoice->setOwner($user);
        $invoice->setNumber(sprintf('INV-%s', (new \DateTimeImmutable())->format('Ymd-His')));
        $invoice->setReferenceMonth((new \DateTimeImmutable('today'))->format('Y-m'));
        if ($user->getDefaultDailyRateCurrency() !== null) {
            $invoice->setCurrency($user->getDefaultDailyRateCurrency());
        }
        $invoice->setDueDate($invoice->getIssueDate()->modify('+10 days'));
        $defaultDescription = $user->getJobDescription() !== null && $user->getJobDescription() !== ''
            ? $user->getJobDescription()
            : 'Service fee';
        $defaultDailyRate = $user->getDefaultDailyRate() ?? '0.00';
        $defaultHourlyRate = $user->getDefaultHourlyRate() ?? '0.00';
        $defaultHourlyHoursPerBusinessDay = (float) ($user->getDefaultHourlyHoursPerBusinessDay() ?? '8');
        if ($defaultHourlyHoursPerBusinessDay <= 0) {
            $defaultHourlyHoursPerBusinessDay = 8.0;
        }
        $defaultBillingType = $user->getDefaultDailyRate() !== null
            ? InvoiceItem::BILLING_DAILY_RATE
            : ($user->getDefaultHourlyRate() !== null ? InvoiceItem::BILLING_HOURLY_RATE : InvoiceItem::BILLING_DAILY_RATE);
        $businessDays = $businessDayCalculator->countWeekdaysInMonth($invoice->getReferenceMonth());
        $hourlyMonthQuantity = number_format($businessDays * $defaultHourlyHoursPerBusinessDay, 2, '.', '');
        $invoice->addItem((new InvoiceItem())
            ->setDescription($defaultDescription)
            ->setBillingType($defaultBillingType)
            ->setQuantity($defaultBillingType === InvoiceItem::BILLING_DAILY_RATE ? (string) $businessDays : $hourlyMonthQuantity)
            ->setUnitPrice($defaultBillingType === InvoiceItem::BILLING_DAILY_RATE ? $defaultDailyRate : $defaultHourlyRate));

        $form = $this->createForm(InvoiceType::class, $invoice, [
            'owner' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->setDueDate($invoice->getIssueDate()->modify('+10 days'));

            foreach ($invoice->getItems() as $item) {
                $item->setInvoice($invoice);
            }

            $entityManager->persist($invoice);
            $entityManager->flush();
            $this->addFlash('success', 'Invoice criada com sucesso.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/form.html.twig', [
            'form' => $form,
            'title' => 'Nova invoice',
            'is_edit' => false,
            'invoice' => null,
            'default_daily_rate' => $defaultDailyRate,
            'default_hourly_rate' => $defaultHourlyRate,
            'default_hourly_hours_per_business_day' => number_format($defaultHourlyHoursPerBusinessDay, 2, '.', ''),
            'user_local_currency' => $user->getLocalCurrency(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_invoice_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Invoice $invoice,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if ($invoice->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(InvoiceType::class, $invoice, [
            'owner' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invoice->setDueDate($invoice->getIssueDate()->modify('+10 days'));

            foreach ($invoice->getItems() as $item) {
                $item->setInvoice($invoice);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Invoice atualizada com sucesso.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        return $this->render('invoice/form.html.twig', [
            'form' => $form,
            'title' => 'Editar invoice',
            'is_edit' => true,
            'invoice' => $invoice,
            'default_daily_rate' => $user->getDefaultDailyRate() ?? '0.00',
            'default_hourly_rate' => $user->getDefaultHourlyRate() ?? '0.00',
            'default_hourly_hours_per_business_day' => $user->getDefaultHourlyHoursPerBusinessDay() ?? '8.00',
            'user_local_currency' => $user->getLocalCurrency(),
        ]);
    }

    #[Route('/{id}', name: 'app_invoice_show', requirements: ['id' => '\d+'])]
    public function show(Invoice $invoice, CurrencyConversionService $currencyConversionService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($invoice->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $localCurrencyConversion = null;
        $localCurrency = $user->getLocalCurrency();
        if ($localCurrency !== null && $localCurrency !== '' && $localCurrency !== $invoice->getCurrency()) {
            $localCurrencyConversion = $currencyConversionService->convert(
                $invoice->getTotalAmount(),
                $invoice->getCurrency(),
                $localCurrency
            );
        }

        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
            'local_currency_conversion' => $localCurrencyConversion,
        ]);
    }

    #[Route('/fx-preview', name: 'app_invoice_fx_preview', methods: ['GET'])]
    public function fxPreview(Request $request, CurrencyConversionService $currencyConversionService): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $localCurrency = $user->getLocalCurrency();
        if ($localCurrency === null || $localCurrency === '') {
            return $this->json(['conversion' => null]);
        }

        $amount = (string) $request->query->get('amount', '0');
        $fromCurrency = (string) $request->query->get('from', '');
        $conversion = $currencyConversionService->convert($amount, $fromCurrency, $localCurrency);

        return $this->json(['conversion' => $conversion]);
    }

    #[Route('/{id}/email-history', name: 'app_invoice_email_history', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function emailHistory(Invoice $invoice): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($invoice->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('invoice/email_history.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_invoice_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function pdf(Invoice $invoice): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($invoice->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $pdfOutput = $this->generateInvoicePdfBinary($invoice);

        return new Response(
            $pdfOutput,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => sprintf('inline; filename="%s.pdf"', $invoice->getNumber()),
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]
        );
    }

    #[Route('/{id}/send-email', name: 'app_invoice_send_email', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function sendEmail(
        Invoice $invoice,
        Request $request,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
    ): RedirectResponse {
        /** @var User $user */
        $user = $this->getUser();
        if ($invoice->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('send_invoice_email_'.$invoice->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Token CSRF inválido para envio de e-mail.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $toEmail = $invoice->getRecipientCompany()?->getEmail();
        if ($toEmail === null || $toEmail === '') {
            $this->addFlash('warning', 'A empresa destinatária não possui e-mail cadastrado.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $fromEmail = $invoice->getIssuerCompany()?->getEmail() ?: 'no-reply@example.com';
        $pdfOutput = $this->generateInvoicePdfBinary($invoice);
        $filename = sprintf('%s.pdf', $invoice->getNumber());
        $subject = sprintf('Invoice %s', $invoice->getNumber());

        $email = new Email()
            ->from($fromEmail)
            ->to($toEmail)
            ->subject($subject)
            ->text(sprintf(
                "Hello,\n\nPlease find attached invoice %s.\nTotal due: %s %s\nDue date: %s\n\nBest regards.",
                $invoice->getNumber(),
                $invoice->getCurrency(),
                $invoice->getTotalAmount(),
                $invoice->getDueDate()?->format('Y-m-d') ?? '-'
            ))
            ->attach($pdfOutput, $filename, 'application/pdf');

        $emailLog = (new InvoiceEmailLog())
            ->setInvoice($invoice)
            ->setRecipientEmail($toEmail)
            ->setSubject($subject)
            ->setSentAt(new \DateTimeImmutable());

        try {
            $mailer->send($email);
            $emailLog->setStatus(InvoiceEmailLog::STATUS_SUCCESS);
            $this->addFlash('success', sprintf('PDF enviado para %s.', $toEmail));
        } catch (TransportExceptionInterface $exception) {
            $emailLog->setStatus(InvoiceEmailLog::STATUS_FAILED);
            $emailLog->setErrorMessage($exception->getMessage());
            $this->addFlash('warning', sprintf('Não foi possível enviar o e-mail: %s', $exception->getMessage()));
        }

        $entityManager->persist($emailLog);
        $entityManager->flush();

        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_invoice_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Invoice $invoice, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($invoice->getOwner()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('delete_invoice_'.$invoice->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('warning', 'Token CSRF inválido para exclusão.');

            return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
        }

        $entityManager->remove($invoice);
        $entityManager->flush();
        $this->addFlash('success', 'Invoice excluída com sucesso.');

        return $this->redirectToRoute('app_invoice_index');
    }

    private function generateInvoicePdfBinary(Invoice $invoice): string
    {
        $html = $this->renderView('invoice/pdf.html.twig', [
            'invoice' => $invoice,
            'fontFile' => 'file://'.$this->getParameter('kernel.project_dir').'/public/fonts/DejaVuSans.ttf',
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->setChroot((string) $this->getParameter('kernel.project_dir'));
        $options->setDefaultFont('InvoiceSans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
