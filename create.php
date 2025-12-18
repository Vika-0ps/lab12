<?php
require_once __DIR__ . '/includes/function.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $mediaPaths = [];

    if (empty($title) || empty($content)) {
        $error = 'Заполните заголовок и содержимое записи';
    } elseif (strlen($title) < 5) {
        $error = 'Заголовок должен содержать минимум 5 символов';
    } elseif (strlen($content) < 10) {
        $error = 'Содержимое должно содержать минимум 10 символов';
    }

  
    if (!$error && isset($_FILES['images'])) {
        
        $files = [];
        if (is_array($_FILES['images']['name'])) {
          
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    ];
                }
            }
        } else {
           
            if ($_FILES['images']['error'] === UPLOAD_ERR_OK) {
                $files[] = $_FILES['images'];
            }
        }

        foreach ($files as $file) {
            $result = uploadImage($file);
            if ($result['success']) {
                $mediaPaths[] = $result['path'];
            } else {
                $error = $result['error'];
                break;
            }
        }
    }

    if (!$error) {
        $newPost = [
            'id' => generateId('post_'),
            'title' => $title,
            'content' => $content,
            'author_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'created_at' => date('Y-m-d H:i:s'),
            'media' => $mediaPaths
        ];

        $posts = getPosts();
        $posts[] = $newPost;

        if (saveData('posts.json', $posts)) {
            header('Location: post.php?id=' . $newPost['id']);
            exit;
        } else {
            $error = 'Ошибка при сохранении записи';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name "viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать запись | Мой блог</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
        }
        
        .image-preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .remove-image {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>Мой интернет-блог</h1>
            <nav class="nav">
                <a href="index.php">Главная</a>
                <a href="logout.php">Выход (<?= htmlspecialchars($_SESSION['username']) ?>)</a>
            </nav>
        </div>
    </header>
    <main class="container">
        <div class="form-container">
            <h2>Создать новую запись</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
<form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="title">Заголовок записи:</label>
                    <input type="text" id="title" name="title" required minlength="5"
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="content">Содержимое:</label>
                    <textarea id="content" name="content" required minlength="10" rows="10"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="images">Изображения (необязательно):</label>
                    <input type="file" id="images" name="images[]" accept="image/*" multiple>
                    <small>Максимальный размер: 2MB каждый. Форматы: JPG, PNG, GIF. Можно выбрать несколько файлов</small>
                    
                    <div class="image-preview" id="imagePreview"></div>
                </div>

                <button type="submit" class="btn btn-primary">Опубликовать запись</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>Мой блог © <?= date('Y') ?> – Практический проект на PHP</p>
        </div>
    </footer>

    <script>
   
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            for (const file of e.target.files) {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'image-preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'remove-image';
                        removeBtn.innerHTML = '×';
                        removeBtn.onclick = function() {
                            div.remove();
                        };
                        
                        div.appendChild(img);
                        div.appendChild(removeBtn);
                        preview.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
</body>
</html>