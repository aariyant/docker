<?php
// Connect to MySQL
$pdo = new PDO('mysql:host=db;dbname=myapp', 'user', 'pass', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $stmt = $pdo->prepare("INSERT INTO users (name) VALUES (:name)");
    $stmt->execute(['name' => $_POST['name']]);
    header("Location: /");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: /");
    exit;
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>PHP Docker CRUD</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        input[type="text"] { padding: 0.5rem; width: 200px; }
        button { padding: 0.5rem; }
        ul { list-style: none; padding: 0; }
        li { margin: 0.5rem 0; }
    </style>
</head>
<body>
    <h1>Simple PHP CRUD</h1>

    <h2>Add User</h2>
    <form method="POST">
        <input type="text" name="name" placeholder="Enter name" required>
        <button type="submit">Add</button>
    </form>

    <h2>Users</h2>
    <ul>
        <?php foreach ($users as $user): ?>
            <li>
                <?= htmlspecialchars($user['name']) ?>
                <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure?')">❌</a>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
