<?php
require_once __DIR__ . '/includes/data.php';

$data = af_reports_data();
$assets = af_assets();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="assetflow-report-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');

fputcsv($out, ['AssetFlow — Reports & Analytics export', date('Y-m-d H:i')]);
fputcsv($out, []);

fputcsv($out, ['Utilization by department']);
fputcsv($out, ['Department', 'Utilization %']);
foreach ($data['utilization'] as $u) {
    fputcsv($out, [$u['label'], $u['value']]);
}
fputcsv($out, []);

fputcsv($out, ['Maintenance frequency (tickets per month)']);
fputcsv($out, ['Month', 'Tickets']);
foreach ($data['maintenance_trend'] as $t) {
    fputcsv($out, [$t['label'], $t['value']]);
}
fputcsv($out, []);

fputcsv($out, ['Most used assets']);
foreach ($data['most_used'] as $item) {
    fputcsv($out, [$item['label'], $item['detail']]);
}
fputcsv($out, []);

fputcsv($out, ['Idle assets']);
foreach ($data['idle'] as $item) {
    fputcsv($out, [$item['label'], $item['detail']]);
}
fputcsv($out, []);

fputcsv($out, ['Assets due for maintenance / nearing retirement']);
foreach ($data['due'] as $item) {
    fputcsv($out, [$item['label'], $item['detail']]);
}
fputcsv($out, []);

fputcsv($out, ['Full asset registry']);
fputcsv($out, ['Asset ID', 'Name', 'Category', 'Status', 'Holder', 'Department']);
foreach ($assets as $a) {
    fputcsv($out, [$a['id'], $a['name'], $a['category'], $a['status'], $a['holder'] ?? '', $a['department'] ?? '']);
}

fclose($out);
exit;
