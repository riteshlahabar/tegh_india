<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Inside Sales Login | Tegh Cables CRM</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <style>
        html, body, input, select, textarea, button {
            font-family: "Hanken Grotesk", system-ui, -apple-system, sans-serif !important;
        }
        .switchboard-grid {
            background-image: radial-gradient(rgba(251, 120, 0, 0.35) 1.2px, transparent 1.2px);
            background-size: 18px 18px;
        }
        .pulse-bar {
            animation: pulsebar 2.4s ease-in-out infinite;
        }
        @keyframes pulsebar {
            0%, 100% { transform: scaleX(0.35); opacity: 0.55; }
            50% { transform: scaleX(1); opacity: 1; }
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#000b28",
                        secondary: "#fb7800",
                        surface: "#f7f9fb",
                        "on-surface-variant": "#43474e",
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-primary text-white">
    <div class="min-h-screen grid lg:grid-cols-[1.1fr_0.9fr]">
        <section class="relative hidden lg:flex flex-col justify-between p-12 overflow-hidden">
            <div class="absolute inset-0 switchboard-grid opacity-70"></div>
            <div class="absolute left-0 top-0 h-full w-2 bg-secondary"></div>
            <div class="relative z-10">
                <img src="{{ asset('images/logo.png') }}" alt="Tegh Cables" class="h-12 w-auto object-contain object-left brightness-0 invert">
                <p class="mt-6 text-[11px] tracking-[0.28em] uppercase text-white/50">Unified CRM · Inside Sales</p>
            </div>
            <div class="relative z-10 max-w-lg">
                <p class="text-secondary text-xs font-bold tracking-[0.22em] uppercase mb-3">Telecaller desk</p>
                <h1 class="text-5xl font-bold leading-[1.05] tracking-tight">Pick up the<br>next lead.</h1>
                <p class="mt-5 text-white/70 text-sm max-w-sm">A separate floor for calling — assigned accounts, follow-ups, and the dial ledger. Admin stays on the Command Centre.</p>
                <div class="mt-8 h-1 w-40 bg-secondary/30 overflow-hidden">
                    <div class="pulse-bar h-full w-full origin-left bg-secondary"></div>
                </div>
            </div>
            <p class="relative z-10 text-[11px] text-white/40 tracking-widest uppercase">Tegh India · Confidential</p>
        </section>

        <section class="bg-[#fff7f0] text-primary flex items-center justify-center p-6 sm:p-10">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-8">
                    <img src="{{ asset('images/logo-mark.png') }}?v=3" alt="Tegh Cables" class="h-14 w-auto">
                </div>
                <p class="text-[11px] font-bold tracking-[0.24em] uppercase text-secondary mb-2">Inside sales login</p>
                <h2 class="text-3xl font-bold tracking-tight">Sign in to your board</h2>
                <p class="text-sm text-on-surface-variant mt-2 mb-8">Leads, callbacks and call notes — not the admin panel.</p>

                @if ($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-5">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('telecaller.login.submit') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus
                               class="w-full rounded-lg border-slate-300 text-sm focus:ring-secondary focus:border-secondary"
                               placeholder="telecaller@teghindia.com">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-on-surface-variant mb-1">Password</label>
                        <input type="password" name="password" required
                               class="w-full rounded-lg border-slate-300 text-sm focus:ring-secondary focus:border-secondary"
                               placeholder="••••••••">
                    </div>
                    <label class="flex items-center gap-2 text-sm text-on-surface-variant">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-secondary focus:ring-secondary">
                        Remember me
                    </label>
                    <button type="submit"
                            class="w-full bg-secondary text-white py-3 rounded-lg font-bold shadow-md hover:opacity-90 transition">
                        Open Dial Queue
                    </button>
                </form>

                <p class="mt-8 text-sm text-on-surface-variant">
                    Admin?
                    <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Command Centre login</a>
                </p>
            </div>
        </section>
    </div>
</body>
</html>
