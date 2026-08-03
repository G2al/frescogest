<meta name="theme-color" content="#18181b">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Paradiso Admin">
<link rel="manifest" href="{{ asset('admin-manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/pwa/admin/apple-touch-icon.png') }}">
<style>
    @media (max-width: 63.999rem) {
        html.fi-admin-pwa-standalone .fi-topbar-ctn {
            padding-top: env(safe-area-inset-top, 0px);
            background-color: #fff;
        }

        html.fi-admin-pwa-standalone.dark .fi-topbar-ctn {
            background-color: #18181b;
        }

        html.fi-admin-pwa-standalone .fi-topbar {
            min-height: 4rem;
        }

        html.fi-admin-pwa-standalone .fi-sidebar {
            padding-bottom: env(safe-area-inset-bottom, 0px);
        }

        html.fi-admin-pwa-standalone .fi-main {
            padding-bottom: calc(2rem + env(safe-area-inset-bottom, 0px));
        }
    }
</style>
<script type="module" src="{{ asset('assets/js/admin-pwa.js') }}?v=20260803.2"></script>
