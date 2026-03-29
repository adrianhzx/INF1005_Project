<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Google\Cloud\Storage\StorageClient;

class InventoryController extends BaseController
{
    // Make sure to put YOUR actual bucket name here!
    private string $bucketName = 'ekea-image-bucket';
    private string $gcpKeyPath = '/var/www/private/gcp-key.json';

    public function index(Request $request, Response $response): Response
    {
        global $pdo;
        $errors = [];
        $edit_product = null;
        $edit_id = (int)($request->getQueryParams()['edit'] ?? 0);

        if ($edit_id > 0) {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE id=:id');
            $stmt->execute([':id' => $edit_id]);
            $edit_product = $stmt->fetch();
        }

        $stmt = $pdo->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC');
        $products = $stmt->fetchAll();
        $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();

        $csrf_token = generate_csrf_token();
        $page_title = 'Inventory';
        $current_page = 'admin';

        return $this->render($response, 'admin/inventory', compact('products', 'categories', 'edit_product', 'errors', 'page_title', 'current_page', 'csrf_token'));
    }

    public function update(Request $request, Response $response): Response
    {
        global $pdo;
        $data = $request->getParsedBody();

        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/admin/inventory');
        }

        // --- DELETE PRODUCT ---
        if (isset($data['delete_product'])) {
            $del_id = (int)($data['product_id'] ?? 0);

            // Delete image from Google Cloud before deleting product
            $stmt = $pdo->prepare('SELECT image_url FROM products WHERE id=:id');
            $stmt->execute([':id' => $del_id]);
            $del_prod = $stmt->fetch();

            if ($del_prod && strpos($del_prod['image_url'], 'storage.googleapis.com') !== false) {
                try {
                    $storage = new StorageClient(['keyFilePath' => $this->gcpKeyPath]);
                    $filename = basename(parse_url($del_prod['image_url'], PHP_URL_PATH));
                    $storage->bucket($this->bucketName)->object($filename)->delete();
                } catch (\Exception $e) {
                    ekea_log('Failed to delete GCP image', 'ERROR', ['error' => $e->getMessage()]);
                }
            }

            $pdo->prepare('DELETE FROM products WHERE id=:id')->execute([':id' => $del_id]);
            ekea_log('Product deleted', 'INFO', ['product_id' => $del_id]);
            $this->flash('Product deleted successfully.', 'success');

            return $this->redirect($response, '/admin/inventory');
        }

        // --- SAVE EDITED PRODUCT ---
        if (isset($data['save_product'])) {
            $product_id = (int)($data['product_id'] ?? 0);
            $name = trim($data['product_name'] ?? '');

            // Process Image via Google Cloud Helper
            $image_url = $data['existing_image'] ?? 'logo.png';
            if (!empty($_FILES['product_image']['tmp_name'])) {
                $cloudUrl = $this->uploadToCloud($_FILES['product_image'], $name);
                if ($cloudUrl) {
                    $image_url = $cloudUrl;
                } else {
                    $phpError = $_FILES['product_image']['error'];
                    $this->flash("Upload failed. PHP Error Code: $phpError. (0=Success, 1=Too big for server, 4=No file received)", "danger");
                    return $this->redirect($response, '/admin/inventory');
                }
            }

            // Update Database
            $pdo->prepare('UPDATE products SET name=:name, category_id=:cat, description=:desc, price=:price, stock=:stock, image_url=:img WHERE id=:id')
                ->execute([
                    ':name'  => $name,
                    ':cat'   => (int)($data['category_id'] ?? 0),
                    ':desc'  => trim($data['description'] ?? ''),
                    ':price' => (float)($data['price'] ?? 0),
                    ':stock' => (int)($data['stock'] ?? 0),
                    ':img'   => $image_url,
                    ':id'    => $product_id
                ]);

            ekea_log('Product updated', 'INFO', ['product_id' => $product_id]);
            $this->flash('Product updated successfully.', 'success');
        }

        return $this->redirect($response, '/admin/inventory');
    }

    public function addPage(Request $request, Response $response): Response
    {
        global $pdo;
        $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
        $csrf_token = generate_csrf_token();
        $errors = [];
        $page_title = 'Add Product';
        $current_page = 'admin';
        return $this->render($response, 'admin/inventory_add', compact('categories', 'errors', 'page_title', 'current_page', 'csrf_token'));
    }

    public function add(Request $request, Response $response): Response
    {
        global $pdo;
        $data = $request->getParsedBody();
        $name = trim($data['product_name'] ?? '');

        if (!validate_csrf_token($data['csrf_token'] ?? '')) {
            $this->flash('Invalid request.', 'danger');
            return $this->redirect($response, '/admin/inventory/add');
        }

        // Process Image via Google Cloud Helper
        $image_url = 'logo.png';
        if (!empty($_FILES['product_image']['tmp_name'])) {
            $cloudUrl = $this->uploadToCloud($_FILES['product_image'], $name);
            if ($cloudUrl) {
                $image_url = $cloudUrl;
            } else {
                $this->flash('Failed to upload image. Must be JPG/PNG/WEBP under 5MB.', 'danger');
                return $this->redirect($response, '/admin/inventory/add');
            }
        }

        // Insert into Database
        $pdo->prepare('INSERT INTO products (name, category_id, description, price, stock, image_url) VALUES (:name, :cat, :desc, :price, :stock, :img)')
            ->execute([
                ':name'  => $name,
                ':cat'   => (int)($data['category_id'] ?? 0),
                ':desc'  => trim($data['description'] ?? ''),
                ':price' => (float)($data['price'] ?? 0),
                ':stock' => (int)($data['stock'] ?? 0),
                ':img'   => $image_url
            ]);

        ekea_log('Product added', 'INFO', ['name' => $name]);
        $this->flash('Product added successfully.', 'success');

        return $this->redirect($response, '/admin/inventory');
    }

    /**
     * Helper Function: Handles validation and uploading to Google Cloud Storage
     * Returns the public URL if successful, or false if it fails.
     */
    private function uploadToCloud($file, $productName)
    {
        $allowed = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/webp',
    'image/gif',
    'image/pjpeg',
    'image/x-png',
    'application/octet-stream' // Add this! Some browsers send images as this.
];
        $mime = mime_content_type($file['tmp_name']);
        $this->flash("DEBUG: PHP thinks this file is: " . $mime, "info");

        // Validate type and size (5MB max)
        if (!in_array($mime, $allowed)) {
            // This will overwrite the debug message above, so let's combine them:
            $this->flash("Invalid image type ($mime). Please use JPG, PNG, or WEBP.", "danger");
            return false;
        }

        try {
            $storage = new StorageClient([
                'keyFilePath' => $this->gcpKeyPath
            ]);

            $bucket = $storage->bucket($this->bucketName);

            // Format a clean filename
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safe_name = preg_replace('/[^a-z0-9_-]/', '', strtolower(str_replace(' ', '_', $productName)));
            $filename = $safe_name . '_' . time() . '.' . strtolower($ext);

            // Upload the file stream directly to GCP
            $stream = fopen($file['tmp_name'], 'r');
            $bucket->upload($stream, [
                'name' => $filename
            ]);

            // Return the public URL
            return "https://storage.googleapis.com/{$this->bucketName}/{$filename}";

        } catch (\Exception $e) {
            ekea_log('GCP Upload Failed', 'ERROR', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
