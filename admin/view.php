<?php
require_once '../includes/functions.php';
require_once '../config/database.php'; // PDO connection


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $document_id = intval($_POST['document_id']);
    $shared_to   = intval($_POST['shared_to']);
    $shared_by   = $_SESSION['user_id']; // current logged-in user

    $stmt = $db->prepare("INSERT INTO shared_documents (document_id, shared_by, shared_to) VALUES (:document_id, :shared_by, :shared_to)");
    $stmt->bindParam(':document_id', $document_id, PDO::PARAM_INT);
    $stmt->bindParam(':shared_by', $shared_by, PDO::PARAM_INT);
    $stmt->bindParam(':shared_to', $shared_to, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Document shared successfully!";
    } else {
        $_SESSION['error'] = "Failed to share document.";
    }

    header("Location: documents.php");
    exit;
}

// Get the document ID safely
$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($doc_id <= 0) {
    echo "Invalid document ID.";
    exit;
}

// Fetch document details using PDO
$stmt = $db->prepare("
    SELECT d.*, 
           c.name AS category_name,
           u.username AS uploader_name,
           f.folder_name AS folder_name,
           divs.name AS division_name
    FROM documents d
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN folders f ON d.folder_id = f.id
    LEFT JOIN division divs ON d.division_id = divs.id
    WHERE d.id = :doc_id
");


$stmt->bindParam(':doc_id', $doc_id, PDO::PARAM_INT);
$stmt->execute();
$document = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$document) {
    echo "Document not found.";
    exit;
}

require_once '../includes/admin_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Document - <?= htmlspecialchars($document['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="path/to/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2>Document Details</h2>
    <table class="table table-bordered">
        <tr><th>Title</th><td><?= htmlspecialchars($document['title']) ?></td></tr>
        <tr><th>Description</th><td><?= htmlspecialchars($document['description']) ?></td></tr>
        <tr><th>Filename</th><td><?= htmlspecialchars($document['filename']) ?></td></tr>
        <tr><th>Original Name</th><td><?= htmlspecialchars($document['original_name']) ?></td></tr>
        <tr><th>File Size</th><td><?= htmlspecialchars($document['file_size']) ?> bytes</td></tr>
        <tr><th>File Type</th><td><?= htmlspecialchars($document['file_type']) ?></td></tr>
        <tr><th>MIME Type</th><td><?= htmlspecialchars($document['mime_type']) ?></td></tr>
        <tr><th>Category</th><td><?= htmlspecialchars($document['category_name']) ?></td></tr>
        <tr><th>Uploaded By</th><td><?= htmlspecialchars($document['uploader_name']) ?></td></tr>
        <tr><th>Downloads</th><td><?= htmlspecialchars($document['downloads']) ?></td></tr>
        <tr><th>Is Public</th><td><?= $document['is_public'] ? 'Yes' : 'No' ?></td></tr>
        <tr><th>Tags</th><td><?= htmlspecialchars($document['tags']) ?></td></tr>
        <tr><th>Folder</th><td><?= htmlspecialchars($document['folder_name']) ?></td></tr>
        <tr><th>Division</th><td><?= htmlspecialchars($document['division_name']) ?></td></tr>
    </table>

    <a href="download.php?id=<?= $document['id'] ?>" class="btn btn-success">
        <i class="fas fa-download"></i> Download
    </a>
    <!-- Share Button -->
<button type="button" class="btn btn-outline-info btn-sm"
        data-bs-toggle="modal"
        data-bs-target="#shareDocumentModal"
        data-document-id="<?= intval($document['id']) ?>"
        data-document-title="<?= htmlspecialchars($document['title'], ENT_QUOTES) ?>">
    <i class="fas fa-share-alt"></i> Share
</button>

    <a href="documents.php" class="btn btn-secondary">Back to List</a>
</div>

<!-- Share Document Modal -->
<div class="modal fade" id="shareDocumentModal" tabindex="-1" aria-labelledby="shareDocumentModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="shareDocumentForm" method="POST" action="view.php">
        <div class="modal-header">
          <h5 class="modal-title" id="shareDocumentModalLabel">Share Document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="document_id" id="modalDocumentId">

          <div class="mb-3">
            <label for="shared_to" class="form-label">Select Admin to Share With</label>
            <select class="form-select" name="shared_to" id="shared_to" required>
              <option value="">-- Select Admin --</option>
              <?php
              // Fetch all admin users
              $admins = $db->query("SELECT id, full_name FROM users WHERE role = 'admin'")->fetchAll(PDO::FETCH_ASSOC);
              foreach ($admins as $admin) {
                  echo '<option value="' . intval($admin['id']) . '">' . htmlspecialchars($admin['full_name']) . '</option>';
              }
              ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Share Document</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var shareModal = document.getElementById('shareDocumentModal');
shareModal.addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget; // Button that triggered the modal
    var documentId = button.getAttribute('data-document-id');
    var documentTitle = button.getAttribute('data-document-title');

    // Set modal values
    document.getElementById('modalDocumentId').value = documentId;
    shareModal.querySelector('.modal-title').textContent = 'Share Document: ' + documentTitle;
});
</script>



</body>
</html>
<?php require_once '../includes/footer.php'; ?>