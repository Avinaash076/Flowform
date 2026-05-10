<?php

declare(strict_types=1);

class EmployeeController extends BaseController
{
    private FormModel $formModel;
    private SubmissionModel $submissionModel;
    private UserModel $userModel;

    public function __construct()
    {
        $this->formModel = new FormModel();
        $this->submissionModel = new SubmissionModel();
        $this->userModel = new UserModel();
    }

    // TODO: Next - Build employee submission history view

    public function dashboard(): void
    {
        $this->requireEmployee();

        $this->render('employee/dashboard', [
            'title' => 'My Dashboard',
            'assignedForms' => $this->formModel->getAssignedFormsForEmployee((int) current_user_id()),
        ]);
    }

    public function fillForm(int $formId): void
    {
        $this->requireEmployee();

        $form = $this->formModel->getEmployeeFormContext($formId, (int) current_user_id());
        if (!$form) {
            set_flash_message('That form is not assigned to you or is no longer active.', 'danger');
            $this->redirect('employee/dashboard');
        }

        $userOptions = [];
        foreach ($form['fields'] as $field) {
            if ($field['field_type'] === 'user_dropdown') {
                $userOptions = $this->userModel->getCurrentUserDropdownOption((int) current_user_id());
                break;
            }
        }

        $this->render('employee/fill-form', [
            'title' => 'Complete Form',
            'form' => $form,
            'existingValues' => array_replace(
                $this->submissionModel->getLatestFormSubmissionMap($formId),
                !empty($form['current_sequence_id'])
                    ? $this->submissionModel->getSubmissionMap($formId, (int) $form['current_sequence_id'], (int) current_user_id())
                    : []
            ),
            'userOptions' => $userOptions,
        ]);
    }
}
