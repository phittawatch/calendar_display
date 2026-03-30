<?php
// 1. ดึงค่า Config จาก Vercel/Local Env
$script_url = getenv('SCRIPT_URL');
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Asia/Bangkok');

// 2. ดึงข้อมูลจาก Google Apps Script
$response = @file_get_contents($script_url);
$events = json_decode($response, true) ?: [];

// 3. เตรียมข้อมูลปฏิทิน
$today = date('Y-m-d');
$monthName = date('F Y');
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
    <title>PEA Smart Calendar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
        .dot { height: 6px; width: 6px; border-radius: 50%; display: inline-block; }
        @media (max-width: 640px) {
            .day-name { font-size: 0.7rem; }
            .calendar-cell { min-height: 60px; }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-800">

    <header class="sticky top-0 z-50 glass border-b border-slate-200 px-4 py-3 sm:px-8 flex justify-between items-center">
        <div>
            <h1 class="text-xl font-bold text-purple-700 flex items-center gap-2">
                <i data-lucide="calendar"></i> PEA Digital Hub
            </h1>
            <p class="text-xs text-slate-500"><?php echo date('D, d M Y'); ?></p>
        </div>
        
        <div class="bg-slate-100 p-1 rounded-lg flex gap-1">
            <button onclick="switchView('day')" id="btn-day" class="view-btn px-4 py-1.5 rounded-md text-sm font-medium transition-all bg-white shadow-sm text-purple-600">Day</button>
            <button onclick="switchView('month')" id="btn-month" class="view-btn px-4 py-1.5 rounded-md text-sm font-medium transition-all text-slate-600 hover:bg-white/50">Month</button>
        </div>
    </header>

    <main class="max-w-5xl mx-auto p-4 sm:p-8">

        <section id="view-day" class="space-y-4">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-semibold">Today's Schedule</h2>
                <span class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">
                    <?php echo count($eventMap[$today] ?? []); ?> Events
                </span>
            </div>

            <?php if (!empty($eventMap[$today])): ?>
                <div class="grid gap-4">
                    <?php foreach ($eventMap[$today] as $ev): ?>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex gap-5 items-start hover:shadow-md transition-shadow">
                        <div class="text-purple-600 font-bold text-lg min-width-[60px]"><?php echo $ev['start']; ?></div>
                        <div>
                            <h3 class="text-lg font-medium mb-1"><?php echo htmlspecialchars($ev['title']); ?></h3>
                            <?php if($ev['location']): ?>
                                <p class="text-slate-500 text-sm flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i> <?php echo htmlspecialchars($ev['location']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-slate-200">
                    <i data-lucide="calendar-x" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                    <p class="text-slate-400">ไม่มีวาระงานสำหรับวันนี้</p>
                </div>
            <?php endif; ?>
        </section>

        <section id="view-month" class="hidden animate-in fade-in duration-500">
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-200">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h2 class="text-xl font-bold uppercase tracking-wider text-slate-600"><?php echo $monthName; ?></h2>
                </div>
                
                <div class="calendar-grid bg-slate-200 gap-px">
                    <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName): ?>
                        <div class="bg-slate-50 py-3 text-center text-xs font-bold text-slate-400 uppercase"><?php echo $dayName; ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 0; $i < $startDayOfWeek; $i++): ?>
                        <div class="bg-white calendar-cell p-2 opacity-30"></div>
                    <?php endfor; ?>

                    <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                        $currentDate = date('Y-m-') . sprintf('%02d', $d);
                        $isToday = ($currentDate == $today);
                    ?>
                        <div class="bg-white calendar-cell p-2 min-h-[100px] flex flex-direction-column <?php echo $isToday ? 'bg-purple-50' : ''; ?>">
                            <span class="text-sm font-semibold mb-1 <?php echo $isToday ? 'bg-purple-600 text-white w-7 h-7 flex items-center justify-center rounded-full' : 'text-slate-400'; ?>">
                                <?php echo $d; ?>
                            </span>
                            
                            <div class="space-y-1 overflow-hidden">
                                <?php if (isset($eventMap[$currentDate])): ?>
                                    <?php foreach ($eventMap[$currentDate] as $idx => $ev): ?>
                                        <div class="hidden sm:block text-[10px] p-1 rounded truncate text-white" style="background-color: <?php echo $ev['color'] ?? '#8b5cf6'; ?>">
                                            <?php echo $ev['title']; ?>
                                        </div>
                                        <div class="sm:hidden dot" style="background-color: <?php echo $ev['color'] ?? '#8b5cf6'; ?>"></div>
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
        // Initialize Icons
        lucide.createIcons();

        // Switch View Logic
        function switchView(view) {
            const dayView = document.getElementById('view-day');
            const monthView = document.getElementById('view-month');
            const btnDay = document.getElementById('btn-day');
            const btnMonth = document.getElementById('btn-month');

            if (view === 'day') {
                dayView.classList.remove('hidden');
                monthView.classList.add('hidden');
                btnDay.classList.add('bg-white', 'shadow-sm', 'text-purple-600');
                btnMonth.classList.remove('bg-white', 'shadow-sm', 'text-purple-600');
            } else {
                dayView.classList.add('hidden');
                monthView.classList.remove('hidden');
                btnMonth.classList.add('bg-white', 'shadow-sm', 'text-purple-600');
                btnDay.classList.remove('bg-white', 'shadow-sm', 'text-purple-600');
            }
        }
    </script>
</body>
</html>