<?php
namespace App\Controllers\Admin;
use App\Controllers\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InventoryController extends BaseController {

    public function index(Request $request, Response $response): Response {
        global $pdo;
        $errors = []; $edit_product = null;
        $edit_id = (int)($request->getQueryParams()['edit'] ?? 0);
        if ($edit_id > 0) { $stmt=$pdo->prepare('SELECT * FROM products WHERE id=:id'); $stmt->execute([':id'=>$edit_id]); $edit_product=$stmt->fetch(); }
        $stmt = $pdo->query('SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC');
        $products = $stmt->fetchAll();
        $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
        $csrf_token = generate_csrf_token();
        $page_title='Inventory'; $current_page='admin';
        return $this->render($response, 'admin/inventory', compact('products','categories','edit_product','errors','page_title','current_page','csrf_token'));
    }

    public function update(Request $request, Response $response): Response {
        global $pdo;
        $data = $request->getParsedBody();
        if (!validate_csrf_token($data['csrf_token'] ?? '')) { $this->flash('Invalid request.','danger'); return $this->redirect($response, '/admin/inventory'); }

        // Delete
        if (isset($data['delete_product'])) {
            $del_id = (int)($data['product_id'] ?? 0);
            $stmt = $pdo->prepare('SELECT image_url FROM products WHERE id=:id'); $stmt->execute([':id'=>$del_id]); $del_prod=$stmt->fetch();
            if ($del_prod && !in_array($del_prod['image_url'],['logo.png','default.jpg'])) { $img=__DIR__.'/../../../public/uploads/'.$del_prod['image_url']; if(file_exists($img)) unlink($img); }
            $pdo->prepare('DELETE FROM products WHERE id=:id')->execute([':id'=>$del_id]);
            ekea_log('Product deleted','INFO',['product_id'=>$del_id]);
            $this->flash('Product deleted successfully.','success');
            return $this->redirect($response, '/admin/inventory');
        }

        // Save edit
        if (isset($data['save_product'])) {
            $errors = [];
            $product_id=(int)($data['product_id']??0); $name=trim($data['product_name']??'');
            $category_id=(int)($data['category_id']??0); $description=trim($data['description']??'');
            $price=(float)($data['price']??0); $stock=(int)($data['stock']??0);
            if (empty($name)) $errors[]='Product name is required.';
            if ($category_id<=0) $errors[]='Please select a category.';
            if ($price<=0) $errors[]='Price must be greater than $0.';
            if ($stock<0) $errors[]='Stock cannot be negative.';
            $image_url = $data['existing_image'] ?? 'logo.png';
            if (!empty($_FILES['product_image']['tmp_name'])) {
                $allowed=['image/jpeg','image/png','image/webp','image/gif'];
                $mime=mime_content_type($_FILES['product_image']['tmp_name']);
                if (!in_array($mime,$allowed)) { $errors[]='Invalid image type.'; }
                elseif ($_FILES['product_image']['size']>5*1024*1024) { $errors[]='Image must be under 5MB.'; }
                else {
                    $ext=pathinfo($_FILES['product_image']['name'],PATHINFO_EXTENSION);
                    $safe_name=preg_replace('/[^a-z0-9_-]/','',strtolower(str_replace(' ','_',$name)));
                    $image_url=$safe_name.'_'.time().'.'.strtolower($ext);
                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'],__DIR__.'/../../../public/uploads/'.$image_url)) { $errors[]='Failed to upload image.'; $image_url=$data['existing_image']??'logo.png'; }
                }
            }
            if (empty($errors)) {
                $pdo->prepare('UPDATE products SET name=:name,category_id=:cat,description=:desc,price=:price,stock=:stock,image_url=:img WHERE id=:id')
                    ->execute([':name'=>$name,':cat'=>$category_id,':desc'=>$description,':price'=>$price,':stock'=>$stock,':img'=>$image_url,':id'=>$product_id]);
                ekea_log('Product updated','INFO',['product_id'=>$product_id]);
                $this->flash('Product updated successfully.','success');
                return $this->redirect($response, '/admin/inventory');
            }
        }
        return $this->redirect($response, '/admin/inventory');
    }

    public function addPage(Request $request, Response $response): Response {
        global $pdo;
        $categories = $pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
        $csrf_token=generate_csrf_token(); $errors=[]; $page_title='Add Product'; $current_page='admin';
        return $this->render($response, 'admin/inventory_add', compact('categories','errors','page_title','current_page','csrf_token'));
    }

    public function add(Request $request, Response $response): Response {
        global $pdo;
        $data=$request->getParsedBody(); $errors=[];
        $name=trim($data['product_name']??''); $description=trim($data['description']??'');
        $price=(float)($data['price']??0); $stock=(int)($data['stock']??0); $category_id=(int)($data['category_id']??0);
        if (!validate_csrf_token($data['csrf_token']??'')) $errors[]='Invalid request.';
        if (empty($name)) $errors[]='Product name is required.';
        if ($category_id<=0) $errors[]='Please select a category.';
        if ($price<=0) $errors[]='Price must be greater than $0.';
        if ($stock<0) $errors[]='Stock cannot be negative.';
        $image_url='logo.png';
        if (!empty($_FILES['product_image']['tmp_name'])) {
            $allowed=['image/jpeg','image/png','image/webp','image/gif'];
            $mime=mime_content_type($_FILES['product_image']['tmp_name']);
            if (!in_array($mime,$allowed)) $errors[]='Invalid image type.';
            elseif ($_FILES['product_image']['size']>5*1024*1024) $errors[]='Image must be under 5MB.';
            else {
                $ext=pathinfo($_FILES['product_image']['name'],PATHINFO_EXTENSION);
                $safe_name=preg_replace('/[^a-z0-9_-]/','',strtolower(str_replace(' ','_',$name)));
                $image_url=$safe_name.'_'.time().'.'.strtolower($ext);
                if (!move_uploaded_file($_FILES['product_image']['tmp_name'],__DIR__.'/../../../public/uploads/'.$image_url)) { $errors[]='Failed to upload image.'; $image_url='logo.png'; }
            }
        }
        if (empty($errors)) {
            $pdo->prepare('INSERT INTO products (name,category_id,description,price,stock,image_url) VALUES (:name,:cat,:desc,:price,:stock,:img)')
                ->execute([':name'=>$name,':cat'=>$category_id,':desc'=>$description,':price'=>$price,':stock'=>$stock,':img'=>$image_url]);
            ekea_log('Product added','INFO',['name'=>$name]);
            $this->flash('Product added successfully.','success');
            return $this->redirect($response, '/admin/inventory');
        }
        $categories=$pdo->query('SELECT * FROM categories ORDER BY name')->fetchAll();
        $csrf_token=generate_csrf_token(); $page_title='Add Product'; $current_page='admin';
        return $this->render($response, 'admin/inventory_add', compact('categories','errors','page_title','current_page','csrf_token'));
    }
}
