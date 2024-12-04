<?php
function getUsers() {
    $file = 'users.json'; 
    if (file_exists($file)) {
        $data = file_get_contents($file); 
        return json_decode($data, true); 
    } else {
        return [];
    }
}

// API untuk mengambil data pengguna
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(getUsers());
}
?>