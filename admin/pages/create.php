<?php
// Include database connection and auth check
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Make sure upload directory exists
$upload_dir = '../../uploads/pages/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Initialize variables
$errors = [];
$title = '';
$content = '';
$meta_description = '';
$status = 'published';
$featured_image = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_page'])) {
    // Log submission for debugging
    error_log('Form submitted in pages/create.php');
    
    // Dump all POST variables for debugging
    error_log('POST data: ' . print_r($_POST, true));
    
    // Get form data with detailed validation
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    error_log('Title value after retrieval: "' . $title . '"');
    
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'published';
    $featured_image = '';
    $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
    $show_in_footer = isset($_POST['show_in_footer']) ? 1 : 0;
    
    // Debug log
    error_log('Form data: ' . json_encode([
        'title' => $title,
        'content_length' => strlen($content),
        'status' => $status,
        'show_in_menu' => $show_in_menu,
        'show_in_footer' => $show_in_footer
    ]));
    
    // Strict validation for required fields
    if (empty($title)) {
        $errors[] = 'Page title is required';
        error_log('Validation error: Page title is empty');
    }
    
    if (empty($content)) {
        $errors[] = 'Page content is required';
        error_log('Validation error: Page content is empty');
    }
    
    if (empty($meta_description)) {
        // Generate meta description from content if empty
        $meta_description = generateExcerpt($content, 160);
    }

    // Create slug from title
    $slug = createSlug($title);
    
    // Check if a file was uploaded
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['featured_image']['name'];
        $file_tmp = $_FILES['featured_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Check if the file type is allowed
        if (in_array($file_ext, $allowed)) {
            // Generate unique filename
            $new_file_name = uniqid('page_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            // Move the file to the uploads directory
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $featured_image = '/uploads/pages/' . $new_file_name;
            } else {
                $errors[] = 'Failed to upload image';
            }
        } else {
            $errors[] = 'Only JPG, JPEG, PNG, and GIF files are allowed';
        }
    }
    
    // Check for duplicate slug
    $check_query = "SELECT COUNT(*) as count FROM pages WHERE slug = :slug";
    $check_stmt = $pdo->prepare($check_query);
    $check_stmt->bindParam(':slug', $slug);
    $check_stmt->execute();
    $row = $check_stmt->fetch();
    
    if ($row['count'] > 0) {
        // Add unique identifier to make slug unique
        $slug = $slug . '-' . date('mdY');
    }
    
    // If no errors, create the page
    if (empty($errors)) {
        try {
            // Check if the pages table has the menu columns
            $columnsExist = true;
            try {
                $checkQuery = "SELECT show_in_menu, show_in_footer FROM pages LIMIT 0";
                $pdo->query($checkQuery);
            } catch (PDOException $e) {
                $columnsExist = false;
                error_log('Menu columns do not exist in pages table: ' . $e->getMessage());
            }
            
            // Prepare the INSERT query based on column existence
            if ($columnsExist) {
                $query = "INSERT INTO pages (
                    title, 
                    slug, 
                    content, 
                    meta_description, 
                    featured_image, 
                    status, 
                    show_in_menu,
                    show_in_footer,
                    created_at, 
                    updated_at
                ) VALUES (
                    :title, 
                    :slug, 
                    :content, 
                    :meta_description, 
                    :featured_image, 
                    :status,
                    :show_in_menu,
                    :show_in_footer,
                    NOW(), 
                    NOW()
                )";
            } else {
                $query = "INSERT INTO pages (
                    title, 
                    slug, 
                    content, 
                    meta_description, 
                    featured_image, 
                    status, 
                    created_at, 
                    updated_at
                ) VALUES (
                    :title, 
                    :slug, 
                    :content, 
                    :meta_description, 
                    :featured_image, 
                    :status,
                    NOW(), 
                    NOW()
                )";
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $slug);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':meta_description', $meta_description);
            $stmt->bindParam(':featured_image', $featured_image);
            $stmt->bindParam(':status', $status);
            
            if ($columnsExist) {
                $stmt->bindParam(':show_in_menu', $show_in_menu, PDO::PARAM_INT);
                $stmt->bindParam(':show_in_footer', $show_in_footer, PDO::PARAM_INT);
            }
            
            $result = $stmt->execute();
            
            if (!$result) {
                error_log('Failed to execute query: ' . json_encode($stmt->errorInfo()));
                $errors[] = 'Database error: ' . implode(', ', $stmt->errorInfo());
            } else {
                // Set success message
                $_SESSION['success_message'] = 'Page created successfully!';
                
                // Redirect to the pages list
                header('Location: list.php');
                exit;
            }
        } catch (PDOException $e) {
            // Database error
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Page variables
$page_title = 'Create Page';
$use_tinymce = true;

// Header actions
$header_actions = '
<a href="list.php" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> Back to Pages
</a>
';

// Extra head content
$extra_head = '<style>
    #featured-image-preview {
        max-width: 100%;
        height: auto;
        max-height: 200px;
        border-radius: 0.25rem;
    }
</style>';

// Include admin header
include_once '../includes/header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="create.php" method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-8">
                    <!-- Main Content Fields -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="content" class="form-label">Content *</label>
                        <textarea class="form-control editor <?php echo empty($content) && $_SERVER['REQUEST_METHOD'] === 'POST' ? 'is-invalid' : ''; ?>" 
                                 id="content" name="content" rows="15" required><?php echo htmlspecialchars($content); ?></textarea>
                        <?php if (empty($content) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <div class="invalid-feedback">Page content is required.</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Sidebar Fields -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Publishing</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="published" <?php echo $status === 'published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                </select>
                            </div>
                            <button type="submit" name="submit_page" value="1" class="btn btn-primary w-100 btn-lg">
                                <i class="fas fa-save me-1"></i> Publish Page
                            </button>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Featured Image</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="featured_image" class="form-label">Upload Image</label>
                                <input class="form-control" type="file" id="featured_image" name="featured_image">
                                <div class="form-text">Recommended size: 1200x630 pixels</div>
                            </div>
                            <div id="imagePreviewContainer" class="mt-2 d-none">
                                <img id="imagePreview" src="" alt="Image Preview" class="img-fluid">
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">SEO Settings</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="meta_description" class="form-label">Meta Description</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"><?php echo htmlspecialchars($meta_description); ?></textarea>
                                <div class="form-text">If left empty, it will be generated from the content. Recommended length: 150-160 characters.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Page Options</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_in_menu" name="show_in_menu" checked>
                                <label class="form-check-label" for="show_in_menu">
                                    Show in Navigation Menu
                                </label>
                                <div class="form-text">Display this page in the main navigation menu.</div>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="show_in_footer" name="show_in_footer" checked>
                                <label class="form-check-label" for="show_in_footer">
                                    Show in Footer Menu
                                </label>
                                <div class="form-text">Display this page in the footer menu.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
// Include admin footer
include_once '../includes/footer.php'; 
?>

<script>
// Form validation and submission
document.addEventListener('DOMContentLoaded', function() {
    // Get form elements
    const form = document.getElementById('createPageForm');
    const titleField = document.getElementById('title');
    const contentField = document.getElementById('content');
    
    // Image preview
    document.getElementById('featured_image').addEventListener('change', function(event) {
        var preview = document.getElementById('imagePreview');
        var previewContainer = document.getElementById('imagePreviewContainer');
        var file = event.target.files[0];
        
        if (file) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.classList.remove('d-none');
            };
            
            reader.readAsDataURL(file);
        } else {
            previewContainer.classList.add('d-none');
        }
    });
    
    // Handle form submission to validate before submitting
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validate title
        if (!titleField.value.trim()) {
            isValid = false;
            titleField.classList.add('is-invalid');
            
            // Add custom error message if not already present
            if (!document.querySelector('.title-error')) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback title-error';
                errorDiv.textContent = 'Page title is required.';
                titleField.parentNode.appendChild(errorDiv);
            }
            console.log('Title validation failed');
        } else {
            titleField.classList.remove('is-invalid');
            console.log('Title validation passed: ' + titleField.value.trim());
        }
        
        // Special handling for TinyMCE content if TinyMCE is active
        if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
            const content = tinymce.get('content').getContent();
            
            if (!content.trim()) {
                isValid = false;
                // Add a red border to the TinyMCE editor
                tinymce.get('content').getContainer().style.border = '1px solid #dc3545';
                console.log('TinyMCE content validation failed');
            } else {
                tinymce.get('content').getContainer().style.border = '';
                console.log('TinyMCE content validation passed: ' + content.substr(0, 50) + '...');
            }
        } else if (!contentField.value.trim()) {
            // Regular textarea validation if TinyMCE is not active
            isValid = false;
            contentField.classList.add('is-invalid');
            console.log('Content validation failed');
        } else {
            contentField.classList.remove('is-invalid');
            console.log('Content validation passed');
        }
        
        // Prevent form submission if validation fails
        if (!isValid) {
            event.preventDefault();
            // Scroll to the top of the form to show error messages
            window.scrollTo(0, form.offsetTop - 100);
        } else {
            console.log('Form validation passed, submitting...');
        }
    });
});
</script>