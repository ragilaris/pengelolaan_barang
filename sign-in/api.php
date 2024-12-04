<?php
// Fungsi untuk mendapatkan data produk
function getProducts() {
    $file = 'products.json';
    
    if (file_exists($file)) {
        $data = file_get_contents($file); 
        return json_decode($data, true);
    }
    return []; 
}

// Fungsi untuk menyimpan data produk ke file JSON
function saveProducts($products) {
    $file = 'products.json';
    file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT));
}

// API untuk mendapatkan data produk
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(getProducts());
}

// API untuk menambah produk
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newProduct = json_decode(file_get_contents('php://input'), true);
    $products = getProducts();
    $newProduct['id'] = max(array_column($products, 'id')) + 1; 
    $products[] = $newProduct; 
    saveProducts($products);
    echo json_encode(['message' => 'Produk berhasil ditambahkan']);
}

// API untuk memperbarui produk
elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $updatedProduct = json_decode(file_get_contents('php://input'), true);
    $productId = $updatedProduct['id'];
    
    $products = getProducts();
    foreach ($products as $key => $product) {
        if ($product['id'] == $productId) {
            $products[$key] = $updatedProduct; // Memperbarui data produk
            saveProducts($products);
            echo json_encode(['message' => 'Produk berhasil diperbarui']);
            exit();
        }
    }
    echo json_encode(['message' => 'Produk tidak ditemukan']);
}

// API untuk menghapus produk
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = $data['id'];

    $products = getProducts();
    foreach ($products as $key => $product) {
        if ($product['id'] == $productId) {
            unset($products[$key]); 
            saveProducts(array_values($products)); 
            echo json_encode(['message' => 'Produk berhasil dihapus']);
            exit();
        }
    }
    echo json_encode(['message' => 'Produk tidak ditemukan']);
}
?>
