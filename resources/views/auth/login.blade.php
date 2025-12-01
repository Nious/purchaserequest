<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

    <title>Login | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
    
    {{-- Vite resources --}}
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        /* Tambahan agar benar-benar blank putih saat disembunyikan */
        body.hidden-mode {
            background-color: #ffffff !important;
        }
    </style>
</head>

{{-- Tambahkan class 'hidden-mode' secara default --}}
<body class="c-app flex-row align-items-center hidden-mode">

    {{-- 
        MODIFIKASI 1: 
        Tambahkan ID 'app-content' dan style 'display: none' 
        agar konten tidak terlihat saat pertama kali load.
    --}}
    <div class="container" id="app-content" style="display: none;">
        <div class="row mb-3">
            <div class="col-12 d-flex justify-content-center">
                <img width="200" src="{{ asset('images/logo-dark.png') }}" alt="Logo">
            </div>
        </div>
        <div class="text-center">
            <h5 class="fw-light">Sistem Budgeting dan Purchase Request</h5>
            <h2 class="fw-semibold">PT ERAN PLASTINDO UTAMA</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-7 col-xl-4">
                @if(Session::has('account_deactivated'))
                    <div class="alert alert-danger" role="alert">
                        {{ Session::get('account_deactivated') }}
                    </div>
                @endif
                <div class="card rounded-3 p-4 border-0 shadow-md">
                    <div class="card-body">
                        <form id="login" method="post" action="{{ url('/login') }}">
                            @csrf
                            <h2 class="fw-semibold text-gray">Login</h2>
                            <p class="text-muted">Sign In to your account</p>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="bi bi-person"></i>
                                        </span>
                                </div>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                       name="email" value="{{ old('email') }}"
                                       placeholder="Email">
                                @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="input-group mb-4">
                                <div class="input-group-prepend">
                                        <span class="input-group-text">
                                            <i class="bi bi-lock"></i>
                                        </span>
                                </div>
                                <input id="password" type="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Password" name="password">
                                @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <button id="submit" class="btn btn-primary px-4 d-flex align-items-center"
                                            type="submit">
                                        Login
                                        <div id="spinner" class="spinner-border text-info" role="status"
                                             style="height: 20px;width: 20px;margin-left: 5px;display: none;">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-8 text-right">
                                    <a class="btn btn-link px-0" href="{{ route('password.request') }}">
                                        Forgot password?
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="text-center mt-5 lead">
                    Developed By
                    <a href="https://www.linkedin.com/in/fikri-pandu-wibawa-2aaaa5270/" class="font-weight-bold text-primary">Fikri Pandu Wibawa</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // --- LOGIKA MAGIC WORD 'epu' ---
        let keyBuffer = "";
        const secretWord = "epu";
        const container = document.getElementById('app-content');
        const body = document.body;

        document.addEventListener('keydown', function(event) {
            // Jika konten sudah muncul, hentikan logika ini
            if (container.style.display === 'block') return;

            // Tambahkan huruf yang diketik ke buffer
            // event.key mengembalikan karakter yang ditekan
            keyBuffer += event.key.toLowerCase();

            // Jaga agar buffer tidak terlalu panjang (hemat memori), cukup ambil panjang secretWord terakhir
            if (keyBuffer.length > secretWord.length) {
                keyBuffer = keyBuffer.slice(-secretWord.length);
            }

            // Cek apakah buffer cocok dengan 'epu'
            if (keyBuffer === secretWord) {
                // Tampilkan halaman
                container.style.display = 'block';
                // Hapus class hidden-mode agar background kembali normal (jika ada style bawaan CoreUI)
                body.classList.remove('hidden-mode');
                
                // Opsional: Langsung fokus ke field email agar user bisa langsung login
                document.getElementById('email').focus();
            }
        });
        // -------------------------------


        // Logika Form Submit (Yang lama)
        let login = document.getElementById('login');
        let submit = document.getElementById('submit');
        let email = document.getElementById('email');
        let password = document.getElementById('password');
        let spinner = document.getElementById('spinner')

        if (login) {
            login.addEventListener('submit', (e) => {
                submit.disabled = true;
                email.readonly = true;
                password.readonly = true;

                spinner.style.display = 'block';

                login.submit();
            });
        }

        // Reset form jika user kembali (misal back button) atau error
        setTimeout(() => {
            if(submit) submit.disabled = false;
            if(email) email.readonly = false;
            if(password) password.readonly = false;
            if(spinner) spinner.style.display = 'none';
        }, 3000);
    </script>

</body>
</html>