<?php
require __DIR__ . '/bootstrap.php';


function loadData($filename) {
$filepath = DATA_DIR . '/' . $filename;
$key = str_replace('.json', '', $filename);
if (!file_exists($filepath)) {
$emptyData = [$key => []];
file_put_contents(
$filepath,
json_encode($emptyData, JSON_PRETTY_PRINT |
JSON_UNESCAPED_UNICODE)
);
return [];
}
$content = file_get_contents($filepath);
$data = json_decode($content, true);
return $data[$key] ?? [];
}

function saveData(string $filename, array $data): bool {
 $filepath = DATA_DIR . '/' . $filename;
 $key = str_replace('.json', '', $filename);
 $jsonData = [$key => $data];

 $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT |
JSON_UNESCAPED_UNICODE);

 return file_put_contents($filepath, $jsonString) !== false;
 }

function generateId(string $prefix = 'post_'): string {
    return uniqid($prefix, true);
}

function getPosts(): array {
    return loadData('posts.json');
}


function getPostById(string $id): ?array {
    $posts = getPosts();
    foreach ($posts as $post) {
        if (isset($post['id']) && $post['id'] === $id) {
            return $post;
        }
    }
    return null;
}

function getCommentsByPostId(string $postId): array {
    $allComments = loadData('comments.json');
    $postComments = array_filter($allComments, function($comment) use ($postId) {
        return $comment['post_id'] === $postId;
    });
    
   
    usort($postComments, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    
    return $postComments;
}

function uploadImage(array $file): array {

    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Файл не был загружен'];
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Размер файла не должен превышать 2MB'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($mimeType, $allowedTypes) || !in_array($ext, $allowedExts)) {
        return ['success' => false, 'error' => 'Разрешены только изображения JPG, PNG, GIF'];
    }

    $filename = uniqid('img_', true) . '.' . $ext;
    $uploadPath = UPLOADS_DIR . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return [
            'success' => true,
            'path' => 'uploads/' . $filename,
            'filename' => $filename
        ];
    } else {
        return ['success' => false, 'error' => 'Ошибка при загрузке файла'];
    }
}

function deleteImage(string $filename): bool {
    $filepath = UPLOADS_DIR . '/' . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}
?>