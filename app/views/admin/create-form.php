<div class="create-form-header">
    <h1><?php echo isset($title) ? htmlspecialchars($title) : 'Create Form'; ?></h1>
</div>

<form method="POST" action="<?php echo APP_URL; ?>/create-form" class="form-builder">
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
    
    <div class="form-group">
        <label for="form_name">Form Name</label>
        <input type="text" id="form_name" name="form_name" placeholder="Enter form name" required>
    </div>
    
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Enter form description" rows="4"></textarea>
    </div>
    
    <div class="form-group">
        <label for="form_type">Form Type</label>
        <select id="form_type" name="form_type" required>
            <option value="">Select type</option>
            <option value="onboarding">Onboarding</option>
            <option value="feedback">Feedback</option>
            <option value="survey">Survey</option>
            <option value="assessment">Assessment</option>
        </select>
    </div>
    
    <button type="submit" class="btn-primary">Create Form</button>
    <a href="<?php echo APP_URL; ?>/forms" class="btn-secondary">Cancel</a>
</form>
