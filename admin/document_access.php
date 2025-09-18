<?php
require_once '../includes/functions.php';
require_once '../config/database.php'; // PDO connection ($db)

// Get document ID
$document_id = intval($_GET['id'] ?? 0);
if (!$document_id) die('Document ID is missing.');

// Handle POST (saving access)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_id'])) {
    $doc_id = intval($_POST['document_id']);
    $shared_to = $_POST['shared_to'] ?? [];
    $shared_by = $_SESSION['user_id'] ?? 0;

    $stmt = $db->prepare("DELETE FROM shared_documents WHERE document_id = ?");
    $stmt->execute([$doc_id]);

    if (!empty($shared_to)) {
        $stmt2 = $db->prepare("INSERT INTO shared_documents (document_id, shared_by, shared_to) VALUES (?, ?, ?)");
        foreach ($shared_to as $user_id) {
            $stmt2->execute([$doc_id, $shared_by, intval($user_id)]);
        }
    }

    $success_message = "Access updated successfully.";
}

// Fetch the specific document
$stmt = $db->prepare("SELECT d.*, u.full_name AS username, c.name AS category_name
                      FROM documents d
                      LEFT JOIN users u ON u.id = d.user_id
                      LEFT JOIN categories c ON c.id = d.category_id
                      WHERE d.id = ?");
$stmt->execute([$document_id]);
$document = $stmt->fetch();
if (!$document) die('Document not found.');

// Fetch divisions & users
$stmt = $db->query("SELECT d.id AS division_id, d.name AS division_name,
                           u.id AS user_id, u.full_name, u.username
                    FROM division d
                    LEFT JOIN user_divisions ud ON ud.division_id = d.id
                    LEFT JOIN users u ON u.id = ud.user_id
                    ORDER BY d.name, u.full_name");

$divisions = [];
while ($row = $stmt->fetch()) {
    $divId = $row['division_id'];
    if (!isset($divisions[$divId])) {
        $divisions[$divId] = [
            'division_name' => $row['division_name'],
            'users' => []
        ];
    }
    if ($row['user_id']) {
        $divisions[$divId]['users'][] = [
            'id' => $row['user_id'],
            'full_name' => $row['full_name'],
            'username' => $row['username']
        ];
    }
}

// Fetch shared users for this document
$stmt = $db->prepare("SELECT shared_to FROM shared_documents WHERE document_id = ?");
$stmt->execute([$document_id]);
$shared_docs_rows = $stmt->fetchAll();
$shared_docs = array_column($shared_docs_rows, 'shared_to');

require_once '../includes/admin_header.php';
?>

<?php if(isset($success_message)): ?>
<div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
<?php endif; ?>

<h3>Manage Access for: <strong><?= htmlspecialchars($document['title']) ?></strong></h3>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <p><strong>Original Name:</strong> <?= htmlspecialchars($document['original_name']) ?></p>
        <p><strong>Uploaded by:</strong> <?= htmlspecialchars($document['username']) ?></p>
        <p><strong>Category:</strong> <?= $document['category_name'] ?: 'Uncategorized' ?></p>

        <form method="post">
            <input type="hidden" name="document_id" value="<?= $document['id'] ?>">
            <strong>Shared With:</strong>

            <?php foreach ($divisions as $divId => $div): ?>
                <div class="mt-3 border p-2 rounded">
                    <button type="button" 
                        class="btn btn-sm btn-outline-secondary mb-2 d-flex justify-content-between align-items-center w-100"
                        onclick="toggleDivision('div<?= $divId ?>', this)">
                        <?= htmlspecialchars($div['division_name']) ?>
                        <span class="arrow">&#9660;</span>
                    </button>
                    <div id="div<?= $divId ?>" class="user-grid" style="display:none;">
                        <?php foreach ($div['users'] as $user): ?>
                            <div class="form-check p-2">
                                <input class="form-check-input" type="checkbox"
                                    name="shared_to[]"
                                    value="<?= $user['id'] ?>"
                                    id="user<?= $user['id'] ?>"
                                    <?= in_array($user['id'], $shared_docs) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="user<?= $user['id'] ?>">
                                    <?= htmlspecialchars($user['full_name']) ?> (<?= htmlspecialchars($user['username']) ?>)
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary mt-3">Update Access</button>
            <a href="documents.php" class="btn btn-secondary mt-3">Back to Documents</a>
        </form>
    </div>
</div>

<style>
.user-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
.arrow {
    transition: transform 0.3s;
}
.arrow.open {
    transform: rotate(180deg);
}
</style>

<script>
function toggleDivision(id, btn) {
    const div = document.getElementById(id);
    div.style.display = div.style.display === 'none' ? 'grid' : 'none';

    // Rotate arrow
    const arrow = btn.querySelector('.arrow');
    if (arrow) arrow.classList.toggle('open');
}
</script>
