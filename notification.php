<?php
// notifications.php
class Notifications
{

    // Get upcoming events for notifications
    public static function getUpcomingEvents($days = 1)
    {
        include 'koneksi.php';

        $sql = "SELECT 
                    a.id_agenda, 
                    a.nama_kegiatan, 
                    a.tgl_agenda, 
                    a.waktu,
                    a.tempat_kegiatan,
                    p.nama_pejabat,
                    p.nama_jabatan,
                    s.nama_status
                FROM tb_agenda a
                LEFT JOIN tb_pejabat p ON a.pejabat = p.id
                LEFT JOIN tb_status s ON a.id_status = s.id_status
                WHERE a.tgl_agenda = DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND a.id_status != (SELECT id_status FROM tb_status WHERE nama_status = 'Selesai')
                ORDER BY a.waktu";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();

        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }

        $stmt->close();
        $koneksi->close();

        return $events;
    }

    // Send email notification
    public static function sendEmailNotification($to, $subject, $message, $headers = [])
    {
        $defaultHeaders = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Agenda System <noreply@example.com>',
            'X-Mailer: PHP/' . phpversion()
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        // HTML email template
        $htmlMessage = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4361ee; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .event { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #4361ee; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Notifikasi Agenda Pimpinan</h2>
                </div>
                <div class="content">
                    ' . $message . '
                </div>
                <div class="footer">
                    <p>Email ini dikirim secara otomatis dari Sistem Agenda Pimpinan</p>
                    <p>&copy; ' . date('Y') . ' - Semua hak dilindungi</p>
                </div>
            </div>
        </body>
        </html>';

        return mail($to, $subject, $htmlMessage, implode("\r\n", $allHeaders));
    }

    // Generate notification message for upcoming events
    public static function generateNotificationMessage($events, $daysAhead = 1)
    {
        $dayText = $daysAhead == 0 ? 'hari ini' : ($daysAhead == 1 ? 'besok' : "$daysAhead hari lagi");

        $message = "<h3>Agenda Mendatang ($dayText)</h3>";

        if (empty($events)) {
            $message .= "<p>Tidak ada agenda yang terjadwal.</p>";
        } else {
            $message .= "<p>Berikut agenda yang akan datang:</p>";

            foreach ($events as $event) {
                $time = date('H:i', strtotime($event['waktu']));
                $message .= "
                <div class='event'>
                    <strong>{$event['nama_kegiatan']}</strong><br>
                    <small>Waktu: $time | Tempat: {$event['tempat_kegiatan']}</small><br>
                    <small>Pejabat: {$event['nama_pejabat']} ({$event['nama_jabatan']})</small>
                </div>";
            }
        }

        return $message;
    }

    // Send daily reminder
    public static function sendDailyReminder($recipients)
    {
        $todayEvents = self::getUpcomingEvents(0);
        $tomorrowEvents = self::getUpcomingEvents(1);

        $subject = "Pengingat Agenda Harian - " . date('d/m/Y');

        $message = "";

        if (!empty($todayEvents)) {
            $message .= "<h4>📅 Agenda Hari Ini</h4>";
            foreach ($todayEvents as $event) {
                $time = date('H:i', strtotime($event['waktu']));
                $message .= "
                <div style='margin-bottom: 10px; padding: 10px; background: #e8f4fd; border-radius: 5px;'>
                    <strong>⏰ $time - {$event['nama_kegiatan']}</strong><br>
                    <small>📍 {$event['tempat_kegiatan']} | 👤 {$event['nama_pejabat']}</small>
                </div>";
            }
        }

        if (!empty($tomorrowEvents)) {
            $message .= "<h4>📅 Agenda Besok</h4>";
            foreach ($tomorrowEvents as $event) {
                $time = date('H:i', strtotime($event['waktu']));
                $message .= "
                <div style='margin-bottom: 10px; padding: 10px; background: #fff3cd; border-radius: 5px;'>
                    <strong>⏰ $time - {$event['nama_kegiatan']}</strong><br>
                    <small>📍 {$event['tempat_kegiatan']} | 👤 {$event['nama_pejabat']}</small>
                </div>";
            }
        }

        if (empty($todayEvents) && empty($tomorrowEvents)) {
            $message = "<p>Tidak ada agenda untuk hari ini dan besok.</p>";
        }

        // Add action buttons
        $message .= '
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; text-align: center;">
            <a href="' . self::getBaseUrl() . 'index.php" 
               style="display: inline-block; padding: 10px 20px; background: #4361ee; color: white; text-decoration: none; border-radius: 5px; margin: 5px;">
               📋 Lihat Semua Agenda
            </a>
            <a href="' . self::getBaseUrl() . 'tambah.php" 
               style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 5px;">
               ➕ Tambah Agenda Baru
            </a>
        </div>';

        // Send to all recipients
        $results = [];
        foreach ($recipients as $recipient) {
            $results[$recipient] = self::sendEmailNotification($recipient, $subject, $message);
        }

        return $results;
    }

    // Get base URL
    private static function getBaseUrl()
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);

        return $protocol . $host . $path . '/';
    }

    // Log notification sent
    public static function logNotification($type, $recipient, $status, $details = '')
    {
        include 'koneksi.php';

        $sql = "INSERT INTO tb_notification_log (type, recipient, status, details, sent_at) 
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $koneksi->prepare($sql);
        $stmt->bind_param("ssss", $type, $recipient, $status, $details);
        $stmt->execute();
        $stmt->close();
        $koneksi->close();
    }
}

// Cron job function (to be called by cron)
function runDailyNotifications()
{
    // List of recipients (can be fetched from database)
    $recipients = [
        'admin@example.com',
        'pimpinan@example.com'
    ];

    $results = Notifications::sendDailyReminder($recipients);

    // Log results
    foreach ($results as $recipient => $success) {
        Notifications::logNotification(
            'daily_reminder',
            $recipient,
            $success ? 'sent' : 'failed',
            json_encode(['date' => date('Y-m-d')])
        );
    }

    return $results;
}

// Uncomment to run manually for testing
// runDailyNotifications();
