<?php

namespace App\Controller;

use App\Entity\Form;
use App\Form\FormType;
use App\Repository\FormRepository;
use App\Entity\FormField;
use App\Entity\Response as FormResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Model\FormData; 

//use Symfony\Component\Security\Core\Security;

#[Route('/form')]
final class FormController extends AbstractController
{

    private $formRepository;

    public function __construct(FormRepository $formRepository)
    {
        $this->formRepository = $formRepository;
    }


    #[Route(name: 'app_form_index', methods: ['GET'])]
    public function index(FormRepository $formRepository): Response
    {
        return $this->render('form/index.html.twig', [
            'forms' => $formRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_form_show', methods: ['GET'])]
    public function show(Form $form): Response
    {
        $formFields = $form->getFields();
        // Use the injected repository to fetch the form
        $formEntity = $this->formRepository->find($form->getId()); // Example retrieval
        $fields = $formEntity->getFields();

        return $this->render('form/show.html.twig', [
            'form_entity' => $form,
            'fields' => $formFields,
        ]);
    }


    #[Route('/{id}/edit', name: 'app_form_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Form $form): Response
{
    $formFields = $form->getFields();  // Get dynamic fields

    // Create a new FormData object to hold dynamic fields
    $formData = new FormData();

    // Create a form for editing the Form entity (main entity)
    $formBuilder = $this->createForm(FormType::class, $form);

    // Dynamically add form fields like select, checkboxes, etc.
    foreach ($formFields as $field) {
        $fieldName = 'field_' . $field->getId();
        $choices = explode(',', $field->getOptions());  // Assuming options are comma-separated
        $formBuilder->add($fieldName, ChoiceType::class, [
            'choices' => array_flip($choices),  // Convert options to choices
            'expanded' => $field->getType() === 'checkbox',  // For checkboxes, use expanded
            'multiple' => $field->getType() === 'checkbox',  // For multiple checkboxes
        ]);
    }

    // Handle the form submission
    $form = $formBuilder->getForm();
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Get the dynamic field values from the form data and store them in FormData
        foreach ($formFields as $field) {
            $fieldName = 'field_' . $field->getId();
            $formData->setDynamicField($fieldName, $form->get($fieldName)->getData());
        }

        // Persist changes if necessary
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('app_form_show', ['id' => $form->getId()]);
    }

    return $this->render('form/edit.html.twig', [
        'form' => $form->createView(),
    ]);
}

#[Route('/{id}', name: 'app_form_delete', methods: ['POST'])]
public function delete(Request $request, Form $form, EntityManagerInterface $entityManager): Response
{
    // Check if the CSRF token is valid
    $csrfToken = $request->request->get('_token');
    if ($this->isCsrfTokenValid('delete'.$form->getId(), $csrfToken)) {
        // If the token is valid, remove the form from the database
        $entityManager->remove($form);
        $entityManager->flush();
        
        // Redirect after deletion
        return $this->redirectToRoute('app_form_index', [], Response::HTTP_SEE_OTHER);
    }

    // If the CSRF token is invalid, show an error or redirect
    return $this->redirectToRoute('app_form_index');
}

    #[Route('/form/create', name: 'form_create')]
    public function create(Request $request, EntityManagerInterface $em): Response
    {

        $form = new Form();
        $user = $this->getUser(); // Get the currently logged-in user

        $form->setUser($user); // Associate the user as the form owner


        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $form = new Form();
            $form->setTitle($request->request->get('title'));
            $form->setUser($this->getUser());

            $em->persist($form);

            // Handling dynamic fields
            $labels = $request->request->all('labels', []);
            $types = $request->request->all('types', []);
            $options = $request->request->all('options', []);

            foreach ($labels as $index => $label) {
                $field = new FormField();
                $field->setLabel($label);
                $field->setType($types[$index]);
                $field->setOptions($options[$index] ?? null);
                $field->setForm($form);
                $em->persist($field);
            }

            $em->flush();
            return $this->redirectToRoute('app_home');
        }

        return $this->render('form/create.html.twig');
    }

    #[Route('/form/{id}/fill', name: 'form_fill')]
    public function fill(Form $form): Response
    {
        return $this->render('form/fill.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/form/{id}/submit', name: 'form_submit')]
    public function submit(Form $form, Request $request, EntityManagerInterface $em): Response
    {

        $submittedToken = $request->request->get('_csrf_token');

        if (!$csrfTokenManager->isTokenValid(new CsrfToken('form_submit', $submittedToken))) {
        throw $this->createAccessDeniedException('Invalid CSRF token');
        }

        $response = new FormResponse();
        $response->setForm($form);
        $response->setAnswers($request->request->all()['answers']);

        $em->persist($response);
        $em->flush();

        return $this->redirectToRoute('app_home');
    }

    #[Route('/form/{id}/responses', name: 'form_responses')]
    public function viewResponses(Form $form): Response
    {
        if ($this->getUser() !== $form->getUser()) {
            throw $this->createAccessDeniedException('You do not own this form.');
        }

        return $this->render('form/responses.html.twig', [
            'form' => $form,
            'responses' => $form->getResponses()
        ]);
    }

    #[Route('/form/{id}/export', name: 'form_export')]
    public function export(Form $form): Response
    {
        if ($this->getUser() !== $form->getUser()) {
            throw $this->createAccessDeniedException('You do not own this form.');
        }

        $filename = 'responses_' . $form->getId() . '.csv';

        $response = new StreamedResponse(function () use ($form) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, array_merge(['#'], array_map(fn($f) => $f->getLabel(), $form->getFields()), ['Submitted At']));

            // Data rows
            foreach ($form->getResponses() as $index => $response) {
                fputcsv($handle, array_merge([$index + 1], $response->getAnswers(), [$response->getSubmittedAt()->format('Y-m-d H:i')]));
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }




}
