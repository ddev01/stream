<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Sign in with Twitch')" :description="__('Sign in with your Twitch account to continue')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="flex flex-col gap-6">
            <a
                href="/auth/twitch"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-3 px-4 rounded-md text-center transition-colors"
            >
                Sign in with Twitch
            </a>
        </div>
    </div>
</x-layouts.auth>
