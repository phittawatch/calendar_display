<?php
$script_url = getenv('SCRIPT_URL');
date_default_timezone_set('Asia/Bangkok');

$response = @file_get_contents($script_url);
$events = json_decode($response, true) ?: [];

$today = date('Y-m-d');
$monthNameThai = ["01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน", "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม", "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"];
$displayMonth = $monthNameThai[date('m')] . " " . (date('Y') + 543);
$daysInMonth = date('t');
$startDayOfWeek = date('w', strtotime(date('Y-m-01')));

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
    <title>PEA Smart Dashboard</title>
    <meta http-equiv="refresh" content="600"> <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@200;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; overflow: hidden; background: #0f172a; color: white; }

        /* Hotel Style Animated Background */
        .bg-gradient {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(45deg, #1e1b4b, #4c1d95, #1e1b4b, #581c87);
            background-size: 400% 400%;
            z-index: -1;
            animation: gradientMove 15s ease infinite;
        }

        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating Orb Effect */
        .orb {
            position: absolute;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.3) 0%, rgba(139, 92, 246, 0) 70%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            animation: float 20s infinite alternate;
        }

        @keyframes float {
            from { transform: translate(-10%, -10%); }
            to { transform: translate(20%, 20%); }
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .calendar-cell { height: clamp(80px, 12vh, 150px); }
        
        .view-transition { transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .active-dot {
            box-shadow: 0 0 10px #a78bfa;
        }
        /* สไตล์ปุ่ม Navigation */
        .nav-btn {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        .nav-btn.active {
            background: rgba(167, 139, 250, 0.2);
            border-color: #a78bfa;
            color: #a78bfa;
            box-shadow: 0 0 15px rgba(167, 139, 250, 0.3);
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>
    <div class="orb"></div>

    <div class="min-h-screen flex flex-col p-6 lg:p-12">
        <header class="flex justify-between items-end mb-10">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <img src="https://www.pea.co.th/sites/default/files/images/home/pea_logo_big.png" class="h-16" alt="PEA">
                    <div class="h-8 w-[1px] bg-white/20"></div>
                    <h1 class="text-4xl font-light tracking-widest uppercase">ระบบแสดงข้อมูลการจัดอบรม - ประชุม และกิจกรรมของ <span class="font-bold text-purple-400">กฟฉ.3</span></h1>
                </div>
                <p class="text-white/50 text-sm tracking-widest uppercase ml-1">Smart Office Information Board</p>
            </div>
            <div class="text-right">
                <div class="flex justify-end gap-2 mb-4">
                    <button onclick="switchView('day')" id="btn-day" class="nav-btn active px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="calendar-days" class="w-4 h-4"></i> Daily
                    </button>
                    <button onclick="switchView('month')" id="btn-month" class="nav-btn px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="calendar-range" class="w-4 h-4"></i> Monthly
                    </button>
                </div>
                <div id="liveClock" class="text-5xl font-light tracking-tighter mb-1">00:00:00</div>
                <div class="text-purple-300 font-medium uppercase tracking-widest text-sm"><?php echo date('l, d F Y'); ?></div>
            </div>
        </header>

        <div class="flex-grow relative">
            
            <section id="view-day" class="view-transition absolute inset-0">
                <div class="grid grid-cols-12 gap-8 h-full">
                    <div class="col-span-12 lg:col-span-4 flex flex-col justify-center">
                        <h2 class="text-6xl font-bold mb-4">TODAY</h2>
                        <div class="h-1 w-20 bg-purple-500 mb-6"></div>
                        <p class="text-xl text-white/60 leading-relaxed italic">"ข้อมูลวาระงานประจำวันล่าสุด เพื่อการประสานงานที่มีประสิทธิภาพ"</p>
                    </div>
                    
                    <div class="col-span-12 lg:col-span-8 overflow-y-auto pr-4 custom-scroll">
                        <div class="space-y-4">
                            <?php if (!empty($eventMap[$today])): ?>
                                <?php foreach ($eventMap[$today] as $ev): ?>
                                <div class="glass-panel p-8 rounded-3xl flex items-center gap-8 border-l-8" style="border-left-color: <?php echo $ev['color'] ?? '#a78bfa'; ?>">
                                    <div class="text-3xl font-bold text-purple-300 w-24"><?php echo $ev['start']; ?></div>
                                    <div class="flex-grow">
                                        <h3 class="text-2xl font-semibold mb-2"><?php echo htmlspecialchars($ev['title']); ?></h3>
                                        <div class="flex items-center gap-2 text-white/40">
                                            <i data-lucide="map-pin" class="w-4 h-4"></i>
                                            <span><?php echo htmlspecialchars($ev['location'] ?: 'ไม่ระบุสถานที่'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="glass-panel p-20 rounded-[3rem] text-center">
                                    <i data-lucide="calendar-check-2" class="w-16 h-16 mx-auto mb-6 opacity-20"></i>
                                    <p class="text-2xl text-white/30 tracking-widest uppercase">No Events Scheduled Today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section id="view-month" class="view-transition absolute inset-0 opacity-0 translate-y-10 pointer-events-none">
                <div class="glass-panel rounded-[3rem] overflow-hidden flex flex-col h-full">
                    <div class="p-8 border-b border-white/10 flex justify-between items-center bg-white/5">
                        <h2 class="text-3xl font-bold tracking-tight"><?php echo $displayMonth; ?></h2>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-white/40">
                                <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span> ประชุม/อบรม
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 flex-grow">
                        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                            <div class="p-4 text-center text-xs font-bold text-white/30 uppercase tracking-tighter border-b border-white/5"><?php echo $day; ?></div>
                        <?php endforeach; ?>

                        <?php 
                        for ($i = 0; $i < $startDayOfWeek; $i++) echo '<div class="border-r border-b border-white/5 opacity-10"></div>';
                        
                        for ($d = 1; $d <= $daysInMonth; $d++): 
                            $curr = date('Y-m-') . sprintf('%02d', $d);
                            $isT = ($curr == $today);
                            $evs = $eventMap[$curr] ?? [];
                        ?>
                            <div class="border-r border-b border-white/5 p-3 calendar-cell relative <?php echo $isT ? 'bg-purple-600/20' : ''; ?>">
                                <span class="text-sm font-bold <?php echo $isT ? 'text-purple-400' : 'text-white/20'; ?>"><?php echo $d; ?></span>
                                <div class="mt-2 space-y-1">
                                    <?php foreach (array_slice($evs, 0, 3) as $ev): ?>
                                        <div class="h-1.5 rounded-full shadow-sm" style="background-color: <?php echo $ev['color'] ?? '#a78bfa'; ?>" title="<?php echo $ev['title']; ?>"></div>
                                    <?php endforeach; ?>
                                    <?php if(count($evs) > 3): ?>
                                        <div class="text-[8px] text-white/30 text-center font-bold">+<?php echo count($evs)-3; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>

        </div>
    </div>

<script>
        // เริ่มต้นใช้งาน Lucide Icons
        lucide.createIcons();

        // ฟังก์ชันนาฬิกา
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-GB', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // --- ส่วนจัดการการสลับหน้า (Views) ---
        
        let currentView = 'day';
        let autoSwitchInterval;

        function switchView(view) {
            const dayView = document.getElementById('view-day');
            const monthView = document.getElementById('view-month');
            const btnDay = document.getElementById('btn-day');
            const btnMonth = document.getElementById('btn-month');
            
            currentView = view;

            if (view === 'day') {
                // แสดงหน้า Day และซ่อน Month
                dayView.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                monthView.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                
                // อัปเดตสถานะปุ่ม (ถ้ามีปุ่ม ID นี้)
                if(btnDay) btnDay.classList.add('active');
                if(btnMonth) btnMonth.classList.remove('active');
            } else {
                // แสดงหน้า Month และซ่อน Day
                monthView.classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                dayView.classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                
                // อัปเดตสถานะปุ่ม
                if(btnMonth) btnMonth.classList.add('active');
                if(btnDay) btnDay.classList.remove('active');
            }

            // ทุกครั้งที่สลับหน้า (ไม่ว่าจะกดเองหรือออโต้) ให้เริ่มนับเวลา 30 วิใหม่เสมอ
            resetAutoSwitch();
        }

        function resetAutoSwitch() {
            // ล้าง Timer เก่าทิ้งเพื่อป้องกันการซ้อนกัน
            clearInterval(autoSwitchInterval);
            // ตั้ง Timer ใหม่
            autoSwitchInterval = setInterval(() => {
                const nextView = (currentView === 'day') ? 'month' : 'day';
                switchView(nextView);
            }, 30000); // 30 วินาที
        }

        // รันครั้งแรกเมื่อโหลดหน้าเว็บ
        resetAutoSwitch();
    </script>
</body>
</html>
