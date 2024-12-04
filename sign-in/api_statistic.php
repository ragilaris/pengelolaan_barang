<?php
// Endpoint untuk mendapatkan statistik produk
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['stats'])) {
    // Ambil semua produk dari sumber data
    $products = json_decode(file_get_contents('products.json'), true); 

    // Inisialisasi statistik
    $totalProducts = count($products);
    $availableProducts = 0;
    $outOfStockProducts = 0;

    // Hitung produk yang tersedia dan tidak tersedia
    foreach ($products as $product) {
        if ($product['availability'] === 'Tersedia') {
            $availableProducts++;
        } else {
            $outOfStockProducts++;
        }
    }

    // Kirimkan hasil sebagai respons JSON
    header('Content-Type: application/json');
    echo json_encode([
        'total' => $totalProducts,
        'available' => $availableProducts,
        'out_of_stock' => $outOfStockProducts
    ]);
    exit();
}
?>