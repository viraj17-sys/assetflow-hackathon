<?php
require_once __DIR__ . '/includes/data.php';

$successMsg = '';
$errorMsg = '';
$prefillRoom = $_GET['room'] ?? 'Room B2';
$prefillDate = $_GET['date'] ?? date('Y-m-d');
$prefillStart = $_GET['start'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_booking') {
    if (!af_csrf_check()) {
        $errorMsg = 'Session expired, please try again.';
    } else {
        $room = trim($_POST['room'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $start = trim($_POST['start'] ?? '');
        $end = trim($_POST['end'] ?? '');
        $bookedBy = trim($_POST['booked_by'] ?? '');
        $prefillRoom = $room ?: $prefillRoom;
        $prefillDate = $date ?: $prefillDate;
        if ($room !== '' && $date !== '' && $start !== '' && $end !== '' && $bookedBy !== '') {
            if (strtotime($end) <= strtotime($start)) {
                $errorMsg = 'End time must be after the start time.';
            } elseif ($conflict = af_booking_conflict($room, $date, $start, $end)) {
                $errorMsg = "Conflict: $room is already booked " . af_format_time($conflict['start']) . '–' . af_format_time($conflict['end'])
                    . ' on ' . date('D j M', strtotime($date)) . " by {$conflict['booked_by']}. Choose a different slot.";
            } else {
                af_create_booking($room, $date, $start, $end, $bookedBy);
                $successMsg = "$room booked " . af_format_time($start) . '–' . af_format_time($end) . ' on ' . date('D j M', strtotime($date)) . '.';
            }
        } else {
            $errorMsg = 'Fill in room, date, time, and who is booking.';
        }
    }
}

$bookings = af_bookings();
$rooms = ['Room B2', 'Room A1', 'Conference Hall', 'Huddle Room 3'];

// Week grid: Monday–Sunday of the current week, 8:00–18:00 hourly slots.
$weekStartParam = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
$weekStart = date('Y-m-d', strtotime($weekStartParam));
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date('Y-m-d', strtotime("$weekStart +$i days"));
}
$hours = range(8, 18); // 8am - 6pm

// Index bookings by date for quick lookup while rendering the grid.
$byDate = [];
foreach ($bookings as $b) {
    $byDate[$b['date']][] = $b;
}

function af_slot_top($time) {
    [$h, $m] = array_map('intval', explode(':', date('H:i', strtotime($time))));
    return ($h - 8) * 48 + ($m / 60) * 48;
}
function af_slot_height($start, $end) {
    $mins = (strtotime($end) - strtotime($start)) / 60;
    return max(20, ($mins / 60) * 48);
}

$pageTitle = 'Resource Booking';
$pageSubtitle = 'Rooms and shared equipment';
$activeNav = 'booking';
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
    <h2 class="section-title" style="margin:0;">Week of <?php echo htmlspecialchars(date('j M', strtotime($weekStart)) . ' – ' . date('j M Y', strtotime($weekStart . ' +6 days'))); ?></h2>
    <div style="display:flex; gap:8px;">
      <a class="btn btn-ghost" style="padding:6px 12px;" href="booking.php?week=<?php echo urlencode(date('Y-m-d', strtotime($weekStart . ' -7 days'))); ?>">← Prev week</a>
      <a class="btn btn-ghost" style="padding:6px 12px;" href="booking.php">This week</a>
      <a class="btn btn-ghost" style="padding:6px 12px;" href="booking.php?week=<?php echo urlencode(date('Y-m-d', strtotime($weekStart . ' +7 days'))); ?>">Next week →</a>
    </div>
  </div>

  <?php if ($successMsg): ?>
    <div class="confirm-banner"><strong>Booking confirmed</strong><?php echo htmlspecialchars($successMsg); ?></div>
  <?php endif; ?>
  <?php if ($errorMsg): ?>
    <div class="block-banner"><strong>Couldn't book that</strong><?php echo htmlspecialchars($errorMsg); ?></div>
  <?php endif; ?>

  <p style="margin:0 0 10px; color:var(--text-faint); font-size:12.5px;">Click any empty slot to start a booking there.</p>

  <div class="cal-scroll">
    <div class="cal-grid" style="grid-template-columns: 56px repeat(7, 1fr);">
      <div class="cal-corner"></div>
      <?php foreach ($days as $d): $isToday = $d === date('Y-m-d'); ?>
        <div class="cal-day-head <?php echo $isToday ? 'is-today' : ''; ?>">
          <div class="cal-day-name"><?php echo htmlspecialchars(date('D', strtotime($d))); ?></div>
          <div class="cal-day-num"><?php echo htmlspecialchars(date('j M', strtotime($d))); ?></div>
        </div>
      <?php endforeach; ?>

      <div class="cal-hours">
        <?php foreach ($hours as $h): ?>
          <div class="cal-hour-label"><?php echo htmlspecialchars(date('g A', strtotime("$h:00"))); ?></div>
        <?php endforeach; ?>
      </div>

      <?php foreach ($days as $d): ?>
        <div class="cal-day-col" style="height: <?php echo count($hours) * 48; ?>px;">
          <?php foreach ($hours as $h): ?>
            <a class="cal-slot" href="booking.php?week=<?php echo urlencode($weekStart); ?>&date=<?php echo urlencode($d); ?>&start=<?php echo urlencode(sprintf('%02d:00', $h)); ?>&room=<?php echo urlencode($prefillRoom); ?>#bookForm" style="top: <?php echo ($h - 8) * 48; ?>px; height:48px;"></a>
          <?php endforeach; ?>
          <?php foreach ($byDate[$d] ?? [] as $b): ?>
            <div class="cal-event" style="top: <?php echo af_slot_top($b['start']); ?>px; height: <?php echo af_slot_height($b['start'], $b['end']); ?>px;" title="<?php echo htmlspecialchars($b['room'] . ' — ' . $b['booked_by']); ?>">
              <div class="cal-event-title"><?php echo htmlspecialchars($b['room']); ?></div>
              <div class="cal-event-time"><?php echo htmlspecialchars(af_format_time($b['start']) . '–' . af_format_time($b['end'])); ?></div>
              <div class="cal-event-by"><?php echo htmlspecialchars($b['booked_by']); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="panel" id="bookForm">
  <h2 class="section-title">Book a resource</h2>
  <form method="post" action="booking.php">
    <input type="hidden" name="action" value="create_booking">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
    <div class="form-grid">
      <div class="field">
        <label for="room">Room / resource</label>
        <select id="room" name="room" required>
          <?php foreach ($rooms as $r): ?>
            <option value="<?php echo htmlspecialchars($r); ?>" <?php echo $r === $prefillRoom ? 'selected' : ''; ?>><?php echo htmlspecialchars($r); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="booked_by">Booked by</label>
        <input id="booked_by" name="booked_by" type="text" placeholder="Your name" required>
      </div>
      <div class="field">
        <label for="date">Date</label>
        <input id="date" name="date" type="date" value="<?php echo htmlspecialchars($prefillDate); ?>" required>
      </div>
      <div class="field">
        <label for="start">Start time</label>
        <input id="start" name="start" type="time" value="<?php echo htmlspecialchars($prefillStart); ?>" required>
      </div>
      <div class="field">
        <label for="end">End time</label>
        <input id="end" name="end" type="time" required>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Confirm booking</button>
  </form>
</div>

<div class="panel">
  <h2 class="section-title">All bookings</h2>
  <table class="data-table">
    <thead>
      <tr><th>Room</th><th>Date</th><th>Time</th><th>Booked by</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php foreach (array_reverse($bookings) as $b): ?>
        <tr>
          <td><?php echo htmlspecialchars($b['room']); ?></td>
          <td><?php echo htmlspecialchars(date('D, j M', strtotime($b['date']))); ?></td>
          <td><?php echo htmlspecialchars(af_format_time($b['start']) . ' – ' . af_format_time($b['end'])); ?></td>
          <td><?php echo htmlspecialchars($b['booked_by']); ?></td>
          <td><span class="badge badge-confirmed"><?php echo htmlspecialchars($b['status']); ?></span></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
