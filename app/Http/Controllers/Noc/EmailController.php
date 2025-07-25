<?php

namespace App\Http\Controllers\Noc;

use App\Http\Controllers\Controller;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    protected GoogleClient $client;
    protected Gmail       $gmail;

    public function __construct()
    {
        // Always instantiate the client so it's never null
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(env('GOOGLE_REDIRECT_URI'));
    }

    // ========== Folder Views ==========

    public function inbox()
    {
        return $this->folderView('INBOX', 'settings.mail.inbox', 'Inbox');
    }

    public function sent()
    {
        return $this->folderView('SENT', 'settings.mail.sent', 'Sent Mail');
    }

    public function spam()
    {
        return $this->folderView('SPAM', 'settings.mail.spam', 'Spam');
    }

    protected function folderView(string $label, string $routeName, string $title)
    {
        $messages = [];
        if ($token = Auth::user()->google_token) {
            $this->refreshAccessToken();
            $this->gmail = new Gmail($this->client);

            $list = $this->gmail->users_messages->listUsersMessages('me', [
                'labelIds'   => [$label],
                'maxResults' => 20,
            ]);

            foreach ($list->getMessages() ?? [] as $m) {
                $detail = $this->gmail->users_messages->get('me', $m->getId(), [
                    'format'          => 'metadata',
                    'metadataHeaders' => ['Subject','From','Date'],
                ]);
                $hdrs = $detail->getPayload()->getHeaders();
                $meta = array_reduce($hdrs, fn($a,$h) =>
                    in_array($h->getName(), ['Subject','From','Date'])
                      ? array_merge($a, [$h->getName() => $h->getValue()])
                      : $a
                , []);
                $messages[] = [
                    'id'      => $m->getId(),
                    'snippet' => $detail->getSnippet(),
                    'meta'    => $meta,
                ];
            }
        }

        return view('settings.mail.folder', compact('messages','routeName','title'));
    }

    // ========== Compose ==========

    public function create()
    {
        $user = Auth::user();
        return view('settings.mail.create', [
            'signature'   => $user->signature,
            'fontChoices' => ['Poppins','Arial','Times New Roman','Courier New'],
            'defaultFont' => $user->default_font,
        ]);
    }

    public function store(Request $req)
    {
        $req->validate([
            'to'      => 'required|email',
            'subject' => 'required|string',
            'body'    => 'required|string',
            'font'    => 'required|string',
        ]);

        $this->refreshAccessToken();
        $this->gmail = new Gmail($this->client);

        $user = Auth::user();

        // Prepare HTML body with font + signature
        $html  = "<div style=\"font-family:'{$req->font}',sans-serif;\">{$req->body}</div>";
        if ($user->signature) {
            $html .= "<div style=\"margin-top:1em;font-size:0.9em;color:#555;\">"
                   . nl2br(e($user->signature))
                   . "</div>";
        }

        $raw  = "From: me\r\n";
        $raw .= "To: {$req->to}\r\n";
        $raw .= "Subject: {$req->subject}\r\n";
        $raw .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $encoded = rtrim(strtr(base64_encode($raw.$html), '+/', '-_'), '=');

        $msg = new \Google\Service\Gmail\Message();
        $msg->setRaw($encoded);
        $this->gmail->users_messages->send('me', $msg);

        return redirect()->route('settings.mail.sent')->with('success','Email terkirim.');
    }

    // ========== Show & Reply ==========

    public function show($id)
    {
        $this->refreshAccessToken();
        $this->gmail = new Gmail($this->client);

        $detail = $this->gmail->users_messages->get('me', $id, ['format'=>'full']);
        $body   = $this->extractHtml($detail);
        $hdrs   = $detail->getPayload()->getHeaders();
        $meta   = array_reduce($hdrs, fn($a,$h) =>
            in_array($h->getName(), ['Subject','From','Date'])
                ? array_merge($a, [$h->getName() => $h->getValue()])
                : $a
        , []);

        return view('settings.mail.show', compact('body','meta','id'));
    }

    public function reply(Request $req, $id)
    {
        $req->validate(['body'=>'required|string']);

        $this->refreshAccessToken();
        $this->gmail = new Gmail($this->client);

        $user    = Auth::user();
        $to      = $req->input('to');
        $subject = $req->input('subject');

        $html  = "<div style=\"font-family:'{$user->default_font}',sans-serif;\">{$req->body}</div>";
        if ($user->signature) {
            $html .= "<div style=\"margin-top:1em;font-size:0.9em;color:#555;\">"
                   . nl2br(e($user->signature))
                   . "</div>";
        }

        $raw  = "From: me\r\n";
        $raw .= "To: $to\r\n";
        $raw .= "Subject: Re: $subject\r\n";
        $raw .= "In-Reply-To: <$id>\r\n";
        $raw .= "References: <$id>\r\n";
        $raw .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $encoded = rtrim(strtr(base64_encode($raw.$html), '+/', '-_'), '=');

        $msg = new \Google\Service\Gmail\Message();
        $msg->setRaw($encoded);
        $this->gmail->users_messages->send('me', $msg);

        return back()->with('success','Balasan terkirim.');
    }

    // ========== Unread Count API ==========

    public function unreadCount()
    {
        $this->refreshAccessToken();
        $this->gmail = new Gmail($this->client);
        $list = $this->gmail->users_messages->listUsersMessages('me', [
            'labelIds'   => ['UNREAD'],
            'maxResults' => 100,
        ]);
        return response()->json(['count'=>count($list->getMessages() ?? [])]);
    }

    // ========== Helpers ==========

    private function refreshAccessToken(): void
    {
        $user  = Auth::user();
        $token = $user->google_token ?? [];
        $rt    = $user->google_refresh_token;

        if (empty($token['refresh_token']) && $rt) {
            $token['refresh_token'] = $rt;
        }
        $this->client->setAccessToken(json_encode($token));

        if ($this->client->isAccessTokenExpired()) {
            $new = $this->client->fetchAccessTokenWithRefreshToken($rt);
            $user->update([
                'google_token'         => $new,
                'google_refresh_token' => $new['refresh_token'] ?? $rt,
            ]);
            $this->client->setAccessToken(json_encode($new));
        }
    }

    private function extractHtml($msg): string
    {
        foreach ($msg->getPayload()->getParts() ?? [] as $p) {
            if ($p->getMimeType() === 'text/html') {
                return base64_decode(strtr($p->getBody()->getData(), '-_', '+/'));
            }
        }
        return '';
    }
}
