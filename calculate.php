<?php
// =============================================
// RAW CALCULATION (No Security Tokens)
// =============================================

// Start simple session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. ตรวจสอบ HTTP Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// --- (ส่วนรับค่า) ---
function validate_integer($value, $min = 0, $max = 1000) {
    $value = intval($value);
    return ($value >= $min && $value <= $max) ? $value : $min;
}

$numProcesses = isset($_POST['n']) ? validate_integer($_POST['n'], 1, 10) : 0;
$numResources = isset($_POST['m']) ? validate_integer($_POST['m'], 1, 10) : 0;

if ($numProcesses <= 0 || $numResources <= 0) {
    die('
        <div class="container mt-5">
            <div class="alert alert-danger text-center">
                <h4><i class="bi bi-x-circle-fill me-2"></i>ข้อมูลไม่ครบถ้วน</h4>
                <p>กรุณากรอกจำนวน Process และ Resource ให้ถูกต้อง</p>
                <a href="index.php" class="btn btn-primary mt-3">กลับหน้าหลัก</a>
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
    $gradStyle = "style=\"background: linear-gradient(120deg,#0d6a44,#23a56f); -webkit-background-clip:text; color:transparent;\"";
    
    if (strpos($title, 'Allocation') !== false) {
        $icon = "<i class='ti ti-table-filled me-2' $gradStyle></i>";
    } elseif (strpos($title, 'Need') !== false) {
        $icon = "<i class='ti ti-math-function me-2' $gradStyle></i>";
    } else {
        $icon = "<i class='ti ti-stack-2 me-2' $gradStyle></i>";
    }

    echo "<h3 class='h5 mt-4 mb-3'>$icon" . htmlspecialchars($title) . "</h3>";
    echo "<div class='table-responsive shadow-sm rounded mb-4'>";
    echo "<table class='table table-bordered text-center align-middle mb-0 table-gradient'>";
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

$NeedMatrix = toIntMatrix($NeedMatrix_raw, $numProcesses, $numResources);
$AllocationMatrix = toIntMatrix($AllocationMatrix_raw, $numProcesses, $numResources);
$AvailableVector = toIntArray($AvailableVector_raw, $numResources);

// --- HTML Header ---
echo <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการคำนวณ Banker's Algorithm</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.47.0/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
  --emerald-dark:  #064e3b;
  --emerald-primary: #059669;
  --emerald-light: #10b981;
  --surface: rgba(255,255,255,0.94);
  --surface-soft: rgba(248,250,252,0.9);
  --line-soft: rgba(2, 44, 34, 0.10);
}

body{
  margin:0;
  font-family:"Bai Jamjuree",system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  background: radial-gradient(circle at top left, #ffffffff 0%, #ecfdf5 40%, #e0f2f1 75%, #f9fafb 100%);
  background-attachment: fixed !important;
  color:#0f172a;
}

.card{
  border-radius:22px !important;
  background: var(--surface) !important;
  backdrop-filter: blur(12px);
  box-shadow: 0 10px 35px rgba(15,23,42,0.08);
}

/* --- เพิ่ม CSS สำหรับ About Us Modal ที่หายไป --- */
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
/* ---------------------------------------- */

.btn-emerald{
  background-image: linear-gradient(135deg, #047857 0%, #059669 45%, #34d399 100%) !important;
  background-color: transparent !important; 
  color:#fff !important;
  border:none !important;
  font-weight:600 !important;
  padding:.9rem 1.2rem !important;
  border-radius:12px !important;
  box-shadow:0 8px 22px rgba(16,185,129,.30) !important;
  transition: all .2s ease;
}

.btn-emerald:hover{
  background-image: linear-gradient(135deg, #065f46 0%, #059669 50%, #6ee7b7 100%) !important;
  background-color: transparent !important;
  transform: translateY(-1px);
}

.btn-emerald:focus, .btn-emerald:active, .btn-emerald.active{
  background-image: linear-gradient(135deg, #047857 0%, #059669 45%, #34d399 100%) !important;
  background-color: transparent !important;
  box-shadow:0 6px 18px rgba(16,185,129,.35) !important;
}

:root { --bs-primary: #059669 !important; --bs-primary-rgb: 5,150,105 !important; }
.text-primary{ color:#059669 !important; }
.bg-primary{ background-color:#059669 !important; }

.custom-navbar{
  background: linear-gradient(135deg, #022c22 0%, #064e3b 40%, #059669 100%) !important;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.45) !important;
  padding: 0.8rem 0 !important;
}

.custom-navbar .navbar-brand, .custom-navbar .navbar-brand i, .custom-navbar .nav-link, .custom-navbar .nav-link i{
  color: #ffffff !important;
  font-weight: 700 !important;
  text-shadow: 0 1px 2px rgba(0,0,0,.35);
}

.custom-navbar .navbar-brand:hover, .custom-navbar .nav-link:hover{ color: #ffffff !important; opacity: .9; }
.custom-navbar .navbar-toggler{ border-color: rgba(255,255,255,.6) !important; }
.custom-navbar .navbar-toggler-icon{ filter: invert(1); }

.table-gradient{
  border-radius:14px;
  overflow:hidden;
  border:1px solid rgba(5,150,105,.20);
  background: var(--surface-soft);
}

.table-gradient thead th{
  background: linear-gradient(90deg, var(--emerald-dark), var(--emerald-primary));
  color:#fff !important;
  font-weight:600;
  border:none !important;
  letter-spacing:.2px;
}

.table-gradient tbody td{ border-color: rgba(5,150,105,.12) !important; color:#0f172a; }
.table-gradient tbody tr:nth-child(odd) td{ background: rgba(255,255,255,0.95); }
.table-gradient tbody tr:nth-child(even) td{ background: rgba(236,253,245,0.55); }

.step-box{
  background: rgba(250,252,251,0.95);
  border-left: 5px solid var(--emerald-primary);
  padding:18px 22px;
  border-radius:14px;
  box-shadow:0 4px 12px rgba(0,0,0,0.05);
  font-size:.96rem;
  color:#0f172a;
  white-space:pre-wrap;
}

.step-box strong.text-white{ color: var(--emerald-dark) !important; }

.finish-true{ background: #d1fae5 !important; color: #047857 !important; border: 1px solid #10b981 !important; padding: 4px 10px; border-radius: 8px; font-weight: 700; display: inline-block; white-space: nowrap; width: 80px; text-align: center;}
.finish-false{ background: #fee2e2 !important; color: #b91c1c !important; border: 1px solid #f87171 !important; padding: 4px 10px; border-radius: 8px; font-weight: 700; display: inline-block; white-space: nowrap; width: 80px; text-align: center;}

.alert-success { background: linear-gradient(135deg, rgba(184, 247, 217, 0.75), rgba(152, 236, 195, 0.65)); border: none; color: #046C4E; font-weight: 600; border-left: 6px solid #34D399; border-radius: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.alert-danger { background: linear-gradient(135deg, rgba(254,202,202,0.7), rgba(253,164,164,0.65)); border: none; color: #7f1d1d; font-weight: 600; border-left: 6px solid #e11d48; border-radius: 14px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }

.modal-footer .btn.btn-primary { border-radius: 999px; padding: 0.6rem 1.4rem; font-weight: 600; color: #fff; border: none; background: linear-gradient(135deg, #065f46 0%, #059669 50%, #10b981 100%); box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35), 0 8px 20px rgba(5, 150, 105, 0.25); transition: 0.2s ease; }
.modal-footer .btn.btn-primary:hover { transform: translateY(-2px); filter: brightness(1.08); }

</style>
</head>
<body>
HTML;

// --- Navigation Bar ---
echo "<nav class='navbar navbar-expand-lg custom-navbar sticky-top'>
    <div class='container'>
        <a class='navbar-brand text-white fw-bold' href='index.php'>
            <i class='bi bi-cpu me-2 text-white'></i>
            Banker's Algorithm (CS422)
        </a>

        <button class='navbar-toggler border-light' type='button' data-bs-toggle='collapse' data-bs-target='#navbarNav'>
            <span class='navbar-toggler-icon' style='filter: invert(1);'></span>
        </button>

        <div class='collapse navbar-collapse' id='navbarNav'>
            <ul class='navbar-nav ms-auto'>
                <li class='nav-item'>
                    <a class='nav-link text-white fw-bold' href='#' data-bs-toggle='modal' data-bs-target='#aboutModal'>
                        <i class='bi bi-people-fill me-1 text-white'></i>About Us
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>";

// --- Layout Container ---
echo "<div class='container my-5'><div class='card shadow-lg border-0 rounded-3'><div class='card-body p-4 p-md-5'>";

// --- หัวเรื่อง ---
echo "<h1 class='card-title text-center mb-5 fw-bold' style='color: var(--emerald-primary);'>
<i class='bi bi-cpu me-2' style='color: var(--emerald-primary);'></i>ผลการคำนวณ Banker's Algorithm
</h1>";


// --- แสดงตาราง Input ---
showMatrixTH("ตาราง Allocation (ที่จัดสรรแล้ว)", $AllocationMatrix, $numResources);
showMatrixTH("ตาราง Need (ความต้องการที่เหลืออยู่)", $NeedMatrix, $numResources);

echo "<h3 class='h5 mt-4 mb-3'>
        <i class='bi bi-layers-fill me-2' style='color: var(--emerald-primary);'></i>
        ทรัพยากรที่เหลืออยู่ (Available)
      </h3>";
echo "<div class='table-responsive shadow-sm rounded mb-4'>
      <table class='table table-bordered text-center align-middle mb-0 table-gradient'>";
echo "<thead><tr>";
for($j=0;$j<$numResources;$j++) echo "<th>R".($j+1)."</th>";
echo "</tr></thead><tbody><tr>";
for($j=0;$j<$numResources;$j++){
    $availValue = isset($AvailableVector[$j]) ? htmlspecialchars($AvailableVector[$j]) : '-';
    echo "<td>$availValue</td>";
}
echo "</tr></tbody></table></div>";

// --- Safety Algorithm Step-by-Step ---
echo "<h2 class='h5 mt-4 mb-3'>
        <i class='ti ti-search me-2' style='color: var(--emerald-primary);'></i>
        ขั้นตอนการตรวจสอบ
      </h2>";
echo "<pre class='step-box'>";

$WorkVector = $AvailableVector;
$Finish = array_fill(0,$numProcesses,false);
$SafeSequence = array();
$processesFinishedCount = 0;
$pass = 1;
$maxIterations = $numProcesses * 3;

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

if ($pass > $maxIterations) {
    echo "<span class='text-danger'>⚠️  การคำนวณถูกหยุดเนื่องจากใช้รอบมากเกินไป</span>\n";
}
echo "</pre>";

// --- ตาราง Finish ---
echo "<h2 class='h5 mt-4 mb-3'>
        <i class='ti ti-layout-grid me-2' style='color: var(--emerald-primary);'></i>
        ตารางสถานะการทำงานของ Process
      </h2>";

echo "<div class='table-responsive shadow-sm rounded mb-4'>";
echo "<table class='table table-bordered text-center align-middle mb-0 table-gradient table-Finish'>";
echo "<thead class='table-light'><tr><th style='width:50%'>Process</th><th style='width:50%'>Status (Finish)</th></tr></thead><tbody>";
for($i=0;$i<$numProcesses;$i++){
    $isFinished = isset($Finish[$i]) ? $Finish[$i] : false;
    echo "<tr><td>P".($i+1)."</td>";
echo $isFinished
    ? "<td><span class='finish-true'><i class='bi bi-check-circle-fill me-1'></i>True</span></td>"
    : "<td><span class='finish-false'><i class='bi bi-x-circle-fill me-1'></i>False</span></td>";
    echo "</tr>";
}
echo "</tbody></table></div>";

// --- สรุปผล ---
echo "<h2 class='h5 mt-4 mb-3'>
        <i class='ti ti-flag me-2' style='color: var(--emerald-primary);'></i>
        ผลลัพธ์สุดท้าย
      </h2>";
      
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
echo "
<div class='d-flex justify-content-center mt-4'>
    <a href='index.php' class='btn btn-emerald btn-lg px-5'>
        <i class='bi bi-arrow-left-circle-fill me-2'></i>
        ย้อนกลับไปกรอกข้อมูลใหม่
    </a>
</div>
";
echo "</div></div></div>";

// --- About Us Modal ---
echo "<div class='modal fade' id='aboutModal' tabindex='-1'>
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
?>