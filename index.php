<?php
// =============================================
// SECURITY ENHANCEMENTS - FORM VALIDATION
// =============================================

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Generate form token สำหรับตรวจสอบใน calculate.php
if (empty($_SESSION['form_token'])) {
    $_SESSION['form_token'] = bin2hex(random_bytes(32));
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
    <style>
        body { background-color: #f8f9fa; }

        :root {
            --bs-primary: #003366;
            --bs-primary-rgb: 0, 51, 102;
        }

        /* Navbar Styles */
        .custom-navbar {
            background: linear-gradient(135deg, #003366 0%, #00264d 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 0.8rem 0;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }
        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            transform: translateY(-2px);
        }

        /* ปุ่มหลักสีเข้ม Hover แล้วไม่หาย */
        .btn-primary {
            --bs-btn-bg: var(--bs-primary);
            --bs-btn-border-color: var(--bs-primary);
            --bs-btn-hover-bg: #00264d;
            --bs-btn-hover-border-color: #00264d;
            --bs-btn-active-bg: #001a33;
            --bs-btn-active-border-color: #001a33;
            --bs-btn-disabled-bg: var(--bs-primary);
            --bs-btn-disabled-border-color: var(--bs-primary);
            color: #fff;
        }

        /* เพิ่ม contrast เวลา hover */
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            filter: brightness(1.1);
        }

        #banker-form .table thead th {
            background-color: var(--bs-primary);
            color: white; 
        }

        .table-input {
            width: 80px;
            padding: 0.375rem 0.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .form-control.is-invalid {
            background-color: #fdeeee !important;
        }

        .accordion-button {
            font-weight: 600;
        }

        .accordion-body ul {
            padding-left: 1.2em;
            margin-bottom: 0;
        }

        .card-header {
            background-color: #e9ecef;
        }

        #n::placeholder,
        #m::placeholder {
            font-size: 0.9em;
            color: #adb5bd;
            opacity: 1;
        }

        /* About Modal Styles */
        .member-card {
            border-left: 4px solid #003366;
            padding: 15px;
            margin-bottom: 10px;
            background: #f8f9fa;
            border-radius: 0 8px 8px 0;
        }
        .section-badge {
            background: #003366;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg custom-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand text-white" href="/">
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

<div class="container my-5">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-body p-4 p-md-5">

            <h1 class="card-title text-center mb-5 fw-bold text-primary">จำลอง Banker's Algorithm (CS422)</h1>

            <?php if ($n == 0 || $m == 0): ?>

                <div class="mb-5">
                    <h2 class="h4 mb-3 text-primary"><i class="bi bi-info-circle-fill me-2"></i>เกร็ดความรู้ (Knowledge)</h2>
                    <div class="accordion" id="knowledgeAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>1. Deadlock (การติดตาย) คืออะไร?
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
                                    <i class="bi bi-bank me-2 text-success"></i>2. Banker's Algorithm คืออะไร?
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
                                    <i class="bi bi-calculator-fill me-2 text-info"></i>3. สูตรที่ใช้ (Safety Algorithm)
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

                <div class="card mb-5 border-primary shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h4 mb-0"><i class="bi bi-1-circle-fill me-2"></i>ขั้นตอนที่ 1: เริ่มต้นจำลอง</h2>
                    </div>
                    <div class="card-body bg-light p-4">
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
                                    <i class="bi bi-table me-1"></i> สร้างตาราง
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <h2 class="h4 mb-3 text-primary"><i class="bi bi-2-circle-fill me-2"></i>ขั้นตอนที่ 2: ป้อนข้อมูลระบบ <small class="text-muted fw-normal">(Processes n=<?php echo $n; ?>, Resources m=<?php echo $m; ?>)</small></h2>

                <!-- 🔧 แก้ไข Form Action เป็น /result -->
                <form action="/result" method="POST" id="banker-form">
                    <!-- 🔒 ADD SECURITY TOKEN -->
                    <input type="hidden" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">

                    <input type="hidden" name="n" value="<?php echo $n; ?>">
                    <input type="hidden" name="m" value="<?php echo $m; ?>">

                    <h3 class="h5 mt-4"><i class="bi bi-grid-3x3-gap-fill me-2 text-info"></i>ตาราง Allocation</h3>
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

                    <h3 class="h5 mt-4"><i class="bi bi-grid-3x3-gap-fill me-2 text-warning"></i>ตาราง Need</h3>
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
                                            <td><input type="number" class="form-control table-input mx-auto" name="need[<?php echo $i; ?>][<?php echo $j; ?>]" min="0" max="1000" required></td>
                                        <?php endfor; ?>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="h5 mt-4"><i class="bi bi-grid-1x2-fill me-2 text-success"></i>ทรัพยากรที่เหลืออยู่ (Available)</h3>
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
                                        <td><input type="number" class="form-control table-input mx-auto" name="avail[]" min="0" max="1000" required></td>
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

<!-- About Us Modal -->
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

        // จำกัดค่าในตาราง matrix
        var matrixInputs = document.querySelectorAll('#banker-form input[type="number"]');
        matrixInputs.forEach(function(input) {
            input.addEventListener('input', function() {
                var value = parseInt(this.value);
                if (value > 1000) this.value = 1000;
                if (value < 0) this.value = 0;
                if (isNaN(value)) this.value = 0;
            });
        });
    });
</script>

</body>
</html>