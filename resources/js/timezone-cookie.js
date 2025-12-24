(() => {
    try {
        const cookieName = "timezone";
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (!tz) {
            return;
        }

        const encoded = encodeURIComponent(tz);
        const existing = document.cookie
            .split("; ")
            .find((row) => row.startsWith(cookieName + "="));

        const maxAge = 60 * 60 * 24 * 365;

        if (!existing || !existing.endsWith(encoded)) {
            document.cookie = `${cookieName}=${encoded}; Max-Age=${maxAge}; Path=/; SameSite=Lax`;

            // If this is the first time we set the cookie, reload once so server-rendered
            // timestamps can immediately render in the viewer's timezone.
            if (!existing && !sessionStorage.getItem("tz:reloaded")) {
                sessionStorage.setItem("tz:reloaded", "1");
                location.reload();
            }
        }
    } catch {
        // Silently fail if timezone detection is not supported.
    }
})();



