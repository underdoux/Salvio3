<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Test Notifications</h5>
        <a href="<?= base_url('notifications') ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-arrow-left"></i>
            Back to Notifications
        </a>
    </div>

    <div class="card-body">
        <?= $this->getFlash() ?>

        <form action="<?= base_url('notifications/test') ?>" method="POST" id="testForm">
            <?= $this->csrf() ?>

            <!-- Notification Type -->
            <div class="mb-4">
                <label for="type" class="form-label">Notification Type</label>
                <select name="type" id="type" class="form-select" required>
                    <option value="">Select notification type</option>
                    <?php foreach ($templates['templates'] as $key => $template): ?>
                        <option value="<?= $key ?>" data-template="<?= htmlspecialchars(json_encode($template)) ?>">
                            <?= $template['title'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Select the type of notification you want to test
                </div>
            </div>

            <!-- Recipients -->
            <div class="mb-4">
                <label for="recipients" class="form-label">Recipients</label>
                <select name="recipients[]" id="recipients" class="form-select" multiple required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>">
                            <?= $this->e($user['name']) ?> (<?= $user['email'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    Select one or more recipients for the test notification
                </div>
            </div>

            <!-- Template Preview -->
            <div class="mb-4">
                <label class="form-label">Template Preview</label>
                <div class="template-preview p-3 border rounded">
                    <div class="preview-header mb-2">
                        <i class="fas fa-bell"></i>
                        <span id="previewTitle">Select a notification type</span>
                    </div>
                    <div class="preview-body" id="previewMessage">
                        Message will appear here
                    </div>
                </div>
            </div>

            <!-- Template Variables -->
            <div class="mb-4" id="variablesSection" style="display: none;">
                <label class="form-label">Template Variables</label>
                <div class="variables-list" id="variablesList">
                    <!-- Variables will be added here dynamically -->
                </div>
            </div>

            <!-- Test Options -->
            <div class="mb-4">
                <label class="form-label">Test Options</label>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="email" name="channels[]" value="email" checked>
                    <label class="form-check-label" for="email">
                        Send Email Notification
                    </label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="browser" name="channels[]" value="browser" checked>
                    <label class="form-check-label" for="browser">
                        Send Browser Notification
                    </label>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Send Test Notification
                </button>
                <button type="reset" class="btn btn-light ms-2">
                    <i class="fas fa-undo"></i>
                    Reset Form
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.template-preview {
    background-color: var(--light);
}

.preview-header {
    font-weight: 600;
}

.preview-header i {
    margin-right: 0.5rem;
}

.variables-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.variable-item {
    background-color: var(--light);
    padding: 1rem;
    border-radius: var(--border-radius);
}

.variable-name {
    font-family: monospace;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

/* Select2 customization */
.select2-container--default .select2-selection--multiple {
    border-color: var(--border-color);
    min-height: 38px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: var(--primary);
    border: none;
    color: var(--white);
    padding: 2px 8px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: var(--white);
    margin-right: 5px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for recipients
    $('#recipients').select2({
        placeholder: 'Select recipients',
        allowClear: true,
        width: '100%'
    });

    // Handle notification type change
    const typeSelect = document.getElementById('type');
    typeSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        if (!option.value) {
            resetPreview();
            return;
        }

        const template = JSON.parse(option.dataset.template);
        updatePreview(template);
        updateVariables(template);
    });

    // Reset preview
    function resetPreview() {
        document.getElementById('previewTitle').textContent = 'Select a notification type';
        document.getElementById('previewMessage').textContent = 'Message will appear here';
        document.getElementById('variablesSection').style.display = 'none';
        document.getElementById('variablesList').innerHTML = '';
    }

    // Update preview
    function updatePreview(template) {
        document.getElementById('previewTitle').textContent = template.title;
        document.getElementById('previewMessage').textContent = template.message;
    }

    // Update variables
    function updateVariables(template) {
        const variables = extractVariables(template.message);
        if (variables.length === 0) {
            document.getElementById('variablesSection').style.display = 'none';
            return;
        }

        const variablesList = document.getElementById('variablesList');
        variablesList.innerHTML = '';

        variables.forEach(variable => {
            const div = document.createElement('div');
            div.className = 'variable-item';
            div.innerHTML = `
                <div class="variable-name">{${variable}}</div>
                <input type="text" 
                       class="form-control" 
                       name="variables[${variable}]"
                       placeholder="Enter value for ${variable}">
            `;
            variablesList.appendChild(div);
        });

        document.getElementById('variablesSection').style.display = 'block';
    }

    // Extract variables from template
    function extractVariables(template) {
        const matches = template.match(/\{([^}]+)\}/g) || [];
        return matches.map(match => match.slice(1, -1));
    }

    // Form submission
    document.getElementById('testForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Collect form data
        const formData = new FormData(this);
        const data = {
            type: formData.get('type'),
            recipients: $('#recipients').val(),
            channels: Array.from(formData.getAll('channels[]')),
            variables: {}
        };

        // Collect variables
        const variableInputs = document.querySelectorAll('[name^="variables["]');
        variableInputs.forEach(input => {
            const name = input.name.match(/\[(.*?)\]/)[1];
            data.variables[name] = input.value;
        });

        // Send test notification
        fetch(this.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test notification sent successfully');
                this.reset();
                $('#recipients').val(null).trigger('change');
                resetPreview();
            } else {
                alert('Failed to send test notification: ' + data.message);
            }
        })
        .catch(error => {
            alert('An error occurred while sending the test notification');
            console.error(error);
        });
    });
});
</script>
