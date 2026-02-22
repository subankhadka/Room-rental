<?php
require 'config/config.php';
$data = [];
$errMsg = "";

if (isset($_POST['search'])) {
    $keywords = trim($_POST['keywords']);
    $location = trim($_POST['location']);

    // Helper function to prepare strings for SQL IN clause safely
    function prepareInClause($input) {
        if (empty($input)) return "('')";
        $items = array_map('trim', explode(',', $input));
        return "'" . implode("','", array_map('addslashes', $items)) . "'";
    }

    $concats = prepareInClause($keywords);
    $loc = prepareInClause($location);

    try {
        // Query Apartment Table
        $query1 = "SELECT *, 'apartment' as type FROM room_rental_registrations_apartment 
                   WHERE country IN ($concats, $loc) OR state IN ($concats, $loc) 
                   OR city IN ($concats, $loc) OR address IN ($concats, $loc) 
                   OR rooms IN ($concats) OR landmark IN ($concats, $loc) 
                   OR rent IN ($concats) OR deposit IN ($concats)";
        $stmt1 = $connect->prepare($query1);
        $stmt1->execute();
        $data1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);

        // Query Individual Room Table
        $query2 = "SELECT *, 'individual' as type FROM room_rental_registrations 
                   WHERE country IN ($concats, $loc) OR state IN ($concats, $loc) 
                   OR city IN ($concats, $loc) OR rooms IN ($concats) 
                   OR address IN ($concats, $loc) OR landmark IN ($concats) 
                   OR rent IN ($concats) OR deposit IN ($concats)";
        $stmt2 = $connect->prepare($query2);
        $stmt2->execute();
        $data2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        $data = array_merge($data1, $data2);
    } catch (PDOException $e) {
        $errMsg = "Search Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Room Rental App</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { --primary-color: #ff5a5f; }
        body { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8f9fa; }
        
        .masthead {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/img/header-bg.jpg');
            background-size: cover;
            padding: 120px 0;
            color: white;
            text-align: center;
        }

        .search-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: -50px;
        }

        .result-card {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            background: #fff;
            overflow: hidden;
        }

        .result-card:hover { transform: translateY(-5px); }
        
        .badge-status { position: absolute; top: 15px; right: 15px; }
        
        .owner-img {
            width: 100%;
            max-width: 120px;
            border-radius: 8px;
            object-fit: cover;
        }

        .section-heading { font-weight: 700; margin-bottom: 40px; }
        
        .navbar { background-color: #343a40 !important; }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">ROOM RENTAL</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link text-uppercase" href="#search">Search</a></li>
                    <?php if(empty($_SESSION['username'])): ?>
                        <li class="nav-item"><a class="nav-link text-uppercase" href="./auth/login.php">Login</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link text-uppercase" href="./auth/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link btn btn-danger btn-sm text-white px-3 ms-lg-2" href="./auth/register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <header class="masthead">
        <div class="container">
            <h1 class="display-4 fw-bold">Find Your Next Home</h1>
            <p class="lead">Simple, fast, and reliable room rentals.</p>
        </div>
    </header>

    <!-- Search Section -->
    <section id="search" class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="search-card">
                    <form action="" method="POST">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">Keywords</label>
                                <input class="form-control form-control-lg" name="keywords" type="text" placeholder="e.g. 1BHK, Studio, Wifi" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Location</label>
                                <input class="form-control form-control-lg" name="location" type="text" placeholder="City or Landmark" required>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-danger btn-lg w-100 py-2" name="search" type="submit">
                                    <i class="fa fa-search me-2"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if($errMsg): ?>
            <div class="alert alert-danger mt-4 text-center"><?= $errMsg ?></div>
        <?php endif; ?>

        <!-- Results -->
        <div class="mt-5">
            <?php if(count($data) > 0): ?>
                <h3 class="section-heading text-center">Available Properties (<?= count($data) ?>)</h3>
                <div class="row">
                    <?php foreach ($data as $row): ?>
                        <div class="col-12">
                            <div class="card result-card p-3 p-md-4">
                                <?php if($row['vacant'] == 1): ?>
                                    <span class="badge bg-success badge-status">Available</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary badge-status">Occupied</span>
                                <?php endif; ?>

                                <div class="row align-items-center">
                                    <!-- Section 1: Image & Owner -->
                                    <div class="col-12 col-md-3 text-center border-end-md">
                                        <?php 
                                            $img = (!empty($row['image']) && $row['image'] != 'uploads/') ? 'app/'.$row['image'] : 'assets/img/no-image.jpg';
                                        ?>
                                        <img src="<?= $img ?>" class="owner-img mb-3 shadow-sm" alt="Property">
                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($row['fullname']) ?></h6>
                                        <p class="text-muted small mb-0"><i class="fa fa-phone"></i> <?= $row['mobile'] ?></p>
                                    </div>

                                    <!-- Section 2: Property Details -->
                                    <div class="col-12 col-md-5 py-3 py-md-0 px-md-4">
                                        <h5 class="text-primary fw-bold">
                                            <?= isset($row['apartment_name']) ? htmlspecialchars($row['apartment_name']) : 'Private Residence' ?>
                                        </h5>
                                        <p class="mb-1 text-muted">
                                            <i class="fa fa-map-marker-alt text-danger"></i> 
                                            <?= $row['city'] ?>, <?= $row['state'] ?>
                                        </p>
                                        <div class="d-flex gap-3 my-2">
                                            <span class="small border rounded px-2 py-1"><i class="fa fa-door-open"></i> <?= $row['rooms'] ?> Rooms</span>
                                            <span class="small border rounded px-2 py-1"><i class="fa fa-info-circle"></i> <?= $row['accommodation'] ?></span>
                                        </div>
                                        <p class="small text-secondary mb-0"><strong>Address:</strong> <?= htmlspecialchars($row['address']) ?></p>
                                    </div>

                                    <!-- Section 3: Pricing & Action -->
                                    <div class="col-12 col-md-4 text-center text-md-end">
                                        <div class="bg-light p-3 rounded">
                                            <h4 class="fw-bold mb-0">$<?= number_format((float)$row['rent']) ?></h4>
                                            <p class="small text-muted">Monthly Rent</p>
                                            <hr class="my-2">
                                            <p class="small mb-2"><strong>Deposit:</strong> $<?= number_format((float)$row['deposit']) ?></p>
                                            <button class="btn btn-outline-dark btn-sm w-100">View Details</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif(isset($_POST['search'])): ?>
                <div class="text-center py-5">
                    <i class="fa fa-search-minus fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No properties found matching those criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container text-center">
            <p class="mb-3">&copy; 2023 Room Rental App. All Rights Reserved.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
