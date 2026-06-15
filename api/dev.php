<?php
$script_url = getenv('SCRIPT_URL');
date_default_timezone_set('Asia/Bangkok');

$response = @file_get_contents($script_url);
$events = json_decode($response, true) ?: [];

// แปลงกิจกรรมให้อยู่ในรูปแบบที่ JavaScript นำไปใช้ได้ง่าย
$events_json = json_encode($events);

$monthNameThai = ["01"=>"มกราคม", "02"=>"กุมภาพันธ์", "03"=>"มีนาคม", "04"=>"เมษายน", "05"=>"พฤษภาคม", "06"=>"มิถุนายน", "07"=>"กรกฎาคม", "08"=>"สิงหาคม", "09"=>"กันยายน", "10"=>"ตุลาคม", "11"=>"พฤศจิกายน", "12"=>"ธันวาคม"];
$dayNameThai = ["Sunday"=>"วันอาทิตย์", "Monday"=>"วันจันทร์", "Tuesday"=>"วันอังคาร", "Wednesday"=>"วันพุธ", "Thursday"=>"วันพฤหัสบดี", "Friday"=>"วันศุกร์", "Saturday"=>"วันเสาร์"];

$thaiDate = $dayNameThai[date('l')] . "ที่ " . date('j') . " " . $monthNameThai[date('m')] . " " . (date('Y') + 543);
$today = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEA Smart Dashboard</title>
    <meta http-equiv="refresh" content="120"> 
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { 
            font-family: 'Kanit', sans-serif; 
            background: #f8fafc; 
            color: #1e293b; 
            margin: 0; 
            padding: 0; 
        }
        
        .view-transition { transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1); }
        
        .bg-gradient {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(45deg, #f1f5f9, #e0f2fe, #f3e8ff, #fae8ff);
            background-size: 400% 400%; z-index: -1;
            animation: gradientMove 15s ease infinite;
        }
        @keyframes gradientMove { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        .glass-panel {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 768px) {
            body { overflow-y: auto; }
            .hide-on-mobile { display: none; }
            .calendar-grid { grid-template-columns: repeat(7, 1fr); font-size: 0.75rem; }
            .calendar-cell { min-height: 60px !important; padding: 6px !important; }
            header { flex-direction: column; align-items: flex-start !important; gap: 1.5rem; }
            .text-right { text-align: left !important; width: 100%; }
            .flex-grow { position: relative !important; min-height: 550px; }
        }

        .modal {
            display: none; position: fixed; inset: 0; z-index: 100;
            background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(10px);
            align-items: center; justify-content: center; padding: 1.5rem;
        }
        .modal.active { display: flex; }

        .nav-btn { 
            background: rgba(15, 23, 42, 0.05); 
            border: 1px solid rgba(15, 23, 42, 0.08); 
            color: #475569;
            transition: all 0.3s ease; 
        }
        .nav-btn.active { 
            background: #9333ea; 
            border-color: #9333ea; 
            color: white; 
        }
    </style>
</head>
<body>
    <div class="bg-gradient"></div>

    <div class="min-h-screen flex flex-col p-4 lg:p-10">
        <header class="flex justify-between items-end mb-8">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <img src="https://www.pea.co.th/sites/default/files/images/home/pea_logo_big.png" class="h-14 lg:h-24" alt="PEA">
                    <div class="h-10 w-[2px] bg-slate-300"></div>
                    <h1 class="text-2xl lg:text-4xl font-light tracking-wide uppercase leading-tight text-slate-800">
                        ระบบแสดงข้อมูลการจัดอบรม - ประชุม และกิจกรรมของ <span class="font-bold text-purple-600">กฟฉ.3</span>
                    </h1>
                </div>
                <p class="text-slate-500 text-xs lg:text-sm font-bold tracking-widest uppercase ml-1">Smart Office Information Board</p>
            </div>
            <div class="text-right">
                <div class="flex lg:justify-end gap-2 mb-4">
                    <button onclick="switchView('day')" id="btn-day" class="nav-btn active px-4 py-2 lg:px-5 lg:py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="calendar-days" class="w-4 h-4"></i> Daily
                    </button>
                    <button onclick="switchView('month')" id="btn-month" class="nav-btn px-4 py-2 lg:px-5 lg:py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest flex items-center gap-2">
                        <i data-lucide="calendar-range" class="w-4 h-4"></i> Monthly
                    </button>
                </div>
                <div id="liveClock" class="text-4xl lg:text-6xl font-semibold tracking-tighter text-slate-800 mb-1">00:00:00</div>
                <div class="text-purple-600 font-bold uppercase tracking-widest text-xs lg:text-base">
                    <?php echo $thaiDate; ?>
                </div>
            </div>
        </header>

        <div class="flex-grow relative">
            <section id="view-day" class="view-transition absolute inset-0">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 h-full">
                    <div class="hidden lg:col-span-4 lg:flex flex-col justify-center">
                        <h2 class="text-8xl font-bold text-slate-700 tracking-tight mb-4">TODAY</h2>
                        <div class="h-2 w-28 bg-purple-600 mb-6 rounded-full"></div>
                    </div>
                    
                    <div class="col-span-1 lg:col-span-8 overflow-y-auto pr-2">
                        <div id="daily-list-container" class="space-y-4"></div>
                    </div>
                </div>
            </section>

            <section id="view-month" class="view-transition absolute inset-0 opacity-0 translate-y-10 pointer-events-none">
                <div class="glass-panel rounded-[2.5rem] overflow-hidden flex flex-col h-full">
                    <div class="p-4 lg:p-6 border-b border-slate-200 flex justify-between items-center bg-slate-50">
                        <div class="flex items-center gap-4">
                            <button onclick="changeMonth(-1)" class="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-700">
                                <i data-lucide="chevron-left" class="w-6 h-6"></i>
                            </button>
                            <h2 id="calendar-month-title" class="text-xl lg:text-3xl font-bold text-slate-800"></h2>
                            <button onclick="changeMonth(1)" class="p-2 hover:bg-slate-200 rounded-full transition-colors text-slate-700">
                                <i data-lucide="chevron-right" class="w-6 h-6"></i>
                            </button>
                        </div>
                        <span class="text-xs text-slate-400 uppercase tracking-tight lg:block hidden italic">คลิกที่วันที่เพื่อดูรายละเอียดและไฟล์แนบ</span>
                    </div>
                    
                    <div class="grid grid-cols-7 flex-grow calendar-grid bg-white/50">
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-red-500 uppercase border-b border-slate-100">Sun</div>
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-slate-600 uppercase border-b border-slate-100">Mon</div>
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-slate-600 uppercase border-b border-slate-100">Tue</div>
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-slate-600 uppercase border-b border-slate-100">Wed</div>
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-slate-600 uppercase border-b border-slate-100">Thu</div>
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-slate-600 uppercase border-b border-slate-100">Fri</div>
                        <div class="p-3 text-center text-xs lg:text-sm font-bold text-blue-500 uppercase border-b border-slate-100">Sat</div>

                        <div id="calendar-cells" class="contents"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div id="eventModal" class="modal" onclick="closeModal()">
        <div class="glass-panel w-full max-w-2xl rounded-[2.5rem] p-6 lg:p-10 relative overflow-hidden bg-white" onclick="event.stopPropagation()">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <div id="m-date" class="text-purple-600 font-bold tracking-widest text-sm uppercase mb-1">DATE</div>
                    <h2 class="text-3xl font-bold text-slate-800">รายละเอียดกิจกรรม</h2>
                </div>
                <button onclick="closeModal()" class="p-2 hover:bg-slate-100 rounded-full text-slate-500"><i data-lucide="x"></i></button>
            </div>
            <div id="m-content" class="space-y-4 max-h-[60vh] overflow-y-auto pr-2"></div>
        </div>
    </div>

    <script>
        const allEvents = <?php echo $events_json; ?>;
        const todayStr = "<?php echo $today; ?>";
        
        const monthNameThai = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
        let currentCalendarDate = new Date(); 

        lucide.createIcons();

        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').textContent = now.toLocaleTimeString('en-GB', { hour12: false });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function getEventMap() {
            const map = {};
            allEvents.forEach(e => {
                const dateKey = e.startDate || todayStr;
                if (!map[dateKey]) map[dateKey] = [];
                map[dateKey].push(e);
            });
            return map;
        }

        // 1. เรนเดอร์รายการกิจกรรมของวันนี้ (Daily View)
        function renderDailyList() {
            const eventMap = getEventMap();
            const todayEvents = eventMap[todayStr] || [];
            const container = document.getElementById('daily-list-container');
            
            if (todayEvents.length > 0) {
                container.innerHTML = todayEvents.map(ev => {
                    const eventColor = ev.color || '#9333ea'; // ใช้สีดั้งเดิมที่แมปมาจากหลังบ้านเลย
                    return `
                        <div onclick='showEventDetails("วันนี้", ${JSON.stringify([ev])})' 
                             class="glass-panel p-6 lg:p-8 rounded-[2rem] flex items-center gap-6 lg:gap-8 border-l-8 cursor-pointer hover:bg-white transition-all bg-white/90" 
                             style="border-left-color: ${eventColor};">
                            <div class="text-2xl lg:text-4xl font-bold text-purple-600 w-20 lg:w-32 shrink-0">${ev.start || '--:--'}</div>
                            <div class="flex-grow">
                                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-medium mb-1 inline-block">${escapeHtml(ev.calendarName || 'กิจกรรม')}</span>
                                <h3 class="text-xl lg:text-3xl font-bold text-slate-800 mb-2">${escapeHtml(ev.title)}</h3>
                                <div class="flex items-center gap-2 text-slate-500 text-sm lg:text-base">
                                    <i data-lucide="map-pin" class="w-4 h-4 text-slate-400"></i>
                                    <span class="truncate font-medium">${escapeHtml(ev.location || 'ไม่ระบุสถานที่')}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } else {
                container.innerHTML = `
                    <div class="glass-panel p-20 rounded-[3rem] text-center bg-white/80">
                        <i data-lucide="calendar-check-2" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                        <p class="text-2xl text-slate-400 font-medium tracking-wide">ไม่พบข้อมูลกิจกรรมวันนี้</p>
                    </div>
                `;
            }
            lucide.createIcons();
        }

        // 2. เรนเดอร์ปฏิทินรายเดือน
        function renderCalendar() {
            const eventMap = getEventMap();
            const year = currentCalendarDate.getFullYear();
            const month = currentCalendarDate.getMonth(); 
            
            document.getElementById('calendar-month-title').textContent = monthNameThai[month] + " " + (year + 543);
            
            const firstDayIndex = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            
            let cellsHtml = '';
            
            for (let i = 0; i < firstDayIndex; i++) {
                cellsHtml += `<div class="border-r border-b border-slate-100 bg-slate-50/50 opacity-40"></div>`;
            }
            
            for (let d = 1; d <= totalDays; d++) {
                const currentMonthStr = String(month + 1).padStart(2, '0');
                const currentDayStr = String(d).padStart(2, '0');
                const dateKey = `${year}-${currentMonthStr}-${currentDayStr}`;
                
                const isToday = (dateKey === todayStr);
                const dayEvents = eventMap[dateKey] || [];
                const thaiDisplayMonth = monthNameThai[month] + " " + (year + 543);
                
                cellsHtml += `
                    <div onclick='showEventDetails("${d} ${thaiDisplayMonth}", ${JSON.stringify(dayEvents)})' 
                         class="border-r border-b border-slate-200/60 p-2 min-h-[85px] lg:min-h-[120px] cursor-pointer hover:bg-purple-50 transition-colors relative bg-white ${isToday ? 'bg-purple-100/70 border-2 border-purple-400 z-10' : ''}">
                        <span class="text-xs lg:text-base font-bold ${isToday ? 'text-purple-700 bg-purple-200/60 px-2 py-0.5 rounded-full' : 'text-slate-400'}">${d}</span>
                        <div class="mt-2 space-y-1">
                            ${dayEvents.slice(0, 3).map(ev => {
                                // ดึงรหัสสี HEX โดยตรงมาจาก Apps Script ปรับความโปร่งแสงลงเล็กน้อยในพรีวิว
                                const bgStyle = ev.color ? `background-color: ${ev.color}15; border-color: ${ev.color}; color: ${ev.color};` : '';
                                return `
                                    <div class="text-[9px] lg:text-xs truncate px-1.5 py-0.5 rounded border font-medium" style="${bgStyle}">
                                        ${escapeHtml(ev.title)}
                                    </div>
                                `;
                            }).join('')}
                            ${dayEvents.length > 3 ? `<div class="text-[10px] lg:text-xs text-purple-600 font-bold text-center">+${dayEvents.length - 3} รายการ</div>` : ''}
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('calendar-cells').innerHTML = cellsHtml;
        }

        function changeMonth(direction) {
            currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
            renderCalendar();
        }

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

        // แสดงผลใน Modal รองรับไฟล์แนบแบบ Array หลายรายการ
        function showEventDetails(dateStr, events) {
            const modal = document.getElementById('eventModal');
            const content = document.getElementById('m-content');
            document.getElementById('m-date').textContent = dateStr;
            
            if (events.length === 0) {
                content.innerHTML = '<div class="py-12 text-center text-slate-400 text-lg italic font-light">ไม่มีกิจกรรมในวันนี้</div>';
            } else {
                content.innerHTML = events.map(ev => {
                    const eventColor = ev.color || '#9333ea';
                    
                    // ปรับลูปโครงสร้างรองรับไฟล์แนบ (attachments เป็น Array)
                    let attachmentsHtml = '';
                    if (ev.attachments && ev.attachments.length > 0) {
                        attachmentsHtml = `
                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                                <span class="text-xs lg:text-sm text-slate-500 font-medium flex items-center gap-1">
                                    <i data-lucide="paperclip" class="w-4 h-4"></i> เอกสารแนบประจำกิจกรรม (${ev.attachments.length} ไฟล์):
                                </span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    ${ev.attachments.map(att => `
                                        <a href="${att.url}" target="_blank" class="inline-flex items-center justify-between p-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-bold rounded-xl transition-all border border-blue-200 group">
                                            <span class="truncate pr-2 max-w-[180px]">${escapeHtml(att.name)}</span>
                                            <i data-lucide="external-link" class="w-3.5 h-3.5 shrink-0 group-hover:translate-x-0.5 transition-transform"></i>
                                        </a>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    }

                    return `
                        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 border-l-8 shadow-sm mb-4" style="border-left-color: ${eventColor}">
                            <div class="flex justify-between items-start gap-2 mb-1.5">
                                <div class="text-purple-600 font-bold text-xl lg:text-2xl">${ev.start || '--:--'} น.</div>
                                <span class="text-xs bg-white border px-2.5 py-1 rounded-full text-slate-500 font-medium shadow-sm">${escapeHtml(ev.calendarName || 'กิจกรรม')}</span>
                            </div>
                            <div class="text-xl lg:text-2xl font-bold text-slate-800 mb-3">${escapeHtml(ev.title)}</div>
                            <div class="flex items-start gap-2 text-slate-600 text-sm lg:text-base font-light mb-2">
                                <i data-lucide="map-pin" class="w-5 h-5 shrink-0 mt-0.5 text-slate-400"></i>
                                <span><strong>สถานที่:</strong> ${escapeHtml(ev.location || 'ไม่ระบุสถานที่')}</span>
                            </div>
                            ${attachmentsHtml}
                        </div>
                    `;
                }).join('');
            }
            modal.classList.add('active');
            lucide.createIcons();
            clearInterval(autoSwitchInterval); 
        }

        function closeModal() {
            document.getElementById('eventModal').classList.remove('active');
            resetAutoSwitch();
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        renderDailyList();
        renderCalendar();
        resetAutoSwitch();
    </script>
</body>
</html>
