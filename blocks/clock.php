<?php
if (!defined('IN_BLOCK')) die('Direct access not allowed.');

// Текуща дата
$today = getdate();
$currentDay = $today['mday'];
$currentMonth = $today['mon'];
$currentYear = $today['year'];

// Първи ден от месеца
$firstDay = getdate(mktime(0, 0, 0, $currentMonth, 1, $currentYear));
$startDayOfWeek = $firstDay['wday']; // 0=Неделя, 1=Понеделник...

// Последен ден от месеца
$lastDay = date('t', mktime(0, 0, 0, $currentMonth, 1, $currentYear));

// Имена на дните от седмицата (зависи от езика)
$weekdays = [
    $lang->get('sunday_short'),
    $lang->get('monday_short'),
    $lang->get('tuesday_short'),
    $lang->get('wednesday_short'),
    $lang->get('thursday_short'),
    $lang->get('friday_short'),
    $lang->get('saturday_short')
];

// ✅ Имена на месеците — локализирани
$monthNames = [
    1 => $lang->get('january'),
    2 => $lang->get('february'),
    3 => $lang->get('march'),
    4 => $lang->get('april'),
    5 => $lang->get('may'),
    6 => $lang->get('june'),
    7 => $lang->get('july'),
    8 => $lang->get('august'),
    9 => $lang->get('september'),
    10 => $lang->get('october'),
    11 => $lang->get('november'),
    12 => $lang->get('december')
];

$currentMonthName = $monthNames[$currentMonth] ?? 'Unknown';
?>

<div class="card mb-4">
    <div class="card-header">
        <?= $lang->get('clock_and_calendar') ?>
    </div>
    <div class="card-body">
        <!-- Часовник -->
        <div class="text-center mb-3">
            <h2 id="current-time" class="display-6 fw-bold text-primary"><?= date('H:i:s') ?></h2>
        </div>

        <!-- 📅 Текущ месец и година -->
        <div class="text-center mb-4">
            <div class="h5 fw-normal text-muted">
                📅 <?= htmlspecialchars($currentMonthName) ?> <?= $currentYear ?>
            </div>
        </div>

        <!-- Календар -->
        <div class="calendar-grid">
            <!-- Заглавия на дните -->
            <div class="calendar-header row g-1 mb-1">
                <?php foreach ($weekdays as $weekday): ?>
                    <div class="col text-center small fw-bold text-muted"><?= $weekday ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Дни от месеца -->
            <div class="calendar-days row g-1">
                <?php
                // Празни клетки преди първия ден
                for ($i = 0; $i < $startDayOfWeek; $i++) {
                    echo '<div class="col p-1"><div class="day empty"></div></div>';
                }

                // Дните от месеца
                for ($day = 1; $day <= $lastDay; $day++) {
                    $isToday = ($day == $currentDay);
                    $dayClass = $isToday ? 'today' : 'normal';
                    
                    echo '<div class="col p-1">';
                    echo '<div class="day ' . $dayClass . '">' . $day . '</div>';
                    echo '</div>';
                }

                // Празни клетки след последния ден (за да завърши реда)
                $totalCells = $startDayOfWeek + $lastDay;
                $remainingCells = (7 - ($totalCells % 7)) % 7;
                for ($i = 0; $i < $remainingCells; $i++) {
                    echo '<div class="col p-1"><div class="day empty"></div></div>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    font-family: Arial, sans-serif;
}
.calendar-header .col {
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.calendar-days {
    --day-size: 36px;
}
.calendar-days .col {
    height: var(--day-size);
}
.day {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: default;
}
.day.normal {
    background: #f8f9fa;
    color: #495057;
    border: 1px solid #e9ecef;
}
.day.today {
    background: #0d6efd;
    color: white;
    font-weight: bold;
    border: 1px solid #0a58ca;
}
.day.empty {
    background: transparent;
    border: none;
}
@media (max-width: 575.98px) {
    .calendar-days {
        --day-size: 30px;
    }
    .calendar-header .col {
        font-size: 0.8rem;
    }
}
</style>

<script>
function updateClock() {
    const now = new Date();
    let hours = now.getHours().toString().padStart(2, '0');
    let minutes = now.getMinutes().toString().padStart(2, '0');
    let seconds = now.getSeconds().toString().padStart(2, '0');
    document.getElementById('current-time').textContent = `${hours}:${minutes}:${seconds}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>