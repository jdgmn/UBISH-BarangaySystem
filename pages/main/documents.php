<?php
session_start();
include '../../config/dbfetch.php';

// file fetch
$documentsFetch = "SELECT * FROM files";
$stmt = $pdo->query($documentsFetch);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['upload'])) {
    $title = $_POST['title'];
    $uploadedBy = $_SESSION['user_id'];
    $uploadDate = date('Y-m-d H:i:s');
    $privacy = 'Public'; // default

    $uploadDir = '../../uploads/documents/';
    $maxSize = 10 * 1024 * 1024;
    $errors = [];

    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName = $_FILES['document']['tmp_name'];
        $fileName = uniqid() . '-' . basename($_FILES['document']['name']);
        $fileSize = $_FILES['document']['size'];
        $fileType = $_FILES['document']['type']; // MIME type

        if ($fileSize > $maxSize) {
            $errors[] = "File exceeds 10MB size limit.";
        }

        if (empty($errors)) {
            $filePath = $uploadDir . $fileName;
            if (move_uploaded_file($fileTmpName, $filePath)) {
                $insert = "INSERT INTO files (file_name, file_path, uploaded_by, upload_date, title, privacy, file_type) 
                    VALUES (:file_name, :file_path, :uploaded_by, :upload_date, :title, :privacy, :file_type)";
                $stmt = $pdo->prepare($insert);
                $stmt->execute([
                    ':file_name' => htmlspecialchars($title),
                    ':file_path' => htmlspecialchars($filePath),
                    ':uploaded_by' => $uploadedBy,
                    ':upload_date' => $uploadDate,
                    ':title' => htmlspecialchars($title),
                    ':privacy' => $privacy,
                    ':file_type' => $fileType
                ]);

                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $errors[] = "Failed to upload the file.";
            }
        }
    } else {
        $errors[] = "No file selected or an error occurred during file upload.";
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }
}

// generate documents table
function getDocumentsTable($pdo, $currentUserId)
{
    $documentsFetch = "SELECT * FROM files";
    $stmt = $pdo->query($documentsFetch);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = "";

    $mimeMap = [
        'application/pdf' => 'PDF',
        'application/msword' => 'Word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'Word',
        'application/vnd.ms-excel' => 'Excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'Excel',
        'image/jpeg' => 'JPEG',
        'image/png' => 'PNG',
        'text/plain' => 'Text'
    ];

    foreach ($documents as $document) {
        $documentId = $document['file_id'];
        $title = htmlspecialchars($document['file_name']);
        $filePath = htmlspecialchars($document['file_path']);

        $rawType = $document['file_type'] ?? 'unknown/unknown';
        $fileType = $mimeMap[$rawType] ?? strtoupper(explode('/', $rawType)[1] ?? 'Unknown');

        $uploadedBy = $document['uploaded_by'];
        $uploadDate = $document['upload_date'];

        // username fetch
        $userFetch = "SELECT username FROM login_details WHERE user_id = :uploaded_by";
        $stmtUser = $pdo->prepare($userFetch);
        $stmtUser->execute([':uploaded_by' => $uploadedBy]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        $username = $user ? $user['username'] : 'Unknown';

        $actionBtn = $uploadedBy == $currentUserId
            ? "<button onclick='confirmDelete($documentId)'>Delete</button>"
            : "<em style='color:gray;'>N/A</em>";

        $output .= "<tr>
                        <td>$title</td>
                        <td><a href='$filePath' download>Download</a></td>
                        <td>$fileType</td>
                        <td>$username</td>
                        <td>$uploadDate</td>
                        <td>$actionBtn</td>
                    </tr>";
    }

    return $output;
}
?>

<!-- HTML STARTS HERE -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <title>UBISH Dashboard | Documents</title>
    <style>
        .document-upload-form {
            text-align: left;
            margin: 0;
            width: 100%;
        }

        button {
            border: 2px solid gray;
            background-color: white;
            color: black;
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: lightgray;
        }

        button.logout {
            border: none;
            background-color: white;
            font-size: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
        }

        table th,
        table td {
            padding: 8px 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: lightgray;
        }

        .document-credentials {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .document-credentials input,
        .document-credentials textarea {
            margin-bottom: 16px;
            width: 100%;
            padding: 8px;
            padding-left: 0px;
            box-sizing: border-box;
        }
    </style>
</head>

<body>
    <style>
    header {
        background-color: #e1f3e2 !important;
        border-bottom: 5px solid #356859 !important;
    }
    .logout {
        background-color: #e1f3e2 !important;
        color: #356859 !important;
        font-weight: bold !important;
        font-size: 1.1rem !important;
    }
    footer {
        background-color: #d0e9d2 !important;
        text-align: center !important;
        padding: 20px !important;
        color: #2b3d2f !important;
        border-top: 5px solid #356859 !important;
        margin-top: 60px !important;
    }
    </style>
    <header>
        <div class="navigation">
            <div class="logo">
                <img src="../../assets/img/greenwater-village-logo.jpg" alt="Greenwater Village Logo">
                <h1>UBISH</h1>
            </div>
            <form method="POST">
                <nav>
                    <ul>
                        <li><button class="logout" style="cursor: pointer;" name="logout">Log Out</button></li>
                    </ul>
                </nav>
            </form>
        </div>
        <hr>
    </header>
    <main>
        <div class="dashboard-main">
            <div class="dashboard-sidebar">
                <ul>
                    <h3>Home</h3>
                    <li><a href="../main/dashboard.php">Home</a></li>
                    <li><a href="../main/account.php">Account</a></li>
                    
                    <h3>Documents & Disclosure</h3>
                    <li class="active"><a href="../main/documents.php">Documents</a></li>
                    <li><a href="../main/announcements.php">Post Announcement</a></li>
                    
                    <h3>Tables & Requests</h3>
                    <li><a href="../main/employee_table.php">Employee Table</a></li>

                    <?php if ($accessLevel >= 2) { echo '<li><a href="../main/certificates.php">Certificate Requests</a></li>'; } ?>
                    <?php if ($accessLevel >= 2) { echo '<li><a href="../main/permits.php">Permit Requests</a></li>'; } ?>
                    
                    <?php if ($accessLevel >= 3) { echo '<li><a href="../main/account_requests.php">Account Requests</a></li>'; } ?>
                    
                    <h3>Reports</h3>
                    <?php if ($accessLevel >= 2) { echo '<li><a href="../main/incidents.php">Incident Reports</a></li>'; } ?>
                    
                    <li><a href="../main/incident_table.php">Incident History</a></li>
                    <li><a href="../main/reports.php">Analytics</a></li>
                </ul>
            </div>
            <div class="dashboard-content">
                <h1><center>Documents</center></h1><br>
                <div class="document-upload-form">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="document-credentials">
                            <h3>Document Title</h3>
                            <input type="text" name="title" placeholder="Enter document title" required>
                        </div>
                        <div class="document-credentials">
                            <h3>Upload Document</h3>
                            <input type="file" name="document" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.png" required>
                            <button name="upload" id="uploadDocument">Upload</button>
                        </div>
                    </form>
                </div>

                <h2><center>Uploaded Documents</center></h2>
                <table class="documents-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>File</th>
                            <th>Type</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php echo getDocumentsTable($pdo, $_SESSION['user_id']); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <footer>
        <hr>
        <p><?php echo "&copy; " . date('Y') . " | Unified Barangay Information Service Hub"; ?></p>
    </footer>

    <script>
        function confirmDelete(documentId) {
            const confirmation = confirm("Are you sure you want to delete this document?");
            if (confirmation) {
                window.location.href = "delete_document.php?id=" + documentId;
            }
        }
    </script>
</body>

</html>
