<section class="hero-row">
    <div>
        <p class="eyebrow">Administration</p>
        <h1>Employees</h1>
        <p class="muted-copy">Create user accounts and assign workflow ownership for each sequence.</p>
    </div>
</section>

<section class="content-grid">
    <article class="panel-card">
        <div class="section-heading">
            <h2>Add User</h2>
            <p>Passwords are hashed with bcrypt before storage.</p>
        </div>
        <form method="POST" action="<?= e(app_url('employees')); ?>" class="stack-form">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <label class="field-stack">
                <span>Name</span>
                <input type="text" name="name" required>
            </label>
            <label class="field-stack">
                <span>Email</span>
                <input type="email" name="email" required>
            </label>
            <label class="field-stack">
                <span>Password</span>
                <input type="password" name="password" minlength="8" required>
            </label>
            <label class="field-stack">
                <span>Role</span>
                <select name="role">
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button type="submit" class="btn-primary">Create User</button>
        </form>
    </article>

    <article class="panel-card">
        <div class="section-heading">
            <h2>Employee Directory</h2>
            <p>Sequence assignment counts are shown for planning.</p>
        </div>
        <?php if ($employees === []): ?>
            <p class="empty-state">No employee accounts found.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Assigned Sequences</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?= e($employee['name']); ?></td>
                                <td><?= e($employee['email']); ?></td>
                                <td><?= e(ucfirst($employee['role'])); ?></td>
                                <td><?= (int) $employee['assigned_sequences']; ?></td>
                                <td><?= e(date('d M Y', strtotime((string) $employee['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>
