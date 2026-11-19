<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<body style="margin:0;background:#F7F4EE;font-family:Tajawal,Arial,sans-serif;color:#2E2A27;">
    <div style="max-width:520px;margin:0 auto;padding:32px 20px;">
        <div style="background:#ffffff;border-radius:20px;padding:32px;box-shadow:0 1px 3px rgba(28,25,23,.08);">
            <p style="margin:0 0 16px;font-weight:800;font-size:18px;">T3al</p>
            <h1 style="margin:0 0 12px;font-size:22px;font-weight:900;">{{ __('app.invite_mail_heading', ['shop' => $invitation->merchant->name]) }}</h1>
            <p style="margin:0 0 24px;line-height:1.7;color:#6B645D;">{{ __('app.invite_mail_body', ['inviter' => $invitation->inviter?->name ?? 'T3al']) }}</p>
            <p style="margin:0 0 28px;text-align:center;">
                <a href="{{ $acceptUrl }}" style="display:inline-block;background:#A9D6BD;color:#2E2A27;text-decoration:none;font-weight:800;padding:14px 28px;border-radius:14px;">{{ __('app.invite_mail_cta') }}</a>
            </p>
            <p style="margin:0;font-size:13px;line-height:1.6;color:#6B645D;">{{ __('app.invite_mail_expiry', ['days' => \App\Models\Invitation::VALID_DAYS]) }}</p>
        </div>
    </div>
</body>
</html>
