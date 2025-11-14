<main class="main-content">
    <h2 class="mb-4">Admin Dashboard</h2>

    <div class="row">
        <div class="col-md-3">
            <div class="card text-bg-primary mb-3">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <p class="card-text">Tổng: <?= isset($userCount) ? $userCount : 0 ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Tours</h5>
                    <p class="card-text">Tổng: <?= isset($tourCount) ? $tourCount : 0 ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-warning mb-3">
                <div class="card-body">
                    <h5 class="card-title">Bookings</h5>
                    <p class="card-text">Tổng: <?= isset($bookingCount) ? $bookingCount : 0 ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Guides</h5>
                    <p class="card-text">Tổng: <?= isset($guideCount) ? $guideCount : 0 ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

