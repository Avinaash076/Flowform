<?php

declare(strict_types=1);

class AdminController extends BaseController
{
    private UserModel $userModel;
    private FormModel $formModel;
    private SubmissionModel $submissionModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->formModel = new FormModel();
        $this->submissionModel = new SubmissionModel();
    }

    // TODO: Next - Build admin audit trail view

    public function dashboard(): void
    {
        $this->requireLogin();

        if (current_user_role() === 'employee') {
            $this->redirect('employee/dashboard');
        }

        $this->requireAdmin();

        $stats = $this->formModel->getDashboardStats();
        $recentForms = $this->formModel->getRecentForms();

        $this->render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => $stats,
            'recentForms' => $recentForms,
        ]);
    }

    public function employees(): void
    {
        $this->requireAdmin();

        if (is_post_request()) {
            $this->validateCsrfOrFail();

            $name = $this->inputString('name');
            $email = filter_var($this->inputString('email'), FILTER_VALIDATE_EMAIL);
            $password = (string) ($_POST['password'] ?? '');
            $role = $this->allowedValue($this->inputString('role', 'employee'), ['admin', 'employee'], 'employee');

            if ($name === '' || $email === false || $password === '') {
                set_flash_message('Name, email, and password are required.', 'danger');
            } elseif ($this->userModel->findByEmail((string) $email)) {
                set_flash_message('That email address is already in use.', 'danger');
            } else {
                $this->userModel->createUser($name, (string) $email, $password, $role);
                set_flash_message('User account created successfully.', 'success');
                $this->redirect('employees');
            }
        }

        $this->render('admin/employees', [
            'title' => 'Employees',
            'employees' => $this->userModel->getEmployees(),
        ]);
    }

    public function forms(): void
    {
        $this->requireAdmin();
        $forms = $this->formModel->getAdminForms();

        $this->render('admin/forms', [
            'title' => 'Forms',
            'forms' => $forms,
            'completedForms' => array_values(array_filter($forms, static fn (array $form): bool => $form['status'] === 'completed')),
        ]);
    }

    public function copyForm(): void
    {
        $this->requireAdmin();
        $this->validateCsrfOrFail();

        $sourceFormId = (int) ($_POST['source_form_id'] ?? 0);
        if ($sourceFormId <= 0) {
            set_flash_message('Select a completed form to copy.', 'danger');
            $this->redirect('forms');
        }

        $result = $this->formModel->copyCompletedForm($sourceFormId, (int) current_user_id());
        if (!$result['success']) {
            set_flash_message($result['message'], 'danger');
            $this->redirect('forms');
        }

        set_flash_message($result['message'], 'success');
        $this->redirect('forms');
    }

    public function formDetail(int $formId): void
    {
        $this->requireAdmin();

        $form = $this->formModel->getAdminFormDetail($formId);
        if (!$form) {
            $this->abort(404, 'Form not found.');
        }

        $this->render('admin/form-detail', [
            'title' => 'Form Details',
            'form' => $form,
            'submissionReport' => $this->submissionModel->getFormSubmissionReport($formId),
        ]);
    }
}
