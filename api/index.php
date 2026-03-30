<?php
// 1. ดึงค่า Config จาก Vercel/Local Env
$script_url = getenv('SCRIPT_URL');
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Bangkok');

// 2. ดึงข้อมูลจาก Google Apps Script
$response = @file_get_contents($script_url);
$events = json_decode($response, true) ?: [];

// 3. เตรียมข้อมูลปฏิทิน
$today = date('Y-m-d');
$currentMonth = date('m');
$currentYear = date('Y');
$monthNameThai = [
    "01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน", 
    "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม", 
    "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"
];
$displayMonth = $monthNameThai[$currentMonth] . " " . ($currentYear + 543);
$daysInMonth = date('t');
$startDayOfWeek = date('w', strtotime(date('Y-m-01')));

// จัดกลุ่ม Event ตามวันที่
$eventMap = [];
foreach ($events as $e) {
    $dateKey = $e['startDate'] ?? $today;
    $eventMap[$dateKey][] = $e;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEA Digital Hub Calendar</title>
    <meta http-equiv="refresh" content="300"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            overflow-x: hidden;
        }
        
        /* Animated Background Movement */
        .bg-animate {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: -1;
            background: linear-gradient(125deg, #f3f4f6 0%, #ddd6fe 50%, #ede9fe 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glass { 
            background: rgba(255, 255, 255, 0.7); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
        
        /* ปรับแต่ง Scrollbar ให้ดูทันสมัย */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #a78bfa; border-radius: 10px; }

        .event-card:hover { transform: translateY(-2px); transition: all 0.2s; }
        
        @media (max-width: 640px) {
            .calendar-cell { min-height: 80px !important; }
            .event-text { display: none; } /* ซ่อนตัวหนังสือในปฏิทินเมื่อจอมือถือ */
        }
    </style>
</head>
<body class="min-h-screen text-slate-800">
    <div class="bg-animate"></div>

    <header class="sticky top-0 z-50 glass px-6 py-4 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-3">
            <div class="bg-purple-600 p-2 rounded-lg text-white shadow-lg shadow-purple-200">
                <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-900 leading-none">D-HUB CALENDAR</h1>
                <p class="text-[10px] uppercase tracking-[0.2em] text-purple-600 font-semibold">กฟฉ.3 Digital Authority</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="hidden md:block text-right mr-4">
                <p class="text-sm font-medium text-slate-700"><?php echo date('l, d F Y'); ?></p>
                <p id="liveClock" class="text-xs text-purple-600 font-mono font-bold">00:00:00</p>
            </div>
            <div class="glass p-1 rounded-xl flex gap-1">
                <button onclick="switchView('day')" id="btn-day" class="view-btn px-5 py-2 rounded-lg text-sm font-bold transition-all bg-purple-600 text-white shadow-md">Today</button>
                <button onclick="switchView('month')" id="btn-month" class="view-btn px-5 py-2 rounded-lg text-sm font-bold transition-all text-slate-600 hover:bg-white/50">Month</button>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        
        <section id="view-day" class="animate-in slide-in-from-bottom-4 duration-500">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-slate-800">ตารางงานวันนี้</h2>
                    <p class="text-slate-500">รายงานสรุปกิจกรรมและวาระประชุมล่าสุด</p>
                </div>
                <div class="text-right">
                    <span class="text-4xl font-black text-purple-200"><?php echo count($eventMap[$today] ?? []); ?></span>
                    <span class="text-sm font-bold text-slate-400 block uppercase">Events</span>
                </div>
            </div>

            <?php if (!empty($eventMap[$today])): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($eventMap[$today] as $ev): ?>
                    <div class="event-card glass p-6 rounded-3xl shadow-sm hover:shadow-xl transition-all border-l-4" style="border-left-color: <?php echo $ev['color'] ?? '#8b5cf6'; ?>">
                        <div class="flex justify-between items-start mb-4">
                            <span class="bg-white/80 px-3 py-1 rounded-full text-xs font-bold text-purple-700 shadow-sm border border-purple-100">
                                <i data-lucide="clock" class="w-3 h-3 inline mr-1"></i> <?php echo $ev['start']; ?>
                            </span>
                            <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: <?php echo $ev['color'] ?? '#8b5cf6'; ?>"></div>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 mb-3 leading-tight"><?php echo htmlspecialchars($ev['title']); ?></h3>
                        <?php if($ev['location']): ?>
                            <div class="flex items-center gap-2 text-slate-500 text-sm bg-slate-50/50 p-2 rounded-xl">
                                <i data-lucide="map-pin" class="w-4 h-4 text-purple-400"></i>
                                <span class="truncate"><?php echo htmlspecialchars($ev['location']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="glass py-24 rounded-[40px] text-center border-2 border-dashed border-purple-200">
                    <div class="bg-purple-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="calendar-check" class="w-10 h-10 text-purple-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-400">วันนี้ไม่มีวาระงาน</h3>
                    <p class="text-slate-400 text-sm">ขอให้เป็นวันที่ดีสำหรับการทำงานครับ!</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="view-month" class="hidden animate-in zoom-in-95 duration-500">
            <div class="glass rounded-[2rem] shadow-2xl overflow-hidden">
                <div class="p-8 border-b border-white/50 flex justify-between items-center bg-white/30">
                    <h2 class="text-2xl font-black text-slate-800"><?php echo $displayMonth; ?></h2>
                    <div class="flex gap-2">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400">
                            <span class="w-3 h-3 rounded-full bg-purple-500"></span> กิจกรรม
                        </div>
                    </div>
                </div>
                
                <div class="calendar-grid bg-slate-200/50 gap-[1px]">
                    <?php foreach (['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'] as $dayName): ?>
                        <div class="bg-white/50 py-4 text-center text-xs font-black text-slate-400 uppercase tracking-widest"><?php echo $dayName; ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
                        <div class="bg-white/20 calendar-cell p-2"></div>
                    <?php endfor; ?>

                    <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                        $currentDate = date('Y-m-') . sprintf('%02d', $d);
                        $isToday = ($currentDate == $today);
                        $hasEvents = isset($eventMap[$currentDate]);
                    ?>
                        <div class="bg-white/60 calendar-cell p-2 min-h-[120px] flex flex-col transition-all hover:bg-white <?php echo $isToday ? 'ring-2 ring-inset ring-purple-500 bg-white' : ''; ?>">
                            <span class="text-sm font-bold mb-2 <?php echo $isToday ? 'bg-purple-600 text-white w-7 h-7 flex items-center justify-center rounded-lg shadow-lg' : 'text-slate-400'; ?>">
                                <?php echo $d; ?>
                            </span>
                            
                            <div class="space-y-1 overflow-y-auto max-h-[80px]">
                                <?php if ($hasEvents): ?>
                                    <?php foreach ($eventMap[$currentDate] as $ev): ?>
                                        <div class="event-text text-[9px] p-1.5 rounded-md text-white font-medium truncate shadow-sm mb-1" 
                                             style="background-color: <?php echo $ev['color'] ?? '#8b5cf6'; ?>"
                                             title="<?php echo $ev['title']; ?>">
                                            <?php echo $ev['start'] == "ทั้งวัน" ? "" : $ev['start']; ?> <?php echo $ev['title']; ?>
                                        </div>
                                        <div class="sm:hidden w-full h-1 rounded-full mb-1" style="background-color: <?php echo $ev['color'] ?? '#8b5cf6'; ?>"></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

    </main>

    <script>
        lucide.createIcons();

        // นาฬิกา Real-time สำหรับหน้าจอ TV
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-GB', { hour12: false });
            document.getElementById('liveClock').textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Switch View Logic
        function switchView(view) {
            const dayView = document.getElementById('view-day');
            const monthView = document.getElementById('view-month');
            const btnDay = document.getElementById('btn-day');
            const btnMonth = document.getElementById('btn-month');

            if (view === 'day') {
                dayView.classList.remove('hidden');
                monthView.classList.add('hidden');
                btnDay.className = "view-btn px-5 py-2 rounded-lg text-sm font-bold transition-all bg-purple-600 text-white shadow-md";
                btnMonth.className = "view-btn px-5 py-2 rounded-lg text-sm font-bold transition-all text-slate-600 hover:bg-white/50";
            } else {
                dayView.classList.add('hidden');
                monthView.classList.remove('hidden');
                btnMonth.className = "view-btn px-5 py-2 rounded-lg text-sm font-bold transition-all bg-purple-600 text-white shadow-md";
                btnDay.className = "view-btn px-5 py-2 rounded-lg text-sm font-bold transition-all text-slate-600 hover:bg-white/50";
            }
        }
    </script>
</body>
</html>
