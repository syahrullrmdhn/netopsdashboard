<?php
// app/Http/Controllers/EmailSettingsController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailSetting;

class EmailSettingsController extends Controller
{
    public function edit()
    {
        $settings = EmailSetting::first();
        return view('settings.mail.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'mail_mailer'    => 'required|string',
            'mail_host'      => 'required|string',
            'mail_port'      => 'required|integer',
            'mail_username'  => 'required|string',
            'mail_password'  => 'required|string',
            'mail_encryption'=> 'nullable|string',
            'from_address'   => 'required|email',
            'from_name'      => 'required|string|max:255',
        ]);

        $settings = EmailSetting::first();
        if ($settings) {
            $settings->update($data);
        } else {
            EmailSetting::create($data);
        }

        return back()->with('success', 'SMTP settings saved.');
    }
}
