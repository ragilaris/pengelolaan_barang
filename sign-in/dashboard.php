<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); 
    exit();
}

// Ambil data produk dari API
$products = json_decode(file_get_contents('http://localhost/pengelolaanBarang/sign-in/api.php'), true);

// Jika data produk kosong atau tidak ada
if ($products === null) {
    $products = []; 
}

// Fungsi untuk menambah produk
$message = ''; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $newProduct = [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category' => $_POST['category'],
            'availability' => $_POST['availability']
        ];
        // Kirim request POST ke API untuk menambah produk
        $ch = curl_init('http://localhost/pengelolaanBarang/sign-in/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newProduct));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        $message = 'Produk berhasil ditambahkan!'; 
        header("Location: dashboard.php?message=" . urlencode($message)); 
        exit();
    } elseif (isset($_POST['update_product'])) {
        $updatedProduct = [
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'category' => $_POST['category'],
            'availability' => $_POST['availability']
        ];
        // Kirim request PUT ke API untuk memperbarui produk
        $ch = curl_init('http://localhost/pengelolaanBarang/sign-in/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updatedProduct));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        $message = 'Produk berhasil diperbarui!'; 
        header("Location: dashboard.php?message=" . urlencode($message)); 
        exit();
    } elseif (isset($_POST['delete_product'])) {
        $productId = $_POST['id'];
        // Kirim request DELETE ke API untuk menghapus produk
        $ch = curl_init('http://localhost/pengelolaanBarang/sign-in/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id' => $productId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_exec($ch);
        curl_close($ch);
        $message = 'Produk berhasil dihapus!'; 
        header("Location: dashboard.php?message=" . urlencode($message)); 
        exit();
    }
}

// Proses Pencarian Produk
$searchTerm = ''; 
$availabilityFilter = ''; 
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
}
if (isset($_GET['availability'])) {
    $availabilityFilter = $_GET['availability'];
}

// Filter produk berdasarkan kata kunci pencarian dan ketersediaan
$filteredProducts = array_filter($products, function($product) use ($searchTerm, $availabilityFilter) {
    $matchesSearch = stripos($product['name'], $searchTerm) !== false || 
                     stripos($product['description'], $searchTerm) !== false || 
                     stripos($product['category'], $searchTerm) !== false;

    $matchesAvailability = $availabilityFilter === '' || $product['availability'] === $availabilityFilter;

    return $matchesSearch && $matchesAvailability;
});

// Pagination
$productsPerPage = 5; 
$totalProducts = count($filteredProducts); 
$totalPages = ceil($totalProducts / $productsPerPage); 

// Ambil halaman saat ini
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, min($currentPage, $totalPages)); 
// Hitung offset untuk query produk
$offset = ($currentPage - 1) * $productsPerPage;
$paginatedProducts = array_slice($filteredProducts, $offset, $productsPerPage); 

// Ambil statistik produk
$stats = json_decode(file_get_contents('http://localhost/pengelolaanBarang/sign-in/api_statistic.php?stats'), true);
$totalProducts = $stats['total'] ?? 0;
$availableProducts = $stats['available'] ?? 0;
$outOfStockProducts = $stats['out_of_stock'] ?? 0;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Dashboard - CRUD Produk</title>
</head>
<body class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <a href="logout.php" class="btn btn-secondary">Logout</a>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h3>Daftar Produk</h3>

    <div class="alert alert-info" role="alert">
    <strong>Statistik Produk:</strong><br>
    Total Produk: <?php echo $totalProducts; ?><br>
    Produk Tersedia: <?php echo $availableProducts; ?><br>
    Produk Habis: <?php echo $outOfStockProducts; ?>
    </div>

    <!-- Form Pencarian dan Filter Ketersediaan -->
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Cari produk..." value="<?php echo htmlspecialchars($searchTerm); ?>">
            <select name="availability" class="form-select">
                <option value="">Semua Ketersediaan</option>
                <option value="Tersedia" <?php echo $availabilityFilter == 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                <option value="Tidak Tersedia" <?php echo $availabilityFilter == 'Tidak Tersedia' ? 'selected' : ''; ?>>Tidak Tersedia</option>
            </select>
            <button class="btn btn-primary" type="submit">Cari</button>
        </div>
    </form>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Deskripsi</th>
                <th>Harga</th>
                <th>Kategori</th>
                <th>Status Ketersediaan</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paginatedProducts as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                    <td><?php echo htmlspecialchars($product['price']); ?></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td><?php echo htmlspecialchars($product['availability']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required class="form-control">
                            <input type="text" name="description" value="<?php echo htmlspecialchars($product['description']); ?>" required class="form-control">
                            <input type="number" name="price" value="<?php echo htmlspecialchars($product['price']); ?>" required class="form-control">
                            <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required class="form-control">
                            <select name="availability" class="form-select">
                                <option value="Tersedia" <?php echo $product['availability'] == 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                <option value="Tidak Tersedia" <?php echo $product['availability'] == 'Tidak Tersedia' ? 'selected' : ''; ?>>Tidak Tersedia</option>
                            </select>
                            <button type="submit" name="update_product" class="btn btn-warning mt-2">Update</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <button type="submit" name="delete_product" class="btn btn-danger mt-2">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Navigasi Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination">
            <li class="page-item <?php if ($currentPage <= 1) echo 'disabled'; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($availabilityFilter); ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php if ($i == $currentPage) echo 'active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($availabilityFilter); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php if ($currentPage >= $totalPages) echo 'disabled'; ?>">
                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&availability=<?php echo urlencode($availabilityFilter); ?>">Next</a>
            </li>
        </ul>
    </nav>

    <h3>Tambah Produk</h3>
    <form method="POST" action="">
        <div class="mb-3">
            <label for="name" class="form-label">Nama Produk:</label>
            <input type="text" name="name" required class="form-control">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Deskripsi:</label>
            <input type="text" name="description" required class="form-control">
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Harga:</label>
            <input type="number" name="price" required class="form-control">
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">Kategori:</label>
            <input type="text" name="category" required class="form-control">
        </div>
        <div class="mb-3">
            <label for="availability" class="form-label">Ketersediaan:</label>
            <select name="availability" required class="form-select">
                <option value="Tersedia">Tersedia</option>
                <option value="Tidak Tersedia">Tidak Tersedia</option>
            </select>
        </div>
        <button type="submit" name="add_product" class="btn btn-success">Tambah Produk</button>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
