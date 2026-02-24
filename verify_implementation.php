<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Category.php';
require_once __DIR__ . '/includes/Tag.php';
require_once __DIR__ . '/includes/Page.php';

echo "Starting Verification...\n";

try {
    $categoryModel = new Category();
    $tagModel = new Tag();
    $pageModel = new Page();
    $db = Database::getInstance();

    // 1. Create Category
    echo "[1] Creating Category 'Verification News'...\n";
    $catData = [
        'name' => 'Verification News',
        'slug' => 'verify-news',
        'description' => 'News for verification',
        'parent_id' => null,
        'sort_order' => 0
    ];
    $catId = $categoryModel->create($catData);
    if ($catId) {
        echo "SUCCESS: Category created with ID $catId\n";
    } else {
        throw new Exception("Failed to create category");
    }

    // 2. Create Tags
    echo "[2] Creating Tags 'VerifyTag1', 'VerifyTag2'...\n";
    $tagId1 = $tagModel->create('VerifyTag1');
    $tagId2 = $tagModel->create('VerifyTag2');
    if ($tagId1 && $tagId2) {
        echo "SUCCESS: Tags created with IDs $tagId1, $tagId2\n";
    } else {
        throw new Exception("Failed to create tags");
    }

    // 3. Create Page with Category and Tags
    echo "[3] Creating Page 'Verification Page' assigned to Category and Tags...\n";
    $pageData = [
        'title' => 'Verification Page',
        'slug' => 'verify-page',
        'content' => '<p>This is a verification page.</p>',
        'template' => 'default',
        'status' => 'published',
        'category_id' => $catId,
        'meta_title' => 'Verify Meta',
        'meta_description' => 'Verify Desc'
    ];
    $pageId = $pageModel->createPage($pageData);

    // Sync Tags
    $tagModel->syncPageTags($pageId, [$tagId1, $tagId2]);

    if ($pageId) {
        echo "SUCCESS: Page created with ID $pageId\n";
    } else {
        throw new Exception("Failed to create page");
    }

    // 4. Verify Data (Backend)
    echo "[4] Verifying Data Integrity...\n";
    $page = $pageModel->getPageById($pageId);
    if ($page['category_id'] == $catId) {
        echo "SUCCESS: Category ID matches.\n";
    } else {
        echo "FAILURE: Category ID mismatch. Expected $catId, got {$page['category_id']}\n";
    }

    $tags = $tagModel->getTagsByPageId($pageId);
    if (count($tags) === 2) {
        echo "SUCCESS: Page has 2 tags.\n";
    } else {
        echo "FAILURE: Page has " . count($tags) . " tags.\n";
    }

    // 5. Frontend Simulation (Manual check via output inspection implemented implicitly by checking DB)
    // To properly simulate frontend, we would use curl or include index.php with capturing output,
    // but verifying DB is sufficient for backend logic.
    // Let's verify route fetching logic.

    echo "[5] Verifying Route Logic...\n";
    $pagesByCat = $pageModel->getPagesByCategory($catId);
    if (count($pagesByCat) > 0 && $pagesByCat[0]['id'] == $pageId) {
        echo "SUCCESS: getPagesByCategory returned the page.\n";
    } else {
        echo "FAILURE: getPagesByCategory failed.\n";
    }

    $pagesByTag = $pageModel->getPagesByTag($tagId1);
    if (count($pagesByTag) > 0 && $pagesByTag[0]['id'] == $pageId) {
        echo "SUCCESS: getPagesByTag returned the page.\n";
    } else {
        echo "FAILURE: getPagesByTag failed.\n";
    }

    // Cleanup (optional, but good for repeatability if we wanted, but let's keep it to see results)

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
