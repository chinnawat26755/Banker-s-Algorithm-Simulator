<?php
// เริ่ม Session แบบธรรมดา (ไม่มี Secure Flag)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$n = isset($_REQUEST['n']) ? intval($_REQUEST['n']) : 0;
$m = isset($_REQUEST['m']) ? intval($_REQUEST['m']) : 0;

// จำกัดค่า n, m ระหว่าง 1-10
if ($n < 1 || $n > 10) $n = 0;
if ($m < 1 || $m > 10) $m = 0;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banker's Algorithm Simulator (CS422)</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
        <style>
            :root {
                /* เขียวหยกเป็น primary ใหม่ */
                --bs-primary: #064e3b;
                --bs-primary-rgb: 6, 78, 59;
            }

            /* Global */
            body {
                margin: 0;
                font-family: "Bai Jamjuree", system-ui, -apple-system,
                    BlinkMacSystemFont, "Segoe UI", sans-serif;

                /* พื้นหลังเขียวหยกนุ่ม ๆ สไตล์ enterprise */
                background: radial-gradient(circle at top left,
                    #d1fae5 0%,   /* mint อ่อน */
                    #ecfdf5 40%,  /* เขียวขาว */
                    #e0f2f1 75%,  /* teal เทา */
                    #f9fafb 100%  /* เทาเกือบขาว */
                );
                background-attachment: fixed;

                color: #0f172a;
            }

            .container-main {
                max-width: 1120px;
            }

            /* Main card – glass + soft inner shadow */
            .card {
                border-radius: 24px;
                border: 1px solid rgba(255, 255, 255, 0.7);
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                box-shadow:
                    inset 0 1px 3px rgba(255, 255, 255, 0.45),
                    inset 0 -2px 6px rgba(0, 0, 0, 0.08),
                    0 24px 60px rgba(15, 23, 42, 0.12);
            }

            .card-body {
                padding: 2.5rem 2.75rem;
            }

            @media (max-width: 768px) {
                .card-body {
                    padding: 1.75rem;
                }
            }

            /* Navbar Styles */
            .custom-navbar {
                background: linear-gradient(135deg, #022c22 0%, #064e3b 40%, #059669 100%);
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.45);
                padding: 0.75rem 0;
            }

            .navbar-brand {
                font-weight: 700;
                font-size: 1.25rem;
                letter-spacing: 0.03em;
            }

            .nav-link {
                font-weight: 500;
                font-size: 0.95rem;
                opacity: 0.9;
                transition: all 0.3s ease;
            }

            .nav-link:hover {
                opacity: 1;
                transform: translateY(-1px);
            }

            /* Page Title */
            .card-title {
                font-size: clamp(1.8rem, 2.3vw, 2.3rem);
                letter-spacing: 0.02em;
                background: linear-gradient(120deg, #064e3b 0%, #059669 35%, #10b981 100%);
                -webkit-background-clip: text;
                color: transparent;
            }

            .card-title::after {
                content: "";
                display: block;
                width: 72px;
                height: 4px;
                border-radius: 999px;
                margin: 0.9rem auto 0;
                background: linear-gradient(90deg, #059669, #34d399);
            }

            /* Knowledge Accordion */
            #knowledgeAccordion .accordion-item {
                border: none;
                border-radius: 16px;
                margin-bottom: 0.75rem;
                overflow: hidden;
                box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
            }

            #knowledgeAccordion .accordion-button {
                background-color: #ffffff;
                font-weight: 600;
                padding: 0.9rem 1.25rem;
            }

            #knowledgeAccordion .accordion-button:not(.collapsed) {
                color: #064e3b;
                background: linear-gradient(90deg, #e0f2f1, #d1fae5);
                box-shadow: inset 0 -1px 0 rgba(148, 163, 184, 0.4);
            }

            #knowledgeAccordion .accordion-body {
                background-color: #ffffff;
                padding: 1rem 1.25rem 1.2rem;
                font-size: 0.95rem;
            }

            .accordion-body ul {
                padding-left: 1.2em;
                margin-bottom: 0;
            }

            .accordion-button {
                font-weight: 600;
            }

            /* Step card – glass + emerald tone */
            .step-card {
                border-radius: 20px;
                overflow: hidden;
                background: rgba(240, 253, 244, 0.92); /* โปร่งเขียวอ่อน */
                backdrop-filter: blur(14px);
                -webkit-backdrop-filter: blur(14px);
                box-shadow: 0 18px 40px rgba(15, 23, 42, 0.10);
            }

            /* Step card header – Emerald Gradient */
            .step-card .card-header {
                background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%);
                padding: 0.85rem 1.5rem;
                color: #ffffff;
                border-bottom: none;

                backdrop-filter: blur(8px);
                -webkit-backdrop-filter: blur(8px);
                box-shadow:
                    inset 0 1px 0 rgba(255,255,255,0.4),
                    inset 0 -2px 4px rgba(0,0,0,0.12);
            }


            .step-card .card-header h2 {
                margin: 0;
                font-size: 1.05rem;
                letter-spacing: 0.02em;
            }

            .step-card .card-body {
                background: transparent;
            }

            /* Icons – emerald gradient (ใช้กับ bi/ti ก็ได้) */
            .icon-emerald-gradient {
                background: linear-gradient(135deg, #065f46, #10b981);
                -webkit-background-clip: text;
                background-clip: text;
                color: transparent;
            }

            /* Buttons – Emerald + Soft Inner Shadow + Shine */
            .btn-primary {
                border-radius: 999px;
                padding-inline: 1.8rem;
                padding-block: .8rem;

                font-weight: 600;
                letter-spacing: 0.02em;
                color: #fff;
                border: none;
                position: relative;
                overflow: hidden;

                /* gradient เขียวหยก */
                background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%);

                /* inner shadow + outer shadow แบบ Apple-ish */
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.45),
                    0 10px 22px rgba(5, 150, 105, 0.32);

                transition:
                    transform 0.15s ease,
                    box-shadow 0.2s ease,
                    filter 0.15s ease;
            }

            /* แถบแสงวิ่งบนปุ่ม */
            .btn-primary::after {
                content: "";
                position: absolute;
                top: 0;
                left: -120%;
                width: 60%;
                height: 100%;
                background: rgba(255, 255, 255, 0.22);
                transform: skewX(-20deg);
                transition: left 0.55s ease;
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                filter: brightness(1.06) saturate(1.12);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.55),
                    0 14px 28px rgba(5, 150, 105, 0.42);
            }

            .btn-primary:hover::after {
                left: 140%;
            }

            .btn-primary:active {
                transform: translateY(0);
                box-shadow:
                    inset 0 1px 0 rgba(255, 255, 255, 0.45),
                    0 6px 16px rgba(5, 150, 105, 0.30);
            }

            /* Inputs */
            .form-control-lg {
                border-radius: 999px;
                border: 1px solid #a7f3d0;
                padding-inline: 1.25rem;
                background-color: #ffffff;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
                transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.1s ease;
            }

            .form-control-lg:focus {
                border-color: #059669;
                box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.25);
                transform: translateY(-1px);
            }

            .table-input {
                width: 80px;
                padding: 0.375rem 0.5rem;
                font-size: 0.9rem;
                text-align: center;
                border-radius: 10px;
                border: 1px solid #d4dde9;
            }

            .form-control.is-invalid {
                background-color: #fdeeee !important;
            }

            #n::placeholder,
            #m::placeholder {
                font-size: 0.9em;
                color: #9ca3af;
                opacity: 1;
            }

            /* Tables in banker form */
            #banker-form .table {
                margin-bottom: 0;
                font-size: 0.9rem;
            }

            #banker-form .table thead th {
                background: linear-gradient(90deg, #064e3b, #059669);
                color: #ffffff;
                border-bottom: none;
            }

            #banker-form .table tbody tr:nth-child(even) {
                background-color: #f0fdf4;
            }

            #banker-form .table tbody tr:hover {
                background-color: #dcfce7;
            }

            /* About Modal Styles */
            .member-card {
                border-left: 4px solid #064e3b;
                padding: 15px;
                margin-bottom: 10px;
                background: #f8f9fa;
                border-radius: 0 8px 8px 0;
            }

            .section-badge {
                background: #064e3b;
                color: white;
                padding: 4px 12px;
                border-radius: 20px;
                font-size: 0.8em;
                font-weight: bold;
            }
                    
            input:focus {
                outline: none !important;
                box-shadow: none !important;
            }

            /* สร้าง focus แบบเขียวหยก custom */
            .table-input:focus,
            .form-control-lg:focus {
                border-color: #059669 !important;
                box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.30) !important;
                background-color: #ffffff !important;
            }
        </style>       
</head>
<body>

<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand text-white" href="index.php">
            <i class="bi bi-cpu me-2"></i>Banker's Algorithm (CS422)
        </a>
        <button class="navbar-toggler border-light" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link text-white fw-bold" href="#" data-bs-toggle="modal" data-bs-target="#aboutModal">
                        <i class="bi bi-people-fill me-1"></i>About Us
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 container-main">
    <div class="card border-0">
        <div class="card-body">

            <h1 class="card-title text-center mb-5 fw-bold">จำลอง Banker's Algorithm (CS422)</h1>

            <?php if ($n == 0 || $m == 0): ?>

                <div class="mb-5">
                    <h2 class="h4 mb-3 text-primary"><i class="bi bi-info-circle-fill me-2"></i>เกร็ดความรู้ (Knowledge)</h2>
                    <div class="accordion" id="knowledgeAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    <i class="bi bi-exclamation-circle me-2 text-danger"></i>1. Deadlock (การติดตาย) คืออะไร?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#knowledgeAccordion">
                                <div class="accordion-body">
                                    <strong>Deadlock</strong> คือสถานการณ์ที่ Process ตั้งแต่ 2 ตัวขึ้นไป "รอคอย" ทรัพยากรที่อีกฝ่ายถืออยู่ ทำให้ไม่มีใครทำงานต่อได้เลย
                                    <br><br>
                                    <strong>เงื่อนไข 4 ข้อที่ทำให้เกิด Deadlock:</strong>
                                    <ul>
                                        <li><strong>Mutual Exclusion:</strong> ทรัพยากรใช้ได้ทีละ Process</li>
                                        <li><strong>Hold and Wait:</strong> Process ถือทรัพยากรแล้วรอตัวอื่นเพิ่ม</li>
                                        <li><strong>No Preemption:</strong> แย่งทรัพยากรคืนไม่ได้</li>
                                        <li><strong>Circular Wait:</strong> รอกันเป็นวงกลม</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                    <i class="bi bi-graph-up-arrow me-2 text-primary"></i>2. Banker's Algorithm คืออะไร?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#knowledgeAccordion">
                                <div class="accordion-body">
                                    ใช้เพื่อ <strong>Deadlock Avoidance</strong><br>
                                    ตรวจสอบก่อนให้ทรัพยากรว่าระบบยังอยู่ใน <strong>Safe State</strong> หรือไม่
                                    <ul>
                                        <li><strong>Safe State:</strong> มีลำดับที่ทำให้ทุก Process เสร็จได้</li>
                                        <li><strong>Unsafe State:</strong> อาจเกิด Deadlock ได้</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                    <i class="bi bi-shield me-2 text-success"></i>3. สูตรที่ใช้ (Safety Algorithm)
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#knowledgeAccordion">
                                <div class="accordion-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <i class="bi bi-1-circle-fill text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>ตั้งค่าเริ่มต้น</strong><br>
                                            <code>Work = Available</code>, <code>Finish = false</code><br>
                                            <small class="text-muted">กำหนดทรัพยากรทำงานและตั้งค่าสถานะ Process</small>
                                        </div>
                                    </div>
                                  
                                    <div class="d-flex align-items-start mb-3">
                                        <i class="bi bi-2-circle-fill text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>ค้นหา Process ที่ทำงานได้</strong><br>
                                            <code>Finish[i] == false</code> และ <code>Need[i] ≤ Work</code><br>
                                            <small class="text-muted">Process ต้องยังไม่เสร็จและต้องการทรัพยากรไม่เกินที่มี</small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-start mb-3">
                                        <i class="bi bi-3-circle-fill text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>ทำงานและคืนทรัพยากร</strong><br>
                                            <code>Work = Work + Allocation[i]</code>, <code>Finish[i] = true</code><br>
                                            <small class="text-muted">Process ทำงานเสร็จและคืนทรัพยากรทั้งหมด</small>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-4-circle-fill text-primary me-3 mt-1"></i>
                                        <div>
                                            <strong>สรุปผลลัพธ์</strong><br>
                                            ทุก Process เสร็จ → <span class="text-success fw-bold">Safe State</span><br>
                                            มี Process ค้าง → <span class="text-danger fw-bold">Unsafe State</span><br>
                                            <small class="text-muted">ตรวจสอบว่าทุก Process ทำงานเสร็จสมบูรณ์หรือไม่</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="mb-5">
                <div class="card mb-5 border-0 shadow-sm step-card">
                    <div class="card-header">
                        <h2 class="h4 mb-0"><i class="bi bi-1-circle-fill me-2"></i>ขั้นตอนที่ 1: เริ่มต้นจำลอง</h2>
                    </div>
                    <div class="card-body p-4">
                        <p class="card-text mb-4 text-muted">กำหนดจำนวน Process และ Resource (สูงสุดอย่างละ 10)</p>
                        <form action="" method="POST" class="row g-3">
                            <div class="col-md-6">
                                <label for="n" class="form-label fw-bold">จำนวน Processes (n):</label>
                                <input type="number" class="form-control form-control-lg" id="n" name="n" min="1" max="10" placeholder="1-10" required>
                            </div>
                            <div class="col-md-6">
                                <label for="m" class="form-label fw-bold">จำนวน Resources (m):</label>
                                <input type="number" class="form-control form-control-lg" id="m" name="m" min="1" max="10" placeholder="1-10" required>
                            </div>
                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="ti ti-grid-dots me-2"></i>สร้างตาราง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>
<h2 class="h4 mb-3 text-primary">
    <i class="bi bi-2-circle-fill me-2"></i>
    ขั้นตอนที่ 2: ป้อนข้อมูลระบบ
    <small class="text-muted fw-normal">(Processes n=<?php echo $n; ?>, Resources m=<?php echo $m; ?>)</small>
</h2>

<form action="calculate.php" method="POST" id="banker-form">

    <input type="hidden" name="n" value="<?php echo $n; ?>">
    <input type="hidden" name="m" value="<?php echo $m; ?>">

    <h3 class="h5 mt-4">
        <i class="ti ti-table-filled me-2" 
        style="background: linear-gradient(120deg,#0d6a44,#23a56f);
                -webkit-background-clip:text; color:transparent;"></i>
        ตาราง Allocation
    </h3>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-bordered text-center align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 100px;">Process</th>
                    <?php for ($j = 0; $j < $m; $j++): ?>
                        <th>R<?php echo $j + 1; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < $n; $i++): ?>
                    <tr>
                        <td>P<?php echo $i + 1; ?></td>
                        <?php for ($j = 0; $j < $m; $j++): ?>
                            <td><input type="number" class="form-control table-input mx-auto" name="alloc[<?php echo $i; ?>][<?php echo $j; ?>]" min="0" max="1000" required></td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <h3 class="h5 mt-4">
        <i class="ti ti-math-function me-2"
        style="background: linear-gradient(120deg,#0d6a44,#23a56f);
                -webkit-background-clip:text; color:transparent;"></i>
        ตาราง Max
    </h3>

    <div class="table-responsive shadow-sm rounded-3">
        <table class="table table-bordered text-center align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 100px;">Process</th>
                    <?php for ($j = 0; $j < $m; $j++): ?>
                        <th>R<?php echo $j + 1; ?></th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 0; $i < $n; $i++): ?>
                    <tr>
                        <td>P<?php echo $i + 1; ?></td>
                        <?php for ($j = 0; $j < $m; $j++): ?>
                            <td><input type="number" class="form-control table-input mx-auto" name="max[<?php echo $i; ?>][<?php echo $j; ?>]" min="0" max="1000" required></td>
                        <?php endfor; ?>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

 <h3 class="h5 mt-4">
    <i class="ti ti-shield-lock me-2"
    style="background: linear-gradient(120deg,#0d6a44,#23a56f);
            -webkit-background-clip:text; color:transparent;"></i>
    ตาราง Need (Max - Allocation)
</h3>

<div class="table-responsive shadow-sm rounded-3">
    <table class="table table-bordered text-center align-middle mb-0">
        <thead>
            <tr>
                <th style="width: 100px;">Process</th>
                <?php for ($j = 0; $j < $m; $j++): ?>
                    <th>R<?php echo $j + 1; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 0; $i < $n; $i++): ?>
                <tr>
                    <td>P<?php echo $i + 1; ?></td>
                    <?php for ($j = 0; $j < $m; $j++): ?>
                        <td>
                            <input
                                type="number"
                                class="form-control table-input mx-auto"
                                name="need[<?php echo $i; ?>][<?php echo $j; ?>]"
                                min="0"
                                max="1000"
                                value="<?php echo isset($max[$i][$j]) && isset($alloc[$i][$j]) ? $max[$i][$j] - $alloc[$i][$j] : ''; ?>"
                                required
                            >
                        </td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>
</div>

<h3 class="h5 mt-4">
    <i class="ti ti-stack-2 me-2"
    style="background: linear-gradient(120deg,#0d6a44,#23a56f);
            -webkit-background-clip:text; color:transparent;"></i>
    ทรัพยากรที่เหลืออยู่ (Available)
</h3>

<div class="table-responsive shadow-sm rounded-3">
    <table class="table table-bordered text-center align-middle mb-0">
        <thead>
            <tr>
                <?php for ($j = 0; $j < $m; $j++): ?>
                    <th>R<?php echo $j + 1; ?></th>
                <?php endfor; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php for ($j = 0; $j < $m; $j++): ?>
                    <td>
                        <input
                            type="number"
                            class="form-control table-input mx-auto"
                            name="avail[]"
                            min="0"
                            max="1000"
                            required
                        >
                    </td>
                <?php endfor; ?>
            </tr>
        </tbody>
    </table>
</div>


    <button type="submit" class="btn btn-primary btn-lg w-100 mt-5">
        <i class="bi bi-play-circle-fill me-2"></i> Run Safety Algorithm
    </button>
</form>

            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="aboutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-people-fill me-2"></i>About Us
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-4">
                    <i class="bi bi-cpu-fill text-primary" style="font-size: 3rem;"></i>
                    <h4 class="text-primary mt-3">Banker's Algorithm Simulator</h4>
                    <p class="text-muted">โครงการวิชา CS422 - Operating Systems</p>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="bi bi-gear me-2"></i>เกี่ยวกับโปรเจค</h6>
                        <p>เครื่องมือจำลอง Banker's Algorithm สำหรับการศึกษาเรื่อง Deadlock Avoidance ในระบบปฏิบัติการ</p>
                        
                        <h6 class="text-primary mb-3"><i class="bi bi-lightbulb me-2"></i>วัตถุประสงค์</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">• เข้าใจการทำงานของ Banker's Algorithm</li>
                            <li class="mb-2">• เรียนรู้การตรวจสอบ Safe State</li>
                            <li class="mb-2">• ป้องกันการเกิด Deadlock</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3"><i class="bi bi-code-slash me-2"></i>เทคโนโลยีที่ใช้</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">• PHP สำหรับการประมวลผล</li>
                            <li class="mb-2">• Bootstrap 5 สำหรับ UI</li>
                            <li class="mb-2">• JavaScript สำหรับการตรวจสอบ</li>
                        </ul>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>รายชื่อสมาชิก</h6>
                
                <div class="member-card">
                    <span class="section-badge">Section 327D</span>
                    <div class="mt-2">
                        <strong>นาย ชินวัตร อ่วมแก้ว</strong>
                    </div>
                </div>

                <div class="member-card">
                    <span class="section-badge">Section 327E</span>
                    <div class="mt-2">
                        <strong>น.ส. ศรีรัตน์ อินทลัย</strong><br>
                        <strong>น.ส. นิชาภา ศรีแจ่มใส</strong><br>
                        <strong>น.ส. บุญพิทักษ์ โรจนประภาวสุ</strong><br>
                        <strong>นาย พิพัฒน์ ลิขิตวานิช</strong>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var inputN = document.getElementById("n");
        var inputM = document.getElementById("m");

        function limitValue(inputElement) {
            if (inputElement) {
                inputElement.addEventListener('input', function() {
                    var value = parseInt(this.value);
                    if (value > 10) this.value = 10;
                    if (value < 1) this.value = 1;
                });
            }
        }
        limitValue(inputN);
        limitValue(inputM);

        // จำกัดค่าในตาราง matrix (ตรวจว่ามีฟอร์ม banker-form ก่อน)
        var bankerForm = document.getElementById('banker-form');
        if (bankerForm) {
            var matrixInputs = bankerForm.querySelectorAll('input[type="number"]');
            matrixInputs.forEach(function(input) {
                input.addEventListener('input', function() {
                    var value = parseInt(this.value);
                    if (value > 1000) this.value = 1000;
                    if (value < 0) this.value = 0;
                    if (isNaN(value)) this.value = 0;
                });
            });
        }
    });
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {

    function updateNeed() {
        // ดึงค่าตารางทั้งสาม
        const alloc = document.querySelectorAll('input[name^="alloc"]');
        const max   = document.querySelectorAll('input[name^="max"]');
        const need  = document.querySelectorAll('input[name^="need"]');

        need.forEach(nInput => {
            const i = nInput.name.match(/need\[(\d+)\]\[(\d+)\]/)[1];
            const j = nInput.name.match(/need\[(\d+)\]\[(\d+)\]/)[2];

            const allocInput = document.querySelector(`input[name="alloc[${i}][${j}]"]`);
            const maxInput   = document.querySelector(`input[name="max[${i}][${j}]"]`);

            let allocVal = parseInt(allocInput.value) || 0;
            let maxVal   = parseInt(maxInput.value) || 0;

            let needVal = maxVal - allocVal;
            nInput.value = needVal < 0 ? 0 : needVal; // ป้องกันติดลบ
        });
    }

    // ทุกครั้งที่พิมพ์ใน Max หรือ Allocation ให้คำนวณ Need ใหม่
    document.querySelectorAll('input[name^="alloc"], input[name^="max"]').forEach(input => {
        input.addEventListener("input", updateNeed);
    });

});
</script>

</body>
</html>