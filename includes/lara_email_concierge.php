<?php
/**
 * Lara — Direct Email Inbox Concierge & Executive Quotation Engine (`info@seacollegediveclub.com`).
 *
 * Connects directly via pure-PHP TLS sockets to:
 *   - Inbound IMAPS (`ssl://imap.hostinger.com:993`)
 *   - Outbound SMTPS (`ssl://smtp.hostinger.com:465`) + IMAP `INBOX.Sent` archive + silent Gmail owner copy
 *
 * Safeguards:
 *   1. Blocks self-emails, auto-responders, newsletters (`List-Unsubscribe` / `Precedence: bulk`),
 *      system notifications, job applications, and B2B/SEO/agency cold pitches.
 *   2. Uses Gemini Flash AI (`GEMINI_API_KEY`) to verify `is_genuine_guest_inquiry === true` and
 *      draft a bespoke, itemized, human-warm executive response answering EVERY point in the guest's email.
 *   3. Exclusive `flock(LOCK_EX)` state file (`storage/lara_email_state.json`) prevents duplicate replies.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/site_config.php';
require_once __DIR__ . '/live_prices.php';

/**
 * Decode RFC 2047 MIME encoded header words (`=?UTF-8?B?...?=` / `=?UTF-8?Q?...?=`).
 */
function lara_mime_decode_header(string $raw): string
{
    $unfolded = preg_replace('/\r?\n[ \t]+/', ' ', trim($raw)) ?? trim($raw);
    if ($unfolded === '') {
        return '';
    }
    if (function_exists('iconv_mime_decode')) {
        $dec = @iconv_mime_decode($unfolded, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
        if (is_string($dec) && $dec !== '') {
            return trim($dec);
        }
    }
    if (function_exists('mb_decode_mimeheader')) {
        return trim((string) @mb_decode_mimeheader($unfolded));
    }
    return $unfolded;
}

/**
 * Extract display name and lowercase email address from a `From:` header.
 *
 * @return array{name:string,email:string}
 */
function lara_parse_email_address(string $fromHeader): array
{
    $decoded = lara_mime_decode_header($fromHeader);
    if (preg_match('/^(.*?)<([^>]+@[^>]+)>/u', $decoded, $m)) {
        $name = trim($m[1], " \t\n\r\0\x0B\"'");
        $email = strtolower(trim($m[2]));
        return ['name' => $name, 'email' => $email];
    }
    if (preg_match('/([a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,})/', $decoded, $m)) {
        return ['name' => '', 'email' => strtolower(trim($m[1]))];
    }
    return ['name' => $decoded, 'email' => ''];
}

/**
 * Parse raw RFC 5322 message into headers + clean plain-text body.
 *
 * @return array{headers:array<string,string>,from_name:string,from_email:string,to:string,subject:string,date:string,message_id:string,in_reply_to:string,references:string,body:string}
 */
function lara_parse_rfc822_message(string $raw): array
{
    $parts = preg_split("/\r?\n\r?\n/", $raw, 2);
    $rawHeaders = (string) ($parts[0] ?? '');
    $rawBody    = (string) ($parts[1] ?? '');

    $unfolded = preg_replace("/\r?\n[ \t]+/", ' ', $rawHeaders) ?? $rawHeaders;
    $headers = [];
    foreach (preg_split("/\r?\n/", $unfolded) ?: [] as $line) {
        $pos = strpos($line, ':');
        if ($pos !== false) {
            $k = strtolower(trim(substr($line, 0, $pos)));
            $v = trim(substr($line, $pos + 1));
            if (!isset($headers[$k])) {
                $headers[$k] = $v;
            }
        }
    }

    $fromParsed = lara_parse_email_address($headers['from'] ?? '');
    $replyToParsed = isset($headers['reply-to']) ? lara_parse_email_address($headers['reply-to']) : ['name' => '', 'email' => ''];
    $effectiveEmail = ($replyToParsed['email'] !== '' && !str_ends_with($replyToParsed['email'], '@seacollegediveclub.com'))
        ? $replyToParsed['email']
        : $fromParsed['email'];
    $effectiveName = $fromParsed['name'] ?: $replyToParsed['name'];

    $body = lara_extract_mime_text($headers['content-type'] ?? 'text/plain', $headers['content-transfer-encoding'] ?? '', $rawBody);

    return [
        'headers'     => $headers,
        'from_name'   => $effectiveName,
        'from_email'  => $effectiveEmail,
        'orig_from'   => $fromParsed['email'],
        'to'          => lara_mime_decode_header($headers['to'] ?? ''),
        'subject'     => lara_mime_decode_header($headers['subject'] ?? ''),
        'date'        => $headers['date'] ?? '',
        'message_id'  => trim($headers['message-id'] ?? ''),
        'in_reply_to' => trim($headers['in-reply-to'] ?? ''),
        'references'  => trim($headers['references'] ?? ''),
        'body'        => trim($body),
    ];
}

/**
 * Recursively extract UTF-8 plain text from single-part or multipart MIME bodies.
 */
function lara_extract_mime_text(string $contentType, string $transferEnc, string $body): string
{
    $ctLow = strtolower($contentType);
    if (str_contains($ctLow, 'multipart/') && preg_match('/boundary=["\']?([^"\';\s]+)["\']?/i', $contentType, $bm)) {
        $boundary = $bm[1];
        $chunks = explode('--' . $boundary, $body);
        $plainCandidate = '';
        $htmlCandidate  = '';
        foreach ($chunks as $idx => $chunk) {
            if ($idx === 0 || str_starts_with(trim($chunk), '--')) {
                continue;
            }
            $chunk = ltrim($chunk, "\r\n");
            $subParts = preg_split("/\r?\n\r?\n/", $chunk, 2);
            if (!$subParts || count($subParts) < 2) {
                continue;
            }
            $subHdrRaw = preg_replace("/\r?\n[ \t]+/", ' ', $subParts[0]) ?? $subParts[0];
            $subBody   = $subParts[1];
            $subCt     = 'text/plain';
            $subEnc    = '';
            $subDisp   = '';
            foreach (preg_split("/\r?\n/", $subHdrRaw) ?: [] as $hl) {
                $p = strpos($hl, ':');
                if ($p === false) {
                    continue;
                }
                $hk = strtolower(trim(substr($hl, 0, $p)));
                $hv = trim(substr($hl, $p + 1));
                if ($hk === 'content-type') {
                    $subCt = $hv;
                } elseif ($hk === 'content-transfer-encoding') {
                    $subEnc = $hv;
                } elseif ($hk === 'content-disposition') {
                    $subDisp = strtolower($hv);
                }
            }
            if (str_starts_with($subDisp, 'attachment')) {
                continue;
            }
            if (str_contains(strtolower($subCt), 'multipart/')) {
                $nested = lara_extract_mime_text($subCt, $subEnc, $subBody);
                if ($nested !== '' && $plainCandidate === '') {
                    $plainCandidate = $nested;
                }
            } elseif (str_contains(strtolower($subCt), 'text/plain') && $plainCandidate === '') {
                $plainCandidate = lara_extract_mime_text($subCt, $subEnc, $subBody);
            } elseif (str_contains(strtolower($subCt), 'text/html') && $htmlCandidate === '') {
                $htmlCandidate = lara_extract_mime_text($subCt, $subEnc, $subBody);
            }
        }
        return $plainCandidate !== '' ? $plainCandidate : $htmlCandidate;
    }

    $encLow = strtolower(trim($transferEnc));
    if ($encLow === 'base64') {
        $decoded = base64_decode(preg_replace('/\s+/', '', $body) ?? '', true);
        $body = is_string($decoded) ? $decoded : $body;
    } elseif ($encLow === 'quoted-printable') {
        $body = quoted_printable_decode($body);
    }

    if (preg_match('/charset=["\']?([a-zA-Z0-9_\-]+)["\']?/i', $contentType, $cm)) {
        $charset = strtoupper($cm[1]);
        if ($charset !== 'UTF-8' && $charset !== 'UTF8' && function_exists('mb_convert_encoding')) {
            $conv = @mb_convert_encoding($body, 'UTF-8', $charset);
            if (is_string($conv) && $conv !== '') {
                $body = $conv;
            }
        }
    }

    if (str_contains($ctLow, 'text/html')) {
        $body = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', '', $body) ?? $body;
        $body = preg_replace('/<br\s*\/?>/i', "\n", $body) ?? $body;
        $body = preg_replace('/<\/(p|div|li|tr|h[1-6])>/i', "\n", $body) ?? $body;
        $body = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $body = preg_replace("/\r\n|\r/", "\n", $body) ?? $body;
    $body = preg_replace("/\n{3,}/", "\n\n", $body) ?? $body;
    return trim($body);
}

/**
 * Minimal Pure-PHP IMAPS Client (`ssl://imap.hostinger.com:993`).
 */
final class LaraImapClient
{
    /** @var resource|null */
    private $fp = null;
    private int $tag = 0;

    public function connect(string $host, int $port, string $user, string $pass): bool
    {
        $this->fp = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 12);
        if (!$this->fp) {
            return false;
        }
        stream_set_timeout($this->fp, 15);
        fgets($this->fp, 4096); // server greeting
        $res = $this->command('LOGIN "' . addcslashes($user, '\\"') . '" "' . addcslashes($pass, '\\"') . '"');
        return $res['ok'];
    }

    /**
     * @return array{ok:bool,lines:array<int,string>,tagged:string}
     */
    public function command(string $cmd): array
    {
        if (!$this->fp) {
            return ['ok' => false, 'lines' => [], 'tagged' => ''];
        }
        $t = 'T' . (++$this->tag);
        fwrite($this->fp, "{$t} {$cmd}\r\n");
        $lines = [];
        $tagged = '';
        while (($line = fgets($this->fp)) !== false) {
            if (preg_match('/\{(\d+)\}\r?\n$/', $line, $m)) {
                $litLen = (int) $m[1];
                $lit = '';
                while (strlen($lit) < $litLen && !feof($this->fp)) {
                    $chunk = fread($this->fp, $litLen - strlen($lit));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    $lit .= $chunk;
                }
                $lines[] = $line . $lit;
                continue;
            }
            if (str_starts_with($line, $t . ' ')) {
                $tagged = trim($line);
                break;
            }
            $lines[] = $line;
        }
        return [
            'ok'     => str_starts_with($tagged, $t . ' OK'),
            'lines'  => $lines,
            'tagged' => $tagged,
        ];
    }

    /**
     * Select INBOX and return highest UID (`uidnext - 1`) and all UIDs matching criteria.
     *
     * @return array<int, int>
     */
    public function searchUids(string $criteria = 'ALL'): array
    {
        $this->command('SELECT INBOX');
        $res = $this->command('UID SEARCH ' . $criteria);
        if (!$res['ok']) {
            return [];
        }
        $uids = [];
        foreach ($res['lines'] as $l) {
            if (preg_match('/^\* SEARCH\s+(.*)$/i', trim($l), $m)) {
                foreach (preg_split('/\s+/', trim($m[1])) ?: [] as $num) {
                    if (ctype_digit($num)) {
                        $uids[] = (int) $num;
                    }
                }
            }
        }
        sort($uids);
        return $uids;
    }

    public function fetchRawByUid(int $uid): string
    {
        $this->command('SELECT INBOX');
        $res = $this->command("UID FETCH {$uid} (FLAGS BODY.PEEK[])");
        if (!$res['ok'] || empty($res['lines'])) {
            return '';
        }
        $joined = implode('', $res['lines']);
        if (preg_match('/BODY\[\]\s*\{(\d+)\}\r?\n/i', $joined, $m, PREG_OFFSET_CAPTURE)) {
            $len = (int) $m[1][0];
            $start = $m[0][1] + strlen($m[0][0]);
            return substr($joined, $start, $len);
        }
        return '';
    }

    public function markAnswered(int $uid): void
    {
        $this->command('SELECT INBOX');
        $this->command("UID STORE {$uid} +FLAGS.SILENT (\\Seen \\Answered)");
    }

    public function appendSent(string $rawMime): bool
    {
        if (!$this->fp) {
            return false;
        }
        $len = strlen($rawMime);
        $t = 'T' . (++$this->tag);
        fwrite($this->fp, "{$t} APPEND INBOX.Sent (\\Seen) {{$len}}\r\n");
        $cont = (string) fgets($this->fp, 4096);
        if (!str_starts_with($cont, '+')) {
            return false;
        }
        fwrite($this->fp, $rawMime . "\r\n");
        while (($line = fgets($this->fp)) !== false) {
            if (str_starts_with($line, $t . ' ')) {
                return str_starts_with(trim($line), $t . ' OK');
            }
        }
        return false;
    }

    public function close(): void
    {
        if ($this->fp) {
            @$this->command('LOGOUT');
            @fclose($this->fp);
            $this->fp = null;
        }
    }
}

/**
 * Deterministic pre-filter: block loops, self-messages, system alerts, newsletters, job applications, & B2B cold pitches.
 *
 * @param array{headers:array<string,string>,from_name:string,from_email:string,orig_from:string,subject:string,body:string} $msg
 * @return string|null Reason string if skipped, or null if eligible for AI guest verification.
 */
function lara_email_precheck_skip(array $msg): ?string
{
    $email   = strtolower(trim($msg['from_email']));
    $orig    = strtolower(trim($msg['orig_from'] ?? ''));
    $subjLow = mb_strtolower($msg['subject'], 'UTF-8');
    $bodyLow = mb_strtolower(mb_substr($msg['body'], 0, 2000, 'UTF-8'), 'UTF-8');
    $hdrs    = $msg['headers'];

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'invalid_sender_email';
    }
    if (str_ends_with($email, '@seacollegediveclub.com') || str_ends_with($orig, '@seacollegediveclub.com')) {
        return 'self_domain_loop_guard';
    }
    if ($email === strtolower(OWNER_COPY_EMAIL)) {
        return 'owner_gmail_guard';
    }
    if (preg_match('/^(no-?reply|donotreply|mailer-daemon|postmaster|bounce|notification|notifications|alert|alerts|system|daemon|automated|team)@/i', $email)) {
        return 'automated_sender_address';
    }
    if (preg_match('/@(facebookmail\.com|email\.hostinger\.com|hostinger\.com|google\.com|accounts\.google\.com|mailjet\.com|sendgrid\.net)$/i', $email)) {
        return 'platform_notification_domain';
    }
    if (!empty($hdrs['x-lara-email-concierge'])) {
        return 'lara_own_header';
    }
    $autoSub = strtolower(trim($hdrs['auto-submitted'] ?? 'no'));
    if ($autoSub !== '' && $autoSub !== 'no') {
        return 'auto_submitted_header';
    }
    $prec = strtolower(trim($hdrs['precedence'] ?? ''));
    if (in_array($prec, ['bulk', 'list', 'junk', 'auto_reply'], true) || isset($hdrs['list-unsubscribe'])) {
        return 'bulk_or_newsletter_header';
    }
    if (preg_match('/(application\s*:|job application|curriculum vitae|\bcv\b|divemaster internship application|verify your business email|verification code|security alert|password reset)/iu', $subjLow)) {
        return 'job_or_system_subject';
    }
    if (preg_match('/(\bseo\b|backlinks|lead generation|quotes \+ bookings crm|crm system|ai assistant demo|trial class conversions|booking idea for|few ideas for seacollegediveclub|wetsuit factory|neoprene manufacturer|custom logo wetsuits|tauchen atlas|بدلات غوص احترافية مخصصة لأندية الغوص)/iu', $subjLow . ' ' . $bodyLow)) {
        return 'b2b_sales_pitch';
    }
    return null;
}

/**
 * Convert clean Markdown email body into an executive, mobile-friendly Sea College Dive Club HTML email.
 */
function lara_render_branded_email_html(string $markdownText, string $lang = 'en'): string
{
    $dir = ($lang === 'ar') ? 'rtl' : 'ltr';
    $align = ($lang === 'ar') ? 'right' : 'left';
    $lines = preg_split("/\r?\n/", trim($markdownText)) ?: [];
    $htmlBlocks = [];
    $inList = false;

    $formatInline = static function (string $line): string {
        $esc = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        // Markdown links [label](https://...)
        $esc = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u',
            static fn(array $m): string => '<a href="' . $m[2] . '" style="color:#0284c7;text-decoration:underline;font-weight:600;">' . $m[1] . '</a>',
            $esc
        ) ?? $esc;
        // Bare URLs not already inside href="..."
        $esc = preg_replace(
            '/(?<!href=")(https?:\/\/[^\s<]+)/u',
            '<a href="$1" style="color:#0284c7;text-decoration:underline;">$1</a>',
            $esc
        ) ?? $esc;
        // Bold **text**
        $esc = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $esc) ?? $esc;
        return $esc;
    };

    foreach ($lines as $rawLine) {
        $line = trim($rawLine);
        if ($line === '') {
            if ($inList) {
                $htmlBlocks[] = '</ul>';
                $inList = false;
            }
            continue;
        }
        if (preg_match('/^#{1,3}\s+(.+)$/u', $line, $hm)) {
            if ($inList) {
                $htmlBlocks[] = '</ul>';
                $inList = false;
            }
            $htmlBlocks[] = '<h3 style="margin:20px 0 8px;font-size:16px;color:#0c4a6e;border-bottom:1px solid #e2e8f0;padding-bottom:4px;">'
                . $formatInline($hm[1]) . '</h3>';
            continue;
        }
        if (preg_match('/^[-*•]\s+(.+)$/u', $line, $lm)) {
            if (!$inList) {
                $htmlBlocks[] = '<ul style="margin:8px 0 14px;padding-' . $align . ':22px;">';
                $inList = true;
            }
            $htmlBlocks[] = '<li style="margin:5px 0;">' . $formatInline($lm[1]) . '</li>';
            continue;
        }
        if ($inList) {
            $htmlBlocks[] = '</ul>';
            $inList = false;
        }
        $htmlBlocks[] = '<p style="margin:0 0 12px;">' . $formatInline($line) . '</p>';
    }
    if ($inList) {
        $htmlBlocks[] = '</ul>';
    }

    $contentHtml = implode("\n", $htmlBlocks);

    return <<<HTML
<div dir="{$dir}" style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.65;color:#0f172a;max-width:680px;margin:0 auto;text-align:{$align};">
  <div style="background:#082f49;color:#ffffff;padding:16px 22px;border-radius:10px 10px 0 0;">
    <div style="font-size:18px;font-weight:700;letter-spacing:0.2px;">Sea College Dive Club · Sharm El Sheikh</div>
    <div style="font-size:12px;color:#bae6fd;margin-top:2px;">Official PADI 5-Star Dive Resort &amp; CDWS Accredited Marine Center · 4.9★ (3,082+ Reviews)</div>
  </div>
  <div style="border:1px solid #e2e8f0;border-top:none;padding:22px;border-radius:0 0 10px 10px;background:#ffffff;">
    {$contentHtml}
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:22px 0 14px;">
    <div style="font-size:13px;color:#475569;line-height:1.55;">
      <strong style="color:#0f172a;">Lara — Diver &amp; Private Charter Concierge</strong><br>
      Sea College Dive Club · Cyrene Sharm / Ivy Cyrene Island Resort, Al Montazah, Sharm El Sheikh<br>
      WhatsApp &amp; Phone: <a href="https://wa.me/201114567886" style="color:#0284c7;text-decoration:none;font-weight:600;">+20 111 456 7886</a> ·
      Email: <a href="mailto:info@seacollegediveclub.com" style="color:#0284c7;text-decoration:none;">info@seacollegediveclub.com</a> ·
      Web: <a href="https://seacollegediveclub.com" style="color:#0284c7;text-decoration:none;">seacollegediveclub.com</a>
    </div>
  </div>
</div>
HTML;
}

/**
 * Ask Gemini Flash AI to verify `is_genuine_guest_inquiry` AND draft an executive, human-warm,
 * 100% catalog-accurate quotation reply in the guest's language.
 *
 * @param array{from_name:string,from_email:string,subject:string,body:string} $msg
 * @return array{should_reply:bool,reason:string,lang:string,estimated_eur:float,reply_text:string,reply_html:string}|null
 */
function lara_email_compose_ai_reply(array $msg): ?array
{
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
        return null;
    }

    $sysPrompt = <<<'SYS'
You are Lara, Head of Customer Care & Private Charter / Diving Concierge at Sea College Dive Club (Official PADI 5-Star Dive Resort & CDWS Accredited Dive Center in Al Montazah, Sharm El Sheikh, Egypt; rated 4.9★ across 3,082+ Google reviews).
Official Website: https://seacollegediveclub.com
Official Email: info@seacollegediveclub.com
Direct WhatsApp / Phone: +201114567886 (https://wa.me/201114567886)

STEP 1 — STRICT EMAIL QUALIFICATION (`is_genuine_guest_inquiry`):
Set `is_genuine_guest_inquiry` to FALSE immediately if the email is ANY of:
- Cold B2B sales / marketing / SEO / web / CRM / AI / directory / magazine / supplier / factory pitch
- Newsletter, trade show invite, automated receipt, bank/platform verification, or bounce
- Job / internship CV application
- Internal staff / partner agency operational note
ONLY set `is_genuine_guest_inquiry` to TRUE when a real prospective or booked holiday guest / family / group is inquiring about diving, snorkeling, PADI courses, private yacht/speedboat charters, prices, transfers, or bookings in Sharm El Sheikh!

STEP 2 — EXECUTIVE HUMAN QUOTATION STYLE (when `is_genuine_guest_inquiry` is TRUE):
1. Reply in the EXACT language the guest wrote in (`en`, `ru`, `de`, `it`, `fr`, `pl`, `ar`, `zh`, `es`).
2. Address EVERY SINGLE question, requirement, age, hotel, date range, dietary/catering preference, equipment need, safety/instructor language request, and policy question mentioned in the guest's email — never leave any point unanswered!
3. Sound like an experienced, warm, articulate human Senior Concierge writing a bespoke email (never robotic, never generic). If the guest states "quotation only, not a booking", explicitly respect that with zero pressure.
4. Provide crystal-clear itemized EUR math where every line adds up 100% accurately to the stated total!
5. Format `reply_markdown` cleanly with `### Section Headings`, `- ` bullet lists, `**bold**` key figures, and `[Label](https://...)` links. Do NOT include a bottom contact signature block (our email wrapper appends the official signature automatically) — just end warmly with `Warm regards,\nLara`.

VERIFIED SEA COLLEGE DIVE CLUB CATALOG, FLEET, TEAM & POLICIES (DO NOT INVENT OTHER PRICES):
A) PRIVATE MOTOR YACHT CHARTERS (Full Day, 7–8 Hours, 100% Exclusive Use — Zero Other Passengers):
   - Private Boat 1–5 Guests (`M/Y Omar 4` — 24m two-deck family motor yacht: shaded breezy upper lounge & dining deck with cushioned benches & 360° views, indoor sheltered salon, clean marine toilet/WC & fresh-water deck shower, forward sun mattresses, wide water-level aft swim platform with sturdy sea ladder): €550 per boat (includes yacht, licensed Captain & 2 deck crew + chef, fuel, marina/Coast Guard permits, marine passenger liability insurance, adult & child life jackets, masks & snorkels, and round-trip private A/C Sharm hotel transfer).
     * Recent Nov 2026 `M/Y Omar 4` Photos:
       - Full Fleet Album: https://seacollegediveclub.com/gallery#boat-omar4
       - Exterior: https://seacollegediveclub.com/assets/images/fleet/sea-college-omar4-yacht-01-exterior-nov2026.jpg
       - Shaded Upper Lounge & Dining Deck: https://seacollegediveclub.com/assets/images/fleet/sea-college-omar4-yacht-02-upper-deck-nov2026.jpg
       - Starboard Two-Deck Profile: https://seacollegediveclub.com/assets/images/fleet/sea-college-omar4-yacht-03-sinai-bay-nov2026.jpg
       - Shaded Aft Deck & Swim Platform: https://seacollegediveclub.com/assets/images/fleet/sea-college-omar4-yacht-04-aft-deck-nov2026.jpg
   - Private Boat 6–10 Guests (`M/Y Oceano` or `M/Y Nizar` — larger 28m multi-deck luxury motor yacht with air-conditioned wood-trimmed indoor salon, cushioned aft L-lounge, outdoor dining booths, shaded upper canopy deck, marine toilets & wide water-level dive/swim platform; also available as a +€100 luxury vessel upgrade for 1–5 guests): €650 per boat.
     * Recent Nov 2026 `M/Y Oceano` Photos:
       - Full Fleet Album: https://seacollegediveclub.com/gallery#fleet
       - Exterior: https://seacollegediveclub.com/assets/images/fleet/sea-college-oceano-yacht-01-exterior-nov2026.jpg
       - Shaded Upper Deck: https://seacollegediveclub.com/assets/images/fleet/sea-college-oceano-yacht-03-upper-deck-nov2026.jpg
       - A/C Indoor Salon: https://seacollegediveclub.com/assets/images/fleet/sea-college-oceano-yacht-06-indoor-salon-nov2026.jpg
   - Private Boat 11–15 Guests (`M/Y Oceano`): €900 per boat | 16–20 Guests: €1,100 per boat.
   - Private Charter Add-Ons (itemized transparently):
     * Ras Mohammed / Tiran Marine Park Environmental Fee: €5 per person/day.
     * Dedicated Private In-Water PADI Instructor / Snorkeling Guide (fluent Russian, English, German, Italian, French, Polish, or Arabic): €75 per full day (accompanies guests in the water with surface safety float at all 2–3 stops).
     * Snorkeling Masks, Snorkels & Adult/Child Life Jackets: Included (€0).
     * Fins Rental: €4 per person/day | Neoprene Wetsuits (3mm shorty or 5mm full winter Red Sea suits in adult & child/junior sizes): €10 per person/day.
     * Freshly Cooked Onboard Lunch Buffet (grilled chicken & kofta, rice, pasta, fresh salads, seasonal fresh fruit platter, unlimited soft drinks, bottled water, tea & coffee): €10 per person.
     * Optional Fresh Red Sea Seafood Lunch Upgrade (grilled jumbo shrimp, calamari & Red Sea fish fillet + full buffet sides, fruit & soft drinks): +€20 per person supplement (quote separately when asked!).
     * Optional 1-on-1 Beginner Try Scuba Dive (PADI Intro Dive, age 10+): +€45 per person incl. full scuba gear.
   - Private Speedboats (Al Montazah beach pontoon, 15 min to Tiran): 1h Local Reefs €75 (up to 3 pax) | 2h Tiran €150 (2 pax, extra +€75) | 3h Tiran €225 (2 pax, extra +€100).

B) DAILY TRIPS, SNORKELING & PADI COURSES:
   - Shared White Island & Ras Mohammed Yacht Snorkeling: €55 adult / €30 child (ages 5–12), all-inclusive (transfers, mask, vest, lunch, drinks, park fee).
   - Beginner Intro Dives: House Reef €30 | Full-Day Yacht + 1 Intro Dive €70 (2 Intro Dives €100) | Official PADI DSD €85 | Kids Bubblemaker (8+) €70.
   - PADI Courses (max 4 students per instructor): Scuba Diver €250 | Open Water €400 | Advanced Open Water €350 | Nitrox €145 | Rescue €394 | Divemaster €850. Bundles (incl. gear, dive insurance & tax; PADI manual/cert fee €85/course extra): Stars (OWD+Nitrox) €500 | Super (AOWD+Nitrox) €400 | Mega (OWD+AOWD) €600 | Heros (OWD+AOWD+Nitrox) €750.
   - Certified Boat Diving (max 4 divers per PADI guide!): 2 dives €85 | 4 dives €170 | 6 dives €255 | 8 dives €310 | 10 dives €350 (+€10/day boat supplement incl. lunch/drinks, +€5/day Marine Park fee, +€25/day full scuba equipment if needed, Nitrox 12L €8, SS Thistlegorm €150).

C) TEAM, PICKUP ZONES, AVAILABILITY & TERMS:
   - Instructors: Certified PADI Open Water Scuba Instructors & EFR (First Aid/CPR/Emergency Oxygen) — Russian-speaking instructors Jimmy (Джими) & Alex; English/Italian/German/French/Polish/Arabic instructors Mo, Sam, Moussab & Wesam. Emergency O2 & first-aid kit onboard every yacht.
   - Pickup Zones (complimentary A/C hotel transfers across Sharm, outside main hotel security gate): Nabq Bay (incl. Steigenberger Alcazar, Rixos) 07:35–07:50 AM (flexible 07:30–08:15 AM on private charters; ~20–25 min to marina); Al Montazah/Sharks Bay 07:55–08:10; SOHO 07:50–08:05; Coral Bay 07:40–07:55; Naama Bay 07:30–07:45; Hadaba/Old Market 07:20–07:35.
   - Typical 7.5–8h Ras Mohammed & White Island Private Charter Itinerary: ~07:45 hotel pickup -> ~08:30 marina boarding & Russian/English safety briefing -> Stop 1 sheltered Ras Mohammed coral garden (e.g. Marsa Bareika / Yolanda Bay) -> Stop 2 White Island shallow turquoise sandbar -> Fresh hot onboard lunch & fruit on shaded deck -> Stop 3 second sheltered Ras Mohammed reef -> ~15:45–16:00 marina return -> ~16:30 hotel drop-off.
   - Dates: December 2026 & January 2027 dates (including 20–27 December 2026) are currently available.
   - Payment, Cancellation & Weather Guarantee:
     * Official prices in EUR; payable in EUR, USD, GBP, or EGP (cash or card) at our dive center or on charter morning at the official bank exchange rate (no advance online payment required!).
     * To confirm a date later, guests simply reply with their chosen date and submit passport copies & wetsuit/fin EU sizes for the mandatory Egyptian Coast Guard boat permit via our online Check-In Portal: https://seacollegediveclub.com/check-in (never say `/check-in` collects deposits — it collects passport & equipment details only!).
     * When a guest asks for recent yacht photos, include BOTH the gallery album link AND 2–3 direct `.jpg` photo links from section A above!
     * Free cancellation up to 48 hours before departure with zero fee.
     * Weather / Sea Safety Guarantee: If Port Authority closes the marina or wind/sea conditions are unsuitable for safe family snorkeling/diving, guests receive FREE rescheduling to another suitable day during their stay OR a 100% full refund with zero penalty.

OUTPUT FORMAT — Return ONLY valid JSON matching schema.
SYS;

    $userPrompt = "From: {$msg['from_name']} <{$msg['from_email']}>\nSubject: {$msg['subject']}\n\nEmail Body:\n" . mb_substr($msg['body'], 0, 6000, 'UTF-8');

    $payloadJson = json_encode([
        'systemInstruction' => ['parts' => [['text' => $sysPrompt]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => $userPrompt]]]],
        'generationConfig'  => [
            'temperature'      => 0.25,
            'maxOutputTokens'  => 8192,
            'responseMimeType' => 'application/json',
            'responseSchema'   => [
                'type'       => 'OBJECT',
                'properties' => [
                    'is_genuine_guest_inquiry' => ['type' => 'BOOLEAN'],
                    'reason'                   => ['type' => 'STRING'],
                    'lang'                     => ['type' => 'STRING'],
                    'estimated_eur'            => ['type' => 'NUMBER'],
                    'reply_markdown'           => ['type' => 'STRING'],
                ],
                'required'   => ['is_genuine_guest_inquiry', 'reason', 'lang', 'estimated_eur', 'reply_markdown'],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $models = ['gemini-3.5-flash-lite', 'gemini-flash-lite-latest', 'gemini-3.1-flash-lite', 'gemini-flash-latest'];
    $parsed = null;
    foreach ($models as $idx => $model) {
        if ($idx > 0) {
            usleep(600000);
        }
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . rawurlencode(GEMINI_API_KEY);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payloadJson,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $raw = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || $raw === '') {
            continue;
        }
        $outer = json_decode($raw, true);
        $jsonText = '';
        foreach (($outer['candidates'][0]['content']['parts'] ?? []) as $part) {
            if (empty($part['thought']) && isset($part['text']) && is_string($part['text'])) {
                $jsonText = $part['text'];
            }
        }
        if ($jsonText === '') {
            continue;
        }
        $dec = json_decode($jsonText, true);
        if (is_array($dec) && array_key_exists('is_genuine_guest_inquiry', $dec)) {
            $parsed = $dec;
            break;
        }
    }

    if ($parsed === null) {
        return null;
    }

    $md = trim((string) ($parsed['reply_markdown'] ?? $parsed['reply_text'] ?? ''));
    $lang = (string) ($parsed['lang'] ?? 'en');
    $should = (bool) $parsed['is_genuine_guest_inquiry'] && $md !== '';

    $plain = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/u', '$1 ($2)', $md) ?? $md;
    $plain = preg_replace('/^#{1,3}\s+/m', '', $plain) ?? $plain;
    $plain = str_replace('**', '', $plain);
    $plain .= "\n\n--\nLara — Diver & Private Charter Concierge\nSea College Dive Club · Official PADI 5-Star Dive Resort, Sharm El Sheikh\nWhatsApp / Phone: +20 111 456 7886 (https://wa.me/201114567886)\nEmail: info@seacollegediveclub.com | Web: https://seacollegediveclub.com";

    $html = $should ? lara_render_branded_email_html($md, $lang) : '';

    return [
        'should_reply'  => $should,
        'reason'        => (string) ($parsed['reason'] ?? ''),
        'lang'          => $lang,
        'estimated_eur' => (float) ($parsed['estimated_eur'] ?? 0),
        'reply_text'    => $plain,
        'reply_html'    => $html,
    ];
}

/**
 * Deterministic <1ms extractor for CRM lead columns (`Name`, `Phone`, `People`, `Preferred_Date`, `Hotel`, `Diving_Level`, `Estimated_EUR`).
 *
 * @param array<string,mixed> $parsed
 * @return array{name:string,phone:string,people:int,date:string,hotel:string,level:string,lang:string,interest:string,estimated_eur:float}
 */
function lara_extract_email_lead_fields(array $parsed, float $aiEur = 0.0, string $aiLang = ''): array
{
    $subject = trim((string) ($parsed['subject'] ?? ''));
    $body    = trim((string) ($parsed['body'] ?? ''));
    $full    = $subject . "\n" . $body;
    $low     = mb_strtolower($full, 'UTF-8');

    // 1. Name
    $name = trim((string) ($parsed['from_name'] ?? ''));
    if ($name === '' && preg_match('/(?:regards|thank you|thanks|sincerely|best)[,\s]*\r?\n+\s*([\p{L}][\p{L} .\'-]{1,45})/iu', $body, $nm)) {
        $name = trim($nm[1]);
    }
    if ($name === '') {
        $name = explode('@', (string) ($parsed['from_email'] ?? 'Guest'))[0];
    }

    // 2. Phone (digits only so Google Sheets never parses +27... as a formula)
    $phone = '';
    if (preg_match('/(?:cell|tel|phone|mobile|whatsapp|واتساب|هاتف)\s*[:.]?\s*(\+?[0-9][0-9 ()\-]{7,22})/iu', $body, $pm)) {
        $phone = preg_replace('/\D/', '', $pm[1]) ?? '';
    } elseif (preg_match('/(\+[1-9][0-9 ()\-]{8,20})/', $body, $pm)) {
        $cand = preg_replace('/\D/', '', $pm[1]) ?? '';
        if ($cand !== WHATSAPP_NUMBER && strlen($cand) >= 9 && strlen($cand) <= 15) {
            $phone = $cand;
        }
    }

    // 3. People count
    $people = 1;
    $wordNums = ['two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10];
    if (preg_match('/family of (two|three|four|five|six|seven|eight|\d+)/iu', $low, $gm)) {
        $w = strtolower($gm[1]);
        $people = $wordNums[$w] ?? max(1, (int) $w);
    } elseif (preg_match('/\b(\d{1,2})\s*(?:people|guests|persons|pax|divers)\b/iu', $low, $gm)) {
        $people = max(1, min(60, (int) $gm[1]));
    } elseif (preg_match('/\b(\d)\s*adults?\s*(?:and|&|\+)\s*(\d)\s*(?:kids?|children)/iu', $low, $gm)) {
        $people = (int) $gm[1] + (int) $gm[2];
    }

    // 4. Hotel match against data/hotel_zones.php
    $hotel = '';
    $zonesFile = dirname(__DIR__) . '/data/hotel_zones.php';
    if (is_file($zonesFile)) {
        $hz = require $zonesFile;
        foreach (($hz['hotels'] ?? []) as $hEntry) {
            $hName = (string) ($hEntry['name'] ?? '');
            if ($hName !== '' && str_contains($low, mb_strtolower($hName, 'UTF-8'))) {
                $hotel = $hName;
                break;
            }
        }
    }

    // 5. Preferred date (YYYY-MM-DD)
    $prefDate = '';
    $months = [
        'jan' => 1, 'january' => 1, 'feb' => 2, 'february' => 2, 'mar' => 3, 'march' => 3,
        'apr' => 4, 'april' => 4, 'may' => 5, 'jun' => 6, 'june' => 6, 'jul' => 7, 'july' => 7,
        'aug' => 8, 'august' => 8, 'sep' => 9, 'sept' => 9, 'september' => 9,
        'oct' => 10, 'october' => 10, 'nov' => 11, 'november' => 11, 'dec' => 12, 'december' => 12,
    ];
    $defaultYear = (int) gmdate('Y', strtotime((string) ($parsed['date'] ?? '')) ?: time());
    if (preg_match('/\b(\d{1,2})(?:\s*(?:to|–|-)\s*\d{1,2})?\s+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)(?:\s+(202\d))?/iu', $full, $dm)) {
        $day = (int) $dm[1];
        $mon = $months[strtolower($dm[2])] ?? 1;
        $yr  = !empty($dm[3]) ? (int) $dm[3] : ($mon < (int) gmdate('n') ? $defaultYear + 1 : $defaultYear);
        if (checkdate($mon, $day, $yr)) {
            $prefDate = sprintf('%04d-%02d-%02d', $yr, $mon, $day);
        }
    } elseif (preg_match('/\b(202\d-\d{2}-\d{2})\b/', $full, $dm)) {
        $prefDate = $dm[1];
    }

    // 6. Diving certification level
    $level = '';
    if (preg_match('/\b(cmas r3|dive leader|divemaster)\b/iu', $low)) {
        $level = 'dm';
    } elseif (preg_match('/\b(rescue diver)\b/iu', $low)) {
        $level = 'rescue';
    } elseif (preg_match('/\b(advanced open water|aowd)\b/iu', $low) && preg_match('/\b(have|certified|we have)\b/iu', $low)) {
        $level = 'aowd';
    } elseif (preg_match('/\b(certified open water|padi open water|\bowd\b)\b/iu', $low)) {
        $level = 'owd';
    } elseif (preg_match('/\b(beginner|first time|never dived|snorkeling)\b/iu', $low)) {
        $level = 'none';
    }

    // 7. Language
    $lang = $aiLang !== '' ? $aiLang : (preg_match('/\p{Arabic}/u', $body) ? 'ar' : (preg_match('/\p{Cyrillic}/u', $body) ? 'ru' : 'en'));

    // 8. Estimated EUR (use AI figure if > 0, else smart catalog estimate)
    $eur = $aiEur;
    if ($eur <= 0) {
        if (preg_match('/private.*(yacht|boat|charter)/iu', $low)) {
            $eur = $people <= 5 ? 550.0 : ($people <= 10 ? 650.0 : 900.0);
        } elseif (preg_match('/20[- ]dive/iu', $low)) {
            $eur = 950.0;
        } elseif (preg_match('/10[- ]dive/iu', $low)) {
            $eur = 350.0;
        } elseif (preg_match('/advanced open water/iu', $low)) {
            $eur = 350.0;
        } elseif (preg_match('/open water/iu', $low)) {
            $eur = 400.0;
        }
    }

    $interest = $subject !== '' ? $subject : mb_substr(str_replace("\n", ' ', $body), 0, 120, 'UTF-8');

    return [
        'name'          => $name,
        'phone'         => $phone,
        'people'        => $people,
        'date'          => $prefDate,
        'hotel'         => $hotel,
        'level'         => $level,
        'lang'          => $lang,
        'interest'      => $interest,
        'estimated_eur' => round($eur, 2),
    ];
}

/**
 * Push a verified Direct Email client lead into Google Sheets CRM (`CRM_WEBHOOK_URL`),
 * and the Unified ERP SQL Warehouse (`sc_erp_ingest_website_lead`).
 *
 * @param array<string,mixed> $parsed
 * @param array<string,mixed> $plan
 */
function lara_email_sync_lead_to_crm(int $uid, array $parsed, array $plan = [], string $laraStatus = 'Lara Auto-Quoted'): bool
{
    $msgId = (string) ($parsed['message_id'] ?? '');
    $eventId = 'email_uid' . $uid . '_' . substr(sha1($msgId !== '' ? $msgId : ('uid_' . $uid)), 0, 10);
    $receivedTs = strtotime((string) ($parsed['date'] ?? '')) ?: time();
    $receivedIso = gmdate('c', $receivedTs);

    $extracted = lara_extract_email_lead_fields(
        $parsed,
        (float) ($plan['estimated_eur'] ?? 0),
        (string) ($plan['lang'] ?? '')
    );

    $crmPayload = [
        'type'                => 'lead',
        'received_at'         => $receivedIso,
        'event_id'            => $eventId,
        'form'                => 'direct_email',
        'lang'                => $extracted['lang'],
        'name'                => $extracted['name'],
        'phone'               => $extracted['phone'],
        'email'               => strtolower(trim((string) ($parsed['from_email'] ?? ''))),
        'interest'            => $extracted['interest'],
        'people'              => $extracted['people'],
        'date'                => $extracted['date'],
        'hotel'               => $extracted['hotel'],
        'level'               => $extracted['level'],
        'message'             => mb_substr(trim((string) ($parsed['body'] ?? '')), 0, 1000, 'UTF-8'),
        'cart'                => [[
            'source'  => 'direct_email',
            'uid'     => $uid,
            'subject' => mb_substr((string) ($parsed['subject'] ?? ''), 0, 140, 'UTF-8'),
            'lara'    => $laraStatus,
        ]],
        'estimated_total_eur' => $extracted['estimated_eur'],
        'estimated_eur'       => $extracted['estimated_eur'],
        'utm_source'          => 'email',
        'utm_medium'          => 'direct_email',
        'utm_campaign'        => 'info_seacollegediveclub',
        'channel_source'      => 'Direct Email',
        'first_channel'       => 'Direct Email',
        'journey_path'        => 'Direct Email -> ' . $laraStatus,
    ];

    $okCrm = false;
    if (defined('CRM_WEBHOOK_URL') && CRM_WEBHOOK_URL !== '') {
        $ch = curl_init(CRM_WEBHOOK_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($crmPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_TIMEOUT        => 12,
        ]);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $okCrm = ($resp !== false && $code >= 200 && $code < 400);
    }

    $erpFile = __DIR__ . '/erp_db.php';
    if (is_file($erpFile)) {
        try {
            require_once $erpFile;
            if (function_exists('sc_erp_ingest_website_lead')) {
                sc_erp_ingest_website_lead($crmPayload);
            }
        } catch (\Throwable $e) {
            error_log('Lara email ERP lead sync error: ' . $e->getMessage());
        }
    }

    return $okCrm;
}

/**
 * Encode an email Subject or Display Name safely as UTF-8 RFC 2047 Base64 when non-ASCII chars exist.
 */
function lara_encode_header_utf8(string $str): string
{
    $clean = trim(str_replace(["\r", "\n"], ' ', $str));
    if ($clean === '' || !preg_match('/[^\x20-\x7E]/', $clean)) {
        return $clean;
    }
    return '=?UTF-8?B?' . base64_encode($clean) . '?=';
}

/**
 * Send threaded multipart/alternative email via Hostinger SMTPS (`smtp.hostinger.com:465`),
 * silently BCC `OWNER_COPY_EMAIL` (`seacollegediveclubsharm@gmail.com`) so the owner sees it in Gmail,
 * and return `[ok, raw_mime, message_id]`.
 *
 * @return array{ok:bool,raw_mime:string,message_id:string,error:string}
 */
function lara_email_send_reply(
    string $toEmail,
    string $toName,
    string $origSubject,
    string $origMessageId,
    string $origReferences,
    string $plainText,
    string $htmlBody
): array {
    $subj = trim($origSubject);
    if (!preg_match('/^re:/i', $subj)) {
        $subj = 'Re: ' . $subj;
    }
    $boundary = 'sc_lara_' . bin2hex(random_bytes(12));
    $outMsgId = '<lara.' . time() . '.' . bin2hex(random_bytes(6)) . '@seacollegediveclub.com>';

    $toHeader = $toName !== ''
        ? (lara_encode_header_utf8($toName) . " <{$toEmail}>")
        : "<{$toEmail}>";
    $fromHeader = lara_encode_header_utf8('Lara — Sea College Dive Club') . ' <' . MAIL_FROM . '>';

    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromHeader,
        'Reply-To: ' . SITE_NAME . ' <' . MAIL_FROM . '>',
        'To: ' . $toHeader,
        'Subject: ' . lara_encode_header_utf8($subj),
        'Message-ID: ' . $outMsgId,
    ];
    if ($origMessageId !== '') {
        $headers[] = 'In-Reply-To: ' . $origMessageId;
        $refs = trim($origReferences !== '' ? ($origReferences . ' ' . $origMessageId) : $origMessageId);
        $headers[] = 'References: ' . $refs;
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Auto-Submitted: auto-replied';
    $headers[] = 'X-Lara-Email-Concierge: v1';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $qpPlain = quoted_printable_encode(str_replace("\n", "\r\n", str_replace("\r\n", "\n", $plainText)));
    $qpHtml  = quoted_printable_encode(str_replace("\n", "\r\n", str_replace("\r\n", "\n", $htmlBody)));

    $mimeBody = "--{$boundary}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
        . $qpPlain . "\r\n\r\n"
        . "--{$boundary}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
        . $qpHtml . "\r\n\r\n"
        . "--{$boundary}--";

    $rawMime = implode("\r\n", $headers) . "\r\n\r\n" . $mimeBody;

    $fp = @stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 12);
    if (!$fp) {
        return ['ok' => false, 'raw_mime' => $rawMime, 'message_id' => $outMsgId, 'error' => "SMTP connect failed: {$errstr}"];
    }
    stream_set_timeout($fp, 15);
    $read = static function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $lastResp = '';
    $cmd = static function (string $c, string $expect) use ($fp, $read, &$lastResp): bool {
        fwrite($fp, $c . "\r\n");
        $lastResp = $read();
        return str_starts_with($lastResp, $expect);
    };

    $read(); // banner
    $ehloHost = parse_url(SITE_URL, PHP_URL_HOST) ?: 'seacollegediveclub.com';
    $ok = $cmd('EHLO ' . $ehloHost, '250')
        && $cmd('AUTH LOGIN', '334')
        && $cmd(base64_encode(SMTP_USER), '334')
        && $cmd(base64_encode(SMTP_PASS), '235')
        && $cmd('MAIL FROM:<' . MAIL_FROM . '>', '250')
        && $cmd('RCPT TO:<' . $toEmail . '>', '250');

    if ($ok && OWNER_COPY_EMAIL !== '' && strtolower(OWNER_COPY_EMAIL) !== strtolower($toEmail)) {
        @$cmd('RCPT TO:<' . OWNER_COPY_EMAIL . '>', '250');
    }

    if ($ok && $cmd('DATA', '354')) {
        $dotStuffed = preg_replace('/^\./m', '..', $rawMime) ?? $rawMime;
        $ok = $cmd($dotStuffed . "\r\n.", '250');
    } else {
        $ok = false;
    }

    @$cmd('QUIT', '221');
    @fclose($fp);

    return [
        'ok'         => $ok,
        'raw_mime'   => $rawMime,
        'message_id' => $outMsgId,
        'error'      => $ok ? '' : trim($lastResp),
    ];
}

/**
 * Load/save Lara email state atomically under exclusive lock (`storage/lara_email_state.json`).
 *
 * @param callable(array<string,mixed>):array<string,mixed> $mutator
 * @return array<string,mixed>
 */
function lara_email_with_state(callable $mutator): array
{
    $dir = dirname(__DIR__) . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $stateFile = $dir . '/lara_email_state.json';
    $lockFile  = $dir . '/lara_email_state.lock';
    $lfp = @fopen($lockFile, 'c+');
    if ($lfp !== false) {
        @flock($lfp, LOCK_EX);
    }
    $state = ['last_uid' => 0, 'processed_uids' => [], 'replied_msg_ids' => [], 'sender_last_ts' => []];
    if (is_file($stateFile)) {
        $dec = json_decode((string) @file_get_contents($stateFile), true);
        if (is_array($dec)) {
            $state = array_merge($state, $dec);
        }
    }
    $state = $mutator($state);
    @file_put_contents($stateFile, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($lfp !== false) {
        @flock($lfp, LOCK_UN);
        @fclose($lfp);
    }
    return $state;
}

/**
 * Poll `info@seacollegediveclub.com` INBOX for new UIDs (`> last_uid`), qualify via pre-check + Gemini AI,
 * and automatically send bespoke quotations to genuine guest inquiries.
 *
 * @return array{ok:bool,checked:int,replied:int,skipped:int,details:array<int,array<string,mixed>>}
 */
function lara_email_poll_and_reply(bool $doSend = true, int $maxMessages = 5, ?int $specificUid = null): array
{
    if (SMTP_USER === '' || SMTP_PASS === '' || !defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
        return ['ok' => false, 'checked' => 0, 'replied' => 0, 'skipped' => 0, 'details' => [['error' => 'Missing SMTP_USER, SMTP_PASS, or GEMINI_API_KEY']]];
    }

    $imap = new LaraImapClient();
    if (!$imap->connect(IMAP_HOST, IMAP_PORT, SMTP_USER, SMTP_PASS)) {
        return ['ok' => false, 'checked' => 0, 'replied' => 0, 'skipped' => 0, 'details' => [['error' => 'IMAP connection failed']]];
    }

    $allUids = $imap->searchUids('ALL');
    if (empty($allUids)) {
        $imap->close();
        return ['ok' => true, 'checked' => 0, 'replied' => 0, 'skipped' => 0, 'details' => []];
    }
    $highestUid = max($allUids);

    // Initialize baseline on first-ever automatic poll so historical backlog isn't blasted unprompted
    $stateSnapshot = lara_email_with_state(static function (array $st) use ($highestUid, $specificUid): array {
        if ((int) ($st['last_uid'] ?? 0) === 0 && $specificUid === null) {
            $st['last_uid'] = $highestUid;
        }
        return $st;
    });

    if ($specificUid !== null) {
        $targetUids = in_array($specificUid, $allUids, true) ? [$specificUid] : [];
    } else {
        $lastUid = (int) ($stateSnapshot['last_uid'] ?? 0);
        $targetUids = array_values(array_filter($allUids, static fn(int $u): bool => $u > $lastUid));
        $targetUids = array_slice($targetUids, 0, max(1, $maxMessages));
    }

    $checked = 0;
    $replied = 0;
    $skipped = 0;
    $details = [];
    $logFile = dirname(__DIR__) . '/storage/lara_email_log.jsonl';

    foreach ($targetUids as $uid) {
        $checked++;
        $alreadyDone = isset($stateSnapshot['processed_uids'][(string) $uid]) && $specificUid === null;
        if ($alreadyDone) {
            $skipped++;
            continue;
        }

        $raw = $imap->fetchRawByUid($uid);
        if ($raw === '') {
            $skipped++;
            continue;
        }
        $parsed = lara_parse_rfc822_message($raw);
        if ($parsed['message_id'] !== '' && isset($stateSnapshot['replied_msg_ids'][$parsed['message_id']])) {
            $skipped++;
            $details[] = ['uid' => $uid, 'from' => $parsed['from_email'], 'subject' => $parsed['subject'], 'action' => 'skipped_already_replied'];
            continue;
        }

        $preSkip = lara_email_precheck_skip($parsed);
        if ($preSkip !== null) {
            $skipped++;
            $details[] = ['uid' => $uid, 'from' => $parsed['from_email'], 'subject' => $parsed['subject'], 'action' => 'skipped:' . $preSkip];
            if ($doSend) {
                lara_email_with_state(static function (array $st) use ($uid): array {
                    $st['processed_uids'][(string) $uid] = 'skipped';
                    if ($uid > (int) ($st['last_uid'] ?? 0)) {
                        $st['last_uid'] = $uid;
                    }
                    return $st;
                });
            }
            continue;
        }

        // Rate-limit max 2 auto-emails per sender address per 12h to prevent rapid ping-pong loops
        $senderKey = strtolower($parsed['from_email']);
        $lastSenderTs = (int) ($stateSnapshot['sender_last_ts'][$senderKey] ?? 0);
        if ($specificUid === null && $lastSenderTs > 0 && (time() - $lastSenderTs) < 600) {
            $skipped++;
            $details[] = ['uid' => $uid, 'from' => $parsed['from_email'], 'subject' => $parsed['subject'], 'action' => 'skipped:cooldown_10m'];
            continue;
        }

        $plan = lara_email_compose_ai_reply($parsed);
        if ($plan === null || !$plan['should_reply']) {
            $skipped++;
            $reason = $plan ? ('ai_skip:' . $plan['reason']) : 'ai_unavailable';
            $details[] = ['uid' => $uid, 'from' => $parsed['from_email'], 'subject' => $parsed['subject'], 'action' => $reason];
            if ($doSend && $plan !== null) {
                lara_email_with_state(static function (array $st) use ($uid): array {
                    $st['processed_uids'][(string) $uid] = 'ai_skipped';
                    if ($uid > (int) ($st['last_uid'] ?? 0)) {
                        $st['last_uid'] = $uid;
                    }
                    return $st;
                });
            }
            continue;
        }

        if (!$doSend) {
            $details[] = [
                'uid'           => $uid,
                'from'          => "{$parsed['from_name']} <{$parsed['from_email']}>",
                'subject'       => $parsed['subject'],
                'lang'          => $plan['lang'],
                'estimated_eur' => $plan['estimated_eur'],
                'action'        => 'dry_run_ready',
                'reply_text'    => $plan['reply_text'],
            ];
            continue;
        }

        $sendRes = lara_email_send_reply(
            $parsed['from_email'],
            $parsed['from_name'],
            $parsed['subject'],
            $parsed['message_id'],
            $parsed['references'],
            $plan['reply_text'],
            $plan['reply_html']
        );

        if ($sendRes['ok']) {
            $replied++;
            $imap->markAnswered($uid);
            $imap->appendSent($sendRes['raw_mime']);

            $crmOk = false;
            if (empty($stateSnapshot['crm_synced_uids'][(string) $uid])) {
                $crmOk = lara_email_sync_lead_to_crm($uid, $parsed, $plan, 'Lara Auto-Quoted');
            }

            lara_email_with_state(static function (array $st) use ($uid, $parsed, $senderKey, $crmOk): array {
                $st['processed_uids'][(string) $uid] = gmdate('c');
                if ($crmOk) {
                    $st['crm_synced_uids'][(string) $uid] = gmdate('c');
                }
                if ($parsed['message_id'] !== '') {
                    $st['replied_msg_ids'][$parsed['message_id']] = gmdate('c');
                }
                $st['sender_last_ts'][$senderKey] = time();
                if ($uid > (int) ($st['last_uid'] ?? 0)) {
                    $st['last_uid'] = $uid;
                }
                return $st;
            });

            @file_put_contents($logFile, json_encode([
                'ts'            => gmdate('c'),
                'uid'           => $uid,
                'to'            => $parsed['from_email'],
                'name'          => $parsed['from_name'],
                'subject'       => $parsed['subject'],
                'lang'          => $plan['lang'],
                'estimated_eur' => $plan['estimated_eur'],
                'crm_synced'    => $crmOk,
                'out_msg_id'    => $sendRes['message_id'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND | LOCK_EX);

            $details[] = [
                'uid'           => $uid,
                'from'          => "{$parsed['from_name']} <{$parsed['from_email']}>",
                'subject'       => $parsed['subject'],
                'lang'          => $plan['lang'],
                'estimated_eur' => $plan['estimated_eur'],
                'crm_synced'    => $crmOk,
                'action'        => 'sent_ok',
                'out_msg_id'    => $sendRes['message_id'],
                'reply_text'    => $plan['reply_text'],
            ];
        } else {
            $details[] = [
                'uid'     => $uid,
                'from'    => $parsed['from_email'],
                'subject' => $parsed['subject'],
                'action'  => 'smtp_error:' . $sendRes['error'],
            ];
        }
    }

    $imap->close();
    return ['ok' => true, 'checked' => $checked, 'replied' => $replied, 'skipped' => $skipped, 'details' => $details];
}
