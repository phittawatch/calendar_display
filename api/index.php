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
    <meta http-equiv="refresh" content="120"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@200;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Kanit', sans-serif; background: #0f172a; color: white; margin: 0; padding: 0; }
        
        /* สลับหน้าจอแบบเนียนๆ */
        .view-transition { transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
        
        /* Background & Effects */
        .bg-gradient {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(45deg, #1e1b4b, #4c1d95, #1e1b4b, #581c87);
            background-size: 400% 400%; z-index: -1;
            animation: gradientMove 15s ease infinite;
        }
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        .glass-panel {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* สำหรับ Mobile UX */
        @media (max-width: 768px) {
            body { overflow-y: auto; }
            .hide-on-mobile { display: none; }
            .calendar-grid { grid-template-columns: repeat(7, 1fr); font-size: 0.6rem; }
            .calendar-cell { min-height: 50px !important; padding: 4px !important; }
            header { flex-direction: column; align-items: flex-start !important; gap: 1rem; }
            .text-right { text-align: left !important; width: 100%; }
            .flex-grow { position: relative !important; min-height: 500px; }
        }

        /* Modal Style */
        .modal {
            display: none; position: fixed; inset: 0; z-index: 100;
            background: rgba(0, 0, 0, 0.85); backdrop-filter: blur(15px);
            align-items: center; justify-content: center; padding: 1.5rem;
        }
        .modal.active { display: flex; }

        .nav-btn { background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); transition: all 0.3s ease; }
        .nav-btn.active { background: rgba(167, 139, 250, 0.2); border-color: #a78bfa; color: #a78bfa; }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>

    <div class="min-h-screen flex flex-col p-4 lg:p-10">
        <header class="flex justify-between items-end mb-8">
            <div>
                <div class="flex items-center gap-3 mb-1">
                    <img src="https://www.pea.co.th/sites/default/files/images/home/pea_logo_big.png" class="h-12 lg:h-20" alt="PEA">
                    <div class="h-8 w-[1px] bg-white/20"></div>
                    <h1 class="text-xl lg:text-3xl font-light tracking-wide uppercase leading-tight">
                        ระบบแสดงข้อมูลการจัดอบรม - ประชุม และกิจกรรมของ <span class="font-bold text-purple-400">กฟฉ.3</span>
                    </h1>
                </div>
                <p class="text-white/50 text-[10px] lg:text-xs font-bold tracking-widest uppercase ml-1">Smart Office Information Board</p>
            </div>
            <div class="text-right">
                <div class="flex lg:justify-end gap-2 mb-4">
                    <button onclick="switchView('day')" id="btn-day" class="nav-btn active px-3 py-1.5 lg:px-4 lg:py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="calendar-days" class="w-3 h-3 lg:w-4 lg:h-4"></i> Daily
                    </button>
                    <button onclick="switchView('month')" id="btn-month" class="nav-btn px-3 py-1.5 lg:px-4 lg:py-2 rounded-xl text-[10px] font-bold uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="calendar-range" class="w-3 h-3 lg:w-4 lg:h-4"></i> Monthly
                    </button>
                </div>
                <div id="liveClock" class="text-3xl lg:text-5xl font-light tracking-tighter mb-1">00:00:00</div>
                <div class="text-purple-300 font-bold uppercase tracking-widest text-[10px] lg:text-sm"><?php echo date('l, d F Y'); ?></div>
            </div>
        </header>

        <div class="flex-grow relative">
            <section id="view-day" class="view-transition absolute inset-0">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">
                    <div class="hidden lg:col-span-4 lg:flex flex-col justify-center">
                        <h2 class="text-7xl font-bold mb-4">TODAY</h2>
                        <div class="h-1.5 w-24 bg-purple-500 mb-6 rounded-full"></div>
                        <!-- <p class="text-xl text-white/40 italic">ประสานงานฉับไว ข้อมูลถูกต้อง</p> -->
                    </div>
                    
                    <div class="col-span-1 lg:col-span-8 overflow-y-auto pr-2">
                        <div class="space-y-4">
                            <?php if (!empty($eventMap[$today])): ?>
                                <?php foreach ($eventMap[$today] as $ev): ?>
                                <div onclick='showEventDetails("วันนี้", <?php echo json_encode([$ev]); ?>)' class="glass-panel p-6 lg:p-8 rounded-[2rem] flex items-center gap-4 lg:gap-8 border-l-8 cursor-pointer hover:bg-white/10 transition-all" style="border-left-color: <?php echo $ev['color'] ?? '#a78bfa'; ?>">
                                    <div class="text-xl lg:text-3xl font-bold text-purple-300 w-16 lg:w-24 shrink-0"><?php echo $ev['start']; ?></div>
                                    <div class="flex-grow">
                                        <h3 class="text-lg lg:text-2xl font-semibold mb-1"><?php echo htmlspecialchars($ev['title']); ?></h3>
                                        <div class="flex items-center gap-2 text-white/40 text-xs lg:text-sm">
                                            <i data-lucide="map-pin" class="w-3 h-3 lg:w-4 lg:h-4"></i>
                                            <span class="truncate"><?php echo htmlspecialchars($ev['location'] ?: 'ไม่ระบุสถานที่'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="glass-panel p-16 rounded-[3rem] text-center">
                                    <i data-lucide="calendar-check-2" class="w-12 h-12 mx-auto mb-4 opacity-20 text-purple-400"></i>
                                    <p class="text-xl text-white/30 tracking-widest uppercase">No Events Today</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section id="view-month" class="view-transition absolute inset-0 opacity-0 translate-y-10 pointer-events-none">
                <div class="glass-panel rounded-[2rem] lg:rounded-[3rem] overflow-hidden flex flex-col h-full">
                    <div class="p-4 lg:p-6 border-b border-white/10 flex justify-between items-center bg-white/5">
                        <h2 class="text-lg lg:text-2xl font-bold"><?php echo $displayMonth; ?></h2>
                        <span class="text-[10px] text-white/40 uppercase tracking-tighter lg:block hidden italic">คลิกที่วันที่เพื่อดูรายละเอียด</span>
                    </div>
                    
                    <div class="grid grid-cols-7 flex-grow calendar-grid">
                        <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day): ?>
                            <div class="p-2 text-center text-[10px] font-bold text-white/30 uppercase border-b border-white/5"><?php echo $day; ?></div>
                        <?php endforeach; ?>

                        <?php 
                        for ($i = 0; $i < $startDayOfWeek; $i++) echo '<div class="border-r border-b border-white/5 opacity-5"></div>';
                        
                        for ($d = 1; $d <= $daysInMonth; $d++): 
                            $curr = date('Y-m-') . sprintf('%02d', $d);
                            $isT = ($curr == $today);
                            $evs = $eventMap[$curr] ?? [];
                        ?>
                            <div onclick='showEventDetails("<?php echo $d . " " . $displayMonth; ?>", <?php echo json_encode($evs); ?>)' 
                                 class="border-r border-b border-white/5 p-1 lg:p-2 min-h-[60px] lg:min-h-[100px] cursor-pointer hover:bg-white/5 transition-colors relative <?php echo $isT ? 'bg-purple-600/20' : ''; ?>">
                                <span class="text-[10px] lg:text-sm font-bold <?php echo $isT ? 'text-purple-400' : 'text-white/20'; ?>"><?php echo $d; ?></span>
                                <div class="mt-1 space-y-0.5 lg:space-y-1">
                                    <?php foreach (array_slice($evs, 0, 2) as $ev): ?>
                                        <div class="text-[8px] lg:text-[10px] truncate px-1 rounded bg-purple-500/30 text-purple-100 border border-purple-500/40">
                                            <?php echo htmlspecialchars($ev['title']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if(count($evs) > 2): ?>
                                        <div class="text-[8px] text-white/30 text-center">+<?php echo count($evs)-2; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div id="eventModal" class="modal" onclick="closeModal()">
        <div class="glass-panel w-full max-w-lg rounded-[2.5rem] p-6 lg:p-10 relative overflow-hidden" onclick="event.stopPropagation()">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div id="m-date" class="text-purple-400 font-bold tracking-widest text-xs uppercase mb-1">DATE</div>
                    <h2 class="text-2xl lg:text-3xl font-bold">รายละเอียดกิจกรรม</h2>
                </div>
                <button onclick="closeModal()" class="p-2 hover:bg-white/10 rounded-full"><i data-lucide="x"></i></button>
            </div>
            <div id="m-content" class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-GB', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        let currentView = 'day';
        let autoSwitchInterval;

        function switchView(view) {
            const views = { 'day': document.getElementById('view-day'), 'month': document.getElementById('view-month') };
            const btns = { 'day': document.getElementById('btn-day'), 'month': document.getElementById('btn-month') };
            currentView = view;

            Object.keys(views).forEach(v => {
                if(v === view) {
                    views[v].classList.remove('opacity-0', 'translate-y-10', 'pointer-events-none');
                    if(btns[v]) btns[v].classList.add('active');
                } else {
                    views[v].classList.add('opacity-0', 'translate-y-10', 'pointer-events-none');
                    if(btns[v]) btns[v].classList.remove('active');
                }
            });
            resetAutoSwitch();
        }

        function resetAutoSwitch() {
            clearInterval(autoSwitchInterval);
            autoSwitchInterval = setInterval(() => {
                switchView(currentView === 'day' ? 'month' : 'day');
            }, 30000); 
        }

        function showEventDetails(dateStr, events) {
            const modal = document.getElementById('eventModal');
            const content = document.getElementById('m-content');
            document.getElementById('m-date').textContent = dateStr;
            
            if (events.length === 0) {
                content.innerHTML = '<div class="py-10 text-center text-white/30 italic">ไม่มีกิจกรรมในวันนี้</div>';
            } else {
                content.innerHTML = events.map(ev => `
                    <div class="p-5 rounded-2xl bg-white/5 border border-white/10 border-l-4" style="border-left-color: ${ev.color || '#a78bfa'}">
                        <div class="text-purple-300 font-bold text-lg mb-1">${ev.start || '--:--'} น.</div>
                        <div class="text-xl font-bold mb-3">${ev.title}</div>
                        <div class="flex items-start gap-2 text-white/50 text-sm italic">
                            <i data-lucide="map-pin" class="w-4 h-4 shrink-0 mt-0.5"></i>
                            <span>${ev.location || 'ไม่ระบุสถานที่'}</span>
                        </div>
                    </div>
                `).join('');
            }
            modal.classList.add('active');
            lucide.createIcons();
            clearInterval(autoSwitchInterval); // หยุด auto switch ตอนดูรายละเอียด
        }

        function closeModal() {
            document.getElementById('eventModal').classList.remove('active');
            resetAutoSwitch(); // กลับมาสลับหน้าจอต่อ
        }

        resetAutoSwitch();
    </script>
</body>
</html>
