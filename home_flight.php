<div class="container mt-5 pt-4 pb-5">
    <h2 class="section-title mb-4 anim-up" style="font-size: 28px;">🔥 Ưu Đãi Vé Máy Bay</h2>
    <div class="row g-4 mb-5">
        <div class="col-md-6 anim-up d1">
            <div class="card bg-dark text-white border-0 rounded-4 overflow-hidden shadow-sm h-100">
                <img src="https://images.unsplash.com/photo-1436491865332-7a61a109cc05?q=80&w=800" class="card-img" alt="Flight" style="opacity: 0.5; height: 220px; object-fit: cover;">
                <div class="card-img-overlay d-flex flex-column justify-content-center p-4">
                    <span class="badge bg-danger mb-2 align-self-start">Khuyến mãi mới</span>
                    <h3 class="fw-bold mb-2">Bay Đêm Giá Rẻ</h3>
                    <p class="mb-3">Giảm ngay 20% cho các chặng bay khởi hành sau 22:00. Số lượng có hạn!</p>
                    <button class="btn btn-light fw-bold rounded-pill text-primary" style="width: 150px;" onclick="document.getElementById('flight_depart').focus();">Săn vé ngay</button>
                </div>
            </div>
        </div>
        <div class="col-md-6 anim-up d2">
            <div class="card bg-primary text-white border-0 rounded-4 overflow-hidden shadow-sm h-100" style="background: linear-gradient(135deg, #0284C7, #00558F) !important;">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <h3 class="fw-bold mb-2"><i class="bi bi-airplane-engines me-2"></i>Vé Thương Gia Ưu Đãi</h3>
                    <p class="mb-4">Trải nghiệm dịch vụ đẳng cấp trên mây. Nâng hạng vé miễn phí cho hội viên thân thiết.</p>
                    <div class="d-flex gap-2">
                        <span class="badge bg-white text-primary px-3 py-2 rounded-pill shadow-sm">Vietnam Airlines</span>
                        <span class="badge bg-white text-primary px-3 py-2 rounded-pill shadow-sm">Bamboo Airways</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3 class="fw-bold mt-5 mb-4 text-center anim-up">✈️ Chặng Bay Phổ Biến</h3>
    <div class="row g-3 justify-content-center">
        <?php 
        $routes = [
            ['Hà Nội', 'TP. Hồ Chí Minh', '799.000đ', 'HAN', 'SGN'], 
            ['TP. Hồ Chí Minh', 'Đà Nẵng', '599.000đ', 'SGN', 'DAD'],
            ['Hà Nội', 'Phú Quốc', '1.200.000đ', 'HAN', 'PQC'], 
            ['Đà Nẵng', 'Hà Nội', '599.000đ', 'DAD', 'HAN']
        ];
        $delay = 1;
        foreach($routes as $r): ?>
        <div class="col-lg-3 col-md-6 anim-up d<?php echo $delay++; ?>">
            <div class="card border border-primary border-opacity-25 rounded-4 shadow-sm h-100 text-center p-3 card-route" onclick="quickSearchFlight('<?= $r[3] ?>', '<?= $r[4] ?>')" style="cursor: pointer; transition: all 0.3s;">
                <div class="d-flex justify-content-center align-items-center gap-3 mb-2">
                    <span class="fw-bold text-dark"><?php echo $r[0]; ?></span>
                    <i class="bi bi-arrow-right text-primary"></i>
                    <span class="fw-bold text-dark"><?php echo $r[1]; ?></span>
                </div>
                <div class="text-muted small mb-1">Giá chỉ từ</div>
                <div class="text-danger fw-bold fs-5 mb-2"><?php echo $r[2]; ?></div>
                <button class="btn btn-sm btn-outline-primary rounded-pill w-100 fw-semibold">Chọn chặng này</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>