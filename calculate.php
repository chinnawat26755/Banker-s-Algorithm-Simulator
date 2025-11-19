<?php
// =============================================
// SECURITY ENHANCEMENTS - ACCESS CONTROL
// =============================================

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => true,
        'cookie_samesite' => 'Strict'
    ]);
}

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// 1. ตรวจสอบ HTTP Method (ต้องเป็น POST เท่านั้น)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('
        <div class="container mt-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body text-center p-5">
                    <div class="alert alert-danger">
                        <h4><i class="bi bi-exclamation-triangle-fill me-2"></i>วิธีการไม่ถูกต้อง</h4>
                        <p>ต้องส่งข้อมูลผ่านแบบฟอร์มเท่านั้น</p>
                        <a href="/" class="btn btn-primary">กลับหน้าหลัก</a>
                    </div>
                </div>
            </div>
        </div>
    ');
}

// 2. ตรวจสอบ Form Token
if (!isset($_POST['form_token']) || !isset($_SESSION['form_token']) || 
    $_POST['form_token'] !== $_SESSION['form_token']) {
    http_response_code(403);
    die('
        <div class="container mt-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body text-center p-5">
                    <div class="alert alert-danger">
                        <h4><i class="bi bi-shield-exclamation me-2"></i>การเข้าถึงไม่ถูกต้อง</h4>
                        <p>กรุณากรอกข้อมูลผ่านหน้าแรกเท่านั้น</p>
                        <a href="/" class="btn btn-primary mt-3">กลับหน้าหลัก</a>
                    </div>
                </div>
            </div>
        </div>
    ');
}

// 3. ลบ token หลังใช้งาน (ป้องกัน reuse)
unset($_SESSION['form_token']);

// 4. Rate Limiting
$rate_limit_key = 'calc_rate_limit_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (!isset($_SESSION[$rate_limit_key])) {
    $_SESSION[$rate_limit_key] = ['count' => 0, 'timestamp' => time()];
}

$current_time = time();
$time_window = 60; // 1 minute
if ($current_time - $_SESSION[$rate_limit_key]['timestamp'] > $time_window) {
    $_SESSION[$rate_limit_key] = ['count' => 1, 'timestamp' => $current_time];
} else {
    $_SESSION[$rate_limit_key]['count']++;
    if ($_SESSION[$rate_limit_key]['count'] > 15) { // Max 15 calculations per minute
        http_response_code(429);
        die('
            <div class="container mt-5">
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-body text-center p-5">
                        <div class="alert alert-warning">
                            <h4><i class="bi bi-clock-history me-2"></i>คำขอมากเกินไป</h4>
                            <p>กรุณารอสักครู่ก่อนทำการคำนวณอีกครั้ง</p>
                            <a href="/" class="btn btn-primary mt-3">กลับหน้าหลัก</a>
                        </div>
                    </div>
                </div>
            </div>
        ');
    }
}

// --- (ส่วนรับค่า) ---
function validate_integer($value, $min = 0, $max = 1000) {
    $value = intval($value);
    return ($value >= $min && $value <= $max) ? $value : $min;
}

$numProcesses = isset($_POST['n']) ? validate_integer($_POST['n'], 1, 10) : 0;
$numResources = isset($_POST['m']) ? validate_integer($_POST['m'], 1, 10) : 0;

// ตรวจสอบว่ามีค่า n และ m หรือไม่
if ($numProcesses <= 0 || $numResources <= 0) {
    http_response_code(400);
    die('
        <div class="container mt-5">
            <div class="card shadow-lg border-0 rounded-3">
                <div class="card-body text-center p-5">
                    <div class="alert alert-danger">
                        <h4><i class="bi bi-x-circle-fill me-2"></i>ข้อมูลไม่ครบถ้วน</h4>
                        <p>กรุณากรอกจำนวน Process และ Resource ให้ถูกต้อง</p>
                        <a href="/" class="btn btn-primary mt-3">กลับหน้าหลัก</a>
                    </div>
                </div>
            </div>
        </div>
    ');
}

$NeedMatrix_raw = isset($_POST['need']) ? $_POST['need'] : array();
$AllocationMatrix_raw = isset($_POST['alloc']) ? $_POST['alloc'] : array();
$AvailableVector_raw = isset($_POST['avail']) ? $_POST['avail'] : array();

// --- (ฟังก์ชัน Helper) ---
function toIntMatrix($arr, $numProcesses, $numResources) {
    $result = array();
    for ($i = 0; $i < $numProcesses; $i++) {
        $result[$i] = array();
        for ($j = 0; $j < $numResources; $j++) {
            $value = isset($arr[$i][$j]) ? validate_integer($arr[$i][$j], 0, 1000) : 0;
            $result[$i][$j] = $value;
        }
    }
    return $result;
}

function toIntArray($arr, $numResources) {
    $result = array();
    for ($j = 0; $j < $numResources; $j++) {
        $value = isset($arr[$j]) ? validate_integer($arr[$j], 0, 1000) : 0;
        $result[$j] = $value;
    }
    return $result;
}

function formatArray($arr) {
    if (is_array($arr)) {
        return "[" . implode(", ", array_map('htmlspecialchars', $arr)) . "]";
    }
    return "[]";
}

function showMatrixTH($title, $matrix, $numResources) {
    $icon = '';
    if (strpos($title, 'Allocation') !== false) $icon = "<i class='bi bi-grid-3x3-gap-fill me-2 text-info'></i>";
    elseif (strpos($title, 'Need') !== false) $icon = "<i class='bi bi-grid-3x3-gap-fill me-2 text-warning'></i>";
    
    echo "<h3 class='h5 mt-4 mb-3'>$icon" . htmlspecialchars($title) . "</h3>";
    echo "<div class='table-responsive shadow-sm rounded mb-4'>";
    echo "<table class='table table-bordered text-center align-middle mb-0'>";
    echo "<thead class='table-light'><tr><th style='width:120px;'>Process</th>";
    for ($j = 0; $j < $numResources; $j++) {
        echo "<th>R" . ($j + 1) . "</th>";
    }
    echo "</tr></thead><tbody>";
    
    for ($i = 0; $i < count($matrix); $i++) {
        echo "<tr><td>P" . ($i + 1) . "</td>";
        if (isset($matrix[$i]) && is_array($matrix[$i])) {
            foreach ($matrix[$i] as $val) {
                $val = htmlspecialchars($val);
                $valClass = (is_numeric($val) && $val < 0) ? 'text-danger fw-bold' : '';
                echo "<td class='$valClass'>$val</td>";
            }
        } else {
            for ($k = 0; $k < $numResources; $k++) echo "<td>-</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table></div>";
}

// --- แปลงค่า ---
$NeedMatrix = toIntMatrix($NeedMatrix_raw, $numProcesses, $numResources);
$AllocationMatrix = toIntMatrix($AllocationMatrix_raw, $numProcesses, $numResources);
$AvailableVector = toIntArray($AvailableVector_raw, $numResources);

// --- HTML Header ---
echo "<!DOCTYPE html><html lang='th'><head><meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>ผลการคำนวณ Banker's Algorithm</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css'>

<!-- CSP Header -->
<meta http-equiv='Content-Security-Policy' content=\"
    default-src 'self';
    script-src 'self' https://cdn.jsdelivr.net 'unsafe-inline';
    style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline';
    img-src 'self' data: https:;
    font-src 'self' https://cdn.jsdelivr.net;
    connect-src 'self';
    frame-ancestors 'none';
\">

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

pre { background: #212529; color: #f8f9fa; padding: 1.5rem; border-radius: 0.5rem; font-family: 'Courier New', monospace; font-size:1rem; line-height:1.8; overflow-x:auto; border:1px solid #495057;}
.table-finish .finish-true { background-color: rgba(25,135,84,0.1); color:#146c43; font-weight:bold;}
.table-finish .finish-false { background-color: rgba(220,53,69,0.1); color:#b02a37; font-weight:bold;}
.comparison { color:#20c997; font-weight:bold; margin:0 0.5em;}
.alert-heading { margin-bottom:0.5rem;}
.alert hr { margin-top:0.8rem; margin-bottom:0.8rem;}
.alert strong { font-size:1.1em; }

/* ปุ่มสีน้ำเงินเหมือนหน้า index */
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
.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active {
    filter: brightness(1.1);
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
</head><body>";

// --- Navigation Bar ---
echo "<!-- Navigation Bar -->
<nav class='navbar navbar-expand-lg custom-navbar sticky-top'>
    <div class='container'>
        <a class='navbar-brand text-white' href='/'>
            <i class='bi bi-cpu me-2'></i>Banker's Algorithm (CS422)
        </a>
        <button class='navbar-toggler border-light' type='button' data-bs-toggle='collapse' data-bs-target='#navbarNav'>
            <span class='navbar-toggler-icon' style='filter: invert(1);'></span>
        </button>
        <div class='collapse navbar-collapse' id='navbarNav'>
            <ul class='navbar-nav ms-auto'>
                <li class='nav-item'>
                    <a class='nav-link text-white fw-bold' href='#' data-bs-toggle='modal' data-bs-target='#aboutModal'>
                        <i class='bi bi-people-fill me-1'></i>About Us
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>";

// --- Layout Container ---
echo "<div class='container my-5'><div class='card shadow-lg border-0 rounded-3'><div class='card-body p-4 p-md-5'>";

// --- หัวเรื่อง ---
echo "<h1 class='card-title text-center mb-5 fw-bold text-primary'>
<i class='bi bi-cpu me-2'></i>ผลการคำนวณ Banker's Algorithm
</h1>";

// --- แสดงตาราง Input ---
showMatrixTH("ตาราง Allocation (ที่จัดสรรแล้ว)", $AllocationMatrix, $numResources);
showMatrixTH("ตาราง Need (ความต้องการที่เหลืออยู่)", $NeedMatrix, $numResources);

echo "<h3 class='h5 mt-4 mb-3'><i class='bi bi-box-fill me-2 text-success'></i>ทรัพยากรที่เหลืออยู่ (Available)</h3>";
echo "<div class='table-responsive shadow-sm rounded mb-4'><table class='table table-bordered text-center align-middle mb-0'>";
echo "<thead class='table-light'><tr>";
for($j=0;$j<$numResources;$j++) echo "<th>R".($j+1)."</th>";
echo "</tr></thead><tbody><tr>";
for($j=0;$j<$numResources;$j++){
    $availValue = isset($AvailableVector[$j]) ? htmlspecialchars($AvailableVector[$j]) : '-';
    echo "<td>$availValue</td>";
}
echo "</tr></tbody></table></div>";

// --- Safety Algorithm Step-by-Step ---
echo "<h2 class='h4 mt-5 mb-3'><i class='bi bi-play-btn-fill me-2'></i>บันทึกการทำงาน (Step-by-Step)</h2>";
echo "<pre>";

$WorkVector = $AvailableVector;
$Finish = array_fill(0,$numProcesses,false);
$SafeSequence = array();
$processesFinishedCount = 0;
$pass = 1;
$maxIterations = $numProcesses * 3; // ป้องกัน infinite loop

echo "<strong class='text-white'>เริ่มต้น:</strong> Work = Available = " . formatArray($WorkVector) . "\n\n";

while($processesFinishedCount < $numProcesses && $pass <= $maxIterations){
    echo "<strong class='text-warning'>--- รอบที่ $pass ---</strong>\n";
    $foundSafeProcessThisRound = false;

    for($i=0;$i<$numProcesses;$i++){
        if(!$Finish[$i]){
            $currentNeed = isset($NeedMatrix[$i]) ? $NeedMatrix[$i] : array();
            echo "  ตรวจสอบ P".($i+1).": Need ".formatArray($currentNeed)." <= Work ".formatArray($WorkVector)."?\n";
            $canExecute = true;
            for($j=0;$j<$numResources;$j++){
                $needVal = isset($NeedMatrix[$i][$j]) ? $NeedMatrix[$i][$j] : 0;
                $workVal = isset($WorkVector[$j]) ? $WorkVector[$j] : 0;
                if($needVal > $workVal) { 
                    $canExecute = false; 
                    break; 
                }
            }
            if($canExecute){
                echo "    ผลลัพธ์: จริง ✅ (P".($i+1)." ทำงานได้)\n";
                $currentAlloc = isset($AllocationMatrix[$i]) ? $AllocationMatrix[$i] : array();
                $oldWork = $WorkVector;
                for($j=0;$j<$numResources;$j++){
                    $WorkVector[$j] = (isset($WorkVector[$j]) ? $WorkVector[$j] : 0) + (isset($AllocationMatrix[$i][$j]) ? $AllocationMatrix[$i][$j] : 0);
                }
                echo "    คำนวณ: Work = Work + Allocation[P".($i+1)."]\n";
                echo "          ".formatArray($oldWork)." + ".formatArray($currentAlloc)." = <strong class='text-info'>".formatArray($WorkVector)."</strong>\n\n";
                $Finish[$i] = true;
                $SafeSequence[] = "P".($i+1);
                $foundSafeProcessThisRound = true;
                $processesFinishedCount++;
            } else {
                echo "    ผลลัพธ์: เท็จ ❌ (P".($i+1)." ต้องรอ)\n\n";
            }
        }
    }

    if(!$foundSafeProcessThisRound){
        echo "<strong class='text-warning'>--- สิ้นสุดรอบ $pass ---</strong>\n";
        echo "<span class='text-danger'>ไม่สามารถหา Process ที่ทำงานต่อได้ในรอบนี้</span>\n";
        break;
    }
    echo "<strong class='text-warning'>--- สิ้นสุดรอบ $pass ---</strong>\n\n";
    $pass++;
}

// ตรวจสอบ infinite loop
if ($pass > $maxIterations) {
    echo "<span class='text-danger'>⚠️  การคำนวณถูกหยุดเนื่องจากใช้รอบมากเกินไป</span>\n";
}
echo "</pre>";

// --- ตาราง Finish ---
echo "<h2 class='h4 mt-5 mb-3'><i class='bi bi-check-circle-fill me-2'></i>ตารางสถานะ Finish (หลังประมวลผล)</h2>";
echo "<div class='table-responsive shadow-sm rounded mb-4'>";
echo "<table class='table table-bordered text-center align-middle mb-0 table-finish'>";
echo "<thead class='table-light'><tr><th style='width:50%'>Process</th><th style='width:50%'>Status (Finish)</th></tr></thead><tbody>";
for($i=0;$i<$numProcesses;$i++){
    $isFinished = isset($Finish[$i]) ? $Finish[$i] : false;
    echo "<tr><td>P".($i+1)."</td>";
    echo $isFinished ? "<td class='finish-true'>✅ True</td>" : "<td class='finish-false'>❌ False</td>";
    echo "</tr>";
}
echo "</tbody></table></div>";

// --- สรุปผล ---
echo "<h2 class='h4 mt-5 mb-3'><i class='bi bi-flag-fill me-2'></i>ผลลัพธ์สุดท้าย</h2>";
if($processesFinishedCount == $numProcesses){
    echo "<div class='alert alert-success fs-5' role='alert'>";
    echo "<h4 class='alert-heading'><i class='bi bi-shield-check-fill me-2'></i>ระบบอยู่ในสถานะปลอดภัย (Safe State)</h4><hr>";
    echo "ลำดับที่ปลอดภัย (Safe Sequence): <br><strong>".implode(" → ",$SafeSequence)."</strong>";
    echo "</div>";
} else {
    echo "<div class='alert alert-danger fs-5' role='alert'>";
    echo "<h4 class='alert-heading'><i class='bi bi-sign-stop-fill me-2'></i>ระบบอยู่ในสถานะไม่ปลอดภัย (Unsafe State)</h4><hr>";
    echo "ไม่สามารถหาลำดับการทำงานที่ปลอดภัยได้ อาจเกิด Deadlock<br>";
    if(count($SafeSequence) > 0){
        echo "Process ที่ทำงานได้ก่อนติด: <strong>".implode(" → ",$SafeSequence)."</strong>";
    }
    echo "</div>";
}

echo "<hr class='my-5'>";
// 🔧 แก้ไขลิงก์กลับเป็น /
echo "<a href='/' class='btn btn-primary btn-lg w-100'>
<i class='bi bi-arrow-left-circle-fill me-2'></i>ย้อนกลับไปกรอกข้อมูลใหม่
</a>";

echo "</div></div></div>";

// --- About Us Modal ---
echo "<!-- About Us Modal -->
<div class='modal fade' id='aboutModal' tabindex='-1'>
    <div class='modal-dialog modal-lg'>
        <div class='modal-content'>
            <div class='modal-header bg-primary text-white'>
                <h5 class='modal-title'>
                    <i class='bi bi-people-fill me-2'></i>About Us
                </h5>
                <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
            </div>
            <div class='modal-body'>
                <div class='text-center mb-4'>
                    <i class='bi bi-cpu-fill text-primary' style='font-size: 3rem;'></i>
                    <h4 class='text-primary mt-3'>Banker's Algorithm Simulator</h4>
                    <p class='text-muted'>โครงการวิชา CS422 - Operating Systems</p>
                </div>
                
                <div class='row'>
                    <div class='col-md-6'>
                        <h6 class='text-primary mb-3'><i class='bi bi-gear me-2'></i>เกี่ยวกับโปรเจค</h6>
                        <p>เครื่องมือจำลอง Banker's Algorithm สำหรับการศึกษาเรื่อง Deadlock Avoidance ในระบบปฏิบัติการ</p>
                        
                        <h6 class='text-primary mb-3'><i class='bi bi-lightbulb me-2'></i>วัตถุประสงค์</h6>
                        <ul class='list-unstyled'>
                            <li class='mb-2'>• เข้าใจการทำงานของ Banker's Algorithm</li>
                            <li class='mb-2'>• เรียนรู้การตรวจสอบ Safe State</li>
                            <li class='mb-2'>• ป้องกันการเกิด Deadlock</li>
                        </ul>
                    </div>
                    <div class='col-md-6'>
                        <h6 class='text-primary mb-3'><i class='bi bi-code-slash me-2'></i>เทคโนโลยีที่ใช้</h6>
                        <ul class='list-unstyled'>
                            <li class='mb-2'>• PHP สำหรับการประมวลผล</li>
                            <li class='mb-2'>• Bootstrap 5 สำหรับ UI</li>
                            <li class='mb-2'>• JavaScript สำหรับการตรวจสอบ</li>
                        </ul>
                    </div>
                </div>

                <hr class='my-4'>

                <h6 class='text-primary mb-3'><i class='bi bi-person-badge me-2'></i>รายชื่อสมาชิก</h6>
                
                <div class='member-card'>
                    <span class='section-badge'>Section 327D</span>
                    <div class='mt-2'>
                        <strong>นาย ชินวัตร อ่วมแก้ว</strong>
                    </div>
                </div>

                <div class='member-card'>
                    <span class='section-badge'>Section 327E</span>
                    <div class='mt-2'>
                        <strong>น.ส. ศรีรัตน์ อินทลัย</strong><br>
                        <strong>น.ส. นิชาภา ศรีแจ่มใส</strong><br>
                        <strong>น.ส. บุญพิทักษ์ โรจนประภาวสุ</strong><br>
                        <strong>นาย พิพัฒน์ ลิขิตวานิช</strong>
                    </div>
                </div>
            </div>
            <div class='modal-footer'>
                <button type='button' class='btn btn-primary' data-bs-dismiss='modal'>ปิด</button>
            </div>
        </div>
    </div>
</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body></html>";