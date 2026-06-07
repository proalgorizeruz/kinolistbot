<?php
ob_start();

// --- 1. SOZLAMALAR ---
define('API_TOKEN', '8629680753:AAHbUQvhOQAUuaoSZRSZCvuu5Ahd7hpYg3U'); // <--- BotFatherdan olingan token
define('ADMIN_ID', '8588919185'); // <--- O'z ID raqamingizni yozing
$kanal_username = "@kinolistuz"; 

// --- 2. ASOSIY FUNKSIYALAR ---
function bot($method, $datas = []) {
    $url = "https://api.telegram.org/bot" . API_TOKEN . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res);
}

function isMember($channel, $uid) {
    $res = bot('getChatMember', ['chat_id' => $channel, 'user_id' => $uid]);
    $status = $res->result->status ?? null;
    return in_array($status, ['creator', 'administrator', 'member']);
}

// --- 3. YANGILANISHLAR ---
$update = json_decode(file_get_contents('php://input'));
if (!$update) exit;

$message = $update->message;
$cid = $message->chat->id ?? $update->callback_query->message->chat->id;
$text = $message->text;

if (!is_dir("step")) mkdir("step");
$step_file = "step/$cid.txt";
$step = file_exists($step_file) ? file_get_contents($step_file) : null;

// --- 4. MANTIQ ---
if ($text == "/start") {
    $baza = file_exists("statistika.txt") ? file_get_contents("statistika.txt") : "";
    if (mb_stripos($baza, (string)$cid) === false) {
        file_put_contents("statistika.txt", $baza . $cid . "\n", FILE_APPEND);
    }

    if (!isMember($kanal_username, $cid)) {
        bot('sendMessage', [
            'chat_id' => $cid,
            'text' => "Botdan foydalanish uchun kanalimizga obuna bo'ling!",
            'reply_markup' => json_encode(['inline_keyboard' => [[['text' => "Obuna bo'lish", 'url' => "https://t.me/" . str_replace('@', '', $kanal_username)]]]])
        ]);
    } else {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "Salom! Kino kodini yozing."]);
    }
}

// Kino kodini qidirish (Agar son bo'lsa)
if (is_numeric($text)) {
    if (isMember($kanal_username, $cid)) {
        // Eslatma: Bu yerda fayl manzili to'g'ri ekanligiga ishonch hosil qiling
        bot('sendMessage', ['chat_id' => $cid, 'text' => "Kino qidirilmoqda..."]); 
    } else {
        bot('sendMessage', ['chat_id' => $cid, 'text' => "Kanalga obuna bo'ling!"]);
    }
}

// Admin Panel
if ($text == "/panel" && $cid == ADMIN_ID) {
    bot('sendMessage', [
        'chat_id' => $cid,
        'text' => "Admin panel:",
        'reply_markup' => json_encode(['keyboard' => [[['text' => "📊 Statistika"], ['text' => "📢 Xabar yuborish"]]], 'resize_keyboard' => true])
    ]);
}

if ($text == "📊 Statistika" && $cid == ADMIN_ID) {
    $stat = file_exists("statistika.txt") ? count(file("statistika.txt")) : 0;
    bot('sendMessage', ['chat_id' => $cid, 'text' => "Bot a'zolari: $stat ta"]);
}

if ($text == "📢 Xabar yuborish" && $cid == ADMIN_ID) {
    file_put_contents($step_file, "send_all");
    bot('sendMessage', ['chat_id' => $cid, 'text' => "Yuboriladigan xabarni kiriting:"]);
}

if ($step == "send_all" && $cid == ADMIN_ID) {
    $users = file("statistika.txt", FILE_IGNORE_NEW_LINES);
    foreach ($users as $user_id) {
        bot('sendMessage', ['chat_id' => $user_id, 'text' => $text]);
    }
    bot('sendMessage', ['chat_id' => $cid, 'text' => "Xabar yuborildi!"]);
    unlink($step_file);
}
?>