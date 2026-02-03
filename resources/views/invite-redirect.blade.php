<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Sweat93</title>
    <script>
        function getStoreUrl() {
            var ua = navigator.userAgent || navigator.vendor || window.opera;
            if (/android/i.test(ua)) {
                return "{{ $playStoreUrl }}";
            }
            if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
                return "{{ $appStoreUrl }}";
            }
            return "{{ $playStoreUrl }}";
        }

        // Try to open the app
        window.location.href = "{{ $appScheme }}";

        // If the app didn't open, redirect to the store after 2 seconds
        setTimeout(function () {
            window.location.href = getStoreUrl();
        }, 2000);
    </script>
</head>
<body style="font-family: sans-serif; text-align: center; padding: 50px;">
    <h2>Opening Sweat93...</h2>
    <p>If nothing happens, <a id="store-link" href="{{ $playStoreUrl }}">download the app</a>.</p>
    <script>
        document.getElementById('store-link').href = getStoreUrl();
    </script>
</body>
</html>
