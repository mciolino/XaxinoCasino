@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="auth-card">
                <div class="card-header text-center">
                    <h2>Setup Two Factor Authentication</h2>
                </div>

                <div class="card-body">
                    <div class="alert alert-info">
                        <p>Two factor authentication adds an extra layer of security to your account. Once enabled, you'll need to provide a code from your Google Authenticator app when logging in.</p>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="mb-4 text-center">
                                <p>Scan the QR code with your Google Authenticator app:</p>
                                <div class="qr-code-container">
                                    <img src="https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={{ urlencode('otpauth://totp/' . config('app.name') . ':' . Auth::user()->email . '?secret=' . $secretKey . '&issuer=' . config('app.name')) }}" alt="QR Code" class="img-fluid">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-4">
                                <p>If you can't scan the QR code, enter this code manually into your app:</p>
                                <div class="secret-key-container p-2 bg-light">
                                    <code class="user-select-all">{{ $secretKey }}</code>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('2fa.complete') }}">
                                @csrf

                                <div class="mb-3">
                                    <label for="one_time_password" class="form-label">Enter the code from your app:</label>
                                    <input id="one_time_password" type="text" class="form-control @error('one_time_password') is-invalid @enderror" name="one_time_password" required autofocus>
                                    @error('one_time_password')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        Verify & Enable 2FA
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
