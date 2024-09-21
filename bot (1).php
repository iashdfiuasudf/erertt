<?php
include "db.php";
include 'functions.php';

session_start();


define("TOKEN_API", "7316231555:AAHbM7z1DdSXZyiKinsgzCUXvZSmwTdqUWs");
define("URL", "https://api.telegram.org/bot7316231555:AAHbM7z1DdSXZyiKinsgzCUXvZSmwTdqUWs");
$token = '7316231555:AAHbM7z1DdSXZyiKinsgzCUXvZSmwTdqUWs';
$ApiURL = "https://api.telegram.org/bot";


$channel_id = "@NUCKLEARBOMBER3";
$adminLink = "https://t.me/NUCKLEARBOMBER3";
$admin_id_username = "fyrox00";


$line = "\r\n";

// get log
$content = file_get_contents("php://input");
$update = json_decode($content, true);
// file_put_contents("json.txt", $content);


//get information
if (array_key_exists('message', $update)) {
    $message = $update['message']['text'];
    $user_id = $update['message']['from']['id'];
    $username = (array_key_exists('username', $update['message']['from'])) ? $update['message']['from']['username'] : null;
    $firstName = $update['message']['chat']['first_name'];
    $chat_id = $update['message']['chat']['id'];
    $messageID = $update['message']['message_id'];

} elseif (array_key_exists('callback_query', $update)) {
    $callback_id = $update['callback_query']['id'];
    $user_id = $update['callback_query']['from']['id'];
    $chat_id = $update['callback_query']['message']['chat']['id'];
    $messageID = $update['callback_query']['message']['message_id'];
    $username = $update['callback_query']['from']['username'];
    $messageCC = $update['callback_query']['data'];

}

//functions

function forwardMessage($FromeChatID, $messageID)
{
    
    $url = "https://api.telegram.org/bot" . TOKEN_API . "/forwardMessage?" . "chat_id=382308351" . "&from_chat_id=" . $FromeChatID . "&message_id=" . $messageID;
    file_get_contents($url);
    // return;
}

function sendMessage($ChatID, $text)
{
    // $mes="مقدار وارد شده نامعتبر میباشد ...!";
    $url = ("https://api.telegram.org/bot" . TOKEN_API . "/sendMessage?" . "chat_id=" . $ChatID . "&text=" . $text);
    file_get_contents($url);
}

function isUserInChannel($chat_id, $messageID)
{   
    //$res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
    
    global $db; // فرض بر این است که اتصال به دیتابیس انجام شده است.
    $sql = "SELECT channel FROM BotStatus";
    $stmt = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($stmt);
    $channel = $res['channel'];

    if ($channel == "0" || empty($channel)) {
        return true;
    }

    // اگر چندین کانال وجود داشته باشد، آنها را به لیست تبدیل می‌کنیم
    $channels = explode("-", $channel);

    // لیست کانال‌هایی که کاربر عضو نیست
    $nonMemberChannels = [];

    foreach ($channels as $channel_id) {
        $url = "https://api.telegram.org/bot" . TOKEN_API . "/getChatMember?chat_id=". $channel_id . "&user_id=" . $chat_id;
        $response = file_get_contents($url);
        $result = json_decode($response, true);

        if (!($result['ok'] && $result['result']['status'] != 'left' &&
        $result['result']['status'] != 'kicked')) {
            $nonMemberChannels[] = $channel_id;
        }
    }

    // اگر کاربر در تمام کانال‌ها عضو باشد
    if (empty($nonMemberChannels)) {
        return true;
    }

    // ایجاد دکمه‌های شیشه‌ای برای کانال‌هایی که کاربر عضو نیست
    $keyboard = [];
    $data_link = "https://t.me/". str_replace('@', '', $channel_id);
    foreach ($nonMemberChannels as $index => $channel_id) {
        $keyboard[] = [
            [
                'text' => "کانال " . ($index + 1) . $channel_id,
                'url' => $data_link
            ]
        ];
    }

    $replyMarkup = [
        'inline_keyboard' => $keyboard
    ];

    $response = [
        'chat_id' => $chat_id,
        'text' => "لطفاً در کانال‌های زیر عضو شوید:",
        'reply_markup' => json_encode($replyMarkup)
    ];

    // ارسال پیام با استفاده از Telegram API
    file_get_contents("https://api.telegram.org/bot" . TOKEN_API . "/sendMessage?" . http_build_query($response));

    return false;
}



function check_regex_username($userid)
{
    if (strpos($userid, '@') === 0)
    {
        return false;
    } else {
        return true;
    }
}

function isUserBanned($user_id)
{
    global $db;
    $sql = "SELECT Ban FROM users WHERE user_id=". $user_id;
    $result = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($result);
    if ($res['Ban'] == 1)
    {
        return true;
    } else {
        return false;
    }
}
function banUser($user_id, $chat_id)
{
    global $db;
    $line = "\r\n";
    $sql = "SELECT user_id FROM users WHERE user_id=" . $user_id;
    $result = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($result);
    if ($res['user_id'])
    {
        $sql = "UPDATE users set Ban= 1 WHERE user_id = '$user_id'";
        mysqli_query($db, $sql);
        sendMessage($chat_id, "کاربر $user_id مسدود شد.");
    } else {
        $text = "کاربری با ایدی زیر وجود ندارد :". $line ."user id : $user_id";
        $res = file_get_contents("https://api.telegram.org/bot" . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text));
        exit();
    }
    
}

function unbanUser($user_id, $chat_id)
{
    global $db;
    $line = "\r\n";
    $sql = "SELECT user_id FROM users WHERE user_id=" . $user_id;
    $result = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($result);
    if ($res['user_id'])
    {
        $sql = "UPDATE users set Ban= 0 WHERE user_id = '$user_id'";
        mysqli_query($db, $sql);
        sendMessage($chat_id, "کاربر $user_id از لیست مسدودها خارج شد");
    } else {
        $text = "کاربری با ایدی زیر وجود ندارد :". $line ."user id : $user_id";
        $res = file_get_contents("https://api.telegram.org/bot" . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text));
        exit();
    }
}
function check_on_off_bot()
{
    global $db;
    $sql = "SELECT status FROM BotStatus";
    $result = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($result);
    if ($res['status'] == 1)
    {
        return true;
    } elseif ($res['status'] == 0)
    {
        return false;
    } else {
        return false;
    }
}


// $markupJoin = [
//     'inline_keyboard' => [
//         [
//             ['text' => 'عضویت در کانال ما', 'url' => 'https://t.me/NUCKLEARBOMBER3'] // Example inline keyboard button
//         ]
//     ]
// ];


// keyboard.append([InlineKeyboardButton(f"{b[0]}", callback_data=f"info-{b[0]}"), InlineKeyboardButton(
//     "🌐 کانال 🌐", callback_data=f"channel-{b[0]}"), InlineKeyboardButton(
//         "❌ حذف اکانت", callback_data=f"deleteACC-{b[0]}"
//     )])
// if len(botdata) >= 2:
//     keyboard.append([InlineKeyboardButton("بازگشت یه منو", callback_data="HOME")])
// else:
//     pass
// inikey = InlineKeyboardMarkup(keyboard)
function show_channel() {
    global $db;
    $keyboard = ['inline_keyboard' => []];
    $sql = "SELECT channel FROM BotStatus";
    $res = mysqli_query($db, $sql);
    $res = mysqli_fetch_assoc($res);
    $channels = $res['channel'];

    if ($channels != '0') {
        $channelsArray = explode('-', $channels);

        foreach ($channelsArray as $channel) {
            $data1 = ['text' => $channel, 'callback_data' => 'noun'];
            $data2 = ['text' => '❌ Remove ❌', 'callback_data' => "rmCH-$channel"];
            $keyboard['inline_keyboard'][] = [$data1, $data2];
        }
    }

    return $keyboard;
}
//-----------------------  -------------------
function getStep($user_id)
{
    global $db;
    $query = "select step from users WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    return $res['step'];
}

function setStep($user_id, $step)
{
    global $db;
    $query = "update users set step='" . $step . "' WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    return $res;
}

//-----------------------  -------------------
function getSMS($user_id)
{
    global $db;
    $query = "select sms from users WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    return $res['sms'];
}

function setSMS($user_id, $sms)
{
    global $db;
    $query = "update users set sms='" . $sms . "' WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    return $res;
}
//-----------------------  -------------------
function getgiftCode($user_id)
{
    global $db;
    $query = "select giftCode from users WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    return $res['giftCode'];
}

function setgiftCode($code)
{
    global $db;
    $query = "UPDATE users SET giftCode='$code'";
    $res = mysqli_query($db, $query);
    return $res;
}

//-----------------------  -------------------
function getSetPhone($user_id)
{
    global $db;
    $query = "select setphone from users WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    return $res['setphone'];
}

function setSetphone($user_id, $code)
{
    global $db;
    $query = "update users set setphone='" . $code . "' WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    return $res;
}
//-----------------------  -------------------
function getgiftUse($user_id)
{
    global $db;
    $query = "select giftUse from users WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    return $res['giftUse'];
}

function setgiftUse($user_id, $messageID)
{
    global $db;
    $query = "update users set giftUse='" . $messageID . "' WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    return $res;
}
function getlimSMS($user_id)
{
    global $db;
    $query = "select limSMS from users WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    return $res['limSMS'];
}

function setlimSMS($user_id, $messageID)
{
    global $db;
    $query = "update users set limSMS='" . $messageID . "' WHERE user_id=" . $user_id;
    $res = mysqli_query($db, $query);
    return $res;
}

// keyboards

// back
$markupBack = array(
    'keyboard' =>
        array(array("بازگشت...")),
    'resize_keyboard' => true
);
// index
$markupStart = array(
    'keyboard' =>
        array(array("🔌 ارسال پیامک"), array("🗄 پروفایل حساب شما", "💰 خرید اشتراک 💰"), array("📞 پشتیبانی", "🎁 کد هدیه")),
    'resize_keyboard' => true
);
// index Admin
$markupPAndmin = array(
    'keyboard' =>
        array(array("اضافه کردن اشتراک🔗", "🎁هدیه یک روزه"), array("لیست همه یوزر ها", " پیام همگانی"), array("نمایش کانال ها")),
    'resize_keyboard' => true
);
//join


$markupJoin = [
    'inline_keyboard' => [
        [
            ['text' => 'عضویت در کانال ما', 'url' => 'https://t.me/NUCKLEARBOMBER3'] // Example inline keyboard button
        ]
    ]
];

//----------visit channel

$markupchannel = [
    'inline_keyboard' => [
        [
            ['text' => 'مشاهده کانال', 'url' => 'https://t.me/NUCKLEARBOMBER3'] // Example inline keyboard button
        ]
    ]
];
//----------visit Admin

$markupAdmin = [
    'inline_keyboard' => [
        [
            ['text' => 'پیام به ادمین', 'url' => 'https://t.me/fyrox00 '] // Example inline keyboard button
        ]
    ]
];
//----------visit Admin

$markupYesNoSend = [
    'inline_keyboard' => [
        [
            ['text' => 'ارسال شود✅', 'callback_data' => 'YesAccept'],
            // Example inline keyboard button
            ['text' => 'لغو🚫', 'callback_data' => 'NoReject'] // Example inline keyboard button
        ]
    ]
];
$markupDone = [
    'inline_keyboard' => [
        [
            ['text' => '✅', 'callback_data' => 'done']
            // Example inline keyboard button
            // Example inline keyboard button
        ]
    ]
];
$markupReject = [
    'inline_keyboard' => [
        [
            ['text' => '🚫', 'callback_data' => 'reject']
            // Example inline keyboard button
            // Example inline keyboard button
        ]
    ]

];

//---------------------------
$markupLimit = [
    'inline_keyboard' => [
        [
            ['text' => 'تعداد دلخواه ', 'callback_data' => 'limiteds'],
            ['text' => 'نامحدود', 'callback_data' => 'unlimited']

        ]
    ]

];

$markupInBack = [
    'inline_keyboard' => [
        [
            ['text' => 'مرحله قبلی', 'callback_data' => 'goBack']
            // ['text' => 'نامحدود', 'callback_data' => 'unlimited']

        ]
    ]

];

// $sql = "SELECT status FROM BotStatus";
//     $result = mysqli_query($db, $sql);
//     $res = mysqli_fetch_assoc($result);
//     if ($res['status'] == 1)

if (strpos($message, '/addChanel-' && $username == "fyrox00") === 0) {
    if (strpos($message, 'https://t.me/') === 11) {
        global $list_channels;
        $idChanel = str_replace('/addChanel-https://t.me/', '@', $message);

        $query = "SELECT channel FROM BotStatus";
        $res = mysqli_query($db, $query);
        $res = mysqli_fetch_assoc($res);
        $channels = $res['channel'];

        if ($channels == '0') {
            // اگر لیست کانال‌ها خالی است، فقط کانال جدید را اضافه کن
            $sql = "UPDATE BotStatus SET channel = '$idChanel'";
            mysqli_query($db, $sql);
            $keyboard = show_channel();
            sendMessage($chat_id, "✅ $idChanel در لیست اضافه شد");
        } else {
            // بررسی اگر کانال از قبل موجود است یا خیر
            $exist = false;

            // چک کردن وجود کانال در موقعیت‌های مختلف
            if (strpos($channels, "-$idChanel") !== false || strpos($channels, "$idChanel-") !== false || strpos($channels, $idChanel) !== false) {
                $exist = true;
            }

            if ($exist) {
                sendMessage($chat_id, "❌ ایدی وارد شده در لیست وجود دارد");
            } else {
                // اضافه کردن کانال جدید به لیست کانال‌ها
                $newChannels = $channels . '-' . $idChanel;
                $sql = "UPDATE BotStatus SET channel = '$newChannels'";
                mysqli_query($db, $sql);
                sendMessage($chat_id, "✅ $idChanel به لیست کانال‌ها اضافه شد");
            }
        }

        // نمایش لیست کانال‌ها
        $text = "لیست کانال‌های موجود✅" . $line . "ــــــــــــــــــــــــــــ" . $line . "همچنین میتوانید از دستورات زیر برای حذف و اضافه کردن کانال‌ها استفاده کنید:" . $line . "/addChanel-https://t.me/NUCKLEARBOMBER3" . $line . "/rmChanel-https://t.me/NUCKLEARBOMBER3";
        $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($keyboard) . "&reply_to_message_id=" . $messageID);
        exit();

    } else {
        sendMessage($chat_id, "❌ لینک کانال ارسال شده باید به شکل زیر باشد: /addChanel-https://t.me/NUCKLEARBOMBER3");
        exit();
    }
}

if (strpos($message, '/rmChanel-' && $username == "fyrox00") === 0) {
    if (strpos($message, 'https://t.me/') === 10) {
        $idChanel = str_replace('/rmChanel-https://t.me/', '@', $message);

        $query = "SELECT channel FROM BotStatus";
        $res = mysqli_query($db, $query);
        $res = mysqli_fetch_assoc($res);
        $channels = $res['channel'];

        if ($channels != '0') {
            $channelsArray = explode('-', $channels);

            // حذف کانال از لیست
            if (in_array($idChanel, $channelsArray)) {
                $channelsArray = array_diff($channelsArray, [$idChanel]);
                $newChannels = count($channelsArray) > 0 ? implode('-', $channelsArray) : '0';
                $sql = "UPDATE BotStatus SET channel = '$newChannels'";
                mysqli_query($db, $sql);
                sendMessage($chat_id, "✅ $idChanel از لیست کانال‌ها حذف شد");
            } else {
                sendMessage($chat_id, "❌ چنلی با ایدی $idChanel وجود ندارد");
            }

            $keyboard = show_channel();
            $text = "لیست کانال‌های موجود✅" . $line . "ــــــــــــــــــــــــــــ" . $line . "همچنین میتوانید از دستورات زیر برای حذف و اضافه کردن کانال‌ها استفاده کنید:" . $line . "/addChanel-https://t.me/NUCKLEARBOMBER3" . $line . "/rmChanel-https://t.me/NUCKLEARBOMBER3";
            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($keyboard) . "&reply_to_message_id=" . $messageID);
            exit();
        } else {
            sendMessage($chat_id, "❌ هیچ چنلی برای حذف وجود ندارد");
            exit();
        }
    } else {
        sendMessage($chat_id, "❌ لینک کانال ارسال شده باید به شکل زیر باشد: /rmChanel-https://t.me/NUCKLEARBOMBER3");
        exit();
    }
}

if($message == "/offbot" && $username == "fyrox00") {
    $sql = "UPDATE BotStatus SET status = 1";
    mysqli_query($db, $sql);
    sendMessage($chat_id, "ربات غیرفعال شد . فقط کاربر @fyrox00 میتواند با ربات ارتباط برقرار کند .");
} elseif ($message == "/onbot" && $username == "fyrox00") {
    $sql = "UPDATE BotStatus SET status = 0";
    mysqli_query($db, $sql);
    sendMessage($chat_id, "ربات فعال شد اکنون تمامی کاربران میتوانند با ربات ارتباط برقرار کنند .");
}

if (check_on_off_bot()) {
    exit();
}

if ($message == "نمایش کانال ها" && $username == "fyrox00")
{
    $keyboard = show_channel();
    $text = "لیست کانال های موجود✅" . $line . "ــــــــــــــــــــــــــــ" . $line . "همچنین میتوانید از دستورات زیر برای حذف و اضافه کردن کانال ها استفاده کنید :" . $line . "/addChanel-https://t.me/NUCKLEARBOMBER3" . $line . "/rmChanel-https://t.me/NUCKLEARBOMBER3";

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($keyboard) . "&reply_to_message_id=" . $messageID);

    exit();
}
// Start
$step = getStep($user_id);
if ($message == "/start") {
    if (check_on_off_bot()) {
        exit();
    } else {
        if(isUserBanned($user_id))
        {
            exit();
        } else
        {
            if(!isUserInChannel($user_id, $messageID))
            {
                $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
                exit();
                //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
            } elseif (isUserInChannel($user_id, $messageID))
            {

                $query = "select * from users WHERE user_id=" . $user_id;
                $res = mysqli_query($db, $query);
                $num = mysqli_num_rows($res);

                if ($num == 0) {
                    $query = "insert into users(user_id,username,name) VALUES(" . $user_id . ",'" . $username . "','" . $firstName . "')";
                    $res = mysqli_query($db, $query);
                    setStep($user_id, 'home');

                    $text = "خیلی خوش اومدی رفیق😃" . $line . "برای استفاده از خدمات اس ام اس بمبر ربات از کلید های زیر استفاده کنید✅" . $line . $line . "👇🏻👇🏻👇🏻👇🏻";
                    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupStart));

                } else {
                    setStep($user_id, 'home');

                    $text = "خیلی خوش اومدی رفیق😃" . $line . "برای استفاده از خدمات اس ام اس بمبر ربات از کلید های زیر استفاده کنید✅" . $line . $line . "👇🏻👇🏻👇🏻👇🏻";
                    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupStart));

                }
            }
        }
    }
} elseif ($message != "") {
    if (check_on_off_bot()) {
        exit();
    } else {
        // sendMessage($chat_id,$user_id);
        // $res = json_decode(file_get_contents($ApiURL . TOKEN_API . "/getChatMember?chat_id=" . $channel_id . "&user_id=" . $user_id), true);
        $res = file_get_contents($ApiURL . TOKEN_API . "/getChatMember?chat_id=" . $channel_id . "&user_id=" . $user_id);
        file_put_contents("status1.txt", $res);
        $res = json_decode($res, true);

        $status = $res['result']['status'];
        $channel_link = "https://t.me/smsboombertest";
        if(isUserBanned($user_id))
        {
            exit();
        } else
        {
            if(!isUserInChannel($user_id, $messageID))
            {
                $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
                exit();
                //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
            }
            /*
            if (!($status == 'member' || $status == 'creator' || $status == 'administrator')) {

                $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
                exit();
            }
            */
        }
    }
}


if(strpos($message, '/ban-') === 0 && $username == "fyrox00") {
    $userIdToBan = str_replace('/ban-', '', $message);
    if(check_regex_username($userIdToBan))
    {
        banUser($userIdToBan, $chat_id);
    } else {
        $text = "فرمت ایدی وارد شده صحیح نمیباشد و باید به شکل زیر وارد نمایید.". $line . "/ban-55698722";
        $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_to_message_id=" . $messageID);
        exit();
    }

}

if(strpos($message, '/unban-') === 0 && $username == "fyrox00") {
    $userIdToBan = str_replace('/unban-', '', $message);
    if(check_regex_username($userIdToBan))
    {
        unbanUser($userIdToBan, $chat_id);
    } else {
        $text = "فرمت ایدی وارد شده صحیح نمیباشد و باید به شکل زیر وارد نمایید.". $line . "/ban-55698722";
        $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_to_message_id=" . $messageID);
        exit();
    };
}

//----------------------- -----------
$step = getStep($user_id);

if ($message == '🗄 پروفایل حساب شما' && $step == 'home') {
    if (check_on_off_bot()) {
        exit();
    } else {
        if(isUserBanned($user_id))
        {
            exit();
        } else
        {
            if(!isUserInChannel($user_id, $messageID))
            {
                $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
                exit();
                //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
            } elseif (isUserInChannel($user_id, $messageID))
            {
                $id = $user_id;
                $Fname = $firstName;
                $Uname = $username;
                $iSMS = getSMS($user_id);
                if ($iSMS > 1000) {
                    $iSMS = 'نامحدود⭐️';
                }
                //------
                $text = '👤مشخصات اکانت شما 👤' . $line . $line . '🆔 آیدی عددی:' . $id . $line . '🫵🏼 نام:' . $Fname . $line . '👨🏻‍💻یوزرنیم:' . $Uname . $line . $line . '📨 تعداد ارسال پیام(اشتراک) :' . $iSMS;

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupchannel) . "&reply_to_message_id=" . $messageID);
            }
        }
    }
}

//------------------------  --------
$step = getStep($user_id);

if ($message == '📞 پشتیبانی' && $step == 'home') {
    if (check_on_off_bot()) {
        exit();
    } else {
        if(isUserBanned($user_id))
        {
            exit();
        } else
        {
            if(!isUserInChannel($user_id, $messageID))
            {
                $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
                exit();
                //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
            } elseif (isUserInChannel($user_id, $messageID))
            {
                $text = 'برای رفع مشکلات,انتقادات و پرسیدن سوالات خودتون میتونید به پشتیبانی پیام بدین تا به صورت کامل شمارو راهنمایی کنند✅' . $line . $line . '👨🏻‍💻آیدی پشتیبانی: @fyrox00 ';

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupAdmin) . "&reply_to_message_id=" . $messageID);
            }
        }
    }
}
//------------------------  --------
$step = getStep($user_id);

if ($message == '💰 خرید اشتراک 💰' && $step == 'home') {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif (isUserInChannel($user_id, $messageID))
        {

            $text = 'جهت مشاوره و  خرید اشتراک اس ام اس ببمر ( SMS Boomber) به آیدی ادمین پیام بدین 💰' . $line . $line . '👨🏻‍💻آیدی پشتیبانی: @fyrox00 ';

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupAdmin) . "&reply_to_message_id=" . $messageID);
        }
    }
}
//------------------------  --------
$step = getStep($user_id);
$code = getgiftCode($user_id);
if ($message == '🎁 کد هدیه' && $step == 'home' && $code == null) {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif (isUserInChannel($user_id, $messageID))
        {
            $text = 'متاسفانه کد تخفیفی برای امروز ندارید🚫';

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_to_message_id=" . $messageID);
        }
    }
} elseif ($message == '🎁 کد هدیه' && $step == 'home' && $code != null) {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif (isUserInChannel($user_id, $messageID))
        {
            $text = '🥳 تبریک 🥳' . $line . $line . 'کد هدیه امروز شما: ' . $code . $line . $line . 'منتظر کد تخفیف بعدی باش🫵🏼';

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_to_message_id=" . $messageID);

        }
    }
}

$useLimit = getgiftUse($user_id);
if ($message == $code && $step == 'home' && $useLimit == 'true') {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif (isUserInChannel($user_id, $messageID))
        {
            $limited = getSMS($user_id);
            $limited = $limited + 1;
            setSMS($user_id, $limited);
            sendMessage($chat_id, 'کد هدیه شما ثبت شد و میتونید ازش استفاده کنید');

            setgiftUse($user_id, 'false');
        }
    }
} elseif ($message == $code && $step == 'home' && $useLimit == 'false') {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif (isUserInChannel($user_id, $messageID))
        {
            sendMessage($chat_id, 'شما از کد تخفیف خود استفاده کردین.');
        }
    }
}

//--------------------send spam STEP(SET PHONE)-------------------//

$step = getStep($user_id);
$limited = getSMS($user_id);
if ($message == '🔌 ارسال پیامک' && $step == 'home' && $limited > 0) {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif(isUserInChannel($user_id, $messageID))
        {
            $text = '📱لطفا شماره هدف مورد نظر رو با (09) وارد کنید:';

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupBack) . "&reply_to_message_id=" . $messageID);
            setStep($user_id, 'SetPhone');
        }
    }
} elseif ($message == '🔌 ارسال پیامک' && $step == 'home' && $limited <= 0) {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif(isUserInChannel($user_id, $messageID))
        {
            sendMessage($chat_id, " متاسفانه تعداد اشتراک های شما به پایان رسیده است🚫");
        }
    }
}

if ($message == "بازگشت..." && $step == 'SetPhone') {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif(isUserInChannel($user_id, $messageID))
        {
            setStep($user_id, 'home');

            $text = 'صفحه اصلی...';

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupStart) . "&reply_to_message_id=" . $messageID);

        }
    }
}
//--------------------send spam STEP(ANALIZ PHONE)-------------------//
function getFirstTwoCharacters($string)
{
    return substr($string, 0, 2);
}

function countCharacters($string)
{
    return strlen($string);
}

$step = getStep($user_id);

if ($message != "" && $step == 'SetPhone') {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif(isUserInChannel($user_id, $messageID))
        {

            $firstLphone = getFirstTwoCharacters($message);
            $character = countCharacters($message);

            if ($firstLphone != '09' || $character < 11 || $character > 11) {
                sendMessage($chat_id, 'لطفا شماره رو به درستی وارد کنید.');
            } else {

                setSetphone($user_id, $message);

                $text = 'ایا تایید میکنید که پیام ارسال شود؟⚠️';

                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupYesNoSend) . "&reply_to_message_id=" . $messageID);

                setStep($user_id, 'home');
                $text = 'درصورت تایید ارسال پیامک ها لطفا 2دقیقه صبر کنید و بعد از خدمات زیر استفاده کنید';
                $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupStart));
                // setMessageIDsend($user_id, $messageID - 1);
            }

        }
    }
}
//$step = getStep($user_id);
setStep($user_id, 'home');
if (explode('-', $messageCC)[0] == "rmCH" && $username == "fyrox00") {
    $idChanel = explode('-', $messageCC)[1];

    $query = "SELECT channel FROM BotStatus";
    $res = mysqli_query($db, $query);
    $res = mysqli_fetch_assoc($res);
    $channels = $res['channel'];

    if ($channels != '0') {
        $channelsArray = explode('-', $channels);

        if (in_array($idChanel, $channelsArray)) {
            $channelsArray = array_diff($channelsArray, [$idChanel]);
            $newChannels = count($channelsArray) > 0 ? implode('-', $channelsArray) : '0';
            $sql = "UPDATE BotStatus SET channel = '$newChannels'";
            mysqli_query($db, $sql);
            sendMessage($chat_id, "✅ $idChanel از لیست کانال‌ها حذف شد");
        } else {
            sendMessage($chat_id, "❌ چنلی با ایدی $idChanel وجود ندارد");
        }

        $keyboard = show_channel();
        $text = "لیست کانال‌های موجود✅" . $line . "ــــــــــــــــــــــــــــ" . $line . "همچنین میتوانید از دستورات زیر برای حذف و اضافه کردن کانال‌ها استفاده کنید:" . $line . "/addChanel-https://t.me/NUCKLEARBOMBER3" . $line . "/rmChanel-https://t.me/NUCKLEARBOMBER3";
        $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($keyboard) . "&reply_to_message_id=" . $messageID);
        exit();
    } else {
        sendMessage($chat_id, "❌ هیچ چنلی برای حذف وجود ندارد");
        exit();
    }
}

$step = getStep($user_id);
// $messidyes = getMessageIDsend($user_id);
$limited = getSMS($user_id);
if ($messageCC == "YesAccept" && $step == 'home' && $limited > 0) {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        }elseif(isUserInChannel($user_id, $messageID))
        {
            // sendMessage($chat_id, $messageID);
            // $messageID-=1;
            $limited = $limited - 1;
            setSMS($user_id, $limited);
            $phone = getSetPhone($user_id);
            $res = file_get_contents($ApiURL . TOKEN_API . "/editMessageText?" . "chat_id=" . $chat_id . "&text= " . urlencode("انجام شد") . "&reply_markup=" . json_encode($markupDone) . "&message_id=" . $messageID);
            all($phone);
        }
    }

} elseif ($messageCC == "NoReject" && $step == 'home') {
    if(isUserBanned($user_id))
    {
        exit();
    } else
    {
        if(!isUserInChannel($user_id, $messageID))
        {
            $text = "دوست عزیز ⚠️" . $line . "برای استفاده از خدمات ربات ابتدا در کانال ما عضو بشید ✅" . $line . "و بعد مجدد تلاش کنید👍🏻" . $line . "ــــــــــــــــــــــــــــ" . $line . $line . "💻کانال ما : @NUCKLEARBOMBER3";

            $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode($text) . "&reply_markup=" . json_encode($markupJoin) . "&reply_to_message_id=" . $messageID);
            exit();
            //sendMessage($chat_id, 'براس استفاده از خدمات ربات باید عضو کانال زیر شوید و سپس ربات را استارت کنید' . $channel_id);
        } elseif (isUserInChannel($user_id, $messageID))
        {

            $res = file_get_contents($ApiURL . TOKEN_API . "/editMessageText?" . "chat_id=" . $chat_id . "&text= " . urlencode("لغو شد") . "&reply_markup=" . json_encode($markupReject) . "&message_id=" . $messageID);
        }
    }
}





////////-------------------------------------------------------/////////////-----ADMIN PANEL---------------////////////----------------------------------------------//////////////////////////



$Adminuser = 'Padmin_@login9';
$Adminpass = 'AcceptPlogin';
$step = getStep($user_id);

if ($message == $Adminuser && $step == 'home') {

    sendMessage($chat_id, 'رمز رو وارد کنید:');
    setStep($user_id, 'passAdmin');
    exit();
}
if ($message == $Adminpass && $step == 'passAdmin') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode("منوی ادمین باز شد") . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
} elseif ($message != $Adminpass && $step == 'passAdmin') {
    sendMessage($chat_id, 'منوی اصلی');
    setStep($user_id, 'home');
}
if ($message == 'خروج' && $step == 'admin') {
    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode("منوی اصلی") . "&reply_markup=" . json_encode($markupStart) . "&message_id=" . $messageID);
    setStep($user_id, 'home');
}
if ($message == '🎁هدیه یک روزه' && $step == 'admin') {
    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode("کد هدیه یک روزه رو وارد کنید:") . "&reply_markup=" . json_encode($markupBack) . "&message_id=" . $messageID);
    setStep($user_id, 'setCode');
}
if ($message == "بازگشت..." && $step == 'setCode') {
    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode("منوی ادمین") . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
} elseif ($message != "بازگشت..." && $step == 'setCode') {

    setgiftCode($message);
    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode("کد هدیه یک روزه ثبت شد") . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
    setgiftUse($user_id, 'true');
}

//////---------------------------send Primium=============

$step = getStep($user_id);
if ($message == 'اضافه کردن اشتراک🔗' && $step == 'admin') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= بین گزینه های زیر انتخاب کن اشتراک به تعداد دلخواه باشه یا به صورت نامحدود و داعمی :👌" . "&reply_markup=" . json_encode($markupLimit) . "&message_id=" . $messageID);

}
if ($messageCC == 'unlimited' && $step == 'admin') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=چت آیدی کاربر رو وارد کن :" . "&reply_markup=" . json_encode($markupBack) . "&message_id=" . $messageID);
    setStep($user_id, 'SetChatID1');
    exit();
}
if ($message == "بازگشت..." && $step == 'SetChatID1') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=منوی ادمین" . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
}
if ($message != "بازگشت..." && $step == 'SetChatID1') {
    $li = 5000;
    setSMS($message, $li);
    sendMessage($message, 'اکانت شما به صورت نامحدود شارژ شد👌');
    sendMessage($user_id, 'اکانت کاربر نامحدود شد');

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=منوی ادمین" . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
    exit();
}
//----------2------------//
if ($messageCC == 'limiteds' && $step == 'admin') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=تعداد اشتراک ارسال پیام برای کاربر رو به دلخواه وارد کنید(مثال : 100)" . "&reply_markup=" . json_encode($markupBack) . "&message_id=" . $messageID);
    setStep($user_id, 'SetLim2');
    // setlimSMS($user_id,$message);
    exit();
}
if ($message == "بازگشت..." && $step == 'SetLim2') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=منوی ادمین" . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
}
if ($message != "بازگشت..." && $step == 'SetLim2') {
    setlimSMS($user_id, $message);
    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= مقدار چت آیدی کاربر رو وارد کن(در این جا دقت کنید به درستی وارد کنید)" . "&reply_markup=" . json_encode($markupBack) . "&message_id=" . $messageID);

    setStep($user_id, 'SetChatID2');
    exit();
}
if ($message == "بازگشت..." && $step == 'SetChatID2') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=منوی ادمین" . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
}
if ($message != "بازگشت..." && $step == 'SetChatID2') {

    $sms = getSMS($message);
    $Limsms = getlimSMS($user_id);
    $Limsms = $Limsms + $sms;
    setSMS($message, $Limsms);
    sendMessage($message, 'اشتراک اکانت شما بروزرسانی شد');
    sendMessage($user_id, 'اشتراک اکانت کاربر بروزرسانی شد');

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text=منوی ادمین" . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
}




//////---------------------------view Users=============
$step = getStep($user_id);
if ($message == 'لیست همه یوزر ها' && $step == 'admin') {
    # coe...

    global $db;
    // $sql = "SELECT user_id, username, name , sms FROM users";
    // $result = mysqli_query($db, $sql);

    // // بررسی و نمایش نتایج
    // if (mysqli_num_rows($result) > 0) {
    //     // خروجی داده هر ردیف
    //     while ($row = mysqli_fetch_assoc($result)) {
    //         $users = $users . $line . $line . "ID: " . $row["user_id"] . " - UserName: " . $row["username"] . " - Name: " . $row["name"] . " - Limited SMS: " . $row["sms"] . $line;
    //         sendMessage($chat_id, urlencode($users));
    //     }
    // }

    $sql = "SELECT COUNT(*) as total_users FROM users";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_users = $row['total_users'];
        sendMessage($chat_id, "تعداد کاربران: " . $total_users);
    } else {
        sendMessage($chat_id, "Error: " . mysqli_error($db));

    }
    $sql = "SELECT COUNT(*) as total_users FROM users  WHERE sms >= 2000";
    $result = mysqli_query($db, $sql);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_users = $row['total_users'];
        sendMessage($chat_id, "تعداد کاربران نامحدود: " . $total_users);
    } else {
        sendMessage($chat_id, "Error: " . mysqli_error($db));

    }
}
//////---------------------------view Users=============
// پیام ارسالی
$step = getStep($user_id);
if ($message == 'پیام همگانی' && $step == 'admin') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode("لطفا پیام مورد نظر خودتون رو تایب کنید: (از صحت جمله خود اطمینان حاصل کنید)") . "&reply_markup=" . json_encode($markupBack) . "&message_id=" . $messageID);
    setStep($user_id, 'setMess');
    exit();

}
if ($message == "بازگشت..." && $step == 'setMess') {

    $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode('منوی ادمین') . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
    setStep($user_id, 'admin');
    exit();

}
$step = getStep($user_id);
if ($message != "بازگشت..." && $step == 'setMess') {


//     function sendMessage2($chat_id, $message)
//     {
    
//         define('BOT_TOKEN', '7316231555:AAHbM7z1DdSXZyiKinsgzCUXvZSmwTdqUWs');
//         $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
//         $data = [
//             'chat_id' => $chat_id,
//             'text' => $message
//         ];
//         $options = [
//             'http' => [
//                 'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
//                 'method' => 'POST',
//                 'content' => http_build_query($data),
//             ],
//         ];
//         $context = stream_context_create($options);
//         file_get_contents($url, false, $context);
//     }

//     $sql = "SELECT COUNT(*) as count FROM users";
//     $result = mysqli_query($db, $sql);
//     $row = mysqli_fetch_assoc($result);
//     $totalUsers = $row['count'];

//     global $db;
//     $sql = "SELECT user_id FROM users";
//     $result = mysqli_query($db, $sql);

// if ($totalUsers > 0) 
//     for ($i = 0; $i < $totalUsers; $i++) {
//         mysqli_data_seek($result, $i);
//         $row = mysqli_fetch_assoc($result);
//         $chat_idx = $row['user_id'];
//         sendMessage2($chat_idx, $message);
//     }
//             sendMessage($chat_id, "پیام به تمامی کاربران ارسال شد.");
    
//             $res = file_get_contents($ApiURL . TOKEN_API . "/sendMessage?" . "chat_id=" . $chat_id . "&text= " . urlencode('منوی ادمین') . "&reply_markup=" . json_encode($markupPAndmin) . "&message_id=" . $messageID);
//             setStep($user_id, 'admin');

//     // بررسی و ارسال پیام به تمامی کاربران


//         define('BOT_TOKEN', '7316231555:AAHbM7z1DdSXZyiKinsgzCUXvZSmwTdqUWs');
//         $apiURLs = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
//         if (mysqli_num_rows($result) > 0) {
//             for ($i = 0; $i < mysqli_num_rows($result); $i++) {
//                 $row = mysqli_fetch_assoc($result);
//                 $chat_idx = $row['user_id'];
//                 sendMessage($chat_idx, $message);
//             }
//     }

}





