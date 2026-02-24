<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/config.php';

/**
 * 画像アップロード管理クラス
 *
 * 画像のアップロード、サムネイル生成、CRUD操作を提供する。
 * セキュリティ対策としてMIMEタイプ検証、拡張子ホワイトリスト、
 * getimagesize()による実画像チェックを実施。
 */
class Image
{
    private $db;

    /** @var string 画像保存ディレクトリ */
    private string $uploadDir;

    /** @var string サムネイル保存ディレクトリ */
    private string $thumbnailDir;

    /** @var string アップロードURL */
    private string $uploadUrl;

    /** @var int 最大ファイルサイズ（5MB） */
    private const MAX_FILE_SIZE = 5 * 1024 * 1024;

    /** @var int サムネイル最大幅 */
    private const THUMBNAIL_MAX_WIDTH = 300;

    /** @var int サムネイル最大高さ */
    private const THUMBNAIL_MAX_HEIGHT = 300;

    /** @var array 許可するMIMEタイプ */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /** @var array MIMEタイプと拡張子の対応 */
    private const MIME_TO_EXTENSION = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->uploadDir = rtrim(UPLOAD_DIR, '/') . '/images/';
        $this->thumbnailDir = rtrim(UPLOAD_DIR, '/') . '/thumbnails/';
        $this->uploadUrl = rtrim(UPLOAD_URL, '/');

        $this->ensureDirectories();
    }

    /**
     * 必要なディレクトリを作成
     */
    private function ensureDirectories(): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!is_dir($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0755, true);
        }
    }

    /**
     * 画像をアップロードする
     *
     * @param array $file $_FILES['image'] の内容
     * @param int $userId アップロードしたユーザーのID
     * @param string $altText alt属性テキスト
     * @return array アップロード結果の情報
     * @throws \RuntimeException バリデーション失敗時
     */
    public function upload(array $file, int $userId, string $altText = ''): array
    {
        // バリデーション
        $this->validateFile($file);

        // 安全なファイル名を生成
        $mimeType = $file['type'];
        $extension = self::MIME_TO_EXTENSION[$mimeType];
        $filename = $this->generateSafeFilename($extension);

        // 画像情報を取得
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new \RuntimeException('有効な画像ファイルではありません。');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // ファイルを保存
        $destination = $this->uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('ファイルの保存に失敗しました。');
        }

        // サムネイルを生成
        $this->createThumbnail($destination, $filename, $mimeType);

        // データベースに記録
        $sql = "INSERT INTO uploads (filename, original_name, mime_type, file_size, width, height, alt_text, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($sql, [
            $filename,
            $file['name'],
            $mimeType,
            $file['size'],
            $width,
            $height,
            $altText,
            $userId,
        ]);

        $id = (int) $this->db->lastInsertId();

        return [
            'id'            => $id,
            'filename'      => $filename,
            'original_name' => $file['name'],
            'url'           => $this->getImageUrl($filename),
            'thumbnail_url' => $this->getThumbnailUrl($filename),
            'width'         => $width,
            'height'        => $height,
            'alt_text'      => $altText,
        ];
    }

    /**
     * アップロードファイルのバリデーション
     *
     * @param array $file $_FILES の要素
     * @throws \RuntimeException バリデーション失敗時
     */
    private function validateFile(array $file): void
    {
        // アップロードエラーチェック
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'ファイルサイズがサーバーの制限を超えています。',
                UPLOAD_ERR_FORM_SIZE  => 'ファイルサイズがフォームの制限を超えています。',
                UPLOAD_ERR_PARTIAL    => 'ファイルのアップロードが中断されました。',
                UPLOAD_ERR_NO_FILE    => 'ファイルが選択されていません。',
                UPLOAD_ERR_NO_TMP_DIR => 'サーバーの一時ディレクトリが見つかりません。',
                UPLOAD_ERR_CANT_WRITE => 'サーバーへの書き込みに失敗しました。',
                UPLOAD_ERR_EXTENSION  => 'PHPの拡張モジュールによりアップロードが中断されました。',
            ];
            $code = $file['error'] ?? -1;
            $message = $errorMessages[$code] ?? 'ファイルのアップロードに失敗しました。';
            throw new \RuntimeException($message);
        }

        // ファイルサイズチェック
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $maxMb = self::MAX_FILE_SIZE / 1024 / 1024;
            throw new \RuntimeException("ファイルサイズが上限（{$maxMb}MB）を超えています。");
        }

        // MIMEタイプチェック
        if (!in_array($file['type'], self::ALLOWED_MIME_TYPES, true)) {
            throw new \RuntimeException('対応していないファイル形式です。JPEG、PNG、GIF、WebPのみアップロード可能です。');
        }

        // 実画像チェック（getimagesize で検証）
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new \RuntimeException('有効な画像ファイルではありません。');
        }

        // 実際のMIMEタイプと申告されたMIMEタイプの一致チェック
        if (!in_array($imageInfo['mime'], self::ALLOWED_MIME_TYPES, true)) {
            throw new \RuntimeException('ファイルの内容が申告された形式と一致しません。');
        }
    }

    /**
     * 安全なファイル名を生成
     *
     * @param string $extension ファイル拡張子
     * @return string 生成されたファイル名
     */
    private function generateSafeFilename(string $extension): string
    {
        return date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    }

    /**
     * サムネイルを生成
     *
     * @param string $sourcePath 元画像のパス
     * @param string $filename ファイル名
     * @param string $mimeType MIMEタイプ
     */
    private function createThumbnail(string $sourcePath, string $filename, string $mimeType): void
    {
        $sourceImage = $this->createImageResource($sourcePath, $mimeType);
        if ($sourceImage === null) {
            return;
        }

        $origWidth = imagesx($sourceImage);
        $origHeight = imagesy($sourceImage);

        // リサイズ比を計算
        $ratio = min(
            self::THUMBNAIL_MAX_WIDTH / $origWidth,
            self::THUMBNAIL_MAX_HEIGHT / $origHeight,
            1.0 // 元画像が小さい場合は拡大しない
        );

        $newWidth = (int) round($origWidth * $ratio);
        $newHeight = (int) round($origHeight * $ratio);

        // サムネイル画像を作成
        $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

        // PNG/WebPの透過処理
        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
            $transparent = imagecolorallocatealpha($thumbnail, 0, 0, 0, 127);
            imagefilledrectangle($thumbnail, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // GIFの透過処理
        if ($mimeType === 'image/gif') {
            $transparentIndex = imagecolortransparent($sourceImage);
            if ($transparentIndex >= 0) {
                $transparentColor = imagecolorsforindex($sourceImage, $transparentIndex);
                $newTransparentIndex = imagecolorallocate(
                    $thumbnail,
                    $transparentColor['red'],
                    $transparentColor['green'],
                    $transparentColor['blue']
                );
                imagefill($thumbnail, 0, 0, $newTransparentIndex);
                imagecolortransparent($thumbnail, $newTransparentIndex);
            }
        }

        imagecopyresampled(
            $thumbnail,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $origWidth,
            $origHeight
        );

        // サムネイルを保存
        $thumbnailPath = $this->thumbnailDir . $filename;
        $this->saveImage($thumbnail, $thumbnailPath, $mimeType);

        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
    }

    /**
     * MIMEタイプに応じて画像リソースを作成
     *
     * @param string $path 画像ファイルのパス
     * @param string $mimeType MIMEタイプ
     * @return \GdImage|null
     */
    private function createImageResource(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/gif'  => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        };
    }

    /**
     * MIMEタイプに応じて画像を保存
     *
     * @param \GdImage $image 画像リソース
     * @param string $path 保存先パス
     * @param string $mimeType MIMEタイプ
     */
    private function saveImage($image, string $path, string $mimeType): void
    {
        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, 85),
            'image/png'  => imagepng($image, $path, 8),
            'image/gif'  => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, 85),
            default      => null,
        };
    }

    /**
     * IDで画像を取得
     *
     * @param int $id 画像ID
     * @return array|false
     */
    public function getById(int $id)
    {
        $sql = "SELECT u.*, us.username AS uploader_name
                FROM uploads u
                LEFT JOIN users us ON u.uploaded_by = us.id
                WHERE u.id = ?";
        $result = $this->db->query($sql, [$id])->fetch();

        if ($result) {
            $result['url'] = $this->getImageUrl($result['filename']);
            $result['thumbnail_url'] = $this->getThumbnailUrl($result['filename']);
        }

        return $result;
    }

    /**
     * 画像一覧を取得（ページネーション付き）
     *
     * @param int $page ページ番号（1始まり）
     * @param int $perPage 1ページあたりの件数
     * @return array ['items' => [...], 'total' => int, 'pages' => int]
     */
    public function getAll(int $page = 1, int $perPage = 20): array
    {
        // 総件数を取得
        $countSql = "SELECT COUNT(*) as total FROM uploads";
        $total = (int) $this->db->query($countSql)->fetch()['total'];

        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        // 画像一覧を取得
        $sql = "SELECT u.*, us.username AS uploader_name
                FROM uploads u
                LEFT JOIN users us ON u.uploaded_by = us.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";

        // PDOのLIMIT/OFFSETをintとしてバインド
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        // URLを付加
        foreach ($items as &$item) {
            $item['url'] = $this->getImageUrl($item['filename']);
            $item['thumbnail_url'] = $this->getThumbnailUrl($item['filename']);
        }

        return [
            'items'  => $items,
            'total'  => $total,
            'pages'  => $totalPages,
            'page'   => $page,
        ];
    }

    /**
     * 画像を削除（ファイルとDB両方）
     *
     * @param int $id 画像ID
     * @return bool 削除成功時 true
     * @throws \RuntimeException 画像が見つからない場合
     */
    public function delete(int $id): bool
    {
        $image = $this->getById($id);
        if (!$image) {
            throw new \RuntimeException('画像が見つかりません。');
        }

        // ファイルを削除
        $imagePath = $this->uploadDir . $image['filename'];
        $thumbnailPath = $this->thumbnailDir . $image['filename'];

        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        // DBから削除
        $sql = "DELETE FROM uploads WHERE id = ?";
        $this->db->query($sql, [$id]);

        return true;
    }

    /**
     * alt属性を更新
     *
     * @param int $id 画像ID
     * @param string $altText 新しいalt属性テキスト
     * @return bool
     */
    public function updateAltText(int $id, string $altText): bool
    {
        $sql = "UPDATE uploads SET alt_text = ? WHERE id = ?";
        $this->db->query($sql, [$altText, $id]);
        return true;
    }

    /**
     * 画像のURLを取得
     *
     * @param string $filename ファイル名
     * @return string
     */
    public function getImageUrl(string $filename): string
    {
        return $this->uploadUrl . '/images/' . $filename;
    }

    /**
     * サムネイルのURLを取得
     *
     * @param string $filename ファイル名
     * @return string
     */
    public function getThumbnailUrl(string $filename): string
    {
        return $this->uploadUrl . '/thumbnails/' . $filename;
    }

    /**
     * アップロードディレクトリのサイズを取得（バイト）
     *
     * @return int
     */
    public function getTotalSize(): int
    {
        $size = 0;
        $files = glob($this->uploadDir . '*');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    $size += filesize($file);
                }
            }
        }
        return $size;
    }
}
